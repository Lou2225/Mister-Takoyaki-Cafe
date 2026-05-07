<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\FinancialLedger;
use App\Models\IngredientCost;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Traits\HandlesExports;

class BusinessIntelligence extends Component
{
    use WithPagination, HandlesExports;

    public string $activeTab = 'performance'; // performance, forecasting, products, operations, sales
    public string $startDate = '';
    public string $endDate = '';
    public string $selectedBranchId = 'all';
    public string $search = '';
    public int $perPage = 5;
    public string $seasonalityMode = 'weekly'; // weekly | monthly
    public string $activeFilter = 'All Time';

    protected $queryString = [
        'activeTab' => ['except' => 'performance'],
        'selectedBranchId' => ['except' => 'all'],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'seasonalityMode' => ['except' => 'weekly'],
        'activeFilter' => ['except' => 'All Time'],
    ];

    public function mount()
    {
        $user = auth()->user();
        
        // RBAC: Only Owners (1) and Managers (2) can access BI
        if ($user->role_id === 3) {
            abort(403, 'Unauthorized access to Business Intelligence.');
        }

        // Set active tab if provided via query string (e.g., /reports?tab=sales)
        $this->activeTab = request()->query('tab', 'performance');

        // Initialize dates if not set via query string
        if (!$this->startDate) {
            $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
        
        if ($user->role_id !== 1) {
            $this->selectedBranchId = $user->branch_id;
        }

        $this->updateHeader();
    }

    public function applyQuickDateFilter(string $filter)
    {
        switch ($filter) {
            case 'today':
                $this->startDate = Carbon::now()->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
                $this->activeFilter = 'Today Only';
                break;
            case 'week':
                $this->startDate = Carbon::now()->subDays(7)->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
                $this->activeFilter = 'Last 7 Days';
                break;
            case 'month':
                $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
                $this->activeFilter = 'Last 30 Days';
                break;
            case 'all':
                $this->startDate = Carbon::parse(Order::min('created_at') ?? now())->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
                $this->activeFilter = 'All Time';
                break;
        }
        $this->resetPage();
    }

    public function redirectToOrdering(int $ingredientId)
    {
        return redirect()->route('stock.orders', ['ingredient' => $ingredientId]);
    }

    public function updated(string $propertyName)
    {
        if (in_array($propertyName, ['startDate', 'endDate', 'selectedBranchId', 'search', 'perPage'])) {
            if (in_array($propertyName, ['startDate', 'endDate'])) {
                $this->activeFilter = 'Custom Range';
            }
            $this->resetPage();
            if ($propertyName === 'perPage') {
                $this->resetPage('branchPage');
            }
        }
    }

    public function render()
    {
        $salesData = $this->getSalesData();
        $mainBranchId = Branch::where('is_main', true)->first()?->id;
        $canOrder = $this->selectedBranchId !== 'all' && $this->selectedBranchId != $mainBranchId;
        
        return view('livewire.business-intelligence', [
            'branches'       => Branch::all(),
            'performance'    => $this->getPerformanceMetrics($salesData),
            'forecasting'    => $this->getForecastingData(),
            'productInsights'=> $this->getProductInsights(),
            'operations'     => $this->getOperationalData(),
            'recentOrders'   => $this->getRecentOrders(),
            'salesData'      => $salesData,
            'canOrder'       => $canOrder,
        ])->layout('layouts.app');
    }

    private function getPerformanceMetrics(array $analytics)
    {
        $cogsData = $this->getCogsAndProfit();

        return [
            'gross_sales' => $analytics['gross_sales'],
            'order_count' => $analytics['order_count'],
            'avg_order_value' => $analytics['avg_order_value'],
            'total_discounts' => $analytics['total_discounts'],
            'delivery_fees' => $analytics['delivery_fees'],
            'tax_collected' => $analytics['tax_collected'],
            'refunds' => $analytics['refunds'],
            'net_sales' => $analytics['net_sales'],
            'gross_profit' => $analytics['gross_profit'],
        ];
    }

    private function getCogsAndProfit(): array
    {
        $branchId = $this->selectedBranchId === 'all' ? null : $this->selectedBranchId;
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // 1. Get all completed items in period with full recipe hierarchy
        $orderItems = OrderItem::whereHas('order', function($q) use ($branchId, $start, $end) {
                $q->where('status', Order::STATUS_COMPLETED)
                  ->whereBetween('created_at', [$start, $end])
                  ->when($branchId, fn($query) => $query->where('branch_id', $branchId));
            })
            ->with([
                'order',
                'product.recipes',
                'options.option.recipes',
                'modifiers.modifier.recipes'
            ])
            ->get();

        // 2. Fetch standard ingredient costs (grouped by branch)
        $standardCosts = IngredientCost::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(fn($group) => $group->pluck('unit_cost', 'ingredient_id'));

        // 3. Fetch latest purchase prices (grouped by branch)
        $latestPurchasePrices = StockMovement::where('type', 'in')
            ->whereNotNull('unit_cost')
            ->where('unit_cost', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('branch_id')
            ->map(fn($group) => $group->unique('ingredient_id')->pluck('unit_cost', 'ingredient_id'));

        // 4. Fetch fallback costs from ingredients table
        $fallbackCosts = Ingredient::pluck('cost', 'id');

        $totalCogs = 0;
        foreach ($orderItems as $item) {
            $itemCost = 0;
            $itemBranchId = $item->order->branch_id;

            $getUnitPrice = function($ingId) use ($itemBranchId, $standardCosts, $latestPurchasePrices, $fallbackCosts) {
                // Priority: Standard Branch Cost -> Latest Branch Purchase -> Global Ingredient Cost -> 0
                return $standardCosts[$itemBranchId][$ingId] 
                    ?? $latestPurchasePrices[$itemBranchId][$ingId] 
                    ?? $fallbackCosts[$ingId] 
                    ?? 0;
            };

            // Base Product Recipe Cost
            if ($item->product && $item->product->recipes) {
                foreach ($item->product->recipes as $recipe) {
                    $itemCost += ($recipe->quantity * $getUnitPrice($recipe->ingredient_id));
                }
            }

            // Options Recipe Cost
            foreach ($item->options as $itemOpt) {
                if ($itemOpt->option && $itemOpt->option->recipes) {
                    foreach ($itemOpt->option->recipes as $recipe) {
                        $itemCost += ($recipe->quantity * $getUnitPrice($recipe->ingredient_id));
                    }
                }
            }

            // Modifiers Recipe Cost
            foreach ($item->modifiers as $itemMod) {
                if ($itemMod->modifier && $itemMod->modifier->recipes) {
                    foreach ($itemMod->modifier->recipes as $recipe) {
                        $itemCost += ($recipe->quantity * $getUnitPrice($recipe->ingredient_id));
                    }
                }
            }

            $totalCogs += ($itemCost * $item->quantity);
        }

        return ['total_cogs' => $totalCogs];
    }

    private function getForecastingData()
    {
        return [
            'short_term' => $this->calculateRegression('daily', 60, 7),
            'long_term'  => $this->calculateRegression('monthly', 12, 6),
            'restock_insights' => $this->getIngredientDemandForecast(),
        ];
    }

    private function calculateRegression(string $type, int $historyCount, int $predictCount)
    {
        $query = Order::where('status', 'Completed')
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId));

        if ($type === 'daily') {
            $data = (clone $query)->where('created_at', '>=', Carbon::now()->subDays($historyCount))
                ->selectRaw('DATE(created_at) as label, SUM(total_amount) as total')
                ->groupBy('label')
                ->orderBy('label', 'asc')
                ->get();
        } else {
            $data = (clone $query)->where('created_at', '>=', Carbon::now()->subMonths($historyCount))
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as label, SUM(total_amount) as total')
                ->groupBy('label')
                ->orderBy('label', 'asc')
                ->get();
        }



        $n = $data->count();
        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        foreach ($data as $index => $row) {
            $x = $index + 1;
            $y = (float)$row->total;
            $sumX += $x; $sumY += $y; $sumXY += ($x * $y); $sumX2 += ($x * $x);
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) return ['forecast' => [], 'trend' => 'Neutral', 'growth_rate' => 0.0, 'confidence' => 'Low'];

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        // Calculate a 'Baseline' (Average of the last 7 active days)
        $avgDailySales = $data->take(-7)->avg('total') ?: 0;

        $forecast = [];
        $startDate = $type === 'daily' ? Carbon::tomorrow() : Carbon::now()->addMonth()->startOfMonth();

        for ($i = 0; $i < $predictCount; $i++) {
            $predictedDate = $type === 'daily' ? $startDate->copy()->addDays($i) : $startDate->copy()->addMonths($i);
            $predictedX = $n + $i + 1;
            
            // Pure Linear Regression result
            $predictedY = ($slope * $predictedX) + $intercept;

            $forecast[] = [
                'date' => $type === 'daily' ? $predictedDate->format('M d') : $predictedDate->format('M Y'),
                'day'  => strtoupper($predictedDate->format('D')),
                'predicted' => max(0, $predictedY),
            ];
        }

        return [
            'forecast' => $forecast,
            'trend' => $slope > ($type === 'daily' ? 50 : 1000) ? 'Upward' : ($slope < ($type === 'daily' ? -50 : -1000) ? 'Downward' : 'Stable'),
            'growth_rate' => round($slope, 2),
            'confidence' => ($type === 'daily' ? ($n >= 45 ? 'High' : ($n >= 20 ? 'Medium' : 'Low')) : ($n >= 8 ? 'High' : ($n >= 4 ? 'Medium' : 'Low'))),
        ];
    }

    private function getIngredientDemandForecast()
    {
        // 1. Get all products with sales in the period for the branch to predict restocking
        $topProducts = OrderItem::whereHas('order', function($q) {
                $q->where('status', 'Completed')
                  ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId))
                  ->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->select('product_id', DB::raw('SUM(quantity) / 30 as daily_avg'))
            ->groupBy('product_id')
            ->orderBy('daily_avg', 'desc')
            ->with('product.recipes.ingredient')
            ->get();

        $ingredientDemand = [];

        foreach ($topProducts as $tp) {
            if (!$tp->product || !$tp->product->recipes) continue;

            // Simple 14-day projection: daily_avg * 14 * (1 + current growth momentum)
            // Use a 1.2x safety buffer
            $projectedUnits = $tp->daily_avg * 14 * 1.2;

            foreach ($tp->product->recipes as $recipe) {
                if (!$recipe->ingredient) continue;
                
                $id = $recipe->ingredient_id;
                if (!isset($ingredientDemand[$id])) {
                    $ingredientDemand[$id] = [
                        'id' => $id,
                        'name' => $recipe->ingredient->name,
                        'unit' => $recipe->ingredient->unit,
                        'amount' => 0,
                        'priority' => 'Medium'
                    ];
                }
                $ingredientDemand[$id]['amount'] += ($recipe->quantity * $projectedUnits);
            }
        }

        // Sort by amount descending and take top 15 "Must Stock" ingredients
        return collect($ingredientDemand)
            ->sortByDesc('amount')
            ->take(15)
            ->map(function($item) {
                // Round to whole number
                $item['amount'] = ceil($item['amount']);
                // Heuristic: If amount is significant, mark as High priority
                if ($item['amount'] > 25) $item['priority'] = 'High';
                return $item;
            })
            ->values()
            ->toArray();
    }

    private function getProductInsights()
    {
        $query = OrderItem::whereHas('order', function($q) {
            $q->where('payment_status', 'Paid')
              ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId))
              ->whereBetween('created_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()]);
        });

        $topProducts = (clone $query)
            ->with('product')
            ->select('product_id', DB::raw('SUM(quantity) as units_sold'), DB::raw('SUM(subtotal) as revenue'))
            ->groupBy('product_id')
            ->orderBy('units_sold', 'desc')
            ->take(5)
            ->get();

        $categorySales = (clone $query)
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select('product_categories.name', DB::raw('SUM(order_items.subtotal) as revenue'))
            ->groupBy('product_categories.name')
            ->get();

        return [
            'top_products' => $topProducts,
            'category_sales' => $categorySales,
            'seasonality' => $this->getProductSeasonality(),
        ];
    }

    private function getProductSeasonality()
    {
        return $this->seasonalityMode === 'monthly'
            ? $this->getProductSeasonalityMonthly()
            : $this->getProductSeasonalityWeekly();
    }

    private function getProductSeasonalityWeekly()
    {
        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate = Carbon::parse($this->endDate)->endOfDay();

        $topProductIds = OrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
                $q->where('payment_status', 'Paid')
                  ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId))
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->take(3)
            ->pluck('product_id');

        if ($topProductIds->isEmpty()) return [];

        $seasonalityData = [];
        foreach ($topProductIds as $pid) {
            $product = \App\Models\Product::find($pid);

            $dowSales = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $pid)
                ->where('orders.payment_status', 'Paid')
                ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('orders.branch_id', $this->selectedBranchId))
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->selectRaw('DAYOFWEEK(orders.created_at) as dow, SUM(order_items.quantity) as total')
                ->groupBy('dow')
                ->pluck('total', 'dow');

            $days = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
            $formattedData = [];
            foreach ($days as $index => $day) {
                $formattedData[] = ['label' => $day, 'value' => (float)($dowSales[$index + 1] ?? 0)];
            }

            $seasonalityData[] = [
                'name' => $product->name,
                'data' => $formattedData,
                'max'  => collect($formattedData)->max('value') ?: 1,
            ];
        }

        return $seasonalityData;
    }

    private function getProductSeasonalityMonthly()
    {
        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate   = Carbon::parse($this->endDate)->endOfDay();

        $topProductIds = OrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
                $q->where('payment_status', 'Paid')
                  ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId))
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->take(3)
            ->pluck('product_id');

        if ($topProductIds->isEmpty()) return [];

        // Build list of months covered by the selected range
        $months = [];
        $cursor = Carbon::parse($startDate)->startOfMonth();
        while ($cursor->lte(Carbon::parse($endDate)->startOfMonth())) {
            $months[] = ['num' => (int)$cursor->format('n'), 'label' => $cursor->format('M Y')];
            $cursor->addMonth();
        }
        if (empty($months)) {
            $months[] = ['num' => (int)Carbon::parse($startDate)->format('n'), 'label' => Carbon::parse($startDate)->format('M Y')];
        }

        $seasonalityData = [];
        foreach ($topProductIds as $pid) {
            $product = \App\Models\Product::find($pid);

            $monthlySales = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $pid)
                ->where('orders.payment_status', 'Paid')
                ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('orders.branch_id', $this->selectedBranchId))
                ->whereBetween('orders.created_at', [$startDate, $endDate])
                ->selectRaw('MONTH(orders.created_at) as month_num, SUM(order_items.quantity) as total')
                ->groupBy('month_num')
                ->pluck('total', 'month_num');

            $formattedData = [];
            foreach ($months as $m) {
                $formattedData[] = ['label' => $m['label'], 'value' => (float)($monthlySales[$m['num']] ?? 0)];
            }

            $seasonalityData[] = [
                'name' => $product->name,
                'data' => $formattedData,
                'max'  => collect($formattedData)->max('value') ?: 1,
            ];
        }

        return $seasonalityData;
    }

    private function getOperationalData()
    {
        $hourlySales = Order::where('payment_status', 'Paid')
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('created_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count, SUM(total_amount) as revenue')
            ->groupBy('hour')
            ->orderBy('hour', 'asc')
            ->get();

        $branchPerformance = [];
        $globalNetworkTotal = 0;

        if (auth()->user()->role_id === 1) {
            $baseQuery = Order::where('payment_status', 'Paid')
                ->whereBetween('created_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()]);

            $globalNetworkTotal = (clone $baseQuery)->sum('total_amount');

            $branchPerformance = $baseQuery->selectRaw('branch_id, SUM(total_amount) as revenue, COUNT(*) as count')
                ->groupBy('branch_id')
                ->with('branch')
                ->paginate($this->perPage, ['*'], 'branchPage');
        }

        return [
            'hourly_sales' => $hourlySales,
            'branch_performance' => $branchPerformance,
            'global_network_total' => $globalNetworkTotal ?: 1,
        ];
    }

    private function getRecentOrders()
    {
        return Order::with(['branch', 'user'])
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereBetween('created_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
            ->where('reference_no', 'like', '%' . $this->search . '%')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    private function updateHeader()
    {
        $this->emit('setHeader', [
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            'title' => 'Business Intelligence',
            'breadcrumbs' => [
                ['label' => 'Auditing', 'url' => '#'],
                ['label' => 'Consolidated Ledger', 'url' => route('reports.index')],
            ]
        ]);
    }

    private function getSalesData(): array
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end   = Carbon::parse($this->endDate)->endOfDay();

        $query = Order::whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
            ->whereBetween('created_at', [$start, $end])
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId));

        $orders = (clone $query)->get();

        $totalCollected  = $orders->where('status', Order::STATUS_COMPLETED)->sum('total_amount');
        $totalDiscounts  = $orders->where('status', Order::STATUS_COMPLETED)->sum('discount_amount');
        $deliveryFees    = $orders->where('status', Order::STATUS_COMPLETED)->sum('delivery_fee');
        $taxCollected    = $orders->where('status', Order::STATUS_COMPLETED)->sum('tax_amount');
        
        // Accurate Peddlr-inspired logic:
        // Net Sales is what we actually earned from products after discounts
        // Gross Sales is what we would have earned without discounts
        $netSales        = $totalCollected - $deliveryFees - $taxCollected;
        $grossSales      = $netSales + $totalDiscounts;
        
        $orderCount      = $orders->where('status', Order::STATUS_COMPLETED)->count();
        $avgOrderValue   = $orderCount > 0 ? $totalCollected / $orderCount : 0;

        // Refunds (from all orders in period including partially/fully refunded)
        $refunds = $orders->sum('refunded_amount');

        // Gross Profit (using COGS)
        $cogsData = $this->getCogsAndProfit();

        // Payment method breakdown (completed only)
        $paymentMethods = (clone $query)->where('status', Order::STATUS_COMPLETED)
            ->select('payment_method', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Order source breakdown (completed only)
        $orderSources = (clone $query)->where('status', Order::STATUS_COMPLETED)
            ->select('order_type as source', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('order_type')
            ->get();

        // Top Selling Items (completed only)
        $orderIds = $orders->where('status', Order::STATUS_COMPLETED)->pluck('id');
        $topItems = [];
        if ($orderIds->isNotEmpty()) {
            $topItems = OrderItem::with('product')
                ->whereIn('order_id', $orderIds)
                ->select('product_id', DB::raw('sum(quantity) as total_quantity'), DB::raw('sum(subtotal) as total_sales'))
                ->groupBy('product_id')
                ->orderByDesc('total_quantity')
                ->take(5)
                ->get();
        }

        // Daily Sales Trend (completed only)
        $dailySales = (clone $query)->where('status', Order::STATUS_COMPLETED)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->mapWithKeys(fn($item) => [$item->date => $item->total]);

        // Fill gaps in dates
        $trendData = [];
        $tempDate = $start->copy();
        while ($tempDate->lte($end)) {
            $dStr = $tempDate->format('Y-m-d');
            $trendData[] = [
                'label' => $tempDate->format('M d'),
                'value' => (float)($dailySales[$dStr] ?? 0)
            ];
            $tempDate->addDay();
        }

        return [
            'gross_sales'      => $grossSales,
            'net_sales'        => $netSales,
            'total_discounts'  => $totalDiscounts,
            'delivery_fees'    => $deliveryFees,
            'tax_collected'    => $taxCollected,
            'total_collected'  => $totalCollected,
            'order_count'      => $orderCount,
            'avg_order_value'  => $avgOrderValue,
            'refunds'          => $refunds,
            'gross_profit'     => $netSales - $cogsData['total_cogs'],
            'payment_methods'  => $paymentMethods,
            'order_sources'    => $orderSources,
            'top_items'        => $topItems,
            'trend'            => $trendData,
        ];
    }
    // ── Reports ───────────────────────────────────────────────────
    public function exportPdf()
    {
        $data = $this->getExportDataForReport();
        return $this->generatePdfReport('reports.template', $data, 'BI_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv()
    {
        $sales = $this->getSalesData();
        $data = [
            ['Metric' => 'Gross Sales', 'Value' => number_format($sales['gross_sales'], 2)],
            ['Metric' => 'Net Sales', 'Value' => number_format($sales['net_sales'], 2)],
            ['Metric' => 'Order Count', 'Value' => $sales['order_count']],
            ['Metric' => 'Avg Order Value', 'Value' => number_format($sales['avg_order_value'], 2)],
            ['Metric' => 'Total Discounts', 'Value' => number_format($sales['total_discounts'], 2)],
            ['Metric' => 'Refunds', 'Value' => number_format($sales['refunds'], 2)],
            ['Metric' => 'Gross Profit', 'Value' => number_format($sales['gross_profit'], 2)],
        ];

        return $this->generateCsvReport('BI_Report_' . now()->format('Y-m-d') . '.csv', $data);
    }

    public function exportExcel()
    {
        return $this->exportCsv();
    }

    private function getExportDataForReport(): array
    {
        $sales  = $this->getSalesData();
        $branch = $this->selectedBranchId === 'all' ? 'Global Network' : Branch::find($this->selectedBranchId)?->branch_name;

        $trendRows = collect($sales['trend'])->map(fn($d) => [
            $d['label'],
            'PHP ' . number_format($d['value'], 2),
        ])->toArray();

        $paymentRows = $sales['payment_methods']->map(fn($p) => [
            $p->payment_method ?: 'N/A',
            number_format($p->count),
            'PHP ' . number_format($p->total, 2),
        ])->toArray();

        $sourceRows = $sales['order_sources']->map(fn($s) => [
            ucfirst($s->source ?: 'Unknown'),
            number_format($s->count),
            'PHP ' . number_format($s->total, 2),
        ])->toArray();

        $topItemRows = $sales['top_items']->map(fn($i) => [
            optional($i->product)->name ?? 'Unknown',
            number_format($i->total_quantity),
            'PHP ' . number_format($i->total_sales, 2),
        ])->toArray();

        return [
            'reportType'  => 'business_intelligence',
            'title'       => 'Business Intelligence Report',
            'subtitle'    => 'Consolidated Sales & Financial Analysis',
            'period'      => "{$this->startDate}  to  {$this->endDate}",
            'branch'      => $branch,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'kpis' => [
                'Gross Sales'      => 'PHP ' . number_format($sales['gross_sales'], 2),
                'Net Sales'        => 'PHP ' . number_format($sales['net_sales'], 2),
                'Total Collected'  => 'PHP ' . number_format($sales['total_collected'], 2),
                'Gross Profit'     => 'PHP ' . number_format($sales['gross_profit'], 2),
                'Avg. Order Value' => 'PHP ' . number_format($sales['avg_order_value'], 2),
                'Order Count'      => number_format($sales['order_count']),
                'Total Discounts'  => 'PHP ' . number_format($sales['total_discounts'], 2),
                'Delivery Fees'    => 'PHP ' . number_format($sales['delivery_fees'], 2),
                'Tax Collected'    => 'PHP ' . number_format($sales['tax_collected'], 2),
                'Total Refunds'    => 'PHP ' . number_format($sales['refunds'], 2),
            ],
            'sections' => [
                [
                    'title'   => 'Daily Revenue Trend',
                    'headers' => ['Date', 'Revenue'],
                    'rows'    => $trendRows,
                    'empty'   => 'No sales data for this period.',
                ],
                [
                    'title'   => 'Top-Selling Items',
                    'headers' => ['Product', 'Units Sold', 'Total Revenue'],
                    'rows'    => $topItemRows,
                    'empty'   => 'No items sold in this period.',
                ],
                [
                    'title'   => 'Payment Method Breakdown',
                    'headers' => ['Method', 'Transactions', 'Total Amount'],
                    'rows'    => $paymentRows,
                    'empty'   => 'No payment data.',
                ],
                [
                    'title'   => 'Order Source Breakdown',
                    'headers' => ['Source', 'Orders', 'Total Amount'],
                    'rows'    => $sourceRows,
                    'empty'   => 'No order source data.',
                ],
            ],
        ];
    }
}
