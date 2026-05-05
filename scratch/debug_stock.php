<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ingredient;
use App\Models\Branch;
use App\Models\BranchIngredientStock;

$ing = Ingredient::where('name', 'like', '%Quickmelt%')->first();
if (!$ing) {
    echo "INGREDIENT NOT FOUND\n";
    exit;
}

echo "Ingredient: " . $ing->name . " (Base Unit: " . $ing->unit . ")\n";

$stocks = BranchIngredientStock::where('ingredient_id', $ing->id)->get();
foreach($stocks as $s) {
    $b = Branch::find($s->branch_id);
    echo "Branch: " . ($b->branch_name ?? 'Unknown') . " (Is Main: " . ($b->is_main ? 'YES' : 'NO') . ") -> Stock: " . $s->stock_quantity . "\n";
}
