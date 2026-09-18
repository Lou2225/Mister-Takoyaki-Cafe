<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Models\DailyBranchSummary;
use App\Traits\ResolvesIngredientCosts;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class RollupDashboard extends Command
{
    use ResolvesIngredientCosts;

    protected $signature = 'dashboard:rollup
                            {--date=yesterday : Calendar date to process (Y-m-d, or "yesterday"/"today")}
                            {--backfill : Process ALL missing dates from the first order up to yesterday}
                            {--branch= : Only process a specific branch ID}
                            {--force : Re-compute even if a completed row already exists}';

    protected $description = 'Pre-aggregate daily financial summaries per branch into daily_branch_summaries. Run nightly via cron.';

    public function handle(): int
    {
        $branches = $this->option('branch')
            ? Branch::where('id', $this->option('branch'))->get()
            : Branch::all();

        if ($branches->isEmpty()) {
            $this->warn('No branches found.');
            return self::SUCCESS;
        }

        if ($this->option('backfill')) {
            return $this->runBackfill($branches);
        }

        // Default: process the specified date (default: yesterday)
        $rawDate = $this->option('date');
        $targetDate = match ($rawDate) {
            'yesterday' => now()->subDay()->toDateString(),
            'today'     => now()->toDateString(),
            default     => Carbon::parse($rawDate)->toDateString(),
        };

        $this->info("Rollup for {$targetDate} — " . $branches->count() . " branch(es)");

        foreach ($branches as $branch) {
            $this->processDay($branch->id, $targetDate, (bool)$this->option('force'));
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    // ── Backfill ──────────────────────────────────────────────────────────

    private function runBackfill($branches): int
    {
        $this->info('Backfill mode: computing all missing past days...');

        $firstOrder = Order::orderBy('created_at')->value('created_at');
        if (!$firstOrder) {
            $this->warn('No orders in the database. Nothing to backfill.');
            return self::SUCCESS;
        }

        $start = Carbon::parse($firstOrder)->startOfDay();
        $end   = now()->subDay()->startOfDay(); // Never include today — live data

        $period = CarbonPeriod::create($start, '1 day', $end);
        $force  = (bool)$this->option('force');

        $total = iterator_count($period);
        $bar   = $this->output->createProgressBar($total * $branches->count());
        $bar->start();

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            foreach ($branches as $branch) {
                $this->processDay($branch->id, $dateStr, $force, silent: true);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete.');
        return self::SUCCESS;
    }

    // ── Core computation ──────────────────────────────────────────────────

    private function processDay(int $branchId, string $dateStr, bool $force, bool $silent = false): void
    {
        $isToday = $dateStr === now()->toDateString();

        // Skip already-complete rows unless --force
        if (!$force && !$isToday) {
            $exists = DailyBranchSummary::where('branch_id', $branchId)
                ->where('summary_date', $dateStr)
                ->where('is_partial', false)
                ->exists();

            if ($exists) {
                if (!$silent) {
                    $this->line("  Branch {$branchId} / {$dateStr} — already computed, skipping.");
                }
                return;
            }
        }

        $dayStart = Carbon::parse($dateStr)->startOfDay();
        $dayEnd   = Carbon::parse($dateStr)->endOfDay();

        // ── Orders for this day ───────────────────────────────────────────
        $orders = Order::where('branch_id', $branchId)
            ->whereIn('status', [
                Order::STATUS_COMPLETED,
                Order::STATUS_REFUNDED,
                Order::STATUS_PARTIALLY_REFUNDED,
            ])
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->with([
                'items' => function ($q) {
                    $q->select('id', 'order_id', 'product_id', 'quantity', 'subtotal')
                        ->with([
                            'product' => fn($q) => $q->select('id')->with([
                                'recipes' => fn($q) => $q->select('id', 'product_id', 'ingredient_id', 'quantity')
                                    ->whereNull('product_option_id')->whereNull('modifier_id'),
                            ]),
                            'options' => fn($q) => $q->select('id', 'order_item_id', 'product_option_id')
                                ->with(['option' => fn($q) => $q->select('id')->with([
                                    'recipes' => fn($q) => $q->select('id', 'product_option_id', 'ingredient_id', 'quantity'),
                                ])]),
                            'modifiers' => fn($q) => $q->select('id', 'order_item_id', 'modifier_id')
                                ->with(['modifier' => fn($q) => $q->select('id')->with([
                                    'recipes' => fn($q) => $q->select('id', 'modifier_id', 'ingredient_id', 'quantity'),
                                ])]),
                        ]);
                },
            ])
            ->get(['id', 'branch_id', 'status', 'total_amount', 'delivery_fee',
                   'discount_amount', 'refunded_amount', 'service_charge',
                   'payment_method', 'order_type']);

        // Build cost lookup maps for this branch (cached in trait arrays per call)
        $branchCosts  = $this->buildBranchCostMap($branchId);
        $purchaseCosts = $this->buildPurchasePriceMap($branchId);
        $globalCosts  = Ingredient::pluck('cost', 'id');

        $completedOrders = $orders->where('status', Order::STATUS_COMPLETED);

        // Revenue
        $totalCollected = $orders->sum('total_amount');
        $deliveryFees   = $orders->sum('delivery_fee');
        $discounts      = $orders->sum('discount_amount');
        $refunds        = $orders->sum('refunded_amount');
        $serviceCharges = $orders->sum('service_charge');
        $netSales       = $totalCollected - $deliveryFees - $refunds;
        $grossSales     = $netSales + $discounts;
        $orderCount     = $completedOrders->count();

        // COGS via PHP loop (same logic as DashboardOverview::calculateItemCogs)
        $totalCogs      = 0.0;
        $productMap     = [];
        $optionMap      = [];
        $modifierMap    = [];

        foreach ($completedOrders as $order) {
            foreach ($order->items as $item) {
                $itemCost = $this->calculateItemCogsLocal(
                    $item, $branchCosts, $globalCosts, $purchaseCosts,
                    $productMap, $optionMap, $modifierMap, $branchId
                );
                $totalCogs += $itemCost * $item->quantity;
            }
        }

        // Waste
        $wasteCost = (float) StockMovement::where('branch_id', $branchId)
            ->whereIn('type', ['waste', 'waste_expired', 'out', 'return_to_supplier'])
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->sum(DB::raw('ABS(quantity) * COALESCE(unit_cost, 0)'));

        $grossProfit = $netSales - $totalCogs - $wasteCost;

        // Payment / Channel breakdowns
        $cashOrders  = $completedOrders->where('payment_method', 'Cash')->count();
        $cashTotal   = (float)$completedOrders->where('payment_method', 'Cash')->sum('total_amount');
        $gcashOrders = $completedOrders->where('payment_method', 'GCash')->count();
        $gcashTotal  = (float)$completedOrders->where('payment_method', 'GCash')->sum('total_amount');

        $dineIn   = $completedOrders->where('order_type', 'Dine-in')->count();
        $takeOut  = $completedOrders->where('order_type', 'Take-out')->count();
        $delivery = $completedOrders->where('order_type', 'Delivery')->count();

        // Upsert — safe to re-run
        DailyBranchSummary::updateOrCreate(
            ['branch_id' => $branchId, 'summary_date' => $dateStr],
            [
                'order_count'    => $orderCount,
                'gross_sales'    => round($grossSales, 4),
                'net_sales'      => round($netSales, 4),
                'total_discounts'=> round($discounts, 4),
                'refunds'        => round($refunds, 4),
                'delivery_fees'  => round($deliveryFees, 4),
                'service_charges'=> round($serviceCharges, 4),
                'total_cogs'     => round($totalCogs, 4),
                'waste_cost'     => round($wasteCost, 4),
                'gross_profit'   => round($grossProfit, 4),
                'cash_orders'    => $cashOrders,
                'cash_total'     => round($cashTotal, 4),
                'gcash_orders'   => $gcashOrders,
                'gcash_total'    => round($gcashTotal, 4),
                'dine_in_orders' => $dineIn,
                'take_out_orders'=> $takeOut,
                'delivery_orders'=> $delivery,
                'is_partial'     => $isToday,
                'computed_at'    => now(),
            ]
        );

        if (!$silent) {
            $this->line("  Branch {$branchId} / {$dateStr} — {$orderCount} orders, COGS ₱" . number_format($totalCogs, 2) . " ✓");
        }
    }

    // ── Local COGS helper (mirrors DashboardOverview::calculateItemCogs) ──

    private function calculateItemCogsLocal(
        $item,
        $branchCosts,
        $globalCosts,
        $purchaseCosts,
        array &$productMap,
        array &$optionMap,
        array &$modifierMap,
        int $bid
    ): float {
        $resolveCost = fn($ingId) =>
            ($branchCosts[$bid][$ingId] ?? $purchaseCosts[$bid][$ingId] ?? null)
            ?? $globalCosts[$ingId]
            ?? 0;

        $itemCost = 0.0;

        if ($item->product_id) {
            if (!isset($productMap[$item->product_id])) {
                $cost = 0.0;
                if ($item->product) {
                    foreach ($item->product->recipes as $r) {
                        $cost += $r->quantity * $resolveCost($r->ingredient_id);
                    }
                }
                $productMap[$item->product_id] = $cost;
            }
            $itemCost += $productMap[$item->product_id];
        }

        foreach ($item->options as $o) {
            if ($o->product_option_id) {
                if (!isset($optionMap[$o->product_option_id])) {
                    $cost = 0.0;
                    if ($o->option) {
                        foreach ($o->option->recipes as $r) {
                            $cost += $r->quantity * $resolveCost($r->ingredient_id);
                        }
                    }
                    $optionMap[$o->product_option_id] = $cost;
                }
                $itemCost += $optionMap[$o->product_option_id];
            }
        }

        foreach ($item->modifiers as $m) {
            if ($m->modifier_id) {
                if (!isset($modifierMap[$m->modifier_id])) {
                    $cost = 0.0;
                    if ($m->modifier) {
                        foreach ($m->modifier->recipes as $r) {
                            $cost += $r->quantity * $resolveCost($r->ingredient_id);
                        }
                    }
                    $modifierMap[$m->modifier_id] = $cost;
                }
                $itemCost += $modifierMap[$m->modifier_id];
            }
        }

        return $itemCost;
    }
}

