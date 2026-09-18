<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pre-aggregated daily financial summary per branch.
 *
 * Populated nightly by the dashboard:rollup Artisan command,
 * which is triggered via a Hostinger hPanel Cron Job at 00:05 daily.
 *
 * @property int    $branch_id
 * @property string $summary_date    (Y-m-d)
 * @property int    $order_count
 * @property float  $gross_sales
 * @property float  $net_sales
 * @property float  $total_discounts
 * @property float  $refunds
 * @property float  $delivery_fees
 * @property float  $service_charges
 * @property float  $total_cogs
 * @property float  $waste_cost
 * @property float  $gross_profit
 * @property bool   $is_partial
 */
class DailyBranchSummary extends Model
{
    protected $table = 'daily_branch_summaries';

    protected $fillable = [
        'branch_id',
        'summary_date',
        'order_count',
        'gross_sales',
        'net_sales',
        'total_discounts',
        'refunds',
        'delivery_fees',
        'service_charges',
        'total_cogs',
        'waste_cost',
        'gross_profit',
        'cash_orders',
        'cash_total',
        'gcash_orders',
        'gcash_total',
        'dine_in_orders',
        'take_out_orders',
        'delivery_orders',
        'is_partial',
        'computed_at',
    ];

    protected $casts = [
        'summary_date' => 'date',
        'computed_at'  => 'datetime',
        'is_partial'   => 'boolean',
        'gross_sales'  => 'float',
        'net_sales'    => 'float',
        'total_discounts' => 'float',
        'refunds'      => 'float',
        'delivery_fees' => 'float',
        'service_charges' => 'float',
        'total_cogs'   => 'float',
        'waste_cost'   => 'float',
        'gross_profit' => 'float',
        'cash_total'   => 'float',
        'gcash_total'  => 'float',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    // ── Scope helpers ──────────────────────────────────────────────────────

    /**
     * Filter to a specific branch (or all if null / super-admin scope).
     */
    public function scopeForBranch($query, ?int $branchId)
    {
        return $branchId ? $query->where('branch_id', $branchId) : $query;
    }

    /**
     * Filter to a calendar date range.
     */
    public function scopeInDateRange($query, ?string $start, ?string $end)
    {
        if ($start) {
            $query->where('summary_date', '>=', $start);
        }
        if ($end) {
            $query->where('summary_date', '<=', $end);
        }
        return $query;
    }

    /**
     * Exclude today — today is always queried live.
     */
    public function scopePastDaysOnly($query)
    {
        return $query->where('summary_date', '<', now()->toDateString());
    }
}

