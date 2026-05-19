<?php

namespace App\Livewire;

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
use Illuminate\Support\Collection;
use Carbon\Carbon;

use App\Traits\HandlesExports;

class DashboardOverview extends Component
{
    use HandlesExports;

    public ?int $selectedBranchId = null;
    public bool $isSuperAdmin = false;
    
    // Date filtering
    public string $startDate = '';
    public string $endDate = '';
    public string $activeFilter = 'All Time';
    public string $dateError = '';

    // Breakdown Sidebar
    public $showBreakdown = false;
    public $selectedMetric = 'Revenue';
    public $selectedChartMetric = 'Sales'; // Sales, Volume, Profit
    public $breakdownData = [];
    public $stockTab = 'deficiency';


    public function setChartMetric(string $metric): void
    {
        $this->selectedChartMetric = $metric;
        $this->refreshChart();
    }

    public function openBreakdown(string $metric): void
    {
        $this->selectedMetric = $metric;
        $this->breakdownData = match($metric) {
            'Revenue' => $this->getRevenueBreakdown(),
            'COGS'    => $this->getCogsBreakdown(),
            'AOV'     => $this->getAovBreakdown(),
            'Profit'  => $this->getProfitBreakdown(),
            'Margin'  => $this->getMarginBreakdown(),
            default   => [],
        };
        $this->showBreakdown = true;
        $this->dispatch('open-modal', name: 'kpi-breakdown');
    }

    public function closeBreakdown()
    {
        $this->showBreakdown = false;
        $this->dispatch('close-modal', name: 'kpi-breakdown');
    }
    private function getRevenueBreakdown(): array
    {
        $branchId = $this->selectedBranchId;
        $orders = $this->fetchFinancialOrders($branchId);
        
        $categories = [];
        $untrackedRevenue = 0;
        
        $service = $orders->sum('service_charge');
        $delivery = $orders->sum('delivery_fee');
        $discounts = $orders->sum('discount_amount');

        foreach ($orders as $order) {
            if ($order->items->count() > 0) {
                foreach ($order->items as $item) {
                    $categoryName = $item->product->category->name ?? 'Uncategorized';
                    $categories[$categoryName] = ($categories[$categoryName] ?? 0) + $item->subtotal;
                }
            } else {
                // If order has no items but has a total, track it as untracked
                $untrackedRevenue += $order->total_amount;
            }
        }

        $breakdown = collect($categories)->map(fn($val, $key) => ['category' => $key, 'total' => (float)$val])->values()->toArray();
        
        if ($service > 0) $breakdown[] = ['category' => 'Service Charges', 'total' => (float)$service];
        if ($delivery > 0) $breakdown[] = ['category' => 'Delivery Deductions', 'total' => (float)-$delivery];
        if ($discounts > 0) $breakdown[] = ['category' => 'Discounts Applied', 'total' => (float)-$discounts];
        if ($untrackedRevenue > 0) $breakdown[] = ['category' => 'Untracked Sales', 'total' => (float)$untrackedRevenue];

        usort($breakdown, fn($a, $b) => abs($b['total']) <=> abs($a['total']));
        return $breakdown;
    }

    private function getCogsBreakdown(): array
    {
        $branchId = $this->selectedBranchId;
        $orderItems = $this->fetchBreakdownOrderItems($branchId);
        
        $branchCosts = IngredientCost::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->pluck('unit_cost', 'ingredient_id'));

        $purchaseCosts = $this->fetchPurchasePrices($branchId);
        $globalCosts = Ingredient::pluck('cost', 'id');

        $ingredients = Ingredient::pluck('name', 'id');
        $usage = [];
        $categoryCogs = [];

        foreach ($orderItems as $item) {
            $bid = $item->order->branch_id;
            $productCostMap = []; $optionCostMap = []; $modifierCostMap = [];
            $itemCost = $this->calculateItemCogs($item, $branchCosts, $globalCosts, $purchaseCosts, $productCostMap, $optionCostMap, $modifierCostMap, $bid);
            $totalItemCogs = $itemCost * $item->quantity;

            // Group by category for visual consistency
            $categoryName = $item->product->category->name ?? 'Uncategorized';
            $categoryCogs[$categoryName] = ($categoryCogs[$categoryName] ?? 0) + $totalItemCogs;

            $this->trackIngredientUsage($item, $branchCosts, $globalCosts, $purchaseCosts, $ingredients, $usage, $bid);
        }

        arsort($categoryCogs);
        return collect($categoryCogs)->map(fn($val, $key) => ['category' => $key, 'total' => $val])->values()->toArray();
    }

    private function fetchBreakdownOrderItems(?int $branchId): Collection
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
                WHEN total_amount < 200 THEN "Light Snack (Under ₱200)"
                WHEN total_amount BETWEEN 200 AND 500 THEN "Standard Meal (₱200 - ₱500)"
                WHEN total_amount BETWEEN 501 AND 1000 THEN "Family Pack (₱501 - ₱1000)"
                ELSE "Party/Bulk (Above ₱1000)"
            END as bucket'), DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('bucket')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    private function getProfitBreakdown(): array
    {
        $branchId = $this->selectedBranchId;
        $orders = $this->fetchFinancialOrders($branchId);
        
        $branchCosts = IngredientCost::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->pluck('unit_cost', 'ingredient_id'));

        $purchaseCosts = $this->fetchPurchasePrices($branchId);
        $globalCosts = Ingredient::pluck('cost', 'id');

        $categoryProfit = [];
        $untrackedProfit = 0;

        foreach ($orders as $order) {
            $bid = $order->branch_id;
            if ($order->items->count() > 0) {
                foreach ($order->items as $item) {
                    $productCostMap = []; $optionCostMap = []; $modifierCostMap = [];
                    $itemCost = $this->calculateItemCogs($item, $branchCosts, $globalCosts, $purchaseCosts, $productCostMap, $optionCostMap, $modifierCostMap, $bid);
                    $itemProfit = ($item->price - $itemCost) * $item->quantity;

                    $categoryName = $item->product->category->name ?? 'Uncategorized';
                    $categoryProfit[$categoryName] = ($categoryProfit[$categoryName] ?? 0) + $itemProfit;
                }
            } else {
                $untrackedProfit += $order->total_amount;
            }
        }

        // Adjust profit for discounts (Discounts reduce profit directly)
        $discounts = $orders->sum('discount_amount');
        if ($discounts > 0) {
            $categoryProfit['Discounts Impact'] = ($categoryProfit['Discounts Impact'] ?? 0) - $discounts;
        }

        $breakdown = collect($categoryProfit)->map(fn($val, $key) => ['category' => $key, 'total' => (float)$val])->values()->toArray();
        if ($untrackedProfit > 0) $breakdown[] = ['category' => 'Untracked Profit', 'total' => (float)$untrackedProfit];

        usort($breakdown, fn($a, $b) => abs($b['total']) <=> abs($a['total']));
        return $breakdown;
    }

    private function getMarginBreakdown(): array
    {
        $branchId = $this->selectedBranchId;
        $orders = $this->fetchFinancialOrders($branchId);
        
        $branchCosts = IngredientCost::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->pluck('unit_cost', 'ingredient_id'));

        $purchaseCosts = $this->fetchPurchasePrices($branchId);
        $globalCosts = Ingredient::pluck('cost', 'id');

        $margins = [];
        $categoryRevenue = [];
        $categoryProfit = [];

        foreach ($orders as $order) {
            $bid = $order->branch_id;
            foreach ($order->items as $item) {
                $productCostMap = []; $optionCostMap = []; $modifierCostMap = [];
                $itemCost = $this->calculateItemCogs($item, $branchCosts, $globalCosts, $purchaseCosts, $productCostMap, $optionCostMap, $modifierCostMap, $bid);
                $itemProfit = ($item->price - $itemCost) * $item->quantity;

                $categoryName = $item->product->category->name ?? 'Uncategorized';
                $categoryRevenue[$categoryName] = ($categoryRevenue[$categoryName] ?? 0) + ($item->price * $item->quantity);
                $categoryProfit[$categoryName] = ($categoryProfit[$categoryName] ?? 0) + $itemProfit;
            }
        }

        $margins = [];
        foreach ($categoryRevenue as $name => $rev) {
            $profit = $categoryProfit[$name] ?? 0;
            $margins[] = [
                'category' => $name,
                'total'    => $rev > 0 ? ($profit / $rev) * 100 : 0,
                'is_percentage' => true,
                'revenue' => $rev,
                'profit' => $profit
            ];
        }

        usort($margins, fn($a, $b) => $b['total'] <=> $a['total']);
        return $margins;
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
        $this->activeFilter = 'All Time';

        $this->updateHeader();
    }

    public function updatedSelectedBranchId(?int $value): void
    {
        $this->refreshChart();
    }


    public function updatedStartDate(): void
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

            $this->activeFilter = 'All Time';
        }
    }

    public function applyQuickDateFilter(string $range): void
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
        $this->dispatch('update-sales-chart', chart: $chartData);
        $this->dispatch('branchSelectionUpdated');
    }

    public function resetDates()
    {
        $this->applyQuickDateFilter('all');
    }

    private function updateHeader()
    {
        $this->dispatch('setHeader', 
            icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            title: 'Network Intelligence',
            breadcrumbs: [
                ['label' => 'Operations', 'url' => '#'],
                ['label' => 'Dashboard', 'url' => route('dashboard')],
            ]
        );
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
            'branchMapData'   => $this->getBranchMapData(),
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
        
        $branchCosts = IngredientCost::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->pluck('unit_cost', 'ingredient_id'));

        $purchaseCosts = $this->fetchPurchasePrices($branchId);
        $globalCosts = Ingredient::pluck('cost', 'id');

        $totalRevenue = $orders->sum('total_amount');
        $deliveryFees = $orders->sum('delivery_fee');
        $totalDiscounts = $orders->sum('discount_amount');
        $orderCount = $orders->count();
        $totalCogs = 0;

        $productCostMap = [];
        $optionCostMap = [];
        $modifierCostMap = [];

        foreach ($orders as $order) {
            $bid = $order->branch_id;
            foreach ($order->items as $item) {
                $itemCost = $this->calculateItemCogs($item, $branchCosts, $globalCosts, $purchaseCosts, $productCostMap, $optionCostMap, $modifierCostMap, $bid);
                $totalCogs += ($itemCost * $item->quantity);
            }
        }

        // Calculate Waste Cost
        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : null;
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : null;

        $wasteCost = StockMovement::whereIn('type', ['waste', 'waste_expired', 'out', 'return_to_supplier'])
            ->when($start, fn($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn($q) => $q->where('created_at', '<=', $end))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->sum(function($movement) {
                return abs($movement->quantity) * ($movement->unit_cost ?? 0);
            });

        $aov = $orderCount > 0 ? ($totalRevenue / $orderCount) : 0;
        
        // Profit = (Revenue - Delivery) - COGS - Waste
        $grossProfit = ($totalRevenue - $deliveryFees) - $totalCogs - $wasteCost;
        $margin = ($totalRevenue - $deliveryFees) > 0 ? ($grossProfit / ($totalRevenue - $deliveryFees)) * 100 : 0;

        $netSales = $totalRevenue - $deliveryFees;
        $grossSales = $netSales + $totalDiscounts;

        return [
            'revenue' => $totalRevenue,
            'net_sales' => $netSales,
            'gross_sales' => $grossSales,
            'delivery_fees' => $deliveryFees,
            'total_discounts' => $totalDiscounts,
            'order_count' => $orderCount,
            'aov' => $aov,
            'total_cogs' => $totalCogs,
            'waste_cost' => $wasteCost,
            'gross_profit' => $grossProfit,
            'profit_margin_pct' => round($margin, 2),
        ];
    }

    private function fetchPurchasePrices(?int $branchId): Collection
    {
        return StockMovement::where('type', 'in')
            ->whereNotNull('unit_cost')
            ->where('unit_cost', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->unique('ingredient_id')->pluck('unit_cost', 'ingredient_id'));
    }

    private function fetchFinancialOrders(?int $branchId): Collection
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

    private function getBranches(bool $isSuperAdmin): Collection
    {
        return $isSuperAdmin ? Branch::withCount('staff')->orderBy('branch_name')->get() : collect([]);
    }

    private function getBranchMapData(): array
    {
        $branchId = $this->selectedBranchId;
        $start = $this->startDate . ' 00:00:00';
        $end = $this->endDate . ' 23:59:59';

        // Query branches with their total completed sales in the current date range
        $branchesSales = Branch::leftJoin('orders', function($join) use ($start, $end) {
            $join->on('branches.id', '=', 'orders.branch_id')
                ->where('orders.status', Order::STATUS_COMPLETED)
                ->when($this->startDate, fn($q) => $q->where('orders.created_at', '>=', $start))
                ->when($this->endDate, fn($q) => $q->where('orders.created_at', '<=', $end));
        })
        ->select('branches.id', 'branches.branch_name', DB::raw('COALESCE(SUM(orders.total_amount), 0) as total_sales'))
        ->groupBy('branches.id', 'branches.branch_name')
        ->get();

        $totalSales = $branchesSales->sum('total_sales');

        // Geolocation coordinates mapping for Laguna branches (Calauan, Bay, Pila, Calamba)
        // coordinates range within visual SVG viewbox: Left (x%): 15% - 85%, Top (y%): 15% - 85%
        $coordinateMap = [
            'Calauan' => ['left' => '55%', 'top' => '62%', 'color' => 'bg-emerald-500'],
            'Bay'     => ['left' => '38%', 'top' => '48%', 'color' => 'bg-blue-500'],
            'Pila'    => ['left' => '78%', 'top' => '38%', 'color' => 'bg-emerald-500'],
            'Calamba' => ['left' => '20%', 'top' => '25%', 'color' => 'bg-blue-500'],
        ];

        // Determine highest sales to toggle the glowing double-bubble ping
        $maxSales = $branchesSales->max('total_sales');

        return $branchesSales->map(function($branch) use ($totalSales, $coordinateMap, $maxSales) {
            $name = $branch->branch_name;
            $cleanName = trim(str_ireplace('Branch', '', $name));
            
            // Assign coordinate dynamically by hashing name if it is a new branch
            $coords = $coordinateMap[$cleanName] ?? [
                'left' => (abs(crc32($cleanName)) % 50 + 25) . '%',
                'top'  => (abs(crc32($cleanName . 'y')) % 50 + 25) . '%',
                'color' => 'bg-indigo-500'
            ];

            $pct = $totalSales > 0 ? round(($branch->total_sales / $totalSales) * 100, 1) : 0;

            return [
                'id' => $branch->id,
                'name' => $name,
                'clean_name' => $cleanName,
                'total_sales' => (float)$branch->total_sales,
                'sales_pct' => $pct,
                'left' => $coords['left'],
                'top' => $coords['top'],
                'color' => $coords['color'],
                'is_highest' => ($branch->total_sales > 0 && $branch->total_sales == $maxSales),
            ];
        })->sortByDesc('total_sales')->values()->toArray();
    }

    private function getThemeAssets(string $roleName): array
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
        
        $branchCosts = IngredientCost::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(fn($g) => $g->pluck('unit_cost', 'ingredient_id'));

        $purchaseCosts = $this->fetchPurchasePrices($branchId);
        $globalCosts = Ingredient::pluck('cost', 'id');

        if ($start->toDateString() === $end->toDateString()) {
            $hourlyData = $this->aggregateHourlyChartData($orders, $branchCosts, $globalCosts, $purchaseCosts);
            return $this->formatHourlyChartOutput($start, $end, $hourlyData, $orders);
        }

        $dailyData = $this->aggregateDailyChartData($orders, $branchCosts, $globalCosts, $purchaseCosts);
        
        return $this->formatChartOutput($start, $end, $dailyData, $orders);
    }

    private function aggregateHourlyChartData(Collection $orders, Collection $branchCostMap, Collection $globalCostMap, Collection $purchaseCostMap): array
    {
        $hourlyData = [];
        for ($h = 0; $h < 24; $h++) {
            $hourlyData[$h] = ['sales' => 0, 'volume' => 0, 'profit' => 0, 'cogs' => 0];
        }

        $productCostMap = [];
        $optionCostMap = [];
        $modifierCostMap = [];

        foreach ($orders as $order) {
            $hour = (int)Carbon::parse($order->created_at)->hour;
            $bid = $order->branch_id;
            
            $hourlyData[$hour]['sales'] += $order->total_amount;
            $hourlyData[$hour]['volume'] += 1;

            $orderCogs = 0;
            foreach ($order->items as $item) {
                $itemCost = $this->calculateItemCogs($item, $branchCostMap, $globalCostMap, $purchaseCostMap, $productCostMap, $optionCostMap, $modifierCostMap, $bid);
                $orderCogs += ($itemCost * $item->quantity);
            }
            $hourlyData[$hour]['cogs'] += $orderCogs;
            // Profit = (Total Amount - Delivery Fee) - COGS
            $hourlyData[$hour]['profit'] += ($order->total_amount - $order->delivery_fee - $orderCogs);
        }
        
        return $hourlyData;
    }

    private function formatHourlyChartOutput(Carbon $start, Carbon $end, array $hourlyData, Collection $orders): array
    {
        $categories = [];
        $salesSeries = [];
        $volumeSeries = [];
        $profitSeries = [];
        $cogsSeries = [];
        $aovSeries = [];

        for ($h = 0; $h < 24; $h++) {
            if ($h === 0) {
                $label = '12 AM';
            } elseif ($h === 12) {
                $label = '12 PM';
            } elseif ($h < 12) {
                $label = $h . ' AM';
            } else {
                $label = ($h - 12) . ' PM';
            }
            $categories[] = $label;

            $sales = isset($hourlyData[$h]) ? round($hourlyData[$h]['sales'], 2) : 0;
            $volume = isset($hourlyData[$h]) ? (int)$hourlyData[$h]['volume'] : 0;
            $profit = isset($hourlyData[$h]) ? round($hourlyData[$h]['profit'], 2) : 0;
            $cogs = isset($hourlyData[$h]) ? round($hourlyData[$h]['cogs'], 2) : 0;
            $aov = $volume > 0 ? round($sales / $volume, 2) : 0;

            $salesSeries[] = $sales;
            $volumeSeries[] = $volume;
            $profitSeries[] = $profit;
            $cogsSeries[] = $cogs;
            $aovSeries[] = $aov;
        }

        $computeForecast = function(array $arr) {
            $totalVal = array_sum($arr);
            $avgVal = ($totalVal > 0 && count($arr) > 0) ? $totalVal / count($arr) : 0;
            return array_map(fn($v) => $v > 0 ? $v : round($avgVal * 0.8, 2), $arr);
        };

        $salesForecast = $computeForecast($salesSeries);
        $volumeForecast = $computeForecast($volumeSeries);
        $profitForecast = $computeForecast($profitSeries);
        $cogsForecast = $computeForecast($cogsSeries);
        $aovForecast = $computeForecast($aovSeries);

        $series = match($this->selectedChartMetric) {
            'Volume' => $volumeSeries,
            'Profit' => $profitSeries,
            default  => $salesSeries,
        };

        $forecast = match($this->selectedChartMetric) {
            'Volume' => $volumeForecast,
            'Profit' => $profitForecast,
            default  => $salesForecast,
        };

        $posConfig = ConfigurationService::getPosConfig();
        
        $paymentRaw = $orders->groupBy('payment_method')->map->count();
        $paymentTotalsRaw = $orders->groupBy('payment_method')->map(fn($g) => round($g->sum('total_amount'), 2));
        $paymentLabels = $posConfig['payment_methods'] ?? ['Cash', 'GCash'];
        $paymentSeries = [];
        foreach ($paymentLabels as $label) {
            $paymentSeries[] = (int)($paymentRaw[$label] ?? 0);
        }

        $channelsRaw = $orders->groupBy('order_type')->map->count();
        $channelLabels = $posConfig['order_types'] ?? ['Dine-in', 'Take-out'];
        $channelSeries = [];
        foreach ($channelLabels as $label) {
            $channelSeries[] = (int)($channelsRaw[$label] ?? 0);
        }

        return [
            'categories' => $categories,
            'history'    => $series,
            'forecast'   => $forecast,
            'metrics'    => [
                'series' => [
                    'Sales'  => $salesSeries,
                    'Volume' => $volumeSeries,
                    'Profit' => $profitSeries,
                    'COGS'   => $cogsSeries,
                    'AOV'    => $aovSeries,
                ],
                'forecast' => [
                    'Sales'  => $salesForecast,
                    'Volume' => $volumeForecast,
                    'Profit' => $profitForecast,
                    'COGS'   => $cogsForecast,
                    'AOV'    => $aovForecast,
                ]
            ],
            'payment'    => [
                'series' => $paymentSeries,
                'labels' => $paymentLabels,
                'totals' => array_map(fn($label) => $paymentTotalsRaw[$label] ?? 0, $paymentLabels),
            ],
            'channels'   => [
                'series' => $channelSeries,
                'labels' => $channelLabels
            ],
            'metric' => $this->selectedChartMetric
        ];
    }

    private function prepareChartDateRange(?int $branchId): array
    {
        $startDate = $this->parseDateInput($this->startDate);
        $endDate = $this->parseDateInput($this->endDate);

        if (!$startDate) {
            $earliestOrder = Order::query()
                ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
                ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
                ->orderBy('created_at', 'asc')
                ->first();
            $start = $earliestOrder ? Carbon::parse($earliestOrder->created_at)->startOfDay() : now()->subDays(30)->startOfDay();
        } else {
            $start = $startDate->startOfDay();
        }

        $end = $endDate ? $endDate->endOfDay() : now()->endOfDay();
        
        return [$start, $end];
    }

    private function parseDateInput(?string $date): ?Carbon
    {
        if (!$date) {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            return null;
        }

        return $parsed && $parsed->format('Y-m-d') === $date ? $parsed : null;
    }

    private function fetchChartOrders(Carbon $start, Carbon $end, ?int $branchId): Collection
    {
        return Order::where('status', Order::STATUS_COMPLETED)
            ->whereBetween('created_at', [$start, $end])
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with(['items.product.recipes', 'items.options.option.recipes', 'items.modifiers.modifier.recipes'])
            ->get();
    }

    private function fetchChartStandardCosts(?int $branchId): Collection
    {
        return IngredientCost::query()
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();
    }

    private function aggregateDailyChartData(Collection $orders, Collection $branchCostMap, Collection $globalCostMap, Collection $purchaseCostMap): array
    {
        $dailyData = [];
        $productCostMap = [];
        $optionCostMap = [];
        $modifierCostMap = [];

        foreach ($orders as $order) {
            $date = Carbon::parse($order->created_at)->toDateString();
            $bid = $order->branch_id;
            if (!isset($dailyData[$date])) $dailyData[$date] = ['sales' => 0, 'volume' => 0, 'profit' => 0, 'cogs' => 0];
            
            $dailyData[$date]['sales'] += $order->total_amount;
            $dailyData[$date]['volume'] += 1;

            $orderCogs = 0;
            foreach ($order->items as $item) {
                $itemCost = $this->calculateItemCogs($item, $branchCostMap, $globalCostMap, $purchaseCostMap, $productCostMap, $optionCostMap, $modifierCostMap, $bid);
                $orderCogs += ($itemCost * $item->quantity);
            }
            $dailyData[$date]['cogs'] += $orderCogs;
            // Profit = (Total Amount - Delivery Fee) - COGS
            $dailyData[$date]['profit'] += ($order->total_amount - $order->delivery_fee - $orderCogs);
        }
        
        return $dailyData;
    }

    private function formatChartOutput(Carbon $start, Carbon $end, array $dailyData, Collection $orders): array
    {
        $categories = [];
        $salesSeries = [];
        $volumeSeries = [];
        $profitSeries = [];
        $cogsSeries = [];
        $aovSeries = [];
        $curr = clone $start;
        while ($curr <= $end) {
            $dateStr = $curr->toDateString();
            $categories[] = $curr->format('M d');

            $sales = isset($dailyData[$dateStr]) ? round($dailyData[$dateStr]['sales'], 2) : 0;
            $volume = isset($dailyData[$dateStr]) ? (int)$dailyData[$dateStr]['volume'] : 0;
            $profit = isset($dailyData[$dateStr]) ? round($dailyData[$dateStr]['profit'], 2) : 0;
            $cogs = isset($dailyData[$dateStr]) ? round($dailyData[$dateStr]['cogs'], 2) : 0;
            $aov = $volume > 0 ? round($sales / $volume, 2) : 0;

            $salesSeries[] = $sales;
            $volumeSeries[] = $volume;
            $profitSeries[] = $profit;
            $cogsSeries[] = $cogs;
            $aovSeries[] = $aov;

            $curr->addDay();
        }

        // Forecast helper
        $computeForecast = function(array $arr) {
            $totalVal = array_sum($arr);
            $avgVal = ($totalVal > 0 && count($arr) > 0) ? $totalVal / count($arr) : 0;
            return array_map(fn($v) => $v > 0 ? $v : round($avgVal * 0.8, 2), $arr);
        };

        $salesForecast = $computeForecast($salesSeries);
        $volumeForecast = $computeForecast($volumeSeries);
        $profitForecast = $computeForecast($profitSeries);
        $cogsForecast = $computeForecast($cogsSeries);
        $aovForecast = $computeForecast($aovSeries);

        // Choose series based on selected metric for backward compatibility
        $series = match($this->selectedChartMetric) {
            'Volume' => $volumeSeries,
            'Profit' => $profitSeries,
            default  => $salesSeries,
        };

        $forecast = match($this->selectedChartMetric) {
            'Volume' => $volumeForecast,
            'Profit' => $profitForecast,
            default  => $salesForecast,
        };

        // Dynamic Configuration from POS Platform
        $posConfig = ConfigurationService::getPosConfig();
        
        // Payment Distribution
        $paymentRaw = $orders->groupBy('payment_method')->map->count();
        $paymentTotalsRaw = $orders->groupBy('payment_method')->map(fn($g) => round($g->sum('total_amount'), 2));
        $paymentLabels = $posConfig['payment_methods'] ?? ['Cash', 'GCash'];
        $paymentSeries = [];
        foreach ($paymentLabels as $label) {
            $paymentSeries[] = (int)($paymentRaw[$label] ?? 0);
        }

        // Revenue Channels (Order Types)
        $channelsRaw = $orders->groupBy('order_type')->map->count();
        $channelLabels = $posConfig['order_types'] ?? ['Dine-in', 'Take-out'];
        $channelSeries = [];
        foreach ($channelLabels as $label) {
            $channelSeries[] = (int)($channelsRaw[$label] ?? 0);
        }

        return [
            'categories' => $categories,
            'history'    => $series,
            'forecast'   => $forecast,
            'metrics'    => [
                'series' => [
                    'Sales'  => $salesSeries,
                    'Volume' => $volumeSeries,
                    'Profit' => $profitSeries,
                    'COGS'   => $cogsSeries,
                    'AOV'    => $aovSeries,
                ],
                'forecast' => [
                    'Sales'  => $salesForecast,
                    'Volume' => $volumeForecast,
                    'Profit' => $profitForecast,
                    'COGS'   => $cogsForecast,
                    'AOV'    => $aovForecast,
                ]
            ],
            'payment'    => [
                'series' => $paymentSeries,
                'labels' => $paymentLabels,
                'totals' => array_map(fn($label) => $paymentTotalsRaw[$label] ?? 0, $paymentLabels),
            ],
            'channels'   => [
                'series' => $channelSeries,
                'labels' => $channelLabels
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
        [$filterStart, $filterEnd, $days] = $this->resolveInventoryIntelDateWindow($branchId);
        [$branchCostMap, $globalCostMap] = $this->resolveInventoryIntelCostMaps($branchId);

        // 1. Consumption Velocity (Top 5 fastest moving ingredients)
        $velocity = StockMovement::where('type', 'order') // 'order' represents sales deduction
            ->when($filterStart, fn($q) => $q->where('created_at', '>=', $filterStart))
            ->when($filterEnd, fn($q) => $q->where('created_at', '<=', $filterEnd))
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

        // 2. Health Index: compare real loss events against consumed inventory value.
        $salesMovements = StockMovement::where('type', 'order')
            ->when($filterStart, fn($q) => $q->where('created_at', '>=', $filterStart))
            ->when($filterEnd, fn($q) => $q->where('created_at', '<=', $filterEnd))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get(['branch_id', 'ingredient_id', 'quantity']);

        // Exclude generic "adjust" records here because positive reconciliations store
        // the final counted stock level, which would distort loss calculations.
        $wasteMovements = StockMovement::whereIn('type', ['waste', 'waste_expired', 'out', 'return_to_supplier'])
            ->when($filterStart, fn($q) => $q->where('created_at', '>=', $filterStart))
            ->when($filterEnd, fn($q) => $q->where('created_at', '<=', $filterEnd))
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get(['branch_id', 'ingredient_id', 'quantity']);

        $salesValue = $salesMovements->sum(fn ($movement) => abs((float) $movement->quantity) * $this->resolveInventoryIntelUnitCost(
            (int) $movement->branch_id,
            (int) $movement->ingredient_id,
            $branchCostMap,
            $globalCostMap
        ));

        $wasteValue = $wasteMovements->sum(fn ($movement) => abs((float) $movement->quantity) * $this->resolveInventoryIntelUnitCost(
            (int) $movement->branch_id,
            (int) $movement->ingredient_id,
            $branchCostMap,
            $globalCostMap
        ));

        $variancePct = $salesValue > 0
            ? ($wasteValue / $salesValue) * 100
            : ($wasteValue > 0 ? 100 : 0);

        return [
            'velocity' => $velocity,
            'variance_pct' => round($variancePct, 2),
            'sales_value' => round($salesValue, 2),
            'waste_value' => round($wasteValue, 2),
            'health_score' => round(max(0, 100 - $variancePct), 2)
        ];
    }

    private function resolveInventoryIntelDateWindow(?int $branchId): array
    {
        if ($this->startDate && $this->endDate && !$this->dateError) {
            $startDate = $this->parseDateInput($this->startDate);
            $endDate = $this->parseDateInput($this->endDate);

            if ($startDate && $endDate) {
                $start = $startDate->startOfDay();
                $end = $endDate->endOfDay();

                return [$start, $end, max(1, $start->diffInDays($end) + 1)];
            }
        }

        $earliest = StockMovement::where('type', 'order')
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->min('created_at');

        $start = $earliest ? Carbon::parse($earliest)->startOfDay() : now()->subDays(30)->startOfDay();
        $end = now()->endOfDay();

        return [$start, $end, max(1, $start->diffInDays($end) + 1)];
    }

    private function resolveInventoryIntelCostMaps(?int $branchId): array
    {
        $branchCosts = IngredientCost::query()
            ->when(!$this->isSuperAdmin, fn($q) => $q->where('branch_id', auth()->user()->branch_id))
            ->when($this->isSuperAdmin && $branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->groupBy('branch_id')
            ->map(function (Collection $costs) {
                return $costs->mapWithKeys(function ($cost): array {
                    $resolvedCost = (float) ($cost->cost_per_base_unit ?: $cost->unit_cost ?: 0);

                    return [$cost->ingredient_id => $resolvedCost];
                });
            });

        $globalCosts = Ingredient::pluck('cost', 'id')->map(fn ($cost): float => (float) $cost);

        return [$branchCosts, $globalCosts];
    }

    private function resolveInventoryIntelUnitCost(int $branchId, int $ingredientId, Collection $branchCostMap, Collection $globalCostMap): float
    {
        return (float) (
            $branchCostMap->get($branchId)?->get($ingredientId)
            ?? $globalCostMap->get($ingredientId)
            ?? 0
        );
    }

    private function calculateItemCogs(OrderItem $item, Collection $branchCosts, Collection $globalCosts, Collection $purchaseCosts, array &$productMap, array &$optionMap, array &$modifierMap, ?int $bid): float
    {
        $itemCost = 0;

        $resolveCost = fn($ingId) => 
            ($bid ? ($branchCosts[$bid][$ingId] ?? $purchaseCosts[$bid][$ingId] ?? null) : null) 
            ?? $globalCosts[$ingId] 
            ?? 0;

        // Base Product
        if ($item->product_id) {
            if (!isset($productMap[$item->product_id])) {
                $cost = 0;
                if ($item->product) {
                    foreach ($item->product->recipes->where('product_option_id', null)->where('modifier_id', null) as $r) {
                        $cost += ($r->quantity * $resolveCost($r->ingredient_id));
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
                            $cost += ($r->quantity * $resolveCost($r->ingredient_id));
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
                            $cost += ($r->quantity * $resolveCost($r->ingredient_id));
                        }
                    }
                    $modifierMap[$m->modifier_id] = (float)$cost;
                }
                $itemCost += $modifierMap[$m->modifier_id];
            }
        }

        return (float)$itemCost;
    }

    private function trackIngredientUsage(OrderItem $item, Collection $branchCosts, Collection $globalCosts, Collection $purchaseCosts, Collection $ingredients, array &$usage, ?int $bid): void
    {
        $recipes = collect();
        if ($item->product) $recipes = $recipes->concat($item->product->recipes->where('product_option_id', null)->where('modifier_id', null));
        foreach ($item->options as $o) if ($o->option) $recipes = $recipes->concat($o->option->recipes);
        foreach ($item->modifiers as $m) if ($m->modifier) $recipes = $recipes->concat($m->modifier->recipes);

        $resolveCost = fn($ingId) => 
            ($bid ? ($branchCosts[$bid][$ingId] ?? $purchaseCosts[$bid][$ingId] ?? null) : null) 
            ?? $globalCosts[$ingId] 
            ?? 0;

        foreach ($recipes as $r) {
            $unitPrice = $resolveCost($r->ingredient_id);
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
                'Gross Revenue'    => 'PHP ' . number_format($kpi['gross_sales'], 2),
                'Net Sales'        => 'PHP ' . number_format($kpi['net_sales'], 2),
                'Avg. Order Value' => 'PHP ' . number_format($kpi['aov'], 2),
                'Total COGS'       => 'PHP ' . number_format($kpi['total_cogs'], 2),
                'Waste'            => 'PHP ' . number_format($kpi['waste_cost'] ?? 0, 2),
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
