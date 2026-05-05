<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BranchIngredientStock;
use App\Models\Recipe;
use App\Models\Product;

echo "--- STOCKS ---\n";
$stocks = BranchIngredientStock::with('ingredient')->where('stock_quantity', '>', 0)->get();
foreach ($stocks as $s) {
    echo "{$s->ingredient->name}: {$s->stock_quantity} ({$s->ingredient->unit})\n";
}

echo "\n--- PRODUCT RECIPES ---\n";
$products = Product::with('recipes.ingredient')->get();
foreach ($products as $p) {
    echo "Product: {$p->name}\n";
    foreach ($p->recipes as $r) {
        echo "  - {$r->ingredient->name}: {$r->quantity} {$r->ingredient->unit}\n";
    }
}
