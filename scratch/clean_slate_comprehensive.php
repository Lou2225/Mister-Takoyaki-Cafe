<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Starting Comprehensive Clean Slate Operation...\n";

// Tables to truncate
$transactionTables = [
    'orders',
    'order_items',
    'order_item_options',
    'order_item_modifiers',
    'stock_movements',
    'financial_ledgers', // Verified via Model name, but let's be safe
];

// Check for all financial related tables
$allTables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
foreach ($allTables as $table) {
    if (strpos($table, 'ledger') !== false || strpos($table, 'transaction') !== false) {
        $transactionTables[] = $table;
    }
}

$transactionTables = array_unique($transactionTables);

Schema::disableForeignKeyConstraints();

foreach ($transactionTables as $table) {
    if (Schema::hasTable($table)) {
        echo "Truncating table: $table\n";
        DB::table($table)->truncate();
    }
}

// Resetting current stock quantities to zero
if (Schema::hasTable('stock_batches')) {
    echo "Zeroing out current stock batches...\n";
    DB::table('stock_batches')->update(['current_quantity' => 0]);
}

if (Schema::hasTable('branch_ingredient_stocks')) {
    echo "Zeroing out branch ingredient stocks...\n";
    DB::table('branch_ingredient_stocks')->update(['stock_quantity' => 0]);
}

Schema::enableForeignKeyConstraints();

echo "Clean Slate Operation Successful. All transaction and test stock data removed.\n";
