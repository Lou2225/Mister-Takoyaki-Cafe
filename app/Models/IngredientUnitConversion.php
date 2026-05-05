<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientUnitConversion extends Model
{
    protected $fillable = [
        'ingredient_id',
        'unit_name',
        'qty_in_base',
        'price_per_unit',
        'sort_order',
    ];

    protected $casts = [
        'qty_in_base'    => 'float',
        'price_per_unit' => 'float',
        'sort_order'     => 'integer',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Cost per single base unit using this packaging.
     * e.g. if a box (18,000 ml) costs ₱550 → ₱0.0306 per ml
     */
    public function getCostPerBaseAttribute(): float
    {
        return $this->qty_in_base > 0
            ? $this->price_per_unit / $this->qty_in_base
            : 0;
    }
}
