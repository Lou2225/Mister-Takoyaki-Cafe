<?php
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$orderItems = OrderItem::with(['product.recipes.ingredient', 'order'])->get();

echo "Total Order Items in DB: " . $orderItems->count() . "\n";

$totalCogs = 0;
$totalSales = 0;

foreach ($orderItems as $item) {
    if ($item->order->status !== 'Completed') continue;
    
    $totalSales += $item->subtotal;
    $itemCost = 0;
    if ($item->product && $item->product->recipes) {
        foreach ($item->product->recipes as $recipe) {
            $cost = $recipe->ingredient ? $recipe->ingredient->cost : 0;
            $itemCost += ($recipe->quantity * $cost);
        }
    }
    $totalCogs += ($itemCost * $item->quantity);
    
    if ($itemCost > 1000) {
        echo "SUSPICIOUS ITEM: " . ($item->product->name ?? 'Unknown') . " | Qty: " . $item->quantity . " | Cost per unit: " . $itemCost . "\n";
        foreach ($item->product->recipes as $recipe) {
             echo "  - " . ($recipe->ingredient->name ?? '??') . ": Qty " . $recipe->quantity . " x Cost " . $recipe->ingredient->cost . " = " . ($recipe->quantity * $recipe->ingredient->cost) . "\n";
        }
    }
}

echo "TOTAL SALES: " . $totalSales . "\n";
echo "TOTAL COGS: " . $totalCogs . "\n";
echo "GROSS PROFIT: " . ($totalSales - $totalCogs) . "\n";
