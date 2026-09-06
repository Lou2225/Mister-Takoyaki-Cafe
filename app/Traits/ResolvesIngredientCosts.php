<?php

namespace App\Traits;

use App\Models\Ingredient;
use App\Models\IngredientCost;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

/**
 * Single source of truth for resolving an ingredient's cost.
 *
 * unit_cost is the canonical per-base-unit (g/ml/pc) cost — it's what
 * ProfitCalculationService, the IngredientCost model, and every dashboard
 * already read. cost_per_base_unit is a secondary column kept only as a
 * fallback for rows where unit_cost wasn't set. Nothing should read either
 * column directly — always go through here.
 */
trait ResolvesIngredientCosts
{
    protected function buildBranchCostMap($branchId = null): Collection
    {
        return IngredientCost::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(function (Collection $costs) {
                return $costs->mapWithKeys(fn($cost) => [
                    $cost->ingredient_id => (float) ($cost->unit_cost ?: $cost->cost_per_base_unit ?: 0),
                ]);
            });
    }

    protected function buildPurchasePriceMap($branchId = null): Collection
    {
        return StockMovement::where('type', 'in')
            ->whereNotNull('unit_cost')
            ->where('unit_cost', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->unique('ingredient_id')->pluck('unit_cost', 'ingredient_id'));
    }

    protected function buildGlobalCostMap(): Collection
    {
        return Ingredient::pluck('cost', 'id')->map(fn($c) => (float) $c);
    }
}