<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_order_id',
        'reference_no',
        'from_branch_id',
        'to_branch_id',
        'transferred_by',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function stockOrder()
    {
        return $this->belongsTo(StockOrder::class);
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Generate unique transfer reference.
     * Format: TRF-YYYYMMDD-XXXX
     */
    public static function generateReference(): string
    {
        $prefix = 'TRF-' . now()->format('Ymd') . '-';
        $last   = self::where('reference_no', 'like', $prefix . '%')
                      ->orderByDesc('id')
                      ->value('reference_no');

        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
