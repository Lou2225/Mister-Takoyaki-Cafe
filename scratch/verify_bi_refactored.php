<?php
use App\Http\Livewire\BusinessIntelligence;

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bi = new BusinessIntelligence();
$bi->startDate = '2020-01-01';
$bi->endDate = '2026-12-31';

$reflection = new ReflectionClass($bi);
$method = $reflection->getMethod('getAnalytics');
$method->setAccessible(true);

$analytics = $method->invoke($bi);

echo "NET SALES: " . $analytics['net_sales'] . "\n";
echo "TOTAL COGS: " . $analytics['total_cogs'] . "\n";
echo "GROSS PROFIT: " . $analytics['gross_profit'] . "\n";
echo "ORDER COUNT: " . $analytics['order_count'] . "\n";
echo "REFUNDS: " . $analytics['refunds'] . "\n";
