<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class StockOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_no',
        'requesting_branch_id',
        'source_branch_id',
        'priority',
        'status',
        'notes',
        'delivery_fee',
        'total_amount',
        'rejection_reason',
        'admin_remarks',
        'requested_by',
        'approved_by',
        'approved_at',
        'dispatched_at',
        'delivered_at',
    ];

    protected $casts = [
        'approved_at'   => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at'  => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function requestingBranch()
    {
        return $this->belongsTo(Branch::class, 'requesting_branch_id');
    }

    public function sourceBranch()
    {
        return $this->belongsTo(Branch::class, 'source_branch_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(StockOrderItem::class);
    }

    public function transfers()
    {
        return $this->hasMany(StockTransfer::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'preparing', 'in_transit']);
    }

    public function scopeForBranch($query, $branchId)
    {
        return $query->where('requesting_branch_id', $branchId)
                     ->orWhere('source_branch_id', $branchId);
    }

    public function scopeRequestedBy($query, $branchId)
    {
        return $query->where('requesting_branch_id', $branchId);
    }

    // ── Status Helpers ────────────────────────────────────────────

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isApproved(): bool   { return $this->status === 'approved'; }
    public function isPreparing(): bool  { return $this->status === 'preparing'; }
    public function isInTransit(): bool  { return $this->status === 'in_transit'; }
    public function isDelivered(): bool  { return $this->status === 'delivered'; }
    public function isRejected(): bool   { return $this->status === 'rejected'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }

    public function isActionable(): bool
    {
        return in_array($this->status, ['pending', 'approved', 'preparing']);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Generate a unique order reference number.
     * Format: STR-YYYYMMDD-XXXX (e.g. STR-20260504-0042)
     */
    public static function generateReference(): string
    {
        $prefix = 'STR-' . now()->format('Ymd') . '-';
        $last   = self::where('reference_no', 'like', $prefix . '%')
                      ->orderByDesc('id')
                      ->value('reference_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Human-readable status labels and colors for the UI.
     */
    public static function statusConfig(): array
    {
        return [
            'pending'    => ['label' => 'Pending',    'color' => 'amber',   'dot' => 'bg-amber-500'],
            'approved'   => ['label' => 'Approved',   'color' => 'blue',    'dot' => 'bg-blue-500'],
            'preparing'  => ['label' => 'Preparing',  'color' => 'purple',  'dot' => 'bg-purple-500'],
            'in_transit' => ['label' => 'In Transit', 'color' => 'cyan',    'dot' => 'bg-cyan-500'],
            'delivered'  => ['label' => 'Delivered',  'color' => 'emerald', 'dot' => 'bg-emerald-500'],
            'rejected'   => ['label' => 'Rejected',   'color' => 'red',     'dot' => 'bg-red-500'],
            'cancelled'  => ['label' => 'Cancelled',  'color' => 'gray',    'dot' => 'bg-gray-400'],
        ];
    }

    public function priorityConfig(): array
    {
        return [
            'normal'   => ['label' => 'Normal',   'color' => 'slate'],
            'urgent'   => ['label' => 'Urgent',   'color' => 'amber'],
            'critical' => ['label' => 'Critical', 'color' => 'red'],
        ];
    }
}
