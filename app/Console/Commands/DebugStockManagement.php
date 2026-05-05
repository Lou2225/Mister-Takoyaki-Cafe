<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Ingredient;
use App\Models\BranchIngredientStock;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\OrderItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DebugStockManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:stock {--branch_id= : Filter by branch ID} {--ingredient_id= : Filter by ingredient ID} {--discrepancies : Show only discrepancies}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Debug stock management system - analyze batches, movements, and discrepancies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $branchId = $this->option('branch_id');
        $ingredientId = $this->option('ingredient_id');
        $showDiscrepanciesOnly = $this->option('discrepancies');

        $this->info('╔════════════════════════════════════════════════════════════════╗');
        $this->info('║         STOCK MANAGEMENT DEBUG REPORT                          ║');
        $this->info('║  ' . now()->format('Y-m-d H:i:s') . '                                             ║');
        $this->info('╚════════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // ── 1. Check aggregate vs batch totals ────
        $this->debugAggregateVsBatches($branchId, $ingredientId, $showDiscrepanciesOnly);
        $this->newLine();

        // ── 2. Check movement audit trail ────
        $this->debugMovementAuditTrail($branchId, $ingredientId);
        $this->newLine();

        // ── 3. Check order-batch linkage ────
        $this->debugOrderBatchLinkage($branchId);
        $this->newLine();

        // ── 4. Check expired stock ────
        $this->debugExpiredStock($branchId, $ingredientId);
        $this->newLine();

        // ── 5. Summary stats ────
        $this->debugSummaryStats($branchId);

        $this->info('✓ Debug report complete');
    }

    private function debugAggregateVsBatches(?int $branchId, ?int $ingredientId, bool $showDiscrepanciesOnly): void
    {
        $this->info('┌─ 1. AGGREGATE vs BATCH QUANTITY VERIFICATION ─┐');

        $query = BranchIngredientStock::with(['ingredient', 'branch']);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($ingredientId) {
            $query->where('ingredient_id', $ingredientId);
        }

        $stocks = $query->get();
        $discrepancies = [];

        foreach ($stocks as $stock) {
            $batchTotal = StockBatch::where('branch_id', $stock->branch_id)
                ->where('ingredient_id', $stock->ingredient_id)
                ->where('current_quantity', '>', 0)
                ->sum('current_quantity');

            $hasDiscrepancy = abs($stock->stock_quantity - $batchTotal) > 0.01;

            if ($hasDiscrepancy) {
                $discrepancies[] = [
                    'ingredient' => $stock->ingredient->name,
                    'branch' => $stock->branch->branch_name ?? 'N/A',
                    'aggregate' => $stock->stock_quantity,
                    'batch_total' => $batchTotal,
                    'difference' => $stock->stock_quantity - $batchTotal,
                ];
            }

            if (!$showDiscrepanciesOnly || $hasDiscrepancy) {
                $status = $hasDiscrepancy ? '❌ MISMATCH' : '✓ OK';
                $this->line(
                    sprintf(
                        '  %s | %s @ %s | Agg: %s, Batch Total: %s',
                        $status,
                        str_pad($stock->ingredient->name, 25),
                        str_pad($stock->branch->branch_name ?? 'N/A', 15),
                        str_pad((string)$stock->stock_quantity, 8),
                        str_pad((string)$batchTotal, 8)
                    )
                );
            }
        }

        if (!empty($discrepancies)) {
            $this->error("\n⚠️  Found " . count($discrepancies) . " discrepancies!");
            $this->table(
                ['Ingredient', 'Branch', 'Aggregate Qty', 'Batch Total', 'Difference'],
                array_map(fn($d) => [
                    $d['ingredient'],
                    $d['branch'],
                    $d['aggregate'],
                    $d['batch_total'],
                    $d['difference'],
                ], $discrepancies)
            );
        } else {
            $this->info('✓ All quantities match between aggregate and batches');
        }
    }

    private function debugMovementAuditTrail(?int $branchId, ?int $ingredientId): void
    {
        $this->info('┌─ 2. STOCK MOVEMENT AUDIT TRAIL ─┐');

        $query = StockMovement::with(['ingredient', 'branch', 'user']);
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($ingredientId) {
            $query->where('ingredient_id', $ingredientId);
        }

        $movements = $query->orderBy('created_at', 'desc')->limit(20)->get();

        if ($movements->isEmpty()) {
            $this->info('  No movements found');
            return;
        }

        $this->info('  Recent 20 movements:');
        foreach ($movements as $m) {
            $type = str_pad($m->type, 12);
            $qty = str_pad((string)$m->quantity, 10);
            $ingredient = str_pad($m->ingredient->name, 20);
            $date = $m->created_at->format('Y-m-d H:i:s');
            $user = $m->user?->name ?? 'System';
            
            $this->line("    $type | $qty | $ingredient | $date | By: $user");
        }

        // Check for order-type movements
        $orderMovements = StockMovement::where('type', 'order')->count();
        $this->info("\n  ✓ Total order-based movements: $orderMovements");
    }

    private function debugOrderBatchLinkage(?int $branchId): void
    {
        $this->info('┌─ 3. ORDER-BATCH LINKAGE ─┐');

        $query = OrderItem::with(['order', 'batch', 'product']);
        
        if ($branchId) {
            $query->whereHas('order', fn($q) => $q->where('branch_id', $branchId));
        }

        $totalOrderItems = $query->count();
        $linkedToBatch = $query->whereNotNull('stock_batch_id')->count();
        $unlinked = $totalOrderItems - $linkedToBatch;

        $this->line("  Total order items: $totalOrderItems");
        $this->line("  Linked to batch: $linkedToBatch");
        $this->line("  Unlinked (pre-fix): $unlinked");

        if ($unlinked > 0) {
            $this->warn('  ⚠️  Items without batch tracking (created before FEFO implementation)');
        } else {
            $this->info('  ✓ All order items properly tracked to batches');
        }

        // Sample recent orders with batch usage
        $this->info("\n  Recent orders with batch tracking:");
        $orders = Order::with(['items.batch.ingredient'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($orders as $order) {
            $this->line("    Order #{$order->reference_no} - {$order->created_at->format('Y-m-d H:i')}");
            foreach ($order->items as $item) {
                $batch = $item->batch;
                if ($batch) {
                    $expiry = $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : 'No expiry';
                    $this->line("      → {$item->product->name} | Batch #{$batch->batch_number} | Expires: $expiry");
                } else {
                    $this->line("      → {$item->product->name} | ❌ No batch linked");
                }
            }
        }
    }

    private function debugExpiredStock(?int $branchId, ?int $ingredientId): void
    {
        $this->info('┌─ 4. EXPIRED STOCK ANALYSIS ─┐');

        $query = StockBatch::with(['ingredient', 'branch'])
            ->where('current_quantity', '>', 0)
            ->where('expiry_date', '<', now());

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        if ($ingredientId) {
            $query->where('ingredient_id', $ingredientId);
        }

        $expiredBatches = $query->get();

        if ($expiredBatches->isEmpty()) {
            $this->info('  ✓ No expired stock detected');
            return;
        }

        $this->warn('  ❌ Found ' . count($expiredBatches) . ' expired batches:');
        $this->table(
            ['Ingredient', 'Branch', 'Batch #', 'Quantity', 'Expired On'],
            $expiredBatches->map(fn($b) => [
                $b->ingredient->name,
                $b->branch->branch_name ?? 'N/A',
                $b->batch_number ?? 'N/A',
                $b->current_quantity,
                is_string($b->expiry_date) ? $b->expiry_date : ($b->expiry_date?->format('Y-m-d') ?? 'N/A'),
            ])->toArray()
        );

        $totalWaste = $expiredBatches->sum('current_quantity');
        $this->warn("  Total expired quantity at risk: $totalWaste units");
    }

    private function debugSummaryStats(?int $branchId): void
    {
        $this->info('┌─ 5. SUMMARY STATISTICS ─┐');

        $query = BranchIngredientStock::query();
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $totalIngredients = $query->count();
        $totalQuantity = $query->sum('stock_quantity');
        $lowStockItems = BranchIngredientStock::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->leftJoin('ingredients', 'branch_ingredient_stocks.ingredient_id', '=', 'ingredients.id')
            ->whereRaw('branch_ingredient_stocks.stock_quantity < ingredients.minimum_stock')
            ->count();

        $this->line("  Total ingredients in stock: $totalIngredients");
        $this->line("  Total quantity (base units): " . number_format($totalQuantity, 2));
        $this->line("  Low stock warnings: " . $lowStockItems);

        // Batch stats
        $batchQuery = StockBatch::where('current_quantity', '>', 0);
        if ($branchId) {
            $batchQuery->where('branch_id', $branchId);
        }

        $totalBatches = $batchQuery->count();
        $expiringIn7Days = $batchQuery->whereDate('expiry_date', '<=', now()->addDays(7))
            ->whereDate('expiry_date', '>', now())
            ->count();

        $this->line("  Total active batches: $totalBatches");
        $this->line("  Batches expiring in 7 days: $expiringIn7Days");

        // Movement stats
        $movementQuery = StockMovement::query();
        if ($branchId) {
            $movementQuery->where('branch_id', $branchId);
        }

        $totalMovements = $movementQuery->count();
        $orderMovements = $movementQuery->where('type', 'order')->count();

        $this->line("  Total movements logged: $totalMovements");
        $this->line("  Order-based movements: $orderMovements");
    }
}
