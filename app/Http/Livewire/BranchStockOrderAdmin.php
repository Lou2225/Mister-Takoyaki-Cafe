<?php

namespace App\Http\Livewire;

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

class BranchStockOrderAdmin extends Component
{
    use WithPagination, HandlesValidations, HandlesExports;

    // ── Panel / View State ────────────────────────────────────────
    public $panel = 'inbox';

    // ── Detail Side Panel ─────────────────────────────────────────
    public $selectedOrderId   = null;
    public $selectedOrder     = null;

    // ── Approval / Rejection State ────────────────────────────────
    public $rejectTargetId     = null;
    public $rejectTargetRef    = '';
    public $rejectionReason    = '';
    public $adminRemarks       = '';
    public $approveTargetId    = null;
    public $approveTargetRef   = '';
    public $dispatchTargetId   = null;
    public $dispatchTargetRef  = '';
    public $deliveryFee        = 0;
    public $suggestedFee       = 0;
    public $editItemQuantities = [];

    // ── Logistics Management ──────────────────────────────────────
    public $globalRate        = 50; // default PHP per KM
    public $branches;
    public $branchDistances   = []; // branch_id => distance

    // ── Filters ───────────────────────────────────────────────────
    public $search          = '';
    public $branchFilter    = '';
    public $priorityFilter  = '';
    public $statusFilter    = 'all';
    public $perPage         = 10;

    protected $queryString = [
        'panel'         => ['except' => 'inbox'],
        'search'        => ['except' => '', 'as' => 'oa_search'],
        'branchFilter'  => ['except' => '', 'as' => 'oa_branch'],
        'statusFilter'  => ['except' => 'all', 'as' => 'oa_status'],
    ];

    protected $listeners = ['refreshAdminOrders' => '$refresh'];

    // ── Initialization ────────────────────────────────────────────

    public function mount()
    {
        $this->globalRate = 50; // Initial default
        $this->loadLogistics();
        $this->updateHeader();
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

        // Suggested Fee Calculation: Distance * Global Rate
        $distance = $order->requestingBranch->distance_from_main ?? 0;
        $this->suggestedFee = (float)$distance * (float)$this->globalRate;

        // Pre-fill delivery fee: Use existing or suggested
        $this->deliveryFee = $order->delivery_fee > 0 ? $order->delivery_fee : $this->suggestedFee;
        
        $this->editItemQuantities = [];
        foreach ($order->items as $item) {
            $this->editItemQuantities[$item->id] = $item->approved_quantity ?? $item->requested_quantity;
        }

        $this->dispatchBrowserEvent('open-modal', ['name' => 'fulfillment-review']);
    }

    public function closeDetail(): void
    {
        $this->selectedOrderId = null;
        $this->selectedOrder   = null;
        $this->editItemQuantities = [];
        $this->dispatchBrowserEvent('close-modal', 'fulfillment-review');
    }

    // ── Logistics Management ──────────────────────────────────────

    public function updateLogistics()
    {
        // Refresh all branch distances to ensure data accuracy
        $this->syncAllDistances();
        
        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => 'Logistics configuration updated. Network distances have been synchronized.'
        ]);
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
        $this->dispatchBrowserEvent('close-modal', 'confirm-approve-order');
        $this->reset(['approveTargetId', 'approveTargetRef']);
        $this->notify('success', "Order {$order->reference_no} approved.");
    }

    public function rejectOrder(): void
    {
        $this->validate(['rejectionReason' => 'required|string|min:5']);

        $order = StockOrder::findOrFail($this->rejectTargetId);
        $order->update([
            'status'           => 'rejected',
            'rejection_reason' => $this->rejectionReason,
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
        ]);

        $this->notifyBranch($order, '❌ Stock Request Rejected', "Your request {$order->reference_no} was rejected. Reason: {$this->rejectionReason}");

        $this->reset(['rejectTargetId', 'rejectionReason']);
        $this->closeDetail();
        $this->dispatchBrowserEvent('close-modal', 'confirm-reject-order');
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
        if (!$this->dispatchTargetId) return;

        $order = StockOrder::with(['items.ingredient'])->findOrFail($this->dispatchTargetId);
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

                    $this->logMovement($mainBranch->id, $item->ingredient_id, 'transfer_out', $qtyInBase, $order->requesting_branch_id, $transferRef, "Transfer to branch");
                    $this->logMovement($order->requesting_branch_id, $item->ingredient_id, 'transfer_in', $qtyInBase, $mainBranch->id, $transferRef, "Transfer from HQ");
                }

                $order->update(['status' => 'in_transit', 'dispatched_at' => now()]);
                $this->notifyBranch($order, '🚚 Stock Order In Transit', "Your request {$order->reference_no} has been dispatched.");
            });

            $this->reset(['dispatchTargetId', 'dispatchTargetRef']);
            $this->closeDetail();
            $this->dispatchBrowserEvent('close-dispatch-modals');
            $this->notify('success', 'Transfer dispatched!');

        } catch (\Exception $e) {
            $this->notify('error', $e->getMessage());
        }
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatchBrowserEvent('notify', ['type' => $type, 'message' => $message]);
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
                'link'      => route('stock.orders'),
            ]);

            try {
                Mail::to($user->email)->queue(new StockOrderMail($order, $title, $message));
            } catch (\Exception $e) {}
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
        $this->emit('setHeader', [
            'icon'        => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
            'title'       => 'Order Inbox',
            'breadcrumbs' => [['label' => 'Inventory', 'url' => '#'], ['label' => 'Order Inbox', 'url' => route('stock.orders.admin')]],
        ]);
    }

    public function render()
    {
        return view('livewire.branch-stock-order-admin', [
            'inboxOrders'    => $this->getInboxOrders(),
            'activeOrders'   => $this->getActiveOrders(),
            'historyOrders'  => $this->getHistoryOrders(),
            'kpis'           => $this->getKpis(),
            'warehouseStock' => $this->getWarehouseStock(),
            'branches'       => Branch::where('is_main', false)->orderBy('branch_name')->get(),
            'analytics'      => $this->getAnalytics(),
        ])->layout('layouts.app');
    }

    private function getInboxOrders() {
        return StockOrder::with(['items.ingredient', 'requestingBranch', 'requester'])
            ->where('status', 'pending')
            ->when($this->search, fn($q) => $q->where('reference_no', 'like', "%{$this->search}%"))
            ->when($this->branchFilter, fn($q) => $q->where('requesting_branch_id', $this->branchFilter))
            ->when($this->priorityFilter, fn($q) => $q->where('priority', $this->priorityFilter))
            ->latest()->paginate($this->perPage, ['*'], 'inboxPage');
    }

    private function getActiveOrders() {
        return StockOrder::with(['items.ingredient', 'requestingBranch', 'requester', 'approver'])
            ->whereIn('status', ['approved', 'preparing', 'in_transit'])
            ->when($this->branchFilter, fn($q) => $q->where('requesting_branch_id', $this->branchFilter))
            ->latest()->paginate($this->perPage, ['*'], 'activePage');
    }

    private function getHistoryOrders() {
        return StockOrder::with(['items.ingredient', 'requestingBranch', 'requester', 'approver'])
            ->whereIn('status', ['delivered', 'rejected', 'cancelled'])
            ->when($this->search, fn($q) => $q->where('reference_no', 'like', "%{$this->search}%"))
            ->when($this->branchFilter, fn($q) => $q->where('requesting_branch_id', $this->branchFilter))
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->latest()->paginate($this->perPage, ['*'], 'historyPage');
    }

    private function getKpis(): array {
        return [
            'pending'           => StockOrder::where('status', 'pending')->count(),
            'active'            => StockOrder::whereIn('status', ['approved', 'preparing', 'in_transit'])->count(),
            'delivered_month'   => StockOrder::where('status', 'delivered')->whereMonth('created_at', now()->month)->count(),
            'rejected_month'    => StockOrder::where('status', 'rejected')->whereMonth('created_at', now()->month)->count(),
        ];
    }

    private function getWarehouseStock() {
        $main = Branch::where('is_main', true)->first();
        return $main ? BranchIngredientStock::with('ingredient')->where('branch_id', $main->id)->get()->keyBy('ingredient_id') : [];
    }

    private function getAnalytics(): array {
        if ($this->panel !== 'analytics') return [];
        return [
            'topIngredients' => StockOrderItem::with('ingredient')
                ->whereHas('stockOrder', fn($q) => $q->whereMonth('created_at', now()->month))
                ->selectRaw('ingredient_id, SUM(requested_quantity) as total_requested')
                ->groupBy('ingredient_id')->orderByDesc('total_requested')->take(10)->get(),
            'branchVolume' => StockOrder::where('status', 'delivered')->whereMonth('created_at', now()->month)
                ->selectRaw('requesting_branch_id, COUNT(*) as total')->groupBy('requesting_branch_id')->get(),
            'lowStock' => BranchIngredientStock::with('ingredient')->where('branch_id', Branch::where('is_main', true)->first()?->id)
                ->get()->filter(fn($s) => $s->stock_quantity <= ($s->ingredient->minimum_stock ?? 0))->values()
        ];
    }

    public function confirmApprove(int $id): void {
        $order = StockOrder::findOrFail($id);
        $this->approveTargetId = $id;
        $this->approveTargetRef = $order->reference_no;
        $this->dispatchBrowserEvent('open-modal', ['name' => 'confirm-approve-order']);
    }

    public function confirmReject(int $id): void {
        $order = StockOrder::findOrFail($id);
        $this->rejectTargetId = $id;
        $this->rejectTargetRef = $order->reference_no;
        $this->dispatchBrowserEvent('open-modal', ['name' => 'confirm-reject-order']);
    }

    public function confirmDispatch(int $id): void {
        $order = StockOrder::findOrFail($id);
        $this->dispatchTargetId = $id;
        $this->dispatchTargetRef = $order->reference_no;
        $this->dispatchBrowserEvent('open-modal', ['name' => 'confirm-dispatch-order']);
    }
}
