<?php
use App\Models\Ingredient;
use App\Models\IngredientCost;
use App\Models\StockMovement;
use App\Models\IngredientUnitConversion;

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// We'll look for costs that are suspiciously high compared to the fallback cost in the Ingredient table
$ingredients = Ingredient::all();

foreach ($ingredients as $ing) {
    echo "Processing " . $ing->name . " (Base Cost: " . $ing->cost . ")\n";
    
    // Fix IngredientCost table
    $costs = IngredientCost::where('ingredient_id', $ing->id)->get();
    foreach ($costs as $c) {
        if ($c->unit_cost > $ing->cost * 10) { // Suspiciously high
             echo "  - High cost found in IngredientCost (ID: " . $c->id . "): " . $c->unit_cost . "\n";
             // Try to find a conversion
             $conv = IngredientUnitConversion::where('ingredient_id', $ing->id)->first();
             if ($conv && $conv->qty_in_base > 1) {
                 $newCost = $c->unit_cost / $conv->qty_in_base;
                 echo "    - Normalizing to " . $newCost . " using conversion factor " . $conv->qty_in_base . "\n";
                 $c->update(['unit_cost' => $newCost]);
             } else if ($ing->cost > 0) {
                 echo "    - No conversion found. Falling back to Ingredient table cost: " . $ing->cost . "\n";
                 $c->update(['unit_cost' => $ing->cost]);
             }
        }
    }

    // Fix StockMovement table
    $movements = StockMovement::where('ingredient_id', $ing->id)->whereNotNull('unit_cost')->get();
    foreach ($movements as $m) {
         if ($m->unit_cost > $ing->cost * 10) {
             echo "  - High cost found in StockMovement (ID: " . $m->id . "): " . $m->unit_cost . "\n";
              $conv = IngredientUnitConversion::where('ingredient_id', $ing->id)->first();
             if ($conv && $conv->qty_in_base > 1) {
                 $newCost = $m->unit_cost / $conv->qty_in_base;
                 echo "    - Normalizing to " . $newCost . "\n";
                 $m->update(['unit_cost' => $newCost]);
             } else if ($ing->cost > 0) {
                 echo "    - Falling back to Ingredient table cost: " . $ing->cost . "\n";
                 $m->update(['unit_cost' => $ing->cost]);
             }
         }
    }
}

echo "Data Fix Complete.\n";
