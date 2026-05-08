<?php
use App\Http\Livewire\BusinessIntelligence;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bi = new BusinessIntelligence();
$bi->startDate = '2020-01-01';
$bi->endDate = '2026-12-31';

// We need to call the private method getSalesData
$reflection = new ReflectionClass($bi);
$method = $reflection->getMethod('getSalesData');
$method->setAccessible(true);

$metrics = $method->invoke($bi);

echo "GROSS SALES: " . $metrics['gross_sales'] . "\n";
echo "NET SALES: " . $metrics['net_sales'] . "\n";
echo "GROSS PROFIT: " . $metrics['gross_profit'] . "\n";
echo "REFUNDS: " . $metrics['refunds'] . "\n";

// Debug COGS
$cogsMethod = $reflection->getMethod('getCogsAndProfit');
$cogsMethod->setAccessible(true);
$cogs = $cogsMethod->invoke($bi);
echo "TOTAL COGS: " . $cogs['total_cogs'] . "\n";
