<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ingredient;
use App\Models\BranchIngredientStock;
use Illuminate\Support\Facades\DB;

DB::transaction(function() {
    $ingredients = Ingredient::whereIn('unit', ['kg', 'l'])->get();
    foreach($ingredients as $ing) {
        $oldUnit = $ing->unit;
        $newUnit = ($oldUnit === 'kg') ? 'g' : 'ml';
        
        echo "Converting {$ing->name} from {$oldUnit} to {$newUnit}...\n";
        
        // 1. Update Ingredient
        $ing->update([
            'unit' => $newUnit,
            'cost' => $ing->cost / 1000
        ]);
        
        // 2. Update Stocks
        BranchIngredientStock::where('ingredient_id', $ing->id)->get()->each(function($stock) {
            $oldQty = $stock->stock_quantity;
            $newQty = $oldQty * 1000;
            $stock->update(['stock_quantity' => $newQty]);
            echo "  Updated stock: {$oldQty} -> {$newQty}\n";
        });
        
        // 3. Update Unit Conversions (if any exist for kg/l, they are now redundant or need adjustment)
        // Usually, if kg is base, there might be a 'g' conversion. That should be deleted or flipped.
        $ing->unitConversions()->where('unit_name', $newUnit)->delete();
    }
});

echo "STANDARDIZATION COMPLETE\n";
