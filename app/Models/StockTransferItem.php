<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'ingredient_id',
        'quantity',
        'unit',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function stockTransfer()
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
