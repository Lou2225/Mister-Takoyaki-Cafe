<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    protected $fillable = [
        'name',
        'category_id',
        'description',
        'price',
        'cost',
        'is_active',
        'sort_order',
        'image',
        'scope', // 'global' or 'branch'
    ];

    public function getImageUrlAttribute()
    {
        return $this->image 
            ? asset('storage/' . $this->image) 
            : asset('images/placeholder-product.png');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipes')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function optionGroups()
    {
        return $this->hasMany(ProductOptionGroup::class)->orderBy('sort_order');
    }

    public function modifiers()
    {
        return $this->belongsToMany(Modifier::class, 'modifier_product');
    }

    /**
     * Branches this product is explicitly assigned to (used when scope = 'branch').
     */
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_product')
                    ->withPivot('price', 'sort_order')
                    ->withTimestamps();
    }

    /**
     * Get the price of the product at a specific branch.
     * Falls back to the global price if no branch-specific price is set.
     */
    public function getPriceAt(?int $branchId): float
    {
        if (!$branchId) {
            return (float) $this->price;
        }

        $branchPrice = DB::table('branch_product')
            ->where('branch_id', $branchId)
            ->where('product_id', $this->id)
            ->value('price');

        return (float) ($branchPrice ?? $this->price);
    }


    public function availabilityAt(int $branchId, $prefetchedStocks = null): string
    {
        $maxQty = $this->getMaxAvailableQuantity($branchId, $prefetchedStocks);
        
        if ($maxQty <= 0) {
            return 'unavailable';
        }
        
        if ($maxQty <= 10) {
            return 'low_stock';
        }

        return 'available';
    }

    /**
     * Get the maximum quantity of this product that can be sold at a branch
     * (determined by bottleneck ingredient with lowest available quantity)
     * Uses base recipes, or falls back to default option if no base recipe exists
     */
    public function getMaxAvailableQuantity(int $branchId, $prefetchedStocks = null): int
    {
        // Try to get base recipes first (no product_option_id, no modifier_id)
        if ($this->relationLoaded('recipes')) {
            $recipes = $this->recipes
                ->where('product_option_id', null)
                ->where('modifier_id', null);
        } else {
            $recipes = $this->recipes()
                ->whereNull('product_option_id')
                ->whereNull('modifier_id')
                ->get();
        }
        
        // If no base recipes, fall back to default option
        if ($recipes->isEmpty()) {
            if ($this->relationLoaded('recipes') && $this->relationLoaded('optionGroups.options')) {
                // Complex collection logic to find default option recipes
                $defaultOptionIds = $this->optionGroups->flatMap(fn($g) => $g->options->where('is_default', true)->pluck('id'));
                $recipes = $this->recipes
                    ->whereIn('product_option_id', $defaultOptionIds)
                    ->where('modifier_id', null);
            } else {
                $recipes = $this->recipes()
                    ->join('product_options', 'recipes.product_option_id', '=', 'product_options.id')
                    ->where('product_options.is_default', true)
                    ->whereNull('recipes.modifier_id')
                    ->select('recipes.*')
                    ->get();
            }
        }
        
        if ($recipes->isEmpty()) {
            return 0;
        }

        $maxQty = PHP_INT_MAX;

        // Batch fetch stocks if not provided
        if ($prefetchedStocks === null) {
            $ingredientIds = $recipes->pluck('ingredient_id')->unique();
            $prefetchedStocks = \App\Models\BranchIngredientStock::where('branch_id', $branchId)
                ->whereIn('ingredient_id', $ingredientIds)
                ->pluck('stock_quantity', 'ingredient_id');
        }

        foreach ($recipes as $recipe) {
            if ($recipe->quantity <= 0) continue;
            $stock = $prefetchedStocks[$recipe->ingredient_id] ?? 0;
            $possibleQty = intval($stock / $recipe->quantity);
            $maxQty = min($maxQty, $possibleQty);
        }

        return max(0, $maxQty === PHP_INT_MAX ? 0 : $maxQty);
    }

    /**
     * Get availability per option in a group (for display in POS)
     * Returns array: ['optionId' => 'quantity_available']
     */
    public function getOptionAvailability(int $branchId, $prefetchedStocks = null): array
    {
        $availability = [];
        
        // Pre-load all options and recipes to avoid N+1 inside loops
        $this->loadMissing(['optionGroups.options', 'recipes']);
        $allRecipes = $this->recipes;

        // Batch fetch stocks if not provided
        if ($prefetchedStocks === null) {
            $ingredientIds = $allRecipes->pluck('ingredient_id')->unique();
            $prefetchedStocks = \App\Models\BranchIngredientStock::where('branch_id', $branchId)
                ->whereIn('ingredient_id', $ingredientIds)
                ->pluck('stock_quantity', 'ingredient_id');
        }

        foreach ($this->optionGroups as $group) {
            foreach ($group->options as $option) {
                $recipes = $allRecipes->where('product_option_id', $option->id)->whereNull('modifier_id');

                if ($recipes->isEmpty()) {
                    $availability[$option->id] = $this->getMaxAvailableQuantity($branchId, $prefetchedStocks);
                    continue;
                }

                $maxQty = PHP_INT_MAX;
                foreach ($recipes as $recipe) {
                    if ($recipe->quantity <= 0) continue;
                    $stock = $prefetchedStocks[$recipe->ingredient_id] ?? 0;
                    $possibleQty = intval($stock / $recipe->quantity);
                    $maxQty = min($maxQty, $possibleQty);
                }

                $availability[$option->id] = max(0, $maxQty === PHP_INT_MAX ? 0 : $maxQty);
            }
        }

        return $availability;
    }

    /**
     * Get availability for specific modifiers (toppings/extras)
     */
    public function getModifierAvailability(int $branchId, $prefetchedStocks = null): array
    {
        $availability = [];
        $this->loadMissing(['modifiers', 'recipes']);
        $allRecipes = Recipe::whereIn('modifier_id', $this->modifiers->pluck('id'))->get();

        if ($prefetchedStocks === null) {
            $ingredientIds = $allRecipes->pluck('ingredient_id')->unique();
            $prefetchedStocks = \App\Models\BranchIngredientStock::where('branch_id', $branchId)
                ->whereIn('ingredient_id', $ingredientIds)
                ->pluck('stock_quantity', 'ingredient_id');
        }

        foreach ($this->modifiers as $modifier) {
            $recipes = $allRecipes->where('modifier_id', $modifier->id);

            if ($recipes->isEmpty()) {
                $availability[$modifier->id] = 999;
                continue;
            }

            $maxQty = PHP_INT_MAX;
            foreach ($recipes as $recipe) {
                if ($recipe->quantity <= 0) continue;
                $stock = $prefetchedStocks[$recipe->ingredient_id] ?? 0;
                $possibleQty = intval($stock / $recipe->quantity);
                $maxQty = min($maxQty, $possibleQty);
            }

            $availability[$modifier->id] = max(0, $maxQty === PHP_INT_MAX ? 0 : $maxQty);
        }

        return $availability;
    }

    /**
     * Get full availability data for API responses
     */
    public function getAvailabilityData(?int $branchId): array
    {
        if (!$branchId) {
            // ... (keep initial logic)
            $optionAvail = [];
            foreach ($this->optionGroups as $group) {
                foreach ($group->options as $option) {
                    $optionAvail[$option->id] = 999;
                }
            }

            $modifierAvail = [];
            foreach ($this->modifiers as $modifier) {
                $modifierAvail[$modifier->id] = 999;
            }

            return [
                'is_available' => true,
                'available_quantity' => 999,
                'availability_label' => 'Select Branch for Stock',
                'option_availability' => $optionAvail,
                'modifier_availability' => $modifierAvail,
            ];
        }

        // Efficient batch loading for the full data set
        $this->loadMissing(['recipes', 'optionGroups.options', 'modifiers']);
        $ingredientIds = $this->recipes->pluck('ingredient_id')->unique();
        
        $stocks = \App\Models\BranchIngredientStock::where('branch_id', $branchId)
            ->whereIn('ingredient_id', $ingredientIds)
            ->pluck('stock_quantity', 'ingredient_id');

        $qty = $this->getMaxAvailableQuantity($branchId, $stocks);
        $optionAvail = $this->getOptionAvailability($branchId, $stocks);
        $modifierAvail = $this->getModifierAvailability($branchId, $stocks);

        return [
            'is_available' => $qty > 0,
            'available_quantity' => $qty,
            'availability_label' => $qty > 0 ? 'Available' : 'Unavailable / Out of Stock',
            'option_availability' => $optionAvail,
            'modifier_availability' => $modifierAvail,
        ];
    }
}
