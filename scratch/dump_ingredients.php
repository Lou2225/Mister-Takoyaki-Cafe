<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ingredient;

$ings = Ingredient::all();
foreach ($ings as $i) {
    echo "ID: {$i->id} | Name: {$i->name} | Unit: {$i->unit}\n";
}
