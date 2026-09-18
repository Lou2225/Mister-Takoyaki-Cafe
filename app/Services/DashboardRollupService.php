<?php

namespace App\Services;

use App\Models\DailyBranchSummary;
use App\Models\Order;
use App\Models\Ingredient;
use App\Models\StockMovement;
use App\Traits\ResolvesIngredientCosts;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * DashboardRollupService — Hybrid query router for the Dashboard.
 *
 * Strategy:
 *   • Past complete days  → read from daily_branch_summaries (O(1) row reads)
 *   • Today (partial day) → live indexed SQL aggregates, no PHP loops
 *
 * This service produces the same shape of array that DashboardOverview's
 * getFinancialIntelligence() returns, so it can be used as a drop-in replacement.
 */
class DashboardRollupService
{
    use ResolvesIngredientCosts;

    /**
     * Compute the full financial intelligence block for a given date range.
     *
     * @param  int|null  $branchId     Null means all branches (super-admin)
     * @param  string|null $startDate  Y-m-d format, null = no lower bound
     * @param  string|null $endDate    Y-m-d format, null = today
     * @param  bool      $isSuperAdmin
     * @param  int|null  $userBranchId  branch_id of the logged-in non-super-admin user
     */
    public function getFinancialIntelligence(
        ?int $branchId,
        ?string $startDate,
        ?string $endDate,
        bool $isSuperAdmin,
        ?int $userBranchId
    ): array {
        $today     = now()->toDateString();
        $endTarget = $endDate ?: $today;
        $hasToday  = ($endTarget >= $today);

        // Determine the effective branch(es) scope
        $effectiveBranchId = $isSuperAdmin ? $branchId : $userBranchId;

        // ── Past days from rollup table ────────────────────────────────────
        // Only go up to yesterday; today is always queried live.
        $pastEnd = $hasToday
            ? now()->subDay()->toDateString()  // yesterday
            : $endTarget;

        $pastTotals = $this->sumRollupRange($effectiveBranchId, $startDate, $pastEnd);

        // ── Today (live) ───────────────────────────────────────────────────
        $todayTotals = $this->zeroTotals();
        if ($hasToday) {
            $todayTotals = $this->computeTodayLive($effectiveBranchId);
        }

        // ── Merge ──────────────────────────────────────────────────────────
        $orderCount     = $pastTotals['order_count']     + $todayTotals['order_count'];
        $grossSales     = $pastTotals['gross_sales']     + $todayTotals['gross_sales'];
        $netSales       = $pastTotals['net_sales']       + $todayTotals['net_sales'];
        $totalDiscounts = $pastTotals['total_discounts'] + $todayTotals['total_discounts'];
        $refunds        = $pastTotals['refunds']         + $todayTotals['refunds'];
        $deliveryFees   = $pastTotals['delivery_fees']   + $todayTotals['delivery_fees'];
        $totalCogs      = $pastTotals['total_cogs']      + $todayTotals['total_cogs'];
        $wasteCost      = $pastTotals['waste_cost']      + $todayTotals['waste_cost'];

        $totalCollected = $grossSales - $totalDiscounts + $refunds + $deliveryFees;
        $grossProfit    = $netSales - $totalCogs - $wasteCost;
        $aov            = $orderCount > 0 ? ($netSales / $orderCount) : 0;
        $margin         = $netSales > 0 ? ($grossProfit / $netSales) * 100 : 0;

        return [
            'revenue'           => round($totalCollected, 2),
            'net_sales'         => round($netSales, 2),
            'gross_sales'       => round($grossSales, 2),
            'delivery_fees'     => round($deliveryFees, 2),
            'total_discounts'   => round($totalDiscounts, 2),
            'refunds'           => round($refunds, 2),
            'order_count'       => $orderCount,
            'aov'               => round($aov, 2),
            'total_cogs'        => round($totalCogs, 2),
            'waste_cost'        => round($wasteCost, 2),
            'gross_profit'      => round($grossProfit, 2),
            'profit_margin_pct' => round($margin, 2),
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Aggregate rollup rows for the given date range.
     */
    private function sumRollupRange(?int $branchId, ?string $start, ?string $end): array
    {
        // If start > end (e.g. range is today-only and we're handling it live), return zeros
        if ($start && $end && $start > $end) {
            return $this->zeroTotals();
        }

        $row = DailyBranchSummary::query()
            ->forBranch($branchId)
            ->pastDaysOnly()
            ->when($start, fn($q) => $q->where('summary_date', '>=', $start))
            ->when($end,   fn($q) => $q->where('summary_date', '<=', $end))
            ->selectRaw('
                COALESCE(SUM(order_count), 0)     AS order_count,
                COALESCE(SUM(gross_sales), 0)     AS gross_sales,
                COALESCE(SUM(net_sales), 0)       AS net_sales,
                COALESCE(SUM(total_discounts), 0) AS total_discounts,
                COALESCE(SUM(refunds), 0)         AS refunds,
                COALESCE(SUM(delivery_fees), 0)   AS delivery_fees,
                COALESCE(SUM(total_cogs), 0)      AS total_cogs,
                COALESCE(SUM(waste_cost), 0)      AS waste_cost
            ')
            ->first();

        return [
            'order_count'    => (int)($row->order_count    ?? 0),
            'gross_sales'    => (float)($row->gross_sales  ?? 0),
            'net_sales'      => (float)($row->net_sales    ?? 0),
            'total_discounts'=> (float)($row->total_discounts ?? 0),
            'refunds'        => (float)($row->refunds       ?? 0),
            'delivery_fees'  => (float)($row->delivery_fees ?? 0),
            'total_cogs'     => (float)($row->total_cogs    ?? 0),
            'waste_cost'     => (float)($row->waste_cost    ?? 0),
        ];
    }

    /**
     * Today's live aggregates — uses direct SQL aggregation against the
     * now-indexed orders table. No PHP loops; no eager-loading.
     *
     * COGS for today is approximated using the product-level cost field
     * (products.cost) which is set by the menu management and is fast to
     * JOIN. This is accurate enough for today's live card and matches the
     * approach used by Business Intelligence's real-time panel.
     */
    private function computeTodayLive(?int $branchId): array
    {
        $todayStart = now()->toDateString() . ' 00:00:00';
        $todayEnd   = now()->toDateString() . ' 23:59:59';

        // Revenue aggregation (pure SQL — no hydration)
        $rev = DB::table('orders')
            ->whereIn('status', [
                Order::STATUS_COMPLETED,
                Order::STATUS_REFUNDED,
                Order::STATUS_PARTIALLY_REFUNDED,
            ])
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->selectRaw('
                COUNT(CASE WHEN status = ? THEN 1 END)             AS order_count,
                COALESCE(SUM(total_amount), 0)                     AS total_collected,
                COALESCE(SUM(delivery_fee), 0)                     AS delivery_fees,
                COALESCE(SUM(discount_amount), 0)                  AS total_discounts,
                COALESCE(SUM(refunded_amount), 0)                  AS refunds,
                COALESCE(SUM(service_charge), 0)                   AS service_charges
            ', [Order::STATUS_COMPLETED])
            ->first();

        $totalCollected = (float)($rev->total_collected  ?? 0);
        $deliveryFees   = (float)($rev->delivery_fees    ?? 0);
        $totalDiscounts = (float)($rev->total_discounts  ?? 0);
        $refunds        = (float)($rev->refunds          ?? 0);

        $netSales   = $totalCollected - $deliveryFees - $refunds;
        $grossSales = $netSales + $totalDiscounts;

        // COGS: use products.cost * quantity (stored cost per item, fast JOIN)
        $cogs = (float) DB::table('order_items')
            ->join('orders',   'order_items.order_id',   '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->whereBetween('orders.created_at', [$todayStart, $todayEnd])
            ->when($branchId, fn($q) => $q->where('orders.branch_id', $branchId))
            ->sum(DB::raw('order_items.quantity * COALESCE(products.cost, 0)'));

        // Waste
        $waste = (float) StockMovement::whereIn('type', ['waste', 'waste_expired', 'out', 'return_to_supplier'])
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum(DB::raw('ABS(quantity) * COALESCE(unit_cost, 0)'));

        return [
            'order_count'    => (int)($rev->order_count ?? 0),
            'gross_sales'    => $grossSales,
            'net_sales'      => $netSales,
            'total_discounts'=> $totalDiscounts,
            'refunds'        => $refunds,
            'delivery_fees'  => $deliveryFees,
            'total_cogs'     => $cogs,
            'waste_cost'     => $waste,
        ];
    }

    private function zeroTotals(): array
    {
        return [
            'order_count'    => 0,
            'gross_sales'    => 0.0,
            'net_sales'      => 0.0,
            'total_discounts'=> 0.0,
            'refunds'        => 0.0,
            'delivery_fees'  => 0.0,
            'total_cogs'     => 0.0,
            'waste_cost'     => 0.0,
        ];
    }
}

