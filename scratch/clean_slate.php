<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Starting Clean Slate Operation...\n";

$tables = [
    'order_item_options',
    'order_item_modifiers',
    'order_items',
    'orders',
    'financial_ledgers',
    'stock_movements',
];

Schema::disableForeignKeyConstraints();

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        echo "Truncating table: $table\n";
        DB::table($table)->truncate();
    }
}

// Resetting current stock quantities to zero to start fresh if they are using batches
if (Schema::hasTable('stock_batches')) {
    echo "Zeroing out current stock batches...\n";
    DB::table('stock_batches')->update(['current_quantity' => 0]);
}

if (Schema::hasTable('branch_ingredient_stocks')) {
    echo "Zeroing out branch ingredient stocks...\n";
    DB::table('branch_ingredient_stocks')->update(['stock_quantity' => 0]);
}

Schema::enableForeignKeyConstraints();

echo "Clean Slate Operation Successful. Dashboard should now be empty.\n";
