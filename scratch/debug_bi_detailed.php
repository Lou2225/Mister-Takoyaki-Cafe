<?php
use App\Http\Livewire\BusinessIntelligence;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bi = new BusinessIntelligence();
$bi->startDate = '2020-01-01';
$bi->endDate = '2026-12-31';

$reflection = new ReflectionClass($bi);
$cogsMethod = $reflection->getMethod('getCogsAndProfit');
$cogsMethod->setAccessible(true);

// Re-implement getCogsAndProfit with logging
$branchId = null;
$start = Carbon\Carbon::parse($bi->startDate)->startOfDay();
$end = Carbon\Carbon::parse($bi->endDate)->endOfDay();

$orderItems = OrderItem::whereHas('order', function($q) use ($branchId, $start, $end) {
        $q->where('status', Order::STATUS_COMPLETED)
          ->whereBetween('created_at', [$start, $end]);
    })
    ->with(['product.recipes', 'order'])
    ->get();

echo "Order Items count: " . $orderItems->count() . "\n";

foreach ($orderItems as $item) {
    echo "Item: " . ($item->product->name ?? 'Unknown') . " (Qty: " . $item->quantity . ")\n";
    if ($item->product && $item->product->recipes) {
        foreach ($item->product->recipes as $recipe) {
            $ing = $recipe->ingredient;
            $cost = $ing ? $ing->cost : 0;
            $lineCost = $recipe->quantity * $cost;
            echo "  - Ingredient: " . ($ing->name ?? 'Unknown') . " | Recipe Qty: " . $recipe->quantity . " | Unit Cost: " . $cost . " | Line Cost: " . $lineCost . "\n";
        }
    }
}
