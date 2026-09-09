<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Branch;
use App\Models\IngredientCost;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\BranchIngredientStock;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

use App\Traits\HandlesExports;


class BusinessIntelligence extends Component
{
    use WithPagination, HandlesExports;
    use \App\Traits\ResolvesIngredientCosts;

    public string $activeTab = 'descriptive'; // descriptive, diagnostic, predictive, prescriptive
    public string $startDate = '';
    public string $endDate = '';
    public string $selectedBranchId = 'all';
    public string $search = '';
    public int $perPage = 5;
    public int $prescriptivePerPage = 5;
    public string $seasonalityMode = 'weekly'; // weekly | monthly
    public string $activeFilter = 'All Time';
    public string $dateError = '';

    public bool $showBreakdown = false;
    public string $selectedMetric = 'Gross Revenue';
    public array $breakdownData = [];
       public array $stockReview = [];
    public array $stockReviewCache = [];
    public int $stockReviewPerPage = 5;
    public array $networkRestockSummary = [];
    public bool $showNetworkRestockSummary = false;
    protected ?array $analyticsCache = null;
    protected ?array $forecastingCache = null;
    protected ?array $networkRestockCache = null;

    protected $queryString = [
        'activeTab' => ['except' => 'descriptive'],
        'selectedBranchId' => ['except' => 'all'],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'seasonalityMode' => ['except' => 'weekly'],
        'activeFilter' => ['except' => 'All Time'],
    ];

    public function boot(): void
    {
        $user = auth()->user();

        abort_unless($user && ($user->isSuperAdmin() || $user->isAdmin()), 403, 'Unauthorized access to Business Intelligence.');
    }

    public function mount()
{
    $user = auth()->user();

    $tab = request()->query('tab', 'descriptive');
    $this->activeTab = match ($tab) {
        'performance', 'sales' => 'descriptive',
        'products', 'operations' => 'diagnostic',
        'forecasting' => 'predictive',
        default => in_array($tab, ['descriptive', 'diagnostic', 'predictive', 'prescriptive'], true)
            ? $tab
            : 'descriptive',
    };

    // Default branch logic
    if (!$user->isSuperAdmin()) {
        $this->selectedBranchId = (string) $user->branch_id;
    } else {
        // Super admins should default to global network view unless a specific branch is requested.
        if (!$this->selectedBranchId || $this->selectedBranchId === 'all') {
            $this->selectedBranchId = 'all';
        }
    }

    // Initialize dates to All Time on first load
    $this->startDate = '';
    $this->endDate = '';
    $this->activeFilter = 'All Time';

    $this->updateHeader();
}
    public function applyQuickDateFilter(?string $filter = null)
    {
        if (!$filter) {
            $this->dateError = '';
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
                $this->startDate = '';
                $this->endDate = '';
                $this->activeFilter = 'All Time';
                break;
            default:
                // Preserve manually-selected range or custom range.
                break;
        }

        $this->analyticsCache = null;
        $this->forecastingCache = null;
        $this->networkRestockCache = null;
        $this->dateError = '';
        $this->resetPage();
        $this->resetPage('branchPage');
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

    public function openStockReview(int $ingredientId): void
    {
        if ($this->selectedBranchId === 'all') {
            return;
        }

        $branchId = (int) $this->selectedBranchId;
        $cacheKey = $branchId . ':' . $ingredientId;
        $this->resetPage('stockReviewPage');

        $cacheTtlSeconds = 30;
        $cachedAt = $this->stockReviewCache[$cacheKey]['cached_at'] ?? null;

        if ($cachedAt && now()->diffInSeconds($cachedAt) < $cacheTtlSeconds) {
            $this->stockReview = $this->stockReviewCache[$cacheKey]['data'];
            $this->dispatch('open-modal', name: 'prescriptive-stock-review');
            return;
        }

        $ingredient = Ingredient::findOrFail($ingredientId);
        $stock = BranchIngredientStock::where('branch_id', $branchId)
            ->where('ingredient_id', $ingredientId)
            ->value('stock_quantity');

        $batches = StockBatch::where('branch_id', $branchId)
            ->where('ingredient_id', $ingredientId)
            ->where('current_quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get()
            ->map(fn ($batch) => [
                'batch_number' => $batch->batch_number ?: 'Unassigned',
                'quantity' => (float) $batch->current_quantity,
                'expiry_date' => $batch->expiry_date?->format('M d, Y'),
                'expiry_state' => !$batch->expiry_date
                    ? 'No expiry'
                    : ($batch->expiry_date->isPast() ? 'Expired' : ($batch->expiry_date->diffInDays(now()) <= 7 ? 'Expiring soon' : 'Good')),
            ])
            ->values()
            ->all();

        $this->stockReview = [
            'ingredient_id' => $ingredientId,
            'branch_id' => $branchId,
            'ingredient_name' => $ingredient->name,
            'unit' => $ingredient->unit,
            'current_stock_display' => $this->formatInventoryQuantity((float) ($stock ?? 0), $ingredient->unit),
            'batches' => collect($batches)->map(function ($batch) use ($ingredient) {
                $formatted = $this->formatInventoryQuantity((float) $batch['quantity'], $ingredient->unit);
                $batch['quantity_display'] = $formatted;
                return $batch;
            })->values()->all(),
            'adjustment_url' => route('stock.adjustment', ['id' => $ingredientId]),
        ];

        $this->stockReviewCache[$cacheKey] = [
            'data' => $this->stockReview,
            'cached_at' => now(),
        ];

        $this->dispatch('open-modal', name: 'prescriptive-stock-review');
    }

    public function getStockReviewMovementsProperty(): LengthAwarePaginator
    {
        if (empty($this->stockReview['ingredient_id'])) {
            return new LengthAwarePaginator(collect(), 0, $this->stockReviewPerPage, 1, [
                'path' => request()->url(),
                'pageName' => 'stockReviewPage',
            ]);
        }

        $ingredient = Ingredient::find($this->stockReview['ingredient_id']);
        $outgoingTypes = ['order', 'out', 'waste', 'waste_expired', 'return_to_supplier', 'transfer_out'];

        return StockMovement::where('branch_id', $this->stockReview['branch_id'])
            ->where('ingredient_id', $this->stockReview['ingredient_id'])
            ->latest()
            ->paginate($this->stockReviewPerPage, ['*'], 'stockReviewPage')
            ->through(function ($movement) use ($ingredient, $outgoingTypes) {
                $quantity = abs((float) $movement->quantity);
                $signedQuantity = in_array($movement->type, $outgoingTypes, true) ? -$quantity : $quantity;

                return [
                    'type' => ucfirst(str_replace('_', ' ', $movement->type)),
                    'quantity' => $signedQuantity,
                    'quantity_display' => $this->formatInventoryQuantity($signedQuantity, $ingredient?->unit),
                    'reference' => $movement->reference_id ?: 'No reference',
                    'date' => $movement->created_at?->format('M d, Y H:i'),
                ];
            });
    }

    private function formatInventoryQuantity(float $quantity, ?string $unit): array
    {
        $normalizedUnit = strtolower(trim((string) $unit));

        if (in_array($normalizedUnit, ['g', 'gram', 'grams'], true) && abs($quantity) >= 1000) {
            return ['value' => round($quantity / 1000, 2), 'unit' => 'kg'];
        }

        if (in_array($normalizedUnit, ['ml', 'milliliter', 'milliliters'], true) && abs($quantity) >= 1000) {
            return ['value' => round($quantity / 1000, 2), 'unit' => 'L'];
        }

        return ['value' => round($quantity, 2), 'unit' => $unit];
    }

    public function updated(string $propertyName)
{
    if ($propertyName === 'selectedBranchId') {
        if (!auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = (string) auth()->user()->branch_id;
        }

        $this->analyticsCache = null;
        $this->forecastingCache = null;
        $this->networkRestockCache = null;
        $this->showNetworkRestockSummary = false;
        $this->networkRestockSummary = [];
    }

    if (in_array($propertyName, ['selectedBranchId', 'search', 'perPage', 'prescriptivePerPage'])) {
        $this->resetPage();
        $this->resetPage('branchPage');
        $this->resetPage('prescriptivePage');
    }
}

    public function updatedStartDate(): void
    {
        $this->validateDateRange();
        if (!$this->dateError) {
            $this->analyticsCache = null;
            $this->forecastingCache = null;
            $this->networkRestockCache = null;
            $this->resetPage();
            $this->resetPage('branchPage');
            $this->resetPage('prescriptivePage');
        }
    }

    public function updatedEndDate(): void
    {
        $this->validateDateRange();
        if (!$this->dateError) {
            $this->analyticsCache = null;
            $this->forecastingCache = null;
            $this->networkRestockCache = null;
            $this->resetPage();
            $this->resetPage('branchPage');
            $this->resetPage('prescriptivePage');
        }
    }

    private function validateDateRange(): void
    {
        $this->dateError = '';

        if ($this->startDate && !\DateTime::createFromFormat('Y-m-d', $this->startDate)) {
            $this->dateError = 'Invalid start date format.';
            return;
        }

        if ($this->endDate && !\DateTime::createFromFormat('Y-m-d', $this->endDate)) {
            $this->dateError = 'Invalid end date format.';
            return;
        }

        if ($this->startDate && $this->endDate) {
            $start = new \DateTime($this->startDate);
            $end = new \DateTime($this->endDate);
            $today = new \DateTime('today');

            if ($start > $today) {
                $this->dateError = 'Start date cannot be in the future.';
                return;
            }

            if ($end > $today) {
                $this->dateError = 'End date cannot be in the future.';
                return;
            }

            if ($start > $end) {
                $this->dateError = 'Start date cannot be after end date.';
                return;
            }
        }
    }

    public function resetDates(): void
    {
        $this->applyQuickDateFilter('all');
    }

    public function render()
    {
        if (!auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = (string) auth()->user()->branch_id;
        }

        $analytics = $this->getAnalytics();
        $isActionable = $this->selectedBranchId !== 'all';
        $forecasting = $this->getForecastingData();
        $prescriptiveRecommendations = $this->getPrescriptiveRecommendations($forecasting);

        $networkRestockSummary = [];
        if ($this->selectedBranchId === 'all' && (auth()->user()->isSuperAdmin() || auth()->user()->role_id === 1)) {
            $networkRestockSummary = $this->getNetworkRestockSummary();
        }
        $this->networkRestockSummary = $networkRestockSummary;

        $performance = [
            'gross_sales'     => $analytics['gross_sales'],
            'net_sales'       => $analytics['net_sales'],
            'order_count'     => $analytics['order_count'],
            'avg_order_value' => $analytics['avg_order_value'],
            'total_discounts' => $analytics['total_discounts'],
            'delivery_fees'   => $analytics['delivery_fees'],
            'refunds'         => $analytics['refunds'],
            'waste_cost'      => $analytics['waste_cost'],
            'gross_profit'    => $analytics['gross_profit'],
        ];

        return view('livewire.business-intelligence', [
            'branches'        => Branch::all(),
            'performance'     => $performance,
            'forecasting'     => $forecasting,
            'prescriptiveRecommendations' => $prescriptiveRecommendations,
            'executiveSummary'=> $this->getExecutiveSummary($analytics, $forecasting),
            'productInsights' => $this->getProductInsights($analytics),
            'operations'      => $this->getOperationalData($analytics),
            'recentOrders'    => $this->getRecentOrders(),
            'salesData'       => $analytics,
            'isActionable'    => $isActionable,
            'networkRestockSummary' => $networkRestockSummary,
        ])->layout('layouts.app');
    }

    private function getExecutiveSummary(array $analytics, array $forecasting): array
    {
        $accuracy = $forecasting['accuracy'] ?? [
            'model_improvement_pct' => 0,
            'mape' => 0,
            'benchmark_mape' => 0,
        ];

        $trend = $analytics['trend'] ?? ['has_data' => false];

        return [
            'gross_sales' => $analytics['gross_sales'],
            'net_sales' => $analytics['net_sales'],
            'gross_profit' => $analytics['gross_profit'],
            'order_count' => $analytics['order_count'],
            'avg_order_value' => $analytics['avg_order_value'],
            'forecast_improvement' => $accuracy['model_improvement_pct'] ?? 0,
            'benchmark_mape' => $accuracy['benchmark_mape'] ?? 0,
            'model_mape' => $accuracy['mape'] ?? 0,
            'has_sales_trend' => $trend['has_data'] ?? false,
        ];
    }

    private function getAnalytics(): array
    {
        if ($this->analyticsCache !== null) return $this->analyticsCache;

        $branchId = $this->selectedBranchId === 'all' ? null : $this->selectedBranchId;

        // 1. Single eager-loaded query for all orders in range
        $orders = Order::whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
            ->when($this->startDate, fn($q) => $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay()))
            ->when($this->endDate, fn($q) => $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay()))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->with([
                'items.product.recipes.ingredient',
                'items.options.option.recipes.ingredient',
                'items.modifiers.modifier.recipes.ingredient',
                'branch',
            ])
            ->get();

        $completedOrders = $orders->where('status', Order::STATUS_COMPLETED);

        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : ($orders->min('created_at') ? Carbon::parse($orders->min('created_at'))->startOfDay() : Carbon::now()->startOfDay());
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : ($orders->max('created_at') ? Carbon::parse($orders->max('created_at'))->endOfDay() : Carbon::now()->endOfDay());

                // 2. Sales Metrics
        // Use ALL orders in range (Completed + Refunded + Partially Refunded) as the
        // revenue base — a partially refunded order still earned real money on the
        // portion the customer kept, and a fully refunded order should net to ₱0
        // via the refund deduction below rather than being silently excluded.
        $totalCollected = $orders->sum('total_amount');
        $totalDiscounts = $orders->sum('discount_amount');
        $deliveryFees   = $orders->sum('delivery_fee');
        $refunds        = $orders->sum('refunded_amount');
        $netSales       = $totalCollected - $deliveryFees - $refunds;
        $grossSales     = $netSales + $totalDiscounts;
        $orderCount     = $completedOrders->count(); // "Orders" KPI still means fully-completed, unrefunded orders
        $avgOrderValue  = $orderCount > 0 ? $completedOrders->sum('total_amount') / $orderCount : 0;

        // 3. Delegate heavy sub-calculations to focused helpers
        $totalCogs   = $this->computeCogs($completedOrders, $branchId);
        
        // 4. Calculate Waste Cost from stock movements
        $wasteCost = StockMovement::whereIn('type', ['waste', 'waste_expired', 'out', 'return_to_supplier'])
            ->when($this->startDate, fn($q) => $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay()))
            ->when($this->endDate, fn($q) => $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay()))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get()
            ->sum(function($movement) {
                return abs($movement->quantity) * ($movement->unit_cost ?? 0);
            });

        $grossProfit = $netSales - $totalCogs - $wasteCost;
        $trendData   = $this->buildTrendData($completedOrders, $start, $end);
        $breakdown   = $this->buildSalesBreakdown($completedOrders);

        return $this->analyticsCache = [
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
            'trend'           => $trendData,
            'sales_trend'     => $trendData,
            'payment_methods' => $breakdown['payment_methods'],
            'order_sources'   => $breakdown['order_sources'],
            'top_items'       => $breakdown['top_items'],
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
                $standardCosts = $this->buildBranchCostMap(null, $branchId);
        $purchasePrices = $this->buildPurchasePriceMap(null, $branchId);
        $fallback = $this->buildGlobalCostMap();

        $total = 0.0;
        foreach ($completedOrders as $order) {
            $bid = $order->branch_id;
            $resolve = fn($ingId) =>
                $standardCosts[$bid][$ingId] ?? $purchasePrices[$bid][$ingId] ?? $fallback[$ingId] ?? 0;

            foreach ($order->items as $item) {
                $cost = 0.0;
                if ($item->product) {
                    foreach ($item->product->recipes->where('product_option_id', null)->where('modifier_id', null) as $r) {
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
     * Build a day-by-day trend array for the given date range, filling gaps with 0.
     */
    private function buildTrendData(Collection $completedOrders, Carbon $start, Carbon $end): array
    {
        $daily = $completedOrders
            ->groupBy(fn($o) => $o->created_at->format('Y-m-d'))
            ->map(fn($g) => $g->sum('total_amount'));

        $categories = [];
        $gross = [];
        $netSales = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $categories[] = $cursor->format('M d');
            $gross[] = (float)($daily[$key] ?? 0);
            $netSales[] = (float)($daily[$key] ?? 0);
            $cursor->addDay();
        }

        return [
            'categories' => $categories,
            'gross' => $gross,
            'net_sales' => $netSales,
            'has_data' => count($gross) > 0 && array_sum($gross) > 0,
        ];
    }

    /**
     * Build payment method breakdown, order source breakdown, and top-selling items
     * from an already-fetched collection of completed orders.
     */
    private function buildSalesBreakdown(Collection $completedOrders): array
    {
        $paymentGroups = $completedOrders->groupBy(function ($order) {
            $method = strtolower(trim((string) $order->payment_method));

            return in_array($method, ['cod', 'cash on delivery'], true)
                ? 'Cash on Delivery'
                : ($order->payment_method ?: 'Unknown');
        });
        $paymentLabels = collect(['Cash', 'GCash', 'Cash on Delivery'])
            ->merge($paymentGroups->keys())
            ->unique()
            ->values();
        $paymentMethods = $paymentLabels->map(fn($method) => (object)[
                'payment_method' => $method,
                'count'          => $paymentGroups->get($method, collect())->count(),
                'total'          => $paymentGroups->get($method, collect())->sum('total_amount'),
            ])
            ->filter(fn($row) => $row->count > 0 || in_array($row->payment_method, ['Cash', 'GCash', 'Cash on Delivery'], true))
            ->values();

        $sourceGroups = $completedOrders->groupBy(function ($order) {
            $source = strtolower(trim((string) $order->order_type));

            return $source === 'delivery' ? 'Delivery' : ($order->order_type ?: 'Unknown');
        });
        $sourceLabels = collect(['Dine-in', 'Take-out', 'Delivery'])
            ->merge($sourceGroups->keys())
            ->unique()
            ->values();
        $orderSources = $sourceLabels->map(fn($source) => (object)[
                'source' => $source,
                'count'  => $sourceGroups->get($source, collect())->count(),
                'total'  => $sourceGroups->get($source, collect())->sum('total_amount'),
            ])
            ->filter(fn($row) => $row->count > 0 || in_array($row->source, ['Dine-in', 'Take-out', 'Delivery'], true))
            ->values();

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


        private function getForecastingData()
    {
        $shortTerm = $this->calculateRegression('daily', 60, 7);
        $longTerm = $this->calculateRegression('monthly', 12, 6);
        $accuracy = $this->calculateForecastAccuracy($shortTerm['forecast'] ?? []);

        // Attach a simple ± RMSE confidence band to each short-term point so the
        // chart can show a range instead of a single line pretending to be exact.
        $rmse = $accuracy['rmse'] ?? 0;
        if ($rmse > 0) {
            $shortTerm['forecast'] = collect($shortTerm['forecast'])->map(function ($point) use ($rmse) {
                $point['lower'] = round(max(0, $point['predicted'] - $rmse), 2);
                $point['upper'] = round($point['predicted'] + $rmse, 2);
                return $point;
            })->all();
        }

        return [
            'short_term' => $shortTerm,
            'long_term'  => $longTerm,
            'restock_insights' => $this->getIngredientDemandForecast($shortTerm),
            'accuracy' => $accuracy,
        ];
    }

        /**
     * Proper walk-forward backtest: train on all days EXCEPT the most recent 7,
     * predict those 7 held-out days, then compare against what actually happened.
     * The naive baseline uses only pre-test data too (last 7 days of the TRAIN
     * window) so neither side gets to peek at the answer it's being scored against.
     */
    private function calculateForecastAccuracy(array $forecastSeries): array
    {
        $query = Order::whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId))
            ->where('created_at', '>=', Carbon::now()->subDays(60)->startOfDay());

        $daily = $query
            ->selectRaw('DATE(created_at) as label, SUM(total_amount) as total')
            ->groupBy('label')
            ->orderBy('label', 'asc')
            ->get()
            ->pluck('total', 'label')
            ->map(fn($value) => (float)$value);

        // Fill any gap days with 0 so the series has no missing dates
        $start = $daily->keys()->first() ? Carbon::parse($daily->keys()->first()) : Carbon::now()->subDays(30);
        $end = Carbon::now()->subDay(); // exclude today (incomplete day)
        $series = collect();
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d');
            $series->push((float)($daily[$key] ?? 0));
            $cursor->addDay();
        }

        $holdout = 7;
        if ($series->count() < $holdout + 14) {
            return [
                'mae' => 0.0, 'rmse' => 0.0, 'mape' => 0.0,
                'benchmark_mae' => 0.0, 'benchmark_mape' => 0.0,
                'model_improvement_pct' => 0.0,
                'baseline' => 'Not enough data',
                'model' => 'Not enough data',
            ];
        }

        $train = $series->slice(0, -$holdout)->values();
        $actual = $series->slice(-$holdout)->values();
        $nTrain = $train->count();

        // Fit the same weighted regression style used for the real forecast,
        // but ONLY on the train slice, then project forward $holdout days.
        $sumW = $sumWX = $sumWY = $sumWXX = $sumWXY = 0.0;
        foreach ($train as $index => $y) {
            $x = $index + 1;
            $w = exp($index / max($nTrain, 1));
            $sumW   += $w;
            $sumWX  += $w * $x;
            $sumWY  += $w * $y;
            $sumWXX += $w * $x * $x;
            $sumWXY += $w * $x * $y;
        }
        $meanY = $train->avg() ?: 0.0;
        $slope = 0.0;
        $intercept = $meanY;
        $denominator = ($sumW * $sumWXX) - ($sumWX * $sumWX);
        if ($denominator != 0) {
            $slope = (($sumW * $sumWXY) - ($sumWX * $sumWY)) / $denominator;
            $intercept = ($sumWY - ($slope * $sumWX)) / $sumW;
        }

        $phi = 0.98; // same damping factor as the live daily forecast
        $modelPredicted = [];
        $trendCumulative = 0.0;
        for ($i = 0; $i < $holdout; $i++) {
            $trendCumulative += pow($phi, $i + 1) * $slope;
            $modelPredicted[] = max($meanY + $trendCumulative, $meanY * 0.3);
        }

        // True naive baseline: average of the train window's last 7 days —
        // built entirely from data that predates the test window.
        $naiveValue = $train->slice(-7)->avg() ?: $meanY;
        $benchmarkPredicted = array_fill(0, $holdout, (float)$naiveValue);

        $modelErrors = $benchmarkErrors = $modelAbsPct = $benchmarkAbsPct = [];
        foreach ($actual as $idx => $value) {
            $modelErrors[] = abs($value - $modelPredicted[$idx]);
            $benchmarkErrors[] = abs($value - $benchmarkPredicted[$idx]);
            if ($value != 0) {
                $modelAbsPct[] = abs(($value - $modelPredicted[$idx]) / $value) * 100;
                $benchmarkAbsPct[] = abs(($value - $benchmarkPredicted[$idx]) / $value) * 100;
            }
        }

        $mae = count($modelErrors) ? array_sum($modelErrors) / count($modelErrors) : 0.0;
        $rmse = count($modelErrors) ? sqrt(array_sum(array_map(fn($e) => $e ** 2, $modelErrors)) / count($modelErrors)) : 0.0;
        $mape = count($modelAbsPct) ? array_sum($modelAbsPct) / count($modelAbsPct) : 0.0;

        $benchmarkMae = count($benchmarkErrors) ? array_sum($benchmarkErrors) / count($benchmarkErrors) : 0.0;
        $benchmarkMape = count($benchmarkAbsPct) ? array_sum($benchmarkAbsPct) / count($benchmarkAbsPct) : 0.0;
        $improvement = $benchmarkMape > 0 ? (($benchmarkMape - $mape) / $benchmarkMape) * 100 : 0.0;

        return [
            'mae' => round($mae, 2),
            'rmse' => round($rmse, 2),
            'mape' => round($mape, 2),
            'benchmark_mae' => round($benchmarkMae, 2),
            'benchmark_mape' => round($benchmarkMape, 2),
            'model_improvement_pct' => round($improvement, 2), // can now go negative — that's honest
            'baseline' => 'Last 7 days of training window (naive)',
            'model' => 'Weighted regression, backtested on held-out week',
        ];
    }

    private function calculateRegression(string $type, int $historyCount, int $predictCount)
    {
        // Forecasting always trains on a fixed rolling lookback window,
        // independent of the report's date-range filter.
        $lookbackStart = $type === 'daily'
            ? Carbon::now()->subDays($historyCount)->startOfDay()
            : Carbon::now()->subMonths($historyCount)->startOfMonth();

                // Exclude the current, still-in-progress period. Its total is
        // artificially low (day/month isn't over yet) and, since the
        // regression weights recent periods exponentially higher, an
        // incomplete period would systematically drag the forecast down —
        // the same issue calculateForecastAccuracy() already guards against.
        $periodEnd = $type === 'daily'
            ? Carbon::yesterday()->endOfDay()
            : Carbon::now()->startOfMonth()->subSecond(); // end of last full month

        $query = Order::whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED])
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId))
            ->where('created_at', '>=', $lookbackStart)
            ->where('created_at', '<=', $periodEnd);

        if ($type === 'daily') {
            $data = (clone $query)
                ->selectRaw('DATE(created_at) as label, SUM(total_amount) as total')
                ->groupBy('label')
                ->orderBy('label', 'asc')
                ->get();
        } else {
            $data = (clone $query)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as label, SUM(total_amount) as total")
                ->groupBy('label')
                ->orderBy('label', 'asc')
                ->get();
        }

        $n = $data->count();
        if ($n < 2) {
            return ['forecast' => [], 'trend' => 'Insufficient Data', 'growth_rate' => 0.0, 'confidence' => 'Insufficient Data', 'baseline_avg' => 0];
        }

        // ── Step 1: Remove outliers using IQR method ──────────────────
        $values = $data->pluck('total')->map(fn($v) => (float)$v)->sort()->values();
        $q1 = $values[(int)floor($n * 0.25)];
        $q3 = $values[(int)floor($n * 0.75)];
        $iqr = $q3 - $q1;
        $lowerFence = $q1 - 1.5 * $iqr;
        $upperFence = $q3 + 1.5 * $iqr;
        $cleanData = $data->filter(fn($row) => (float)$row->total >= $lowerFence && (float)$row->total <= $upperFence)->values();
        $nClean = $cleanData->count();
        if ($nClean < 2) $cleanData = $data; // fallback if too many removed
        $nClean = $cleanData->count();

        $meanY = $cleanData->avg('total') ?: 0.0;

        // A trend line fitted on only a couple of data points is not
        // trustworthy to extrapolate — that's exactly what produced the
        // ₱0 6-month forecast (a 2-point line extrapolated 6 steps out,
        // hit zero, and got clamped there). Below this threshold we skip
        // the trend and forecast off the recent average instead.
        $minPeriodsForTrend = $type === 'daily' ? 5 : 3;
        $hasReliableTrend = $nClean >= $minPeriodsForTrend;

        $slope = 0.0;
        $intercept = $meanY;

        if ($hasReliableTrend) {
            // ── Step 2: Weighted Linear Regression (recent data weighs more) ──
            $sumW = $sumWX = $sumWY = $sumWXX = $sumWXY = 0.0;
            foreach ($cleanData as $index => $row) {
                $x = $index + 1;
                $y = (float)$row->total;
                $w = exp($index / max($nClean, 1));
                $sumW   += $w;
                $sumWX  += $w * $x;
                $sumWY  += $w * $y;
                $sumWXX += $w * $x * $x;
                $sumWXY += $w * $x * $y;
            }
            $denominator = ($sumW * $sumWXX) - ($sumWX * $sumWX);
            if ($denominator != 0) {
                $slope     = (($sumW * $sumWXY) - ($sumWX * $sumWY)) / $denominator;
                $intercept = ($sumWY - ($slope * $sumWX)) / $sumW;
            }
        }

        // ── Step 3: Day-of-week seasonality indices (daily only) ──────
        $dowIndices = array_fill(1, 7, 1.0);
        if ($type === 'daily' && $n >= 14) {
            $dowSums   = array_fill(1, 7, 0.0);
            $dowCounts = array_fill(1, 7, 0);
            $grandAvg  = $data->avg('total') ?: 1;
            foreach ($data as $row) {
                $dow = (int)Carbon::parse($row->label)->dayOfWeekIso; // 1=Mon..7=Sun
                $dowSums[$dow]   += (float)$row->total;
                $dowCounts[$dow] += 1;
            }
            foreach ($dowIndices as $d => $_) {
                $avg = $dowCounts[$d] > 0 ? $dowSums[$d] / $dowCounts[$d] : $grandAvg;
                $dowIndices[$d] = $grandAvg > 0 ? $avg / $grandAvg : 1.0;
            }
        }

        // ── Step 4: Generate forecast with damped trend + seasonality ──
        // Damped trend (Gardner & McKenzie): each successive period's
        // trend contribution is discounted by phi^step and accumulated
        // around the recent mean, so the curve bends back toward normal
        // the further out it forecasts instead of diverging to 0 or
        // running away indefinitely. phi is lower for the 6-month model
        // since a short trend has even less business being projected
        // that far out at full strength.
        $phi = $type === 'daily' ? 0.98 : 0.85;

        $avgDailySales = $data->take(-7)->avg('total') ?: 0;
        $forecast = [];
        $startDate = $type === 'daily' ? Carbon::tomorrow() : Carbon::now()->addMonth()->startOfMonth();

        $trendCumulative = 0.0;
        for ($i = 0; $i < $predictCount; $i++) {
            $predictedDate = $type === 'daily'
                ? $startDate->copy()->addDays($i)
                : $startDate->copy()->addMonths($i);

            $trendCumulative += pow($phi, $i + 1) * $slope;
            $baselinePrediction = $meanY + $trendCumulative;

            $seasonalIndex = 1.0;
            if ($type === 'daily') {
                $dow = (int)$predictedDate->dayOfWeekIso;
                $seasonalIndex = $dowIndices[$dow] ?? 1.0;
            }

            $adjusted = $baselinePrediction * $seasonalIndex;
            // Never let a forecast collapse to 0 while the business is
            // clearly still selling — floor it at 30% of the recent mean.
            $adjusted = max($adjusted, $meanY * 0.3);

            $forecast[] = [
                'date'      => $type === 'daily' ? $predictedDate->format('M d') : $predictedDate->format('M Y'),
                'day'       => strtoupper($predictedDate->format('D')),
                'predicted' => round($adjusted, 2),
            ];
        }

        // ── Step 5: Confidence & trend scoring ─────────────────────────
        $ssTot = $cleanData->sum(fn($row) => pow((float)$row->total - $meanY, 2));
        $ssRes = 0.0;
        foreach ($cleanData as $index => $row) {
            $predicted = ($slope * ($index + 1)) + $intercept;
            $ssRes += pow((float)$row->total - $predicted, 2);
        }
        $rSquared = $ssTot > 0 ? max(0, 1 - ($ssRes / $ssTot)) : 0;

        $confidence = match(true) {
            !$hasReliableTrend           => 'Insufficient Data',
            $n >= 60 && $rSquared >= 0.7 => 'High',
            $n >= 30 && $rSquared >= 0.4 => 'Medium',
            $n >= 14                     => 'Low',
            default                      => 'Insufficient Data',
        };

        // Relative to the recent average rather than a fixed peso amount,
        // so this classification makes sense whether daily sales run in
        // the hundreds or the hundred-thousands.
        $relativeSlope = $meanY > 0 ? ($slope / $meanY) : 0;
        $trendLabel = match(true) {
            !$hasReliableTrend    => 'Insufficient Data',
            $relativeSlope > 0.03  => 'Upward',
            $relativeSlope < -0.03 => 'Downward',
            default                 => 'Stable',
        };

        return [
            'forecast'     => $forecast,
            'trend'        => $trendLabel,
            'growth_rate'  => round($slope, 2),
            'confidence'   => $confidence,
            'baseline_avg' => round($avgDailySales, 2),
            'r_squared'    => round($rSquared, 3),
            'peak_day'     => collect($forecast)->sortByDesc('predicted')->first()['day'] ?? 'N/A',
        ];
    }

        private function getIngredientDemandForecast(?array $shortTermForecast = null)
    {
        $start = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : null;
        $end = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : null;

        if (!$start || !$end) {
            $rangeQuery = Order::where('status', Order::STATUS_COMPLETED)
                ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId));

            $start = $start ?: ($rangeQuery->min('created_at') ? Carbon::parse($rangeQuery->min('created_at'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay());
            $end = $end ?: ($rangeQuery->max('created_at') ? Carbon::parse($rangeQuery->max('created_at'))->endOfDay() : Carbon::now()->endOfDay());
        }

        $daysInRange = max(1, $start->diffInDays($end) + 1);

        // Reuse the short-term forecast already computed in getForecastingData()
        // instead of re-running the same regression query a second time.
        $forecast = $shortTermForecast ?? $this->calculateRegression('daily', 60, 7);
        $forecastedTotal = collect($forecast['forecast'])->sum('predicted');
        $baselineTotal = max(1, ($forecast['baseline_avg'] ?? 0) * 7);
        $forecastGrowthFactor = min(max($forecastedTotal / $baselineTotal, 0.85), 1.35);

        $topProducts = OrderItem::whereHas('order', function($q) use ($start, $end) {
                $q->where('status', Order::STATUS_COMPLETED)
                  ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId))
                  ->when($start, fn($q) => $q->where('created_at', '>=', $start))
                  ->when($end, fn($q) => $q->where('created_at', '<=', $end));
            })
            ->select('product_id', DB::raw('SUM(quantity) / ' . $daysInRange . ' as daily_avg'))
            ->groupBy('product_id')
            ->orderBy('daily_avg', 'desc')
            ->with('product.recipes.ingredient')
            ->get();

        $ingredientDemand = [];

        foreach ($topProducts as $tp) {
            if (!$tp->product || !$tp->product->recipes) continue;

            $projectedUnits = $tp->daily_avg * 14 * $forecastGrowthFactor * 1.15;

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

        if (empty($ingredientDemand)) {
            $recentSales = Order::where('status', Order::STATUS_COMPLETED)
                ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId))
                ->when($start, fn($q) => $q->where('created_at', '>=', $start))
                ->when($end, fn($q) => $q->where('created_at', '<=', $end))
                ->sum('total_amount');

            $baseDemand = max(18, (float)($recentSales / max(1, $daysInRange)) * 0.18 * $forecastGrowthFactor);

                        return [
                [
                    'id' => 0,
                    'name' => 'Takoyaki Base Mix',
                    'unit' => 'packs',
                    'amount' => (int) ceil($baseDemand * 1.8),
                    'priority' => 'High',
                ],
                [
                    'id' => 0,
                    'name' => 'Sauce & Toppings',
                    'unit' => 'bottles',
                    'amount' => (int) ceil($baseDemand * 1.2),
                    'priority' => 'Medium',
                ],
                [
                    'id' => 0,
                    'name' => 'Packaging Buffer',
                    'unit' => 'pcs',
                    'amount' => (int) ceil($baseDemand * 0.9),
                    'priority' => 'Medium',
                ],
            ];
        }

        return collect($ingredientDemand)
            ->sortByDesc('amount')
            ->take(15)
            ->map(function($item) {
                $item['amount'] = ceil($item['amount']);
                if ($item['amount'] > 25) $item['priority'] = 'High';
                return $item;
            })
            ->values()
            ->toArray();
    }

    /**
     * Historical waste rate per ingredient over a fixed 30-day lookback
     * (independent of the report's date filter, matching the forecasting
     * window) — used to shrink the safety-stock buffer for ingredients that
     * spoil often, since padding those orders just grows what gets wasted.
     */
    private function computeWasteRates(int $branchId, array $ingredientIds): array
    {
        if (empty($ingredientIds)) return [];

        $lookbackStart = Carbon::now()->subDays(30)->startOfDay();

        $movements = StockMovement::where('branch_id', $branchId)
            ->whereIn('ingredient_id', $ingredientIds)
            ->whereIn('type', ['waste', 'waste_expired', 'order', 'out'])
            ->where('created_at', '>=', $lookbackStart)
            ->get(['ingredient_id', 'type', 'quantity']);

        $rates = [];
        foreach ($ingredientIds as $ingredientId) {
            $ingredientMovements = $movements->where('ingredient_id', $ingredientId);
            $wasted = $ingredientMovements->whereIn('type', ['waste', 'waste_expired'])
                ->sum(fn ($m) => abs((float) $m->quantity));
            $consumed = $ingredientMovements->sum(fn ($m) => abs((float) $m->quantity));

            $rates[$ingredientId] = $consumed > 0 ? $wasted / $consumed : 0.0;
        }

        return $rates;
    }

    private function buildPrescriptiveRecommendations(array $forecasting): array
    {
        if ($this->selectedBranchId === 'all') {
            return [[
                'type' => 'scope',
                'priority' => 'medium',
                'title' => 'Select a branch for purchase recommendations',
                'reason' => 'Current stock is branch-specific, so a network-wide forecast cannot produce a safe purchase quantity.',
                'action_label' => 'Choose branch',
                'actionable' => false,
            ]];
        }

        $insights = $forecasting['restock_insights'] ?? [];
        $ingredientIds = collect($insights)->pluck('id')->filter(fn ($id) => (int) $id > 0)->map(fn ($id) => (int) $id)->all();
        $stock = empty($ingredientIds)
            ? collect()
            : Product::getUnexpiredStocks((int) $this->selectedBranchId, $ingredientIds);
        $wasteRates = $this->computeWasteRates((int) $this->selectedBranchId, $ingredientIds);

        return collect($insights)
            ->filter(fn ($item) => (int) ($item['id'] ?? 0) > 0)
            ->map(function (array $item) use ($stock, $wasteRates) {
                $currentStock = max(0, (float) ($stock[(int) $item['id']] ?? 0));

                // Historical sales understate true demand while an ingredient sits
                // at zero — there's nothing to sell. Nudge the projection up for
                // ingredients that are out right now so the recommendation doesn't
                // perpetually under-order based on suppressed sales.
                $stockoutSuppressed = $currentStock <= 0;
                $projectedDemand = max(0, (float) ($item['amount'] ?? 0));
                if ($stockoutSuppressed) {
                    $projectedDemand *= 1.3;
                }

                // High-waste ingredients don't get the same flat 15% safety buffer —
                // padding the order just grows the amount that ends up spoiling.
                $wasteRate = min(0.5, max(0, $wasteRates[(int) $item['id']] ?? 0));
                $safetyStockPct = max(0.05, 0.15 - ($wasteRate * 0.5));
                $safetyStock = (int) ceil($projectedDemand * $safetyStockPct);

                $recommendedQuantity = max(0, (int) ceil($projectedDemand + $safetyStock - $currentStock));
                $dailyDemand = max(0.01, $projectedDemand / 14);
                $coverageDays = $currentStock > 0 ? round($currentStock / $dailyDemand, 1) : 0;

                $priority = match (true) {
                    $recommendedQuantity <= 0 => 'low',
                    $currentStock <= 0 => 'critical',
                    $coverageDays <= 3 => 'high',
                    $coverageDays <= 7 => 'medium',
                    default => 'low',
                };

                $reason = $recommendedQuantity > 0
                    ? "Projected 14-day demand is {$projectedDemand} {$item['unit']}; current stock covers about {$coverageDays} days."
                    : "Current stock covers the projected demand plus safety buffer.";
                if ($stockoutSuppressed) {
                    $reason .= ' Demand estimate increased — this ingredient is currently out of stock, which likely suppressed recent sales.';
                }
                if ($wasteRate > 0.1) {
                    $reason .= ' Safety buffer reduced due to a history of spoilage for this ingredient.';
                }

                return [
                    'type' => 'restock',
                    'priority' => $priority,
                    'title' => $recommendedQuantity > 0
                        ? "Restock {$item['name']}"
                        : "Monitor {$item['name']}",
                    'reason' => $reason,
                    'unit' => $item['unit'],
                    'current_stock' => round($currentStock, 2),
                    'current_stock_display' => $this->formatInventoryQuantity($currentStock, $item['unit']),
                    'projected_demand' => round($projectedDemand, 2),
                    'projected_demand_display' => $this->formatInventoryQuantity($projectedDemand, $item['unit']),
                    'safety_stock' => $safetyStock,
                    'safety_stock_display' => $this->formatInventoryQuantity((float) $safetyStock, $item['unit']),
                    'coverage_days' => $coverageDays,
                    'recommended_quantity' => $recommendedQuantity,
                    'recommended_quantity_display' => $this->formatInventoryQuantity((float) $recommendedQuantity, $item['unit']),
                    'ingredient_id' => (int) $item['id'],
                    'stockout_suppressed' => $stockoutSuppressed,
                    'waste_rate_pct' => round($wasteRate * 100, 1),
                    'action_label' => $recommendedQuantity > 0 ? 'Open stock workflow' : 'Review stock',
                    'actionable' => true,
                ];
            })
            ->sortByDesc(fn ($item) => match ($item['priority']) {
                'critical' => 4,
                'high' => 3,
                'medium' => 2,
                default => 1,
            })
            ->values()
            ->all();
    }

    private function getPrescriptiveRecommendations(array $forecasting): LengthAwarePaginator
    {
        $recommendations = $this->buildPrescriptiveRecommendations($forecasting);
        $currentPage = $this->getPage('prescriptivePage');

        return new LengthAwarePaginator(
            collect($recommendations)->forPage($currentPage, $this->prescriptivePerPage)->values(),
            count($recommendations),
            $this->prescriptivePerPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'prescriptivePage',
            ]
        );
    }

    public function loadNetworkRestockSummary(): void
    {
        if ((!auth()->user()->isSuperAdmin() && auth()->user()->role_id !== 1) || $this->selectedBranchId !== 'all') {
            return;
        }

        $this->networkRestockCache = null;
        $this->networkRestockSummary = $this->getNetworkRestockSummary();
    }

    /**
     * Aggregates each branch's ingredient demand forecast into one network-wide
     * total. Re-runs the regression once per branch to aggregate per-branch demand.
     */
    private function getNetworkRestockSummary(): array
    {
        if ($this->networkRestockCache !== null) {
            return $this->networkRestockCache;
        }

        $branchIds = Branch::pluck('id');
        $totals = [];
        $originalBranch = $this->selectedBranchId;

        foreach ($branchIds as $branchId) {
            $this->selectedBranchId = (string) $branchId;

            $shortTerm = $this->calculateRegression('daily', 60, 7);
            $insights = $this->getIngredientDemandForecast($shortTerm);

            foreach ($insights as $item) {
                if ((int) ($item['id'] ?? 0) <= 0) continue;

                $id = (int) $item['id'];
                if (!isset($totals[$id])) {
                    $totals[$id] = [
                        'id' => $id,
                        'name' => $item['name'],
                        'unit' => $item['unit'],
                        'amount' => 0,
                        'branch_count' => 0,
                    ];
                }
                $totals[$id]['amount'] += $item['amount'];
                $totals[$id]['branch_count']++;
            }
        }

        $this->selectedBranchId = $originalBranch;

        $this->networkRestockCache = collect($totals)
            ->sortByDesc('amount')
            ->take(15)
            ->map(function ($item) {
                $item['amount'] = ceil($item['amount']);
                return $item;
            })
            ->values()
            ->all();

        return $this->networkRestockCache;
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
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : null;
        $endDate = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : null;

        $orderRange = Order::where('status', Order::STATUS_COMPLETED)
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId));

        if (!$startDate) {
            $startDate = $orderRange->min('created_at') ? Carbon::parse($orderRange->min('created_at'))->startOfDay() : Carbon::now()->startOfDay();
        }
        if (!$endDate) {
            $endDate = $orderRange->max('created_at') ? Carbon::parse($orderRange->max('created_at'))->endOfDay() : Carbon::now()->endOfDay();
        }

        $topProductIds = OrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
                $q->where('status', Order::STATUS_COMPLETED)
                  ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId))
                  ->when($startDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]));
            })
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->take(3)
            ->pluck('product_id');

        if ($topProductIds->isEmpty()) return [];

        $seasonalityData = [];
$products = \App\Models\Product::whereIn('id', $topProductIds)->get()->keyBy('id');

foreach ($topProductIds as $pid) {
    $product = $products[$pid] ?? null;
    if (!$product) continue;

    $dowSales = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $pid)
                ->where('orders.status', Order::STATUS_COMPLETED)
                ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('orders.branch_id', $this->selectedBranchId))
                ->when($startDate, fn($q) => $q->where('orders.created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('orders.created_at', '<=', $endDate))
                                ->selectRaw("(DAYOFWEEK(orders.created_at) - 1) as dow, SUM(order_items.quantity) as total")
                ->groupBy('dow')
                ->pluck('total', 'dow');

            $days = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
            $formattedData = [];
            foreach ($days as $index => $day) {
                $dowValue = $index === 0 ? 0 : $index;
                $formattedData[] = ['label' => $day, 'value' => (float)($dowSales[$dowValue] ?? 0)];
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
        $startDate = $this->startDate ? Carbon::parse($this->startDate)->startOfDay() : null;
        $endDate   = $this->endDate ? Carbon::parse($this->endDate)->endOfDay() : null;

        $orderRange = Order::where('status', Order::STATUS_COMPLETED)
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId));

        if (!$startDate) {
            $startDate = $orderRange->min('created_at') ? Carbon::parse($orderRange->min('created_at'))->startOfDay() : Carbon::now()->startOfDay();
        }
        if (!$endDate) {
            $endDate = $orderRange->max('created_at') ? Carbon::parse($orderRange->max('created_at'))->endOfDay() : Carbon::now()->endOfDay();
        }

        $topProductIds = OrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
                $q->where('status', Order::STATUS_COMPLETED)
                  ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId))
                  ->when($startDate, fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]));
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
$products = \App\Models\Product::whereIn('id', $topProductIds)->get()->keyBy('id');

foreach ($topProductIds as $pid) {
    $product = $products[$pid] ?? null;
    if (!$product) continue;

    $monthlySales = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $pid)
                ->where('orders.status', Order::STATUS_COMPLETED)
                ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('orders.branch_id', $this->selectedBranchId))
                ->when($startDate, fn($q) => $q->where('orders.created_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('orders.created_at', '<=', $endDate))
                                ->selectRaw("MONTH(orders.created_at) as month_num, SUM(order_items.quantity) as total")
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

        // Build an hours intensity payload (0-23) for charting
        $hoursMap = collect(range(0,23))->mapWithKeys(fn($h) => [str_pad((string)$h, 2, '0', STR_PAD_LEFT) => 0])->toArray();
        foreach ($hourlySales as $hs) {
            $key = str_pad((string)$hs->hour, 2, '0', STR_PAD_LEFT);
            $hoursMap[$key] = $hs->count;
        }

        $categories = array_map(fn($h) => $h . ':00', array_keys($hoursMap));
        $counts = array_values($hoursMap);

        $hoursIntensity = [
            'categories' => $categories,
            'counts' => $counts,
            'has_data' => array_sum($counts) > 0,
        ];


                $branchPerformance = [];
        $globalNetworkTotal = 0;

        if (auth()->user()->role_id === 1) {
            // Match the Performance tab's branch comparison, which counts
            // Completed + Refunded + Partially Refunded orders as revenue.
            $allOrders = $analytics['orders'] ?? $completedOrders;
            $globalNetworkTotal = $allOrders->sum('total_amount');
            
            $branchPerformance = Order::whereIn('id', $allOrders->pluck('id'))
                ->selectRaw('branch_id, SUM(total_amount) as revenue, COUNT(*) as count')
                ->groupBy('branch_id')
                ->with('branch')
                ->paginate($this->perPage, ['*'], 'branchPage');
        }

        return [
            'hourly_sales' => $hourlySales,
            'hours_intensity' => $hoursIntensity,
            'branch_performance' => $branchPerformance,
            'global_network_total' => $globalNetworkTotal ?: 1,
        ];
    }


    private function getRecentOrders()
    {
        return Order::with(['branch', 'user'])
            ->when($this->selectedBranchId !== 'all', fn($q) => $q->where('branch_id', (int)$this->selectedBranchId))
            ->when($this->startDate, fn($q) => $q->where('created_at', '>=', Carbon::parse($this->startDate)->startOfDay()))
            ->when($this->endDate, fn($q) => $q->where('created_at', '<=', Carbon::parse($this->endDate)->endOfDay()))
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

    public function exportPrescriptiveCsv()
    {
        $forecasting = $this->getForecastingData();
        $recommendations = collect($this->buildPrescriptiveRecommendations($forecasting))
            ->filter(fn ($r) => $r['actionable'] ?? false)
            ->map(fn ($r) => [
                'Ingredient' => $r['title'],
                'Unit' => $r['unit'] ?? '',
                'Current Stock' => $r['current_stock'] ?? 0,
                'Projected 14-Day Demand' => $r['projected_demand'] ?? 0,
                'Safety Stock' => $r['safety_stock'] ?? 0,
                'Recommended Quantity' => $r['recommended_quantity'] ?? 0,
                'Coverage (Days)' => $r['coverage_days'] ?? 0,
                'Priority' => ucfirst($r['priority'] ?? ''),
            ])
            ->values()
            ->all();

        return $this->generateCsvReport('Restock_List_' . now()->format('Y-m-d') . '.csv', $recommendations);
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

        $trendRows = collect(array_map(null, $analytics['trend']['categories'] ?? [], $analytics['trend']['gross'] ?? []))
            ->map(fn($pair) => [
                $pair[0] ?? '',
                'PHP ' . number_format($pair[1] ?? 0, 2),
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

        $topItemRows = collect($analytics['top_items'] ?? [])->map(fn($i) => [
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
