<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap/app.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
echo "Product counts:\n";
foreach (App\Models\ProductCategory::withCount('products as associated_count')->take(5)->get() as $c) {
    echo $c->name . ': ' . $c->associated_count . "\n";
}
echo "\nIngredient counts:\n";
foreach (App\Models\IngredientCategory::withCount('ingredients as associated_count')->take(5)->get() as $c) {
    echo $c->name . ': ' . $c->associated_count . "\n";
}
