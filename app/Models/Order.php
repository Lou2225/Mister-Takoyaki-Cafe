<?php

namespace App\Models;

use App\Events\OrderStatusUpdated;
use App\Models\FinancialLedger;
use App\Models\SystemSetting;
use App\Services\StockDeductionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;
    
    /**
     * Centralized financial recording logic triggered by model events.
     */
    protected static function booted()
    {
        static::created(function ($order) {
            if ($order->payment_status === 'Paid') {
                // POS orders should be COMPLETED immediately when paid (not Preparing/Delivering)
                if ($order->source === 'POS' && $order->status !== self::STATUS_COMPLETED) {
                    $order->update(['status' => self::STATUS_COMPLETED]);
                }
                
                $order->recordFinancialTransaction();
            }
        });

        static::updated(function ($order) {
            // Trigger recording if payment status transitions to 'Paid'
            if ($order->wasChanged('payment_status') && $order->payment_status === 'Paid') {
                // POS orders should be COMPLETED immediately when paid (not Preparing/Delivering)
                if ($order->source === 'POS' && $order->status !== self::STATUS_COMPLETED) {
                    $order->update(['status' => self::STATUS_COMPLETED]);
                }
                
                $order->recordFinancialTransaction();
            }
        });
    }

    protected $fillable = [
        'reference_no',
        'branch_id',
        'user_id',
        'customer_id', // Relation to users table for customers
        'total_amount',
        'tax_amount',
        'service_charge',
        'discount_amount',
        'refunded_amount',
        'payment_method',
        'payment_status',
        'payment_reference',
        'order_type',
        'status',
        'notes',
        'table_number',
        'refund_reason',
        'refunded_by',
        'refunded_at',
        'accepted_at',
        'prepared_at',
        'dispatched_at',
        'delivered_at',
        
        // Delivery Fields
        'customer_name',
        'customer_phone',
        'delivery_address',
        'delivery_fee',
        'delivery_notes',
        'rider_id',
        'source',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'tax_amount' => 'float',
        'service_charge' => 'float',
        'discount_amount' => 'float',
        'refunded_amount' => 'float',
        'refunded_at' => 'datetime',
        'accepted_at' => 'datetime',
        'prepared_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'payment_status' => 'string',
    ];

    // ─── Status Constants ───
    const STATUS_COMPLETED = 'Completed';
    const STATUS_DRAFTED = 'Drafted';
    const STATUS_VOID = 'Void';
    const STATUS_REFUNDED = 'Refunded';
    const STATUS_PARTIALLY_REFUNDED = 'Partially Refunded';
    const STATUS_READY = 'Ready'; // Kitchen has finished preparing

    // Delivery Statuses
    const STATUS_PENDING            = 'Pending';    // Waiting for branch approval
    const STATUS_PREPARING          = 'Preparing';  // Accepted by branch (Stock Deducted)
    const STATUS_OUT_FOR_DELIVERY   = 'Out for Delivery';
    const STATUS_HANDED_TO_RIDER    = 'Handed to Rider';
    const STATUS_CANCELLED          = 'Cancelled';

    // ─── Type Constants ───
    const TYPE_POS      = 'POS';
    const TYPE_DELIVERY = 'Delivery';
    const TYPE_PICKUP   = 'Pickup';

    // ─── Relationships ───

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(FinancialLedger::class);
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    // ─── Accessors & Attributes ───

    public function getNetAmountAttribute()
    {
        return $this->total_amount - $this->refunded_amount;
    }

    public function getRefundableAmountAttribute()
    {
        return $this->total_amount - $this->refunded_amount;
    }

    public function getRefundPercentageAttribute()
    {
        return $this->total_amount > 0 
            ? round(($this->refunded_amount / $this->total_amount) * 100, 2)
            : 0;
    }

    // ─── Status Checks ───

    public function isDrafted(): bool
    {
        return $this->status === self::STATUS_DRAFTED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isVoid(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    public function canBeRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED]) 
            && $this->refundable_amount > 0 
            && $this->created_at->diffInHours(now()) < 1;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPreparing(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function canBeAccepted(): bool
    {
        return $this->isPending() && $this->order_type !== self::TYPE_POS;
    }

    public function canBeVoided(): bool
    {
        // Cannot void an order that is already voided, refunded, drafted, or cancelled.
        // Pending app orders should be 'Rejected' instead.
        // Completed orders CAN be voided (e.g. wrong POS entry)
        $invalidStatuses = [
            self::STATUS_VOID, 
            self::STATUS_REFUNDED, 
            self::STATUS_PARTIALLY_REFUNDED, 
            self::STATUS_DRAFTED,
            self::STATUS_CANCELLED,
            self::STATUS_PENDING,
            self::STATUS_HANDED_TO_RIDER,
            self::STATUS_OUT_FOR_DELIVERY
        ];

        return !in_array($this->status, $invalidStatuses) 
            && $this->created_at->diffInMinutes(now()) <= 30;
    }

    // ─── Actions ───

    public function reject(string $reason)
    {
        if ($this->status !== self::STATUS_PENDING) {
            return $this;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => ($this->notes ? $this->notes . "\n" : "") . trim($reason)
        ]);

        event(new OrderStatusUpdated($this));

        return $this;
    }

    public function void(string $reason = null, int $userId = null)
    {
        $this->update([
            'status' => self::STATUS_VOID,
            'refund_reason' => $reason,
            'refunded_by' => $userId ?? auth()->id(),
            'refunded_at' => now(),
            'refunded_amount' => $this->total_amount,
        ]);

        // Reverse stock deductions for all items
        foreach ($this->items as $item) {
            StockDeductionService::reverseDeduction($this->branch_id, $item->id);
        }

        return $this;
    }

    public function refund(float $amount, string $reason = null, int $userId = null)
    {
        if ($amount > $this->refundable_amount) {
            throw new \Exception("Refund amount exceeds refundable amount (₱{$this->refundable_amount})");
        }

        $newRefundedAmount = $this->refunded_amount + $amount;
        $refundedTotal = $newRefundedAmount >= $this->total_amount;

        $this->update([
            'status' => $refundedTotal ? self::STATUS_REFUNDED : self::STATUS_PARTIALLY_REFUNDED,
            'refunded_amount' => $newRefundedAmount,
            'refund_reason' => $reason,
            'refunded_by' => $userId ?? auth()->id(),
            'refunded_at' => now(),
        ]);

        return $this;
    }

    public function updateStatus(string $newStatus, array $additionalData = [])
    {
        $oldStatus = $this->status;
        if ($oldStatus === $newStatus && empty($additionalData)) {
            return $this;
        }

        return DB::transaction(function () use ($newStatus, $additionalData) {
            $data = array_merge(['status' => $newStatus], $additionalData);

            // ─── Status Lifecycle Logic ───
            switch ($newStatus) {
                case self::STATUS_PREPARING:
                    if (!$this->accepted_at) {
                        $data['accepted_at'] = now();
                        
                        // 1. DEDUCT STOCK (FEFO)
                        $result = StockDeductionService::deductForOrder($this);
                        if (!$result['success']) {
                            throw new \Exception($result['message']);
                        }
                    }
                    break;

                case self::STATUS_READY:
                    if (!$this->prepared_at) {
                        $data['prepared_at'] = now();
                    }
                    break;

                case self::STATUS_OUT_FOR_DELIVERY:
                case self::STATUS_HANDED_TO_RIDER:
                    if (!$this->dispatched_at) {
                        $data['dispatched_at'] = now();
                    }
                    break;

                case self::STATUS_COMPLETED:
                    if (!$this->delivered_at) {
                        $data['delivered_at'] = now();
                        
                        // 2. FINANCIAL AUDIT: COD RECORDING
                        // Marking as Paid will trigger the centralized 'updated' event recording
                        if (in_array($this->payment_method, ['COD', 'Cash on Delivery'])) {
                            $data['payment_status'] = 'Paid';
                        }
                    }
                    break;

                case self::STATUS_CANCELLED:
                case self::STATUS_VOID:
                    // Optional: Reverse stock deduction if cancelled?
                    // Typically handled via a separate "Void" flow to track waste vs return.
                    break;
            }

            $this->update($data);

            // 3. BROADCAST UPDATE
            event(new OrderStatusUpdated($this));

            return $this;
        });
    }

    // ─── Scopes ───

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeDrafted($query)
    {
        return $query->where('status', self::STATUS_DRAFTED);
    }

    public function scopeVoid($query)
    {
        return $query->where('status', self::STATUS_VOID);
    }

    public function scopeRefunded($query)
    {
        return $query->whereIn('status', [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    public function scopeByReference($query, string $ref)
    {
        return $query->where('reference_no', 'like', "%{$ref}%");
    }

    /**
     * Record a financial transaction for this order to the ledger.
     * Uses idempotency check to ensure sales are recorded only once.
     */
    public function recordFinancialTransaction(): void
    {
        // Prevent recording if total is 0 or if sale already exists
        if ($this->total_amount <= 0) {
            return;
        }

        $exists = FinancialLedger::where('order_id', $this->id)
            ->where('transaction_type', FinancialLedger::TRANSACTION_TYPE_SALE)
            ->exists();

        if ($exists) {
            return;
        }

        FinancialLedger::recordSale($this, $this->branch_id, $this->user_id);
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}

