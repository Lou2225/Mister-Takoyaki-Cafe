<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\BranchIngredientStock;
use App\Models\StockOrder;
use App\Models\StockOrderItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockMovement;
use App\Models\Notification;
use App\Models\User;
use App\Helpers\StockHelper;
use App\Services\BranchContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\StockOrderMail;
use App\Traits\HandlesValidations;
use App\Traits\HandlesExports;
use App\Traits\RequiresOperatingBranch;

class BranchStockOrderAdmin extends Component
{
    use WithPagination, HandlesValidations, HandlesExports, RequiresOperatingBranch;

    // ── Panel / View State ────────────────────────────────────────
    public $panel = 'inbox';

    // ── Detail Side Panel ─────────────────────────────────────────
    public $selectedOrderId   = null;
    public $selectedOrder     = null;

    // ── Approval / Rejection State ────────────────────────────────
    public $rejectionReason    = '';
    public $adminRemarks       = '';
    public $deliveryFee        = 0;
    public $suggestedFee       = 0;
    public $editItemQuantities = [];

    // ── Logistics Management ──────────────────────────────────────
    public $baseFee           = 0;
    public $globalRate        = 50; // default PHP per KM
    public $minFee            = 0;
    public $maxFee            = 9999;
    public $freeThreshold     = 0;
    public $branches;
    public $branchDistances   = []; // branch_id => distance

    // ── Filters ───────────────────────────────────────────────────
    public $search          = '';
    public $branchFilter    = '';
    public $priorityFilter  = '';
    public $statusFilter    = 'all';
    public $perPage         = 10;
    public $startDate       = '';
    public $endDate         = '';
    public $activeFilter    = 'All Time';
    public $lastPendingCount = null;

    protected $queryString = [
        'panel'         => ['except' => 'inbox'],
        'search'        => ['except' => '', 'as' => 'oa_search'],
        'branchFilter'  => ['except' => '', 'as' => 'oa_branch'],
        'statusFilter'  => ['except' => 'all', 'as' => 'oa_status'],
        'startDate'     => ['except' => '', 'as' => 'oa_start'],
        'endDate'       => ['except' => '', 'as' => 'oa_end'],
        'activeFilter'  => ['except' => 'All Time', 'as' => 'oa_filter'],
    ];

    protected $listeners = [
        'refreshAdminOrders' => '$refresh',
        'order-submitted' => '$refresh',
    ];

    // ── Initialization ────────────────────────────────────────────

    public function updatedStartDate()
    {
        $this->resetPage('inboxPage');
        $this->resetPage('activePage');
        $this->resetPage('historyPage');
    }

    public function updatedEndDate()
    {
        $this->resetPage('inboxPage');
        $this->resetPage('activePage');
        $this->resetPage('historyPage');
    }

    public function mount()
    {
        $this->guardMainBranchContext();

        $this->baseFee       = \App\Models\SystemSetting::get('logistics_base_fee', 0);
        $this->globalRate    = \App\Models\SystemSetting::get('logistics_global_rate', 50);
        $this->minFee        = \App\Models\SystemSetting::get('logistics_min_fee', 0);
        $this->maxFee        = \App\Models\SystemSetting::get('logistics_max_fee', 5000);
        $this->freeThreshold = \App\Models\SystemSetting::get('logistics_free_threshold', 0);
        
        $this->loadLogistics();
        $this->updateHeader();
        $this->lastPendingCount = StockOrder::where('status', 'pending')->count();
    }

    public function loadLogistics()
    {
        // Distances are now automatically computed in Branch model/BranchManagement
        // We only load them for display here.
        $this->branches = Branch::where('is_main', false)->get();
    }

    // ── Role Helpers ──────────────────────────────────────────────

    public function isSuperAdmin(): bool { return auth()->user()->role_id === 1; }
    public function isAdmin(): bool      { return auth()->user()->role_id === 2; }

    // ── Order Detail Panel ────────────────────────────────────────

    public function viewOrder(int $id): void
    {
        $this->selectedOrderId = $id;
        $order = StockOrder::with(['items.ingredient', 'requestingBranch', 'sourceBranch', 'requester', 'approver'])
            ->findOrFail($id);
        $this->selectedOrder = $order;

        // Calculate initial subtotal
        $itemsSubtotal = 0;
        foreach ($order->items as $item) {
            $qty = $item->approved_quantity ?? $item->requested_quantity;
            $itemsSubtotal += $qty * $item->unit_price;
        }

        // Suggested Fee Calculation
        if ($itemsSubtotal >= (float)$this->freeThreshold && (float)$this->freeThreshold > 0) {
            $this->suggestedFee = 0;
        } else {
            $distance = (float)($order->requestingBranch->distance_from_main ?? 0);
            $computedFee = (float)$this->baseFee + ($distance * (float)$this->globalRate);
            $this->suggestedFee = max((float)$this->minFee, min((float)$this->maxFee, $computedFee));
        }

        // Pre-fill delivery fee: Use existing or suggested
        $this->deliveryFee = $order->delivery_fee > 0 ? $order->delivery_fee : $this->suggestedFee;
        
        $this->editItemQuantities = [];
        foreach ($order->items as $item) {
            $this->editItemQuantities[$item->id] = number_format((float)($item->approved_quantity ?? $item->requested_quantity), 2, '.', '');
        }

        $this->dispatch('open-modal', name: 'fulfillment-review');
    }

    public function closeDetail(): void
    {
        $this->selectedOrderId = null;
        $this->selectedOrder   = null;
        $this->editItemQuantities = [];
        $this->dispatch('close-modal', 'fulfillment-review');
    }

    // ── Logistics Management ──────────────────────────────────────

    public function updateLogistics()
    {
        \App\Models\SystemSetting::set('logistics_base_fee', (float)$this->baseFee);
        \App\Models\SystemSetting::set('logistics_global_rate', (float)$this->globalRate);
        \App\Models\SystemSetting::set('logistics_min_fee', (float)$this->minFee);
        \App\Models\SystemSetting::set('logistics_max_fee', (float)$this->maxFee);
        \App\Models\SystemSetting::set('logistics_free_threshold', (float)$this->freeThreshold);

        // Refresh all branch distances to ensure data accuracy
        $this->syncAllDistances();
        
        $this->dispatch('notify', 
            type: 'success',
            message: 'Logistics rules updated and network distances synchronized.'
        );
    }

    public function syncAllDistances()
    {
        $mainBranch = Branch::where('is_main', true)->first();
        if (!$mainBranch) return;

        $mainAddr = is_array($mainBranch->address) ? $mainBranch->address : json_decode($mainBranch->address, true);
        $mainLat = $mainAddr['lat'] ?? 0;
        $mainLng = $mainAddr['lng'] ?? 0;

        $branches = Branch::where('id', '!=', $mainBranch->id)->get();
        foreach($branches as $branch) {
            $addr = is_array($branch->address) ? $branch->address : json_decode($branch->address, true);
            $dist = $this->calculateDistance($mainLat, $mainLng, $addr['lat'] ?? 0, $addr['lng'] ?? 0);
            $branch->update(['distance_from_main' => $dist]);
        }
        
        $this->loadLogistics();
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        if (!$lat1 || !$lon1 || !$lat2 || !$lon2) return 0;
        $earthRadius = 6371; // KM
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return round($earthRadius * $c, 2);
    }

    // ── Approve Order ─────────────────────────────────────────────

    public function approveOrder(int $id): void
    {
        $order = StockOrder::with(['items.ingredient'])->findOrFail($id);

        if (!$order->isPending()) {
            $this->notify('error', 'Order is no longer pending.');
            return;
        }

        $mainBranch = Branch::where('is_main', true)->first();
        if (!$mainBranch) {
            $this->notify('error', 'Main branch not configured.');
            return;
        }

        $insufficientItems = [];
        foreach ($order->items as $item) {
            $approvedQty = $this->editItemQuantities[$item->id] ?? $item->requested_quantity;
            $mainStock   = BranchIngredientStock::where('branch_id', $mainBranch->id)
                ->where('ingredient_id', $item->ingredient_id)
                ->value('stock_quantity') ?? 0;

            $qtyInBase = $item->ingredient
                ? StockHelper::convertToBase((float)$approvedQty, $item->order_unit, $item->ingredient)
                : (float)$approvedQty;

            if ((float)$mainStock < $qtyInBase) {
                $insufficientItems[] = $item->ingredient->name;
            }
        }

        if (!empty($insufficientItems)) {
            $this->notify('error', 'Insufficient stock for: ' . implode(', ', $insufficientItems));
            return;
        }

        DB::transaction(function () use ($order) {
            $itemsSubtotal = 0;
            foreach ($order->items as $item) {
                $approvedQty = $this->editItemQuantities[$item->id] ?? $item->requested_quantity;
                $itemSubtotal = $approvedQty * $item->unit_price;
                $item->update([
                    'approved_quantity' => $approvedQty,
                    'subtotal'          => $itemSubtotal
                ]);
                $itemsSubtotal += $itemSubtotal;
            }

            $order->update([
                'status'        => 'approved',
                'approved_by'   => auth()->id(),
                'approved_at'   => now(),
                'delivery_fee'  => (float) $this->deliveryFee,
                'total_amount'  => $itemsSubtotal + (float) $this->deliveryFee,
                'admin_remarks' => $this->adminRemarks ?: null,
            ]);

            $this->notifyBranch($order, '✅ Stock Request Approved', "Your request {$order->reference_no} has been approved.");
        });

        $this->adminRemarks = '';
        $this->closeDetail();
        $this->notify('success', "Order {$order->reference_no} approved.");
    }

    public function rejectOrder(): void
    {
        $this->validate(['rejectionReason' => 'required|string|min:5']);

        $order = StockOrder::findOrFail($this->selectedOrderId);
        $order->update([
            'status'           => 'rejected',
            'rejection_reason' => $this->rejectionReason,
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        $this->notifyBranch($order, '❌ Stock Request Rejected', "Your request {$order->reference_no} was rejected. Reason: {$this->rejectionReason}");

        $this->rejectionReason = '';
        $this->closeDetail();
        $this->dispatch('close-modal', 'confirm-reject-order');
        $this->notify('success', "Order rejected.");
    }

    public function markPreparing(int $id): void
    {
        $order = StockOrder::findOrFail($id);
        if ($order->isApproved()) {
            $order->update(['status' => 'preparing']);
            $this->notifyBranch($order, '🔧 Order Being Prepared', "Your request {$order->reference_no} is now being prepared.");
            $this->notify('info', "Marked as preparing.");
            $this->closeDetail();
        }
    }

    public function dispatchOrder(): void
    {
        $order = StockOrder::with(['items.ingredient'])->findOrFail($this->selectedOrderId);
        $mainBranch = Branch::where('is_main', true)->first();

        try {
            DB::transaction(function () use ($order, $mainBranch) {
                $transferRef = StockTransfer::generateReference();
                StockTransfer::create([
                    'stock_order_id'  => $order->id,
                    'reference_no'    => $transferRef,
                    'from_branch_id'  => $mainBranch->id,
                    'to_branch_id'    => $order->requesting_branch_id,
                    'transferred_by'  => auth()->id(),
                    'transferred_at'  => now(),
                ]);

                foreach ($order->items as $item) {
                    $qty = (float) ($item->approved_quantity ?? $item->requested_quantity);
                    $qtyInBase = StockHelper::convertToBase($qty, $item->order_unit, $item->ingredient);
                    
                    $mainStock = BranchIngredientStock::lockForUpdate()->firstOrCreate(
                        ['branch_id' => $mainBranch->id, 'ingredient_id' => $item->ingredient_id],
                        ['stock_quantity' => 0]
                    );
                    $mainStock->stock_quantity -= $qtyInBase;
                    $mainStock->save();

                    $branchStock = BranchIngredientStock::firstOrCreate(
    ['branch_id' => $order->requesting_branch_id, 'ingredient_id' => $item->ingredient_id],
    ['stock_quantity' => 0]
);
$branchStock->stock_quantity += $qtyInBase;
$branchStock->save();

// FIFO deduction: pull from HQ batches oldest-first until qty is fulfilled
$remainingQty = $qtyInBase;

$sourceBatches = \App\Models\StockBatch::where('branch_id', $mainBranch->id)
    ->where('ingredient_id', $item->ingredient_id)
    ->where('current_quantity', '>', 0)
    ->where(function($q) {
        $q->whereNull('expiry_date')
          ->orWhere('expiry_date', '>=', now());
    })
    ->orderBy('expiry_date', 'asc') // FIFO: oldest expiry first
    ->lockForUpdate()
    ->get();

foreach ($sourceBatches as $sourceBatch) {
    if ($remainingQty <= 0) break;

    $deductFromThisBatch = min($remainingQty, $sourceBatch->current_quantity);

    // Deduct from this HQ batch
    $sourceBatch->current_quantity -= $deductFromThisBatch;
    $sourceBatch->save();

        // unit_price is priced per order_unit (e.g. per box/sack), but this
    // batch's quantity is stored in base units (g/ml/pc) — convert so
    // unit_cost matches the unit current_quantity is actually measured in.
    $baseUnitsPerOrderUnit = $item->ingredient
        ? StockHelper::convertToBase(1, $item->order_unit, $item->ingredient)
        : 1;
    $unitCostPerBase = $baseUnitsPerOrderUnit > 0
        ? (float) $item->unit_price / $baseUnitsPerOrderUnit
        : (float) $item->unit_price;

    foreach ($sourceBatches as $sourceBatch) {
        if ($remainingQty <= 0) break;

        $deductFromThisBatch = min($remainingQty, $sourceBatch->current_quantity);

        $sourceBatch->current_quantity -= $deductFromThisBatch;
        $sourceBatch->save();

        \App\Models\StockBatch::create([
            'branch_id'        => $order->requesting_branch_id,
            'ingredient_id'    => $item->ingredient_id,
            'batch_number'     => $transferRef . '-' . $sourceBatch->id,
            'initial_quantity' => $deductFromThisBatch,
            'current_quantity' => $deductFromThisBatch,
            'expiry_date'      => $sourceBatch->expiry_date,
            'unit_cost'        => round($unitCostPerBase, 4),
        ]);

        $remainingQty -= $deductFromThisBatch;
    }

    $remainingQty -= $deductFromThisBatch;
}

                    $this->logMovement($mainBranch->id, $item->ingredient_id, 'transfer_out', $qtyInBase, $order->requesting_branch_id, $transferRef, "Transfer to branch");
                    $this->logMovement($order->requesting_branch_id, $item->ingredient_id, 'transfer_in', $qtyInBase, $mainBranch->id, $transferRef, "Transfer from HQ");
                }

                $order->update(['status' => 'in_transit', 'dispatched_at' => now()]);
                $this->notifyBranch($order, '🚚 Stock Order In Transit', "Your request {$order->reference_no} has been dispatched.");
            });

            $this->closeDetail();
            $this->notify('success', 'Transfer dispatched!');

        } catch (\Exception $e) {
            $this->notify('error', $e->getMessage());
        }
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }

    private function notifyBranch($order, $title, $message): void
    {
        $users = User::where('branch_id', $order->requesting_branch_id)->where('is_active', true)->get();
        foreach ($users as $user) {
            Notification::create([
                'user_id'   => $user->id,
                'branch_id' => $order->requesting_branch_id,
                'type'      => 'stock_order',
                'title'     => $title,
                'message'   => $message,
                'link'      => route('stock.orders', ['so_search' => $order->reference_no]),
            ]);

        }
    }

    private function logMovement($branchId, $ingId, $type, $qty, $fromBranch, $ref, $remarks): void
    {
        StockMovement::create([
            'branch_id'          => $branchId,
            'ingredient_id'      => $ingId,
            'type'               => $type,
            'quantity'           => $qty,
            'from_branch_id'     => $fromBranch,
            'transfer_reference' => $ref,
            'user_id'            => auth()->id(),
            'remarks'            => $remarks,
        ]);
    }

    private function updateHeader(): void
    {
        $this->dispatch('setHeader', 
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
            title: 'Order Inbox',
            breadcrumbs: [['label' => 'Inventory', 'url' => '#'], ['label' => 'Order Inbox', 'url' => route('stock.orders.admin')]]
        );
    }

    public function render()
    {
        $kpis = $this->getKpis();

        if ($this->lastPendingCount !== null && $kpis['pending'] > $this->lastPendingCount) {
            $diff = $kpis['pending'] - $this->lastPendingCount;
            $msg = $diff === 1 ? 'New stock request received.' : "{$diff} new stock requests received.";
            $this->dispatch('notify', type: 'info', message: $msg);
            $this->dispatch('play-chime');
        }
        $this->lastPendingCount = $kpis['pending'];

        return view('livewire.branch-stock-order-admin', [
            'inboxOrders'    => $this->getInboxOrders(),
            'activeOrders'   => $this->getActiveOrders(),
            'historyOrders'  => $this->getHistoryOrders(),
            'kpis'           => $kpis,
            'warehouseStock' => $this->getWarehouseStock(),
            'branches'       => Branch::where('is_main', false)->orderBy('branch_name')->get(),
            'analytics'      => $this->getAnalytics(),
        ])->layout('layouts.app');
    }
    public function updatedPanel($value)
    {
        $this->resetPage();
    }

    private function getInboxOrders() {
        return StockOrder::with(['items.ingredient', 'requestingBranch', 'requester'])
            ->where('status', 'pending')
            ->when($this->startDate && $this->endDate, function($q) {
                $q->whereBetween('created_at', [
                    $this->startDate . ' 00:00:00',
                    $this->endDate . ' 23:59:59'
                ]);
            })
            ->when($this->search, fn($q) => $q->where('reference_no', 'like', "%{$this->search}%"))
            ->when($this->branchFilter, fn($q) => $q->where('requesting_branch_id', $this->branchFilter))
            ->when($this->priorityFilter, fn($q) => $q->where('priority', $this->priorityFilter))
            ->latest()->paginate($this->perPage, ['*'], 'inboxPage');
    }

    private function getActiveOrders() {
        return StockOrder::with(['items.ingredient', 'requestingBranch', 'requester', 'approver'])
            ->whereIn('status', ['approved', 'preparing', 'in_transit'])
            ->when($this->startDate && $this->endDate, function($q) {
                $q->whereBetween('created_at', [
                    $this->startDate . ' 00:00:00',
                    $this->endDate . ' 23:59:59'
                ]);
            })
            ->when($this->branchFilter, fn($q) => $q->where('requesting_branch_id', $this->branchFilter))
            ->latest()->paginate($this->perPage, ['*'], 'activePage');
    }

    private function getHistoryOrders() {
        return StockOrder::with(['items.ingredient', 'requestingBranch', 'requester', 'approver'])
            ->whereIn('status', ['delivered', 'rejected', 'cancelled'])
            ->when($this->startDate && $this->endDate, function($q) {
                $q->whereBetween('created_at', [
                    $this->startDate . ' 00:00:00',
                    $this->endDate . ' 23:59:59'
                ]);
            })
            ->when($this->search, fn($q) => $q->where('reference_no', 'like', "%{$this->search}%"))
            ->when($this->branchFilter, fn($q) => $q->where('requesting_branch_id', $this->branchFilter))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->latest()->paginate($this->perPage, ['*'], 'historyPage');
    }

    private function getKpis(): array {
        $start = $this->startDate ?: null;
        $end = $this->endDate ?: null;

        return [
            'pending'           => StockOrder::where('status', 'pending')
                ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']))
                ->count(),
            'active'            => StockOrder::whereIn('status', ['approved', 'preparing', 'in_transit'])
                ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']))
                ->count(),
            'delivered_month'   => StockOrder::where('status', 'delivered')
                ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']), fn($q) => $q->whereMonth('created_at', now()->month))
                ->count(),
            'rejected_month'    => StockOrder::where('status', 'rejected')
                ->when($start && $end, fn($q) => $q->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']), fn($q) => $q->whereMonth('created_at', now()->month))
                ->count(),
        ];
    }

    private function getWarehouseStock() {
        $main = Branch::where('is_main', true)->first();
        return $main ? BranchIngredientStock::with('ingredient')->where('branch_id', $main->id)->get()->keyBy('ingredient_id') : [];
    }

    private function getAnalytics(): array {
        $start = $this->startDate ?: now()->subDays(29)->toDateString();
        $end = $this->endDate ?: now()->toDateString();
        $branchId = $this->branchFilter ?: null;

        $cacheKey = "admin_analytics_v2_{$start}_{$end}_{$branchId}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(5), function () use ($start, $end, $branchId) {
            // HQ Main Branch ID
            $hqBranch = Branch::where('is_main', true)->first();
            $hqBranchId = $hqBranch?->id;

            // Total Dispatched Value (Delivered, in_transit, preparing, approved)
            $totalDispatched = StockOrder::whereIn('status', ['delivered', 'in_transit', 'preparing', 'approved'])
                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->sum('total_amount');

        // Avg Processing Lead Time in Hours (difference between created_at and delivered_at)
        $avgLeadTimeHours = StockOrder::where('status', 'delivered')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, delivered_at)) as avg_hours')
            ->value('avg_hours') ?? 0;

        // Fulfillment Rate: Delivered / (Delivered + Rejected)
        $deliveredCount = StockOrder::where('status', 'delivered')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->count();
        $rejectedCount = StockOrder::where('status', 'rejected')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->count();
        $totalEnded = $deliveredCount + $rejectedCount;
        $fulfillmentRate = $totalEnded > 0 ? ($deliveredCount / $totalEnded) * 100 : 100;

        // HQ Low Stock Ingredients Count
        $hqLowStockCount = $hqBranchId ? BranchIngredientStock::where('branch_id', $hqBranchId)
            ->whereHas('ingredient')
            ->get()
            ->filter(fn($s) => $s->stock_quantity <= ($s->ingredient->minimum_stock ?? 0))
            ->count() : 0;

        // Top Requested Ingredients (Month or Date Range)
        $topIngredients = StockOrderItem::with('ingredient')
            ->whereHas('stockOrder', fn($q) => $q->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59']))
            ->selectRaw('ingredient_id, SUM(requested_quantity) as total_requested')
            ->groupBy('ingredient_id')
            ->orderByDesc('total_requested')
            ->take(8)
            ->get();

        // Fulfillment Volume by Branch
        $branchVolume = StockOrder::where('status', 'delivered')
            ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->selectRaw('requesting_branch_id, COUNT(*) as total, SUM(total_amount) as total_spent')
            ->groupBy('requesting_branch_id')
            ->orderByDesc('total')
            ->get();

        // 30-Day Activity Trend (Daily Order Count and Spent Amount)
        $trendData = StockOrder::whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->whereIn('status', ['delivered', 'in_transit', 'preparing', 'approved'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $trendMap = [];
        $countMap = [];
        foreach ($trendData as $t) {
            $trendMap[$t->date] = $t->total;
            $countMap[$t->date] = $t->count;
        }

        $current = \Carbon\Carbon::parse($start);
        $last = \Carbon\Carbon::parse($end);

        $dates = [];
        $totals = [];
        $counts = [];

        $diff = $current->diffInDays($last);
        if ($diff > 90) {
            $current = $last->copy()->subDays(90);
        }

        while ($current->lte($last)) {
            $d = $current->toDateString();
            $dates[] = \Carbon\Carbon::parse($d)->format('M d');
            $totals[] = (float)($trendMap[$d] ?? 0);
            $counts[] = (int)($countMap[$d] ?? 0);
            $current->addDay();
        }

            return [
                'totalDispatched' => (float)$totalDispatched,
                'avgLeadTimeHours' => round($avgLeadTimeHours, 1),
                'fulfillmentRate' => round($fulfillmentRate, 1),
                'hqLowStockCount' => (int)$hqLowStockCount,
                'topIngredients' => $topIngredients,
                'branchVolume' => $branchVolume,
                'trend' => [
                    'dates' => $dates,
                    'totals' => $totals,
                    'counts' => $counts
                ]
            ];
        });
    }

}
