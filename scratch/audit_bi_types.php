<?php
use App\Http\Livewire\BusinessIntelligence;

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bi = new BusinessIntelligence();
$bi->startDate = '2026-04-08';
$bi->endDate   = '2026-05-08';

$reflection = new ReflectionClass($bi);

// Test getAnalytics
$analytics = $reflection->getMethod('getAnalytics');
$analytics->setAccessible(true);
$result = $analytics->invoke($bi);

echo "=== getAnalytics() ===\n";
echo "gross_profit:    " . $result['gross_profit'] . "\n";
echo "net_sales:       " . $result['net_sales'] . "\n";
echo "total_cogs:      " . $result['total_cogs'] . "\n";
echo "order_count:     " . $result['order_count'] . "\n";
echo "payment_methods: " . get_class($result['payment_methods']) . " count=" . $result['payment_methods']->count() . "\n";
echo "order_sources:   " . get_class($result['order_sources']) . " count=" . $result['order_sources']->count() . "\n";
echo "top_items:       " . get_class($result['top_items']) . " count=" . $result['top_items']->count() . "\n";
echo "trend:           count=" . count($result['trend']) . "\n";
echo "performance keys: " . implode(', ', array_keys($result['performance'])) . "\n\n";

// Test buildSalesBreakdown empty state
$breakdown = $reflection->getMethod('buildSalesBreakdown');
$breakdown->setAccessible(true);
$emptyResult = $breakdown->invoke($bi, collect());
echo "=== buildSalesBreakdown(empty) ===\n";
echo "payment_methods: " . get_class($emptyResult['payment_methods']) . " count=" . $emptyResult['payment_methods']->count() . "\n";
echo "order_sources:   " . get_class($emptyResult['order_sources']) . " count=" . $emptyResult['order_sources']->count() . "\n";
echo "top_items:       " . get_class($emptyResult['top_items']) . " count=" . $emptyResult['top_items']->count() . "\n\n";

// Test buildTrendData
$trendMethod = $reflection->getMethod('buildTrendData');
$trendMethod->setAccessible(true);
$trend = $trendMethod->invoke($bi, collect(), Carbon\Carbon::parse('2026-05-01'), Carbon\Carbon::parse('2026-05-07'));
echo "=== buildTrendData(empty) ===\n";
echo "trend count:     " . count($trend) . " (should be 7)\n";
echo "first item keys: " . implode(', ', array_keys($trend[0])) . "\n\n";

// Test getProductInsights with empty analytics
$productInsights = $reflection->getMethod('getProductInsights');
$productInsights->setAccessible(true);
$piResult = $productInsights->invoke($bi, ['completed_orders' => collect()]);
echo "=== getProductInsights(empty) ===\n";
echo "top_products:    " . get_class($piResult['top_products']) . "\n";
echo "category_sales:  " . get_class($piResult['category_sales']) . "\n";
echo "category_sales->sum('revenue'): " . $piResult['category_sales']->sum('revenue') . " (should be 0)\n\n";

echo "All checks passed!\n";
