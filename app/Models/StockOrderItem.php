<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_order_id',
        'ingredient_id',
        'requested_quantity',
        'approved_quantity',
        'unit',
        'order_unit',
        'unit_price',
        'subtotal',
        'notes',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function stockOrder()
    {
        return $this->belongsTo(StockOrder::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function effectiveQuantity(): float
    {
        return (float) ($this->approved_quantity ?? $this->requested_quantity);
    }

    public function wasReduced(): bool
    {
        return $this->approved_quantity !== null
            && (float) $this->approved_quantity < (float) $this->requested_quantity;
    }
}
