<?php

namespace App\Models;

use App\Models\Order;
use App\Models\SystemSetting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialLedger extends Model
{
    protected $table = 'financial_ledger';
    public $timestamps = true;

    protected $fillable = [
        'branch_id',
        'order_id',
        'transaction_type',
        'account_type',
        'amount',
        'tax_rate',
        'vat_amount',
        'reference_no',
        'description',
        'recorded_by',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'tax_rate' => 'float',
        'vat_amount' => 'float',
        'amount' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Transaction types:
     * - SALE: Regular sales transaction
     * - TAX_COLLECTED: Tax collected from sales
     * - REFUND: Refund transaction
     * - TAX_ADJUSTMENT: Manual tax adjustment
     */
    const TRANSACTION_TYPE_SALE = 'SALE';
    const TRANSACTION_TYPE_TAX_COLLECTED = 'TAX_COLLECTED';
    const TRANSACTION_TYPE_REFUND = 'REFUND';
    const TRANSACTION_TYPE_TAX_ADJUSTMENT = 'TAX_ADJUSTMENT';

    /**
     * Account types:
     * - SALES: Sales revenue account
     * - TAX_LIABILITY: Tax payable/collected account
     * - BUSINESS: General business account
     */
    const ACCOUNT_TYPE_SALES = 'SALES';
    const ACCOUNT_TYPE_TAX_LIABILITY = 'TAX_LIABILITY';
    const ACCOUNT_TYPE_BUSINESS = 'BUSINESS';

    /**
     * Relationship to Branch
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Relationship to Order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relationship to User (who recorded)
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Record a sales transaction (total amount)
     */
    public static function recordSale(Order $order, ?int $branchId = null, ?int $userId = null)
    {
        $branchId = $branchId ?? auth()->user()->branch_id;
        $userId = $userId ?? auth()->id();

        return static::create([
            'branch_id' => $branchId,
            'order_id' => $order->id,
            'transaction_type' => self::TRANSACTION_TYPE_SALE,
            'account_type' => self::ACCOUNT_TYPE_SALES,
            'amount' => $order->total_amount,
            'reference_no' => $order->reference_no,
            'description' => "Sales from {$order->reference_no}",
            'recorded_by' => $userId,
        ]);
    }

    /**
     * Record a tax collection transaction
     */
    public static function recordTax(Order $order, float $taxAmount, float $taxRate, ?int $branchId = null, ?int $userId = null)
    {
        $branchId = $branchId ?? auth()->user()->branch_id;
        $userId = $userId ?? auth()->id();
        
        // Determine account based on system setting
        $taxDestination = SystemSetting::get('tax_destination', self::ACCOUNT_TYPE_TAX_LIABILITY);

        return static::create([
            'branch_id' => $branchId,
            'order_id' => $order->id,
            'transaction_type' => self::TRANSACTION_TYPE_TAX_COLLECTED,
            'account_type' => $taxDestination,
            'amount' => $taxAmount,
            'tax_rate' => $taxRate,
            'vat_amount' => $taxAmount,
            'reference_no' => $order->reference_no,
            'description' => "Tax (" . round($taxRate * 100) . "%) from {$order->reference_no}",
            'recorded_by' => $userId,
        ]);
    }

    /**
     * Record a refund transaction. Stored as a NEGATIVE amount so that
     * summing SALE + REFUND entries together always yields net revenue,
     * without needing to special-case refunds in every report.
     */
    public static function recordRefund(Order $order, float $amount, ?int $branchId = null, ?int $userId = null)
    {
        $branchId = $branchId ?? auth()->user()->branch_id;
        $userId = $userId ?? auth()->id();

        return static::create([
            'branch_id' => $branchId,
            'order_id' => $order->id,
            'transaction_type' => self::TRANSACTION_TYPE_REFUND,
            'account_type' => self::ACCOUNT_TYPE_SALES,
            'amount' => -abs($amount),
            'reference_no' => $order->reference_no,
            'description' => "Refund of ₱" . number_format($amount, 2) . " for {$order->reference_no}",
            'recorded_by' => $userId,
        ]);
    }

    /**
     * Record a void as a full reversal of the original sale.
     * Idempotent: won't create a duplicate reversal if called more than once
     * for the same order.
     */
    public static function recordVoid(Order $order, ?int $branchId = null, ?int $userId = null)
    {
        $alreadyVoided = static::where('order_id', $order->id)
            ->where('transaction_type', self::TRANSACTION_TYPE_REFUND)
            ->where('description', 'like', 'Void reversal%')
            ->exists();

        if ($alreadyVoided) {
            return null;
        }

        $branchId = $branchId ?? auth()->user()->branch_id;
        $userId = $userId ?? auth()->id();

        return static::create([
            'branch_id' => $branchId,
            'order_id' => $order->id,
            'transaction_type' => self::TRANSACTION_TYPE_REFUND,
            'account_type' => self::ACCOUNT_TYPE_SALES,
            'amount' => -abs($order->total_amount),
            'reference_no' => $order->reference_no,
            'description' => "Void reversal of {$order->reference_no}",
            'recorded_by' => $userId,
        ]);
    }

    /**
     * Get total tax collected for a date range
     */
    public static function getTaxCollected($startDate, $endDate, $branchId = null)
    {
        $query = static::where('transaction_type', self::TRANSACTION_TYPE_TAX_COLLECTED)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->sum('vat_amount');
    }

    /**
     * Get gross sales (SALE transactions only) for a date range —
     * does NOT subtract refunds/voids. Use getNetSales() for actual revenue.
     */
    public static function getTotalSales($startDate, $endDate, $branchId = null)
    {
        $query = static::where('transaction_type', self::TRANSACTION_TYPE_SALE)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->sum('amount');
    }

    /**
     * Get net sales (SALE + REFUND, where refunds/voids are stored as
     * negative amounts) for a date range — this is actual revenue kept.
     */
    public static function getNetSales($startDate, $endDate, $branchId = null)
    {
        $query = static::whereIn('transaction_type', [self::TRANSACTION_TYPE_SALE, self::TRANSACTION_TYPE_REFUND])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return $query->sum('amount');
    }
}
