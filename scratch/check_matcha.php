<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;

$p = Product::where('name', 'Matcha Creme')->first();
echo "Product: {$p->name} (ID: {$p->id})\n";
foreach ($p->recipes as $r) {
    echo "  - {$r->ingredient->name} | Qty: {$r->quantity} | Option ID: " . ($r->product_option_id ?? 'None') . " | Modifier ID: " . ($r->modifier_id ?? 'None') . "\n";
}
