<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'ingredient_id',
        'branch_id',
        'unit_cost',
        'cost_per_base_unit',
        'last_updated_by',
        'notes'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:4',
        'cost_per_base_unit' => 'decimal:4',
    ];

    /**
     * Relationship: Ingredient this cost belongs to
     */
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Relationship: Branch this cost applies to
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relationship: User who last updated this cost
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }

    /**
     * Get the stock for this ingredient at this branch
     */
    public function branchStock()
    {
        return $this->hasOne(BranchIngredientStock::class, 'ingredient_id', 'ingredient_id')
                    ->where('branch_id', $this->branch_id);
    }

    /**
     * Calculate total inventory value for this ingredient at this branch
     * Used for stock management and financial reporting
     */
    public function getInventoryValueAttribute()
    {
        $stock = $this->branchStock;
        return $stock ? ($stock->stock_quantity * $this->unit_cost) : 0;
    }

    /**
     * Scope to get costs for a specific branch
     */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope to get costs for a specific ingredient
     */
    public function scopeForIngredient($query, $ingredientId)
    {
        return $query->where('ingredient_id', $ingredientId);
    }

    /**
     * Scope to get costs above a certain threshold
     */
    public function scopeAboveCost($query, $threshold)
    {
        return $query->where('unit_cost', '>', $threshold);
    }
}

