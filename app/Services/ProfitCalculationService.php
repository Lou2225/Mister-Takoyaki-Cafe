<?php

namespace App\Services;

use App\Models\Product;
use App\Models\IngredientCost;
use App\Models\Recipe;
use App\Models\BranchIngredientStock;
use App\Models\Branch;
use Illuminate\Support\Facades\Cache;

class ProfitCalculationService
{
    /**
     * Calculate profit metrics for a product at a specific branch
     * Integrates with: Menu Management, Stock Management, Branch Management
     * 
     * @param Product $product
     * @param int|null $branchId
     * @return array
     */
    public static function calculateProductProfit(Product $product, ?int $branchId = null): array
    {
        $branchId = $branchId ?? auth()->user()->branch_id ?? 1;

        // Get all recipes for this product
        $recipes = $product->recipes()->with('ingredient')->get();

        $recipeCost = 0;
        $recipeDetails = [];

        foreach ($recipes as $recipe) {
            // Get ingredient cost for this branch
            $ingredientCost = IngredientCost::where('ingredient_id', $recipe->ingredient_id)
                ->where('branch_id', $branchId)
                ->first();

            $unitCost = $ingredientCost?->unit_cost ?? 0;
            $ingredientExpense = $recipe->quantity * $unitCost;
            $recipeCost += $ingredientExpense;

            $recipeDetails[] = [
                'ingredient_id' => $recipe->ingredient_id,
                'ingredient_name' => $recipe->ingredient->name,
                'quantity' => $recipe->quantity,
                'unit' => $recipe->ingredient->unit,
                'unit_cost' => $unitCost,
                'total_cost' => $ingredientExpense,
            ];
        }

        $salePrice = (float)$product->price;
        $netProfit = $salePrice - $recipeCost;
        $profitMargin = $salePrice > 0 ? round(($netProfit / $salePrice) * 100, 2) : 0;

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'branch_id' => $branchId,
            'sale_price' => $salePrice,
            'recipe_cost' => round($recipeCost, 2),
            'net_profit' => round($netProfit, 2),
            'profit_margin_percent' => $profitMargin,
            'health_status' => self::getHealthStatus($profitMargin),
            'recipe_details' => $recipeDetails,
        ];
    }

    /**
     * Get inventory value for a product at a branch
     * Connects to Stock Management for real-time stock data
     * 
     * @param Product $product
     * @param int|null $branchId
     * @return float
     */
    public static function getProductInventoryValue(Product $product, ?int $branchId = null): float
    {
        $branchId = $branchId ?? auth()->user()->branch_id ?? 1;

        $recipes = $product->recipes()->with('ingredient')->get();
        $totalValue = 0;

        foreach ($recipes as $recipe) {
            // Get stock level for this ingredient at this branch
            $stock = BranchIngredientStock::where('ingredient_id', $recipe->ingredient_id)
                ->where('branch_id', $branchId)
                ->first();

            if (!$stock) continue;

            // Get ingredient cost
            $ingredientCost = IngredientCost::where('ingredient_id', $recipe->ingredient_id)
                ->where('branch_id', $branchId)
                ->first();

            $unitCost = $ingredientCost?->unit_cost ?? 0;
            
            // Value is based on how many complete recipes we can make
            $stockValue = $stock->stock_quantity * $unitCost;
            $totalValue += $stockValue;
        }

        return round($totalValue, 2);
    }

    /**
     * Get all products with profit analytics for a branch
     * Useful for profit reports and POS displays
     * 
     * @param int|null $branchId
     * @return array
     */
    public static function getBranchProfitAnalytics(?int $branchId = null): array
    {
        $branchId = $branchId ?? auth()->user()->branch_id ?? 1;
        
        // Use cache to improve performance
        $cacheKey = "branch_profit_analytics_{$branchId}";
        
        return Cache::remember($cacheKey, 3600, function () use ($branchId) {
            $products = Product::where('is_active', true)
                ->where(function ($q) use ($branchId) {
                    $q->where('scope', 'global')
                      ->orWhereHas('branches', fn($b) => $b->where('branches.id', $branchId));
                })
                ->get();

            $analytics = [];
            $totalProfit = 0;
            $healthCounts = ['healthy' => 0, 'warning' => 0, 'critical' => 0];

            foreach ($products as $product) {
                $profit = self::calculateProductProfit($product, $branchId);
                $analytics[] = $profit;
                $totalProfit += $profit['net_profit'];
                $healthCounts[$profit['health_status']]++;
            }

            return [
                'branch_id' => $branchId,
                'products' => $analytics,
                'total_revenue_potential' => array_sum(array_map(fn($p) => $p['sale_price'], $analytics)),
                'total_cogs' => array_sum(array_map(fn($p) => $p['recipe_cost'], $analytics)),
                'total_profit' => round($totalProfit, 2),
                'average_margin' => count($analytics) > 0 ? round(array_sum(array_map(fn($p) => $p['profit_margin_percent'], $analytics)) / count($analytics), 2) : 0,
                'health_distribution' => $healthCounts,
            ];
        });
    }

    /**
     * Determine health status based on profit margin
     * 
     * @param float $marginPercent
     * @return string
     */
    public static function getHealthStatus(float $marginPercent): string
    {
        if ($marginPercent < 0 || $marginPercent < 15) {
            return 'critical';
        } elseif ($marginPercent < 25) {
            return 'warning';
        }
        return 'healthy';
    }

    /**
     * Get low-margin products alert (for reports/POS)
     * Helps identify pricing issues
     * 
     * @param int|null $branchId
     * @param float $threshold Margin threshold percentage
     * @return array
     */
    public static function getLowMarginProducts(?int $branchId = null, float $threshold = 25): array
    {
        $branchId = $branchId ?? auth()->user()->branch_id ?? 1;
        $analytics = self::getBranchProfitAnalytics($branchId);
        
        return array_filter($analytics['products'], fn($p) => $p['profit_margin_percent'] < $threshold);
    }

    /**
     * Calculate recipe cost with option modifications
     * Used when pricing includes dynamic options
     * 
     * @param Product $product
     * @param array $selectedOptions Array of ['option_id' => quantity]
     * @param int|null $branchId
     * @return float
     */
    public static function calculateRecipeCostWithOptions(Product $product, array $selectedOptions = [], ?int $branchId = null): float
    {
        $branchId = $branchId ?? auth()->user()->branch_id ?? 1;
        
        // Get base recipe cost
        $baseCost = 0;
        $baseRecipes = $product->recipes()->where('product_option_id', null)->get();
        
        foreach ($baseRecipes as $recipe) {
            $ingredientCost = IngredientCost::where('ingredient_id', $recipe->ingredient_id)
                ->where('branch_id', $branchId)
                ->first();
            
            $unitCost = $ingredientCost?->unit_cost ?? 0;
            $baseCost += $recipe->quantity * $unitCost;
        }

        // Add option-specific recipes
        $optionCost = 0;
        foreach ($selectedOptions as $optionId => $quantity) {
            $optionRecipes = $product->recipes()
                ->where('product_option_id', $optionId)
                ->get();
            
            foreach ($optionRecipes as $recipe) {
                $ingredientCost = IngredientCost::where('ingredient_id', $recipe->ingredient_id)
                    ->where('branch_id', $branchId)
                    ->first();
                
                $unitCost = $ingredientCost?->unit_cost ?? 0;
                $optionCost += ($recipe->quantity * $unitCost * $quantity);
            }
        }

        return round($baseCost + $optionCost, 2);
    }

    /**
     * Clear profit analytics cache when inventory or costs change
     * Called from Stock Management and Ingredient Cost updates
     * 
     * @param int|null $branchId
     */
    public static function clearAnalyticsCache(?int $branchId = null): void
    {
        if ($branchId) {
            Cache::forget("branch_profit_analytics_{$branchId}");
        } else {
            // Clear all branch caches
            $branches = Branch::all();
            foreach ($branches as $branch) {
                Cache::forget("branch_profit_analytics_{$branch->id}");
            }
        }
    }
}
