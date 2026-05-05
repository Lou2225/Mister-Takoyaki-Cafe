<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Branch;
use App\Models\BranchIngredientStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\IngredientCost;
use App\Models\Recipe;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\ConfigurationService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Traits\HandlesExports;

class DashboardOverview extends Component
{
    use HandlesExports;

    public $selectedBranchId = null;
    public $isSuperAdmin = false;
    
    // Date filtering
    public $startDate = '';
    public $endDate = '';
    public $activeFilter = 'All Time';
    public $dateError = '';

    // Breakdown Sidebar
    public $showBreakdown = false;
    public $selectedMetric = 'Revenue';
    public $selectedChartMetric = 'Sales'; // Sales, Volume, Profit
    public $breakdownData = [];

    public function setChartMetric($metric)
    {
        $this->selectedChartMetric = $metric;
        $data = $this->getChartData();
        $this->dispatchBrowserEvent('updateSalesChart', $data);
    }

    public function openBreakdown($metric)
    {
        $this->selectedMetric = $metric;
        $this->breakdownData = match($metric) {
            'Revenue' => $this->getRevenueBreakdown(),
            'COGS'    => $this->getCogsBreakdown(),
            'AOV'     => $this->getAovBreakdown(),
            default   => [],
        };
        $this->showBreakdown = true;
        $this->dispatchBrowserEvent('open-modal', ['name' => 'kpi-breakdown']);
    }

    public function closeBreakdown()
    {
        $this->showBreakdown = false;
        $this->dispatchBrowserEvent('close-modal', ['name' => 'kpi-breakdown']);
    }
    private function getRevenueBreakdown(): array
    {
        $branchId = $this->selectedBranchId;
        $start = $this->startDate ? $this->startDate . ' 00:00:00' : null;
        $end = $this->endDate ? $this->endDate . ' 23:59:59' : null;

        return OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->when($start, fn($q) => $q->where('orders.created_at', '>=', $start))
            ->when($end, fn($q) => $q->where('orders.created_at', '<=', $end))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('orders.branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('orders.branch_id', $branchId))
            ->select('product_categories.name as category', DB::raw('SUM(order_items.subtotal) as total'))
            ->groupBy('product_categories.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    private function getCogsBreakdown(): array
    {
        $branchId = $this->selectedBranchId;
        $orderItems = $this->fetchBreakdownOrderItems($branchId);
        
        $standardCosts = $this->fetchChartStandardCosts($branchId);
        $branchCostMap = $standardCosts->pluck('cost_per_base_unit', 'ingredient_id')->union($standardCosts->pluck('unit_cost', 'ingredient_id'));
        $globalCostMap = Ingredient::pluck('cost', 'id');

        $ingredients = Ingredient::pluck('name', 'id');
        $usage = [];
        $productCostMap = [];
        $optionCostMap = [];
        $modifierCostMap = [];

        foreach ($orderItems as $item) {
            $this->calculateItemCogs($item, $branchCostMap, $globalCostMap, $productCostMap, $optionCostMap, $modifierCostMap);
            $this->trackIngredientUsage($item, $branchCostMap, $globalCostMap, $ingredients, $usage);
        }

        arsort($usage);
        return collect($usage)->map(fn($val, $key) => ['name' => $key, 'total' => $val])->values()->take(10)->toArray();
    }

    private function fetchBreakdownOrderItems($branchId)
    {
        $start = $this->startDate ? $this->startDate . ' 00:00:00' : null;
        $end = $this->endDate ? $this->endDate . ' 23:59:59' : null;

        return OrderItem::whereHas('order', function($q) use ($branchId, $start, $end) {
                $q->where('status', Order::STATUS_COMPLETED)
                  ->when($start, fn($query) => $query->where('orders.created_at', '>=', $start))
                  ->when($end, fn($query) => $query->where('orders.created_at', '<=', $end))
                  ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                  ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId));
            })
            ->with(['product.recipes', 'options.option.recipes', 'modifiers.modifier.recipes'])
            ->get();
    }

    private function getAovBreakdown(): array
    {
        $branchId = $this->selectedBranchId;
        $start = $this->startDate ? $this->startDate . ' 00:00:00' : null;
        $end = $this->endDate ? $this->endDate . ' 23:59:59' : null;

        return Order::where('status', Order::STATUS_COMPLETED)
            ->when($start, fn($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn($q) => $q->where('created_at', '<=', $end))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select(DB::raw('CASE 
                WHEN total_amount < 100 THEN "Under ₱100"
                WHEN total_amount BETWEEN 100 AND 300 THEN "₱100 - ₱300"
                WHEN total_amount BETWEEN 301 AND 600 THEN "₱301 - ₱600"
                ELSE "Above ₱600"
            END as bucket'), DB::raw('COUNT(*) as count'))
            ->groupBy('bucket')
            ->get()
            ->toArray();
    }

    public function mount()
    {
        $user = auth()->user();
        $this->isSuperAdmin = $user && $user->role_id === 1;

        if ($this->isSuperAdmin) {
            // Default to Global (All Branches)
            $this->selectedBranchId = null;
        } else {
            $this->selectedBranchId = $user->branch_id;
        }

        // Set default date range to All Time (Empty)
        $this->startDate = '';
        $this->endDate = '';

        $this->updateHeader();
    }

    public function updatedSelectedBranchId($value)
    {
        if ($this->isSuperAdmin) {
            \App\Services\BranchContext::setActiveBranch($value);
        }
        $this->refreshChart();
    }


    public function updatedStartDate()
    {
        $this->validateDateRange();
        if (!$this->dateError) {
            $this->refreshChart();
        }
    }

    public function updatedEndDate()
    {
        $this->validateDateRange();
        if (!$this->dateError) {
            $this->refreshChart();
        }
    }

    private function validateDateRange()
    {
        $this->dateError = '';

        // Validate date formats
        if ($this->startDate && !\DateTime::createFromFormat('Y-m-d', $this->startDate)) {
            $this->dateError = 'Invalid start date format.';
            return;
        }

        if ($this->endDate && !\DateTime::createFromFormat('Y-m-d', $this->endDate)) {
            $this->dateError = 'Invalid end date format.';
            return;
        }

        // Both dates must be provided
        if ($this->startDate && !$this->endDate) {
            $this->dateError = 'Please provide an end date.';
            return;
        }

        if ($this->endDate && !$this->startDate) {
            $this->dateError = 'Please provide a start date.';
            return;
        }

        // If both provided, validate range
        if ($this->startDate && $this->endDate) {
            $start = new \DateTime($this->startDate);
            $end = new \DateTime($this->endDate);
            $today = new \DateTime('today');

            // Start date cannot be in future
            if ($start > $today) {
                $this->dateError = 'Start date cannot be in the future.';
                return;
            }

            // End date cannot be in future
            if ($end > $today) {
                $this->dateError = 'End date cannot be in the future.';
                return;
            }

            // Start date cannot be after end date
            if ($start > $end) {
                $this->dateError = 'Start date cannot be after end date.';
                return;
            }

            // Maximum range validation (e.g., 90 days)
            $diff = $start->diff($end)->days;
            if ($diff > 90) {
                $this->dateError = 'Date range cannot exceed 90 days.';
                return;
            }

            $this->activeFilter = 'Custom Range';
        }
    }

    public function applyQuickDateFilter($range)
    {
        switch ($range) {
            case 'today':
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                $this->activeFilter = 'Today Only';
                break;
            case 'week':
                $this->startDate = now()->subDays(7)->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                $this->activeFilter = 'Last 7 Days';
                break;
            case 'month':
                $this->startDate = now()->subDays(30)->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                $this->activeFilter = 'Last 30 Days';
                break;
            case 'all':
                $this->startDate = '';
                $this->endDate = '';
                $this->activeFilter = 'All Time';
                break;
        }
        $this->dateError = '';
        $this->refreshChart();
    }

    public function refreshChart()
    {
        $chartData = $this->getChartData();
        $this->dispatchBrowserEvent('updateSalesChart', $chartData);
        $this->emit('branchSelectionUpdated');
    }

    public function resetDates()
    {
        $this->applyQuickDateFilter('all');
    }

    private function updateHeader()
    {
        $this->emit('setHeader', [
            'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'title' => 'Network Intelligence',
            'breadcrumbs' => [
                ['label' => 'Operations', 'url' => '#'],
                ['label' => 'Dashboard', 'url' => route('dashboard')],
            ]
        ]);
    }

    public function render()
    {
        $user = auth()->user();
        if (!$user) return redirect('/login');

        $roleName = optional($user->role)->name ?? 'Guest';

        $kpis = $this->getKpis();
        $intel = $this->getFinancialIntelligence();

        // Merge analytical intelligence into KPI array for simpler blade access
        $kpi = array_merge($kpis, $intel);

        return view('livewire.dashboard-overview', [
            'user'            => $user,
            'roleName'        => $roleName,
            'isSuperAdmin'    => $this->isSuperAdmin,
            'kpi'             => $kpi,
            'lowStockAlerts'  => $this->getLowStockAlerts(),
            'bestSellers'     => $this->getBestSellers(),
            'branches'        => $this->getBranches($this->isSuperAdmin),
            'theme'           => $this->getThemeAssets($roleName),
            'chart'           => $this->getChartData(),
            'liveOrders'      => $this->getLiveOrders(),
            'expirationAlerts'=> $this->getExpirationAlerts(),
            'inventoryIntel'  => $this->getInventoryIntelligence(),
            // System configuration data
            'businessConfig'  => ConfigurationService::getBusinessConfig(),
            'financialConfig' => ConfigurationService::getFinancialConfig(),
            'posConfig'       => ConfigurationService::getPosConfig(),
        ])->layout('layouts.app');
    }

    /**
     * Consolidate Financial Intelligence
     * Fetches orders once and calculates Revenue, AOV, COGS, and Profit.
     */
    private function getFinancialIntelligence(): array
    {
        $branchId = $this->selectedBranchId;
        $orders = $this->fetchFinancialOrders($branchId);
        $standardCosts = $this->fetchChartStandardCosts($branchId);
        
        $branchCostMap = $standardCosts->pluck('cost_per_base_unit', 'ingredient_id')->union($standardCosts->pluck('unit_cost', 'ingredient_id'));
        $globalCostMap = Ingredient::pluck('cost', 'id');

        $totalRevenue = $orders->sum('total_amount');
        $orderCount = $orders->count();
        $totalCogs = 0;

        $productCostMap = [];
        $optionCostMap = [];
        $modifierCostMap = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $itemCost = $this->calculateItemCogs($item, $branchCostMap, $globalCostMap, $productCostMap, $optionCostMap, $modifierCostMap);
                $totalCogs += ($itemCost * $item->quantity);
            }
        }

        $aov = $orderCount > 0 ? ($totalRevenue / $orderCount) : 0;
        $grossProfit = $totalRevenue - $totalCogs;
        $margin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

        return [
            'revenue' => $totalRevenue,
            'order_count' => $orderCount,
            'aov' => $aov,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'profit_margin_pct' => round($margin, 2),
        ];
    }

    private function fetchFinancialOrders($branchId)
    {
        $start = $this->startDate ? $this->startDate . ' 00:00:00' : null;
        $end = $this->endDate ? $this->endDate . ' 23:59:59' : null;

        return Order::where('status', Order::STATUS_COMPLETED)
            ->when($start, fn($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn($q) => $q->where('created_at', '<=', $end))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['items.product.recipes', 'items.options.option.recipes', 'items.modifiers.modifier.recipes'])
            ->get();
    }

    private function getKpis(): array
    {
        $branchId = $this->selectedBranchId;

        $activeStaff = User::where('is_active', 1)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->count();
        $totalStaff = User::when($branchId, fn($q) => $q->where('branch_id', $branchId))->count();

        $query = DB::table('ingredients')
            ->leftJoin('branch_ingredient_stocks', function ($join) use ($branchId) {
                $join->on('ingredients.id', '=', 'branch_ingredient_stocks.ingredient_id');
                if (!$this->isSuperAdmin) {
                    $join->where('branch_ingredient_stocks.branch_id', auth()->user()->branch_id);
                } elseif ($branchId) {
                    $join->where('branch_ingredient_stocks.branch_id', $branchId);
                }
            })
            ->whereRaw('COALESCE(branch_ingredient_stocks.stock_quantity, 0) < ingredients.minimum_stock');
        
        $lowStockCount = $query->count();

        return [
            'active_products'   => Product::where('is_active', 1)->count(),
            'total_ingredients' => Ingredient::count(),
            'low_stock_count'   => $lowStockCount,
            'staff_active'      => "{$activeStaff} / {$totalStaff}",
        ];
    }

    private function getFinancialStats(): array
    {
        return $this->getFinancialIntelligence();
    }

    private function getCogsAndProfitStats(): array
    {
        return $this->getFinancialIntelligence();
    }

    private function getLowStockAlerts(): array
    {
        $branchId = $this->selectedBranchId;

        $query = DB::table('ingredients')
            ->select('ingredients.name','ingredients.minimum_stock','ingredients.unit',
                DB::raw('COALESCE(branch_ingredient_stocks.stock_quantity, 0) as current_qty'),
                'branches.branch_name')
            ->leftJoin('branch_ingredient_stocks', 'ingredients.id', '=', 'branch_ingredient_stocks.ingredient_id')
            ->leftJoin('branches', 'branch_ingredient_stocks.branch_id', '=', 'branches.id')
            ->whereRaw('COALESCE(branch_ingredient_stocks.stock_quantity, 0) < ingredients.minimum_stock');

        if (!$this->isSuperAdmin) {
            $query->where('branch_ingredient_stocks.branch_id', auth()->user()->branch_id);
        } elseif ($branchId) {
            $query->where('branch_ingredient_stocks.branch_id', $branchId);
        }

        return $query->orderBy('current_qty', 'asc')->limit(8)->get()->map(fn($row) => [
            'ingredient' => $row->name,
            'branch'     => $row->branch_name ?? 'Global',
            'current'    => number_format($row->current_qty, 2) . ' ' . $row->unit,
            'min'        => number_format($row->minimum_stock, 2) . ' ' . $row->unit,
        ])->toArray();
    }

    private function getBestSellers(): array
    {
        $branchId = $this->selectedBranchId;
        $start = $this->startDate . ' 00:00:00';
        $end = $this->endDate . ' 23:59:59';

        return OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->when($this->startDate, fn($q) => $q->where('orders.created_at', '>=', $this->startDate . ' 00:00:00'))
            ->when($this->endDate, fn($q) => $q->where('orders.created_at', '<=', $this->endDate . ' 23:59:59'))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('products.name', 'product_categories.name as category_name', 'order_items.product_id', 
                     DB::raw('SUM(order_items.quantity) as total_sold'),
                     DB::raw('SUM(order_items.subtotal) as total_revenue'))
            ->groupBy('order_items.product_id', 'products.name', 'product_categories.name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'name'     => $row->name,
                'category' => $row->category_name ?? 'Uncategorized',
                'revenue'  => '₱ ' . number_format($row->total_revenue, 2),
                'sold'     => number_format($row->total_sold) . ' pcs',
            ])->toArray();
    }

    private function getBranches($isSuperAdmin)
    {
        return $isSuperAdmin ? Branch::withCount('staff')->orderBy('branch_name')->get() : collect([]);
    }

    private function getThemeAssets($roleName): array
    {
        return [
            'bannerGradient' => match($roleName) {
                'Super Admin' => 'from-gray-800 to-gray-950',
                'Admin'       => 'from-red-700 to-red-950',
                default       => 'from-emerald-700 to-emerald-950',
            },
            'badgeBg' => match($roleName) {
                'Super Admin' => 'bg-gray-600/60 text-gray-100',
                'Admin'       => 'bg-red-600/60 text-red-100',
                default       => 'bg-emerald-600/60 text-emerald-100',
            }
        ];
    }

    private function getChartData(): array
    {
        $branchId = $this->selectedBranchId;
        [$start, $end] = $this->prepareChartDateRange($branchId);
        
        $orders = $this->fetchChartOrders($start, $end, $branchId);
        $standardCosts = $this->fetchChartStandardCosts($branchId);
        
        $branchCostMap = $standardCosts->pluck('cost_per_base_unit', 'ingredient_id')->union($standardCosts->pluck('unit_cost', 'ingredient_id'));
        $globalCostMap = Ingredient::pluck('cost', 'id');

        $dailyData = $this->aggregateDailyChartData($orders, $branchCostMap, $globalCostMap);
        
        return $this->formatChartOutput($start, $end, $dailyData, $orders);
    }

    private function prepareChartDateRange($branchId): array
    {
        if (!$this->startDate) {
            $earliestOrder = Order::query()
                ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
                ->orderBy('created_at', 'asc')
                ->first();
            $start = $earliestOrder ? Carbon::parse($earliestOrder->created_at)->startOfDay() : now()->subDays(30);
        } else {
            $start = Carbon::parse($this->startDate)->startOfDay();
        }
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : now()->endOfDay();
        
        return [$start, $end];
    }

    private function fetchChartOrders($start, $end, $branchId)
    {
        return Order::where('status', Order::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end])
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['items.product.recipes', 'items.options.option.recipes', 'items.modifiers.modifier.recipes'])
            ->get();
    }

    private function fetchChartStandardCosts($branchId)
    {
        return IngredientCost::query()
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();
    }

    private function aggregateDailyChartData($orders, $branchCostMap, $globalCostMap): array
    {
        $dailyData = [];
        $productCostMap = [];
        $optionCostMap = [];
        $modifierCostMap = [];

        foreach ($orders as $order) {
            $date = Carbon::parse($order->created_at)->toDateString();
            if (!isset($dailyData[$date])) $dailyData[$date] = ['sales' => 0, 'volume' => 0, 'profit' => 0];
            
            $dailyData[$date]['sales'] += $order->total_amount;
            $dailyData[$date]['volume'] += 1;

            $orderCogs = 0;
            foreach ($order->items as $item) {
                $itemCost = $this->calculateItemCogs($item, $branchCostMap, $globalCostMap, $productCostMap, $optionCostMap, $modifierCostMap);
                $orderCogs += ($itemCost * $item->quantity);
            }
            $dailyData[$date]['profit'] += ($order->total_amount - $orderCogs);
        }
        
        return $dailyData;
    }

    private function formatChartOutput($start, $end, $dailyData, $orders): array
    {
        $categories = [];
        $series = [];
        $curr = clone $start;
        while ($curr <= $end) {
            $dateStr = $curr->toDateString();
            $categories[] = $curr->format('M d');
            
            $val = 0;
            if (isset($dailyData[$dateStr])) {
                $val = match($this->selectedChartMetric) {
                    'Volume' => $dailyData[$dateStr]['volume'],
                    'Profit' => round($dailyData[$dateStr]['profit'], 2),
                    default  => round($dailyData[$dateStr]['sales'], 2),
                };
            }
            $series[] = $val;
            $curr->addDay();
        }

        // Forecast
        $totalVal = array_sum($series);
        $avgVal = ($totalVal > 0 && count($series) > 0) ? $totalVal / count($series) : 0;
        $forecast = array_map(fn($v) => $v > 0 ? $v : round($avgVal * 0.8, 2), $series);

        // Payment Distribution
        $paymentRaw = $orders->groupBy('payment_method')->map->count();
        $labels = ['Cash', 'GCash', 'Card'];
        $paymentSeries = [
            (int)($paymentRaw['Cash'] ?? 0),
            (int)($paymentRaw['GCash'] ?? 0),
            (int)($paymentRaw['Card'] ?? 0),
        ];

        return [
            'categories' => $categories,
            'history'    => $series,
            'forecast'   => $forecast,
            'payment'    => [
                'series' => $paymentSeries,
                'labels' => $labels
            ],
            'metric' => $this->selectedChartMetric
        ];
    }

    /**
     * Get real recent orders feed
     */
    private function getLiveOrders(): array
    {
        $branchId = $this->selectedBranchId;

        return Order::orderByDesc('created_at')
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'id' => "#" . $o->reference_no,
                'time' => $o->created_at->diffForHumans(),
                'status' => $o->status,
                'amount' => '₱' . number_format($o->total_amount, 2)
            ])->toArray();
    }

    /**
     * Enterprise Intelligence: Get expiring soon batches (7-day window)
     */
    private function getExpirationAlerts(): array
    {
        $branchId = $this->selectedBranchId;
        $threshold = now()->addDays(7);

        return StockBatch::with('ingredient', 'branch')
            ->where('current_quantity', '>', 0)
            ->where('expiry_date', '<=', $threshold) // Include already expired
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(fn($b) => [
                'ingredient' => $b->ingredient->name ?? 'Unknown',
                'branch'     => $b->branch->branch_name ?? 'Global',
                'quantity'   => number_format($b->current_quantity, 2) . ' ' . ($b->ingredient->unit ?? ''),
                'days_left'  => (int) now()->startOfDay()->diffInDays(Carbon::parse($b->expiry_date)->startOfDay(), false),
                'expiry'     => Carbon::parse($b->expiry_date)->format('M d, Y')
            ])->toArray();
    }

    /**
     * Enterprise Intelligence: Velocity and Waste Variance
     */
    private function getInventoryIntelligence(): array
    {
        $branchId = $this->selectedBranchId;
        
        // Determine the time window for daily average calculation
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);
            $days = max(1, $start->diffInDays($end));
        } else {
            // All Time: find the earliest order movement to get a realistic daily average
            $earliest = StockMovement::where('type', 'order')
                ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
                ->min('created_at');
            
            $start = $earliest ? Carbon::parse($earliest) : now()->subDays(30);
            $end = now();
            $days = max(1, $start->diffInDays($end));
        }

        // 1. Consumption Velocity (Top 5 fastest moving ingredients)
        $velocity = StockMovement::where('type', 'order') // 'order' represents sales deduction
            ->when($this->startDate, fn($q) => $q->where('created_at', '>=', $this->startDate . ' 00:00:00'))
            ->when($this->endDate, fn($q) => $q->where('created_at', '<=', $this->endDate . ' 23:59:59'))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->select('ingredient_id', DB::raw('SUM(ABS(quantity)) as total_consumed'))
            ->groupBy('ingredient_id')
            ->orderByDesc('total_consumed')
            ->limit(5)
            ->with('ingredient')
            ->get()
            ->map(fn($m) => [
                'name'     => $m->ingredient->name ?? 'Unknown',
                'daily'    => round($m->total_consumed / $days) . ' ' . ($m->ingredient->unit ?? ''),
                'total'    => number_format($m->total_consumed, 0),
                'progress' => min(100, ($m->total_consumed / ($m->ingredient->minimum_stock ?: 1)) * 10) // Visualization proxy
            ])->toArray();

        // 2. Waste Variance: Comparison between physical adjustments and sales deductions
        $salesDeduction = StockMovement::where('type', 'order')
            ->when($this->startDate, fn($q) => $q->where('created_at', '>=', $this->startDate . ' 00:00:00'))
            ->when($this->endDate, fn($q) => $q->where('created_at', '<=', $this->endDate . ' 23:59:59'))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum(DB::raw('ABS(quantity)'));

        $wasteDeduction = StockMovement::whereIn('type', ['waste', 'out', 'adjust'])
            ->when($this->startDate, fn($q) => $q->where('created_at', '>=', $this->startDate . ' 00:00:00'))
            ->when($this->endDate, fn($q) => $q->where('created_at', '<=', $this->endDate . ' 23:59:59'))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->sum(DB::raw('ABS(quantity)'));

        $variancePct = $salesDeduction > 0 ? ($wasteDeduction / $salesDeduction) * 100 : 0;

        return [
            'velocity' => $velocity,
            'variance_pct' => round($variancePct, 2),
            'waste_qty' => number_format($wasteDeduction, 2),
            'health_score' => round(max(0, 100 - $variancePct), 2)
        ];
    }
    private function calculateItemCogs($item, $branchCostMap, $globalCostMap, &$productMap, &$optionMap, &$modifierMap): float
    {
        $itemCost = 0;

        // Base Product
        if ($item->product_id) {
            if (!isset($productMap[$item->product_id])) {
                $cost = 0;
                if ($item->product) {
                    foreach ($item->product->recipes->where('product_option_id', null)->where('modifier_id', null) as $r) {
                        $cost += ($r->quantity * ($branchCostMap[$r->ingredient_id] ?? $globalCostMap[$r->ingredient_id] ?? 0));
                    }
                }
                $productMap[$item->product_id] = (float)$cost;
            }
            $itemCost += $productMap[$item->product_id];
        }

        // Options
        foreach ($item->options as $o) {
            if ($o->product_option_id) {
                if (!isset($optionMap[$o->product_option_id])) {
                    $cost = 0;
                    if ($o->option) {
                        foreach ($o->option->recipes as $r) {
                            $cost += ($r->quantity * ($branchCostMap[$r->ingredient_id] ?? $globalCostMap[$r->ingredient_id] ?? 0));
                        }
                    }
                    $optionMap[$o->product_option_id] = (float)$cost;
                }
                $itemCost += $optionMap[$o->product_option_id];
            }
        }

        // Modifiers
        foreach ($item->modifiers as $m) {
            if ($m->modifier_id) {
                if (!isset($modifierMap[$m->modifier_id])) {
                    $cost = 0;
                    if ($m->modifier) {
                        foreach ($m->modifier->recipes as $r) {
                            $cost += ($r->quantity * ($branchCostMap[$r->ingredient_id] ?? $globalCostMap[$r->ingredient_id] ?? 0));
                        }
                    }
                    $modifierMap[$m->modifier_id] = (float)$cost;
                }
                $itemCost += $modifierMap[$m->modifier_id];
            }
        }

        return (float)$itemCost;
    }

    private function trackIngredientUsage($item, $branchCostMap, $globalCostMap, $ingredients, &$usage)
    {
        $recipes = collect();
        if ($item->product) $recipes = $recipes->concat($item->product->recipes->where('product_option_id', null)->where('modifier_id', null));
        foreach ($item->options as $o) if ($o->option) $recipes = $recipes->concat($o->option->recipes);
        foreach ($item->modifiers as $m) if ($m->modifier) $recipes = $recipes->concat($m->modifier->recipes);

        foreach ($recipes as $r) {
            $unitPrice = $branchCostMap[$r->ingredient_id] ?? $globalCostMap[$r->ingredient_id] ?? 0;
            $name = $ingredients[$r->ingredient_id] ?? 'Unknown';
            $usage[$name] = ($usage[$name] ?? 0) + ($r->quantity * $unitPrice * $item->quantity);
        }
    }

    // ── Reports ───────────────────────────────────────────────────
    public function exportPdf()
    {
        $data = $this->getExportData();
        return $this->generatePdfReport('reports.template', $data, 'Dashboard_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv()
    {
        $data = $this->getExportData();
        $flatData = [];
        
        // Flatten KPIs for CSV
        foreach ($data['kpis'] as $label => $value) {
            $flatData[] = ['Metric' => $label, 'Value' => $value];
        }

        return $this->generateCsvReport('Dashboard_KPIs_' . now()->format('Y-m-d') . '.csv', $flatData);
    }

    public function exportExcel()
    {
        return $this->exportCsv(); // Reusing CSV as Excel-compatible
    }

    private function getExportData(): array
    {
        $kpi        = array_merge($this->getKpis(), $this->getFinancialIntelligence());
        $branch     = Branch::find($this->selectedBranchId)?->branch_name ?? 'Global';
        $bestSellers = $this->getBestSellers();
        $lowStock    = $this->getLowStockAlerts();
        $expiry      = $this->getExpirationAlerts();
        $invIntel    = $this->getInventoryIntelligence();

        return [
            'reportType'  => 'dashboard',
            'title'       => 'Dashboard Executive Report',
            'subtitle'    => 'Operational & Financial Intelligence Summary',
            'period'      => $this->activeFilter,
            'branch'      => $branch,
            'generatedAt' => now()->format('F d, Y h:i A'),
            'kpis'        => [
                'Gross Revenue'    => 'PHP ' . number_format($kpi['revenue'], 2),
                'Avg. Order Value' => 'PHP ' . number_format($kpi['aov'], 2),
                'Total COGS'       => 'PHP ' . number_format($kpi['total_cogs'], 2),
                'Net Profit'       => 'PHP ' . number_format($kpi['gross_profit'], 2),
                'Profit Margin'    => number_format($kpi['profit_margin_pct'], 2) . '%',
                'Completed Orders' => number_format($kpi['order_count']),
                'Active Products'  => number_format($kpi['active_products']),
                'Total Ingredients'=> number_format($kpi['total_ingredients']),
                'Low Stock Items'  => number_format($kpi['low_stock_count']),
                'Staff Ratio'      => $kpi['staff_active'],
            ],
            'sections' => [
                [
                    'title'   => 'Top-Selling Products',
                    'headers' => ['Product', 'Category', 'Revenue', 'Units Sold'],
                    'rows'    => array_map(fn($r) => [$r['name'], $r['category'], $r['revenue'], $r['sold']], $bestSellers),
                ],
                [
                    'title'   => 'Low Stock Alerts',
                    'headers' => ['Ingredient', 'Branch', 'Current Stock', 'Minimum'],
                    'rows'    => array_map(fn($r) => [$r['ingredient'], $r['branch'], $r['current'], $r['min']], $lowStock),
                    'empty'   => 'All stock levels are healthy.',
                ],
                [
                    'title'   => 'Expiration Alerts (Within 7 Days)',
                    'headers' => ['Ingredient', 'Branch', 'Quantity', 'Expiry Date', 'Days Left'],
                    'rows'    => array_map(fn($r) => [
                        $r['ingredient'],
                        $r['branch'],
                        $r['quantity'],
                        $r['expiry'],
                        $r['days_left'] <= 0 ? 'EXPIRED' : $r['days_left'] . ' days',
                    ], $expiry),
                    'empty'   => 'No expiring batches found.',
                ],
                [
                    'title'   => 'Consumption Velocity (Top Ingredients)',
                    'headers' => ['Ingredient', 'Daily Average', 'Total Consumed'],
                    'rows'    => array_map(fn($r) => [$r['name'], $r['daily'], $r['total']], $invIntel['velocity'] ?? []),
                    'empty'   => 'No movement data available.',
                ],
            ],
        ];
    }
}
