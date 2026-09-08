<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'stock_batch_id',
        'special_instructions',
        'apply_regular_discount',
        'apply_senior_discount',
    ];

    protected $casts = [
        'apply_regular_discount' => 'boolean',
        'apply_senior_discount' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function options()
    {
        return $this->hasMany(OrderItemOption::class);
    }

    public function modifiers()
    {
        return $this->hasMany(OrderItemModifier::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getPriceAttribute(): float
    {
        return (float) ($this->attributes['price'] ?? $this->attributes['unit_price'] ?? 0);
    }

    public function batch()
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }
}
