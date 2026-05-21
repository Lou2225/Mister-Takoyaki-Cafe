<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Branch;
use App\Models\IngredientCost;
use App\Models\Ingredient;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

    public bool $showBreakdown = false;
    public string $selectedMetric = 'Gross Revenue';
    public array $breakdownData = [];

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
            $this->startDate = Carbon::parse(
                Order::when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId))
                    ->min('created_at')
                ?? now()
            )->format('Y-m-d');
            $this->activeFilter = 'All Time';
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
        
        // Default all roles to their own branch; Super Admins default to their branch or first branch
        if ($user->role_id !== 1) {
            $this->selectedBranchId = (string) $user->branch_id;
        } else {
            // Super Admin: default to their own branch if set, otherwise first branch
            if (!$this->selectedBranchId || $this->selectedBranchId === 'all') {
                $this->selectedBranchId = $user->branch_id
                    ? (string) $user->branch_id
                    : (string) (Branch::orderBy('id')->value('id') ?? 'all');
            }
        }

        $this->updateHeader();
    }

    public function applyQuickDateFilter(?string $filter = null)
    {
        if (!$filter) {
            $this->resetPage();
            return;
        }

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
                $this->startDate = Carbon::parse(
                    Order::when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', $this->selectedBranchId))
                        ->min('created_at')
                    ?? now()
                )->format('Y-m-d');
                $this->endDate = Carbon::now()->format('Y-m-d');
                $this->activeFilter = 'All Time';
                break;
        }
        $this->resetPage();
    }

    public function redirectToOrdering(int $ingredientId)
    {
        $mainBranchId = Branch::where('is_main', true)->first()?->id;

        if ($this->selectedBranchId === 'all') {
            return;
        }

        if ((string)$this->selectedBranchId === (string)$mainBranchId) {
            return $this->redirectRoute('stock.adjustment', ['id' => $ingredientId], navigate: true);
        }

        return $this->redirectRoute('stock.orders', ['ingredient' => $ingredientId], navigate: true);
    }

    public function updated(string $propertyName)
    {
        if (in_array($propertyName, ['selectedBranchId', 'search', 'perPage', 'startDate', 'endDate', 'seasonalityMode'])) {
            $this->resetPage();
            if ($propertyName === 'perPage') {
                $this->resetPage('branchPage');
            }
        }
    }

    public function render()
    {
        $analytics = $this->getAnalytics();
        $isActionable = $this->selectedBranchId !== 'all';
        
        $trendData = $analytics['sales_trend'] ?? ['categories' => [], 'gross' => [], 'net_sales' => []];
        
        // Dispatch browser event after Livewire renders, with chart payload
        $this->dispatch('refresh-bi-charts', $trendData);
        
        return view('livewire.business-intelligence', [
            'branches'       => Branch::all(),
            'performance'    => $analytics['performance'],
            'productInsights'=> $this->getProductInsights($analytics),
            'operations'     => $this->getOperationalData($analytics),
            'recentOrders'   => $this->getRecentOrders(),
            'salesData'      => $analytics,
            'isActionable'   => $isActionable,
        ])->layout('layouts.app');
    }

    private function getAnalytics(): array
    {
        $start    = Carbon::parse($this->startDate)->startOfDay();
        $end      = Carbon::parse($this->endDate)->endOfDay();
        $branchId = $this->selectedBranchId === 'all' ? null : $this->selectedBranchId;

        // 1. Single eager-loaded query for all orders in range
        $orders = Order::whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with([
                'items.product.recipes.ingredient',
                'items.options.option.recipes.ingredient',
                'items.modifiers.modifier.recipes.ingredient',
                'branch',
            ])
            ->get();

        $completedOrders = $orders->where('status', Order::STATUS_COMPLETED);

        // 2. Sales Metrics
        $totalCollected = $completedOrders->sum('total_amount');
        $totalDiscounts = $completedOrders->sum('discount_amount');
        $deliveryFees   = $completedOrders->sum('delivery_fee');
        $netSales       = $totalCollected - $deliveryFees;
        $grossSales     = $netSales + $totalDiscounts;
        $orderCount     = $completedOrders->count();
        $avgOrderValue  = $orderCount > 0 ? $totalCollected / $orderCount : 0;
        $refunds        = $orders->sum('refunded_amount');

        // 3. Delegate heavy sub-calculations to focused helpers
        $totalCogs   = $this->computeCogs($completedOrders, $branchId);
        
        // 4. Calculate Waste Cost from stock movements
        $wasteCost = StockMovement::where('type', 'waste')
            ->whereBetween('created_at', [$start, $end])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->sum(function($movement) {
                return abs($movement->quantity) * ($movement->unit_cost ?? 0);
            });

        $grossProfit = $netSales - $totalCogs - $wasteCost;

        $breakdown   = $this->buildSalesBreakdown($completedOrders);
        $salesTrend  = $this->buildSalesTrend($completedOrders);

        return [
            'gross_sales'     => $grossSales,
            'net_sales'       => $netSales,
            'total_discounts' => $totalDiscounts,
            'delivery_fees'   => $deliveryFees,
            'total_collected' => $totalCollected,
            'order_count'     => $orderCount,
            'avg_order_value' => $avgOrderValue,
            'refunds'         => $refunds,
            'total_cogs'      => $totalCogs,
            'waste_cost'      => $wasteCost,
            'gross_profit'    => $grossProfit,
            'performance' => [
                'gross_sales'     => $grossSales,
                'net_sales'       => $netSales,
                'order_count'     => $orderCount,
                'avg_order_value' => $avgOrderValue,
                'total_discounts' => $totalDiscounts,
                'delivery_fees'   => $deliveryFees,
                'refunds'         => $refunds,
                'waste_cost'      => $wasteCost,
                'gross_profit'    => $grossProfit,
            ],
            'payment_methods'  => $breakdown['payment_methods'],
            'order_sources'    => $breakdown['order_sources'],
            'top_items'        => $breakdown['top_items'],
            'sales_trend'      => $salesTrend,
            'orders'           => $orders,
            'completed_orders' => $completedOrders,
        ];
    }

    /**
     * Calculate total Cost of Goods Sold for a set of completed orders.
     * Uses a tiered pricing strategy: Branch Standard Cost → Latest Purchase Price → Global Ingredient Cost.
     */
    private function computeCogs(Collection $completedOrders, ?string $branchId): float
    {
        $standardCosts = IngredientCost::when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->pluck('unit_cost', 'ingredient_id'));

        $purchasePrices = StockMovement::where('type', 'in')
            ->whereNotNull('unit_cost')
            ->where('unit_cost', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->unique('ingredient_id')->pluck('unit_cost', 'ingredient_id'));

        $fallback = Ingredient::pluck('cost', 'id');

        $total = 0.0;
        foreach ($completedOrders as $order) {
            $bid = $order->branch_id;
            $resolve = fn($ingId) =>
                $standardCosts[$bid][$ingId] ?? $purchasePrices[$bid][$ingId] ?? $fallback[$ingId] ?? 0;

            foreach ($order->items as $item) {
                $cost = 0.0;
                if ($item->product) {
                    foreach ($item->product->recipes as $r) {
                        $cost += $r->quantity * $resolve($r->ingredient_id);
                    }
                }
                foreach ($item->options as $opt) {
                    if ($opt->option) {
                        foreach ($opt->option->recipes as $r) {
                            $cost += $r->quantity * $resolve($r->ingredient_id);
                        }
                    }
                }
                foreach ($item->modifiers as $mod) {
                    if ($mod->modifier) {
                        foreach ($mod->modifier->recipes as $r) {
                            $cost += $r->quantity * $resolve($r->ingredient_id);
                        }
                    }
                }
                $total += $cost * $item->quantity;
            }
        }

        return $total;
    }

    /**
     * Build payment method breakdown, order source breakdown, and top-selling items
     * from an already-fetched collection of completed orders.
     */
    private function buildSalesBreakdown(Collection $completedOrders): array
    {
        $paymentMethods = $completedOrders->groupBy('payment_method')
            ->map(fn($g, $method) => (object)[
                'payment_method' => $method ?: 'Unknown',
                'count'          => $g->count(),
                'total'          => $g->sum('total_amount'),
            ])->values();

        $orderSources = $completedOrders->groupBy('order_type')
            ->map(fn($g, $source) => (object)[
                'source' => $source ?: 'Unknown',
                'count'  => $g->count(),
                'total'  => $g->sum('total_amount'),
            ])->values();

        $topItems = collect();
        if ($completedOrders->isNotEmpty()) {
            $topItems = OrderItem::with('product')
                ->whereIn('order_id', $completedOrders->pluck('id'))
                ->select('product_id', DB::raw('sum(quantity) as total_quantity'), DB::raw('sum(subtotal) as total_sales'))
                ->groupBy('product_id')
                ->orderByDesc('total_quantity')
                ->take(5)
                ->get();
        }

        return [
            'payment_methods' => $paymentMethods,
            'order_sources'   => $orderSources,
            'top_items'       => $topItems,
        ];

    }

    private function buildSalesTrend(Collection $completedOrders): array
    {
        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate = Carbon::parse($this->endDate)->endOfDay();

        $groupedByDate = $completedOrders->groupBy(fn($order) => $order->created_at->format('Y-m-d'));

        $categories = [];
        $grossRevenue = [];
        $netSales = [];

        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $key = $cursor->format('Y-m-d');
            $ordersForDay = $groupedByDate->get($key, collect());

            $grossRevenue[] = (float) $ordersForDay->sum(fn($order) => $order->total_amount + $order->discount_amount);
            $netSales[]     = (float) $ordersForDay->sum(fn($order) => $order->total_amount - $order->delivery_fee);
            $categories[]   = $cursor->format('M d');

            $cursor->addDay();
        }

        return [
            'categories' => $categories,
            'gross'      => $grossRevenue,
            'net_sales'  => $netSales,
            'has_data'   => $completedOrders->isNotEmpty(),
        ];
    }

    private function getProductInsights(array $analytics): array
    {
        $orderIds = $analytics['completed_orders']->pluck('id');

        $topProducts = collect();
        $categorySales = collect();

        if ($orderIds->isNotEmpty()) {
            $topProducts = OrderItem::with('product')
                ->whereIn('order_id', $orderIds)
                ->select('product_id', DB::raw('SUM(quantity) as units_sold'), DB::raw('SUM(subtotal) as revenue'))
                ->groupBy('product_id')
                ->orderBy('units_sold', 'desc')
                ->take(5)
                ->get();

            $categorySales = OrderItem::whereIn('order_id', $orderIds)
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('product_categories', 'products.category_id', '=', 'product_categories.id')
                ->select('product_categories.name', DB::raw('SUM(order_items.subtotal) as revenue'))
                ->groupBy('product_categories.name')
                ->get();
        }

        return [
            'top_products'  => $topProducts,
            'category_sales'=> $categorySales,
            'seasonality'   => $this->getProductSeasonality(),
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
                $q->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
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
                ->whereIn('orders.status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
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
                $q->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
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
                ->whereIn('orders.status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
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

    private function getOperationalData(array $analytics)
    {
        $completedOrders = $analytics['completed_orders'];
        
        $hourlySales = $completedOrders->groupBy(fn($o) => $o->created_at->format('H'))
            ->map(fn($group, $hour) => (object)[
                'hour'    => (int)$hour,
                'count'   => $group->count(),
                'revenue' => $group->sum('total_amount'),
            ])->values();


        $branchPerformance = [];
        $globalNetworkTotal = 0;

        if (auth()->user()->role_id === 1) {
            $globalNetworkTotal = $completedOrders->sum('total_amount');
            
            $branchPerformance = Order::whereIn('id', $completedOrders->pluck('id'))
                ->selectRaw('branch_id, SUM(total_amount) as revenue, COUNT(*) as count')
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
        $this->dispatch('setHeader', 
            icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
            title: 'Business Intelligence',
            breadcrumbs: [
                ['label' => 'Auditing', 'url' => '#'],
                ['label' => 'Consolidated Ledger', 'url' => route('reports.index')],
            ]
        );
    }


    // ── Reports ───────────────────────────────────────────────────
    public function exportPdf()
    {
        $data = $this->getExportDataForReport();
        return $this->generatePdfReport('reports.template', $data, 'BI_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv()
    {
        $analytics = $this->getAnalytics();
        $data = [
            ['Metric' => 'Gross Sales', 'Value' => number_format($analytics['gross_sales'], 2)],
            ['Metric' => 'Net Sales', 'Value' => number_format($analytics['net_sales'], 2)],
            ['Metric' => 'Order Count', 'Value' => $analytics['order_count']],
            ['Metric' => 'Avg Order Value', 'Value' => number_format($analytics['avg_order_value'], 2)],
            ['Metric' => 'Total Discounts', 'Value' => number_format($analytics['total_discounts'], 2)],
            ['Metric' => 'Refunds', 'Value' => number_format($analytics['refunds'], 2)],
            ['Metric' => 'Gross Profit', 'Value' => number_format($analytics['gross_profit'], 2)],
            ['Metric' => 'Waste Cost (Spoilage)', 'Value' => number_format($analytics['waste_cost'], 2)],
        ];

        return $this->generateCsvReport('BI_Report_' . now()->format('Y-m-d') . '.csv', $data);
    }


    public function exportExcel()
    {
        return $this->exportCsv();
    }

    public function openBreakdown(string $metric): void
    {
        $this->selectedMetric = $metric;
        $analytics = $this->getAnalytics();
        
        $this->breakdownData = match($metric) {
            'Gross Revenue' => $this->getGrossRevenueBreakdown($analytics),
            'Net Sales'     => $this->getNetSalesBreakdown($analytics),
            'Gross Profit'  => $this->getGrossProfitBreakdown($analytics),
            default         => [],
        };
        $this->showBreakdown = true;
        $this->dispatch('open-modal', name: 'kpi-breakdown');
    }

    public function closeBreakdown()
    {
        $this->showBreakdown = false;
        $this->dispatch('close-modal', name: 'kpi-breakdown');
    }

    private function getGrossRevenueBreakdown(array $analytics): array
    {
        $productInsights = $this->getProductInsights($analytics);
        $categorySales = $productInsights['category_sales'] ?? collect();
        
        $breakdown = [];
        foreach ($categorySales as $row) {
            $breakdown[] = [
                'category' => $row->name ?: 'Uncategorized',
                'total'    => (float)$row->revenue,
            ];
        }
        
        usort($breakdown, fn($a, $b) => $b['total'] <=> $a['total']);
        return $breakdown;
    }

    private function getNetSalesBreakdown(array $analytics): array
    {
        $paymentMethods = $analytics['payment_methods'] ?? [];
        $orderSources = $analytics['order_sources'] ?? [];
        
        $breakdown = [];
        foreach ($paymentMethods as $row) {
            $breakdown[] = [
                'category' => 'Payment: ' . ucfirst($row->payment_method),
                'count'    => $row->count,
                'total'    => (float)$row->total,
            ];
        }
        foreach ($orderSources as $row) {
            $breakdown[] = [
                'category' => 'Source: ' . ucfirst($row->source),
                'count'    => $row->count,
                'total'    => (float)$row->total,
            ];
        }
        
        return $breakdown;
    }

    private function getGrossProfitBreakdown(array $analytics): array
    {
        $netSales = $analytics['net_sales'];
        $grossProfit = $analytics['gross_profit'];
        $pct = $netSales > 0 ? round(($grossProfit / $netSales) * 100, 1) : 0.0;
        
        return [
            'net_sales'         => (float)$netSales,
            'total_cogs'        => (float)$analytics['total_cogs'],
            'waste_cost'        => (float)$analytics['waste_cost'],
            'gross_profit'      => (float)$grossProfit,
            'profit_margin_pct' => (float)$pct,
        ];
    }

    private function getExportDataForReport(): array
    {
        $analytics = $this->getAnalytics();
        $branch = $this->selectedBranchId === 'all' ? 'Global Network' : Branch::find($this->selectedBranchId)?->branch_name;

        $trendRows = collect($analytics['trend'])->map(fn($d) => [
            $d['label'],
            'PHP ' . number_format($d['value'], 2),
        ])->toArray();

        $paymentRows = $analytics['completed_orders']->groupBy('payment_method')
            ->map(fn($group, $method) => [
                $method ?: 'N/A',
                number_format($group->count()),
                'PHP ' . number_format($group->sum('total_amount'), 2),
            ])->values()->toArray();

        $sourceRows = $analytics['completed_orders']->groupBy('order_type')
            ->map(fn($group, $source) => [
                ucfirst($source ?: 'Unknown'),
                number_format($group->count()),
                'PHP ' . number_format($group->sum('total_amount'), 2),
            ])->values()->toArray();

        $topItemRows = $analytics['top_items']->map(fn($i) => [
            optional($i->product)->name ?? 'Unknown',
            number_format($i->total_quantity),
            'PHP ' . number_format($i->total_sales, 2),
        ])->toArray();


        return [
            'reportType'  => 'business_intelligence',
            'title'       => 'Business Reports',
            'subtitle'    => 'Consolidated Sales & Financial Analysis',
            'period'      => "{$this->startDate}  to  {$this->endDate}",
            'branch'      => $branch,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'kpis' => [
                'Gross Sales'      => 'PHP ' . number_format($analytics['gross_sales'], 2),
                'Net Sales'        => 'PHP ' . number_format($analytics['net_sales'], 2),
                'Total Collected'  => 'PHP ' . number_format($analytics['total_collected'], 2),
                'Waste Cost'       => 'PHP ' . number_format($analytics['waste_cost'], 2),
                'Gross Profit'     => 'PHP ' . number_format($analytics['gross_profit'], 2),
                'Avg. Order Value' => 'PHP ' . number_format($analytics['avg_order_value'], 2),
                'Order Count'      => number_format($analytics['order_count']),
                'Total Discounts'  => 'PHP ' . number_format($analytics['total_discounts'], 2),
                'Delivery Fees'    => 'PHP ' . number_format($analytics['delivery_fees'], 2),
                'Total Refunds'    => 'PHP ' . number_format($analytics['refunds'], 2),
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
