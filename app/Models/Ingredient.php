<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'scope',
        'category_id',
        'unit',
        'bulk_unit',
        'bulk_qty',
        'bulk_price',
        'cost',
        'minimum_stock',
    ];

    public function category()
    {
        return $this->belongsTo(IngredientCategory::class, 'category_id');
    }

    public function batches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function branchStocks()
    {
        return $this->hasMany(BranchIngredientStock::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stockOrderItems()
    {
        return $this->hasMany(StockOrderItem::class);
    }

    /**
     * All purchasable packaging units for this ingredient, ordered smallest → largest.
     * e.g. [bottle (750ml @ ₱25), box (18,000ml @ ₱550), case (108,000ml @ ₱3,100)]
     */
    public function unitConversions()
    {
        return $this->hasMany(IngredientUnitConversion::class)->orderBy('sort_order')->orderBy('qty_in_base');
    }

    /**
     * Returns the most economical cost-per-base-unit across all conversion tiers.
     * Used to auto-populate the ingredient's recipe cost after conversions are saved.
     */
    public function bestCostPerBase(): float
    {
        $best = $this->unitConversions()
            ->where('qty_in_base', '>', 0)
            ->where('price_per_unit', '>', 0)
            ->get()
            ->min(fn($c) => $c->price_per_unit / $c->qty_in_base);

        return (float) ($best ?? $this->cost ?? 0);
    }
}
