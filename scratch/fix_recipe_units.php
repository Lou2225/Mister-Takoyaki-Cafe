<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

$recipes = Recipe::with('ingredient')->get();
$updatedCount = 0;

echo "Auditing recipes for unit standardization...\n";

DB::transaction(function() use ($recipes, &$updatedCount) {
    foreach ($recipes as $r) {
        $unit = strtolower($r->ingredient->unit);
        $oldQty = $r->quantity;
        
        // If unit is grams or ml and quantity is very small, it's likely a legacy kg/L value
        if (in_array($unit, ['g', 'grams', 'ml', 'milliliters']) && $oldQty < 1.0) {
            $newQty = $oldQty * 1000;
            $r->update(['quantity' => $newQty]);
            echo "Updated {$r->ingredient->name} in product #{$r->product_id}: {$oldQty} -> {$newQty} {$unit}\n";
            $updatedCount++;
        }
    }
});

echo "\nDone! Updated {$updatedCount} recipe entries.\n";
