<?php
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ingredient;
use App\Models\IngredientCost;
use App\Models\StockMovement;
use Carbon\Carbon;

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$start = Carbon::parse('2020-01-01')->startOfDay();
$end   = Carbon::parse('2026-12-31')->endOfDay();

$orders = Order::whereIn('status', [Order::STATUS_COMPLETED])
    ->whereBetween('created_at', [$start, $end])
    ->with([
        'items.product.recipes.ingredient',
        'items.options.option.recipes.ingredient',
        'items.modifiers.modifier.recipes.ingredient',
    ])
    ->get();

$standardCosts = IngredientCost::get()->groupBy('branch_id')->map(fn($group) => $group->pluck('unit_cost', 'ingredient_id'));
$latestPurchasePrices = StockMovement::where('type', 'in')->whereNotNull('unit_cost')->where('unit_cost', '>', 0)->orderBy('created_at', 'desc')->get()->groupBy('branch_id')->map(fn($group) => $group->unique('ingredient_id')->pluck('unit_cost', 'ingredient_id'));
$fallbackCosts = Ingredient::pluck('cost', 'id');

foreach ($orders as $order) {
    echo "ORDER ID: " . $order->id . " | Total: " . $order->total_amount . "\n";
    foreach ($order->items as $item) {
        echo "  ITEM: " . ($item->product->name ?? '??') . " | Qty: " . $item->quantity . "\n";
        $itemCost = 0;
        $itemBranchId = $order->branch_id;
        
        $getUnitPrice = function($ingId) use ($itemBranchId, $standardCosts, $latestPurchasePrices, $fallbackCosts) {
            $cost = $standardCosts[$itemBranchId][$ingId] 
                ?? $latestPurchasePrices[$itemBranchId][$ingId] 
                ?? $fallbackCosts[$ingId] 
                ?? 0;
            return $cost;
        };

        if ($item->product) {
            foreach ($item->product->recipes as $recipe) {
                $uPrice = $getUnitPrice($recipe->ingredient_id);
                $lineCost = $recipe->quantity * $uPrice;
                echo "    - RECIPE: " . ($recipe->ingredient->name ?? '??') . " | Qty: " . $recipe->quantity . " | Unit Cost: " . $uPrice . " | Line Cost: " . $lineCost . "\n";
                $itemCost += $lineCost;
            }
        }
        
        // Check Options
        foreach ($item->options as $opt) {
             if ($opt->option) {
                foreach ($opt->option->recipes as $recipe) {
                    $uPrice = $getUnitPrice($recipe->ingredient_id);
                    $lineCost = $recipe->quantity * $uPrice;
                    echo "    - OPTION RECIPE: " . ($recipe->ingredient->name ?? '??') . " | Qty: " . $recipe->quantity . " | Unit Cost: " . $uPrice . " | Line Cost: " . $lineCost . "\n";
                    $itemCost += $lineCost;
                }
            }
        }
    }
}
