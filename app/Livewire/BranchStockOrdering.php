<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Branch;
use App\Models\Ingredient;
use App\Models\BranchIngredientStock;
use App\Models\StockOrder;
use App\Models\StockOrderItem;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\StockOrderMail;
use App\Services\BranchContext;
use App\Traits\HandlesValidations;
use App\Traits\RequiresOperatingBranch;

class BranchStockOrdering extends Component
{
    use WithPagination, HandlesValidations, RequiresOperatingBranch;

    // ── Panel / View State ────────────────────────────────────────
    public $panel = 'requests';

    // ── Branch Context ────────────────────────────────────────────
    public string $selectedBranchId = '';
    public string $mainBranchId     = '';

    // ── Detail Side Panel ─────────────────────────────────────────
    public ?int $selectedOrderId   = null;
    public ?StockOrder $selectedOrder     = null;

    // ── Cart (new request form) ───────────────────────────────────
    public array $cartItems        = [];
    public string $cartIngredientId = '';
    public string $cartQty          = '';
    public string $cartUnit         = '';  // The unit the user picks: base ('g') or packaging ('box', 'bottle')
    public float $cartPrice        = 0;
    public string $cartNotes        = '';
    public string $orderPriority    = 'normal';
    public string $orderNotes       = '';
    public float $deliveryFee      = 0;
    public int $globalRate       = 50; // default PHP per KM
    public float $branchDistance   = 0;

    // ── Filters ───────────────────────────────────────────────────
    public string $search       = '';
    public string $statusFilter = 'all';
    public int $perPage      = 10;
    public string $startDate    = '';
    public string $endDate      = '';
    public string $activeFilter = 'All Time';

    // ── Modal state ───────────────────────────────────────────────
    public ?int $cancelTargetId   = null;
    public string $cancelTargetRef  = '';
    public ?int $deliverTargetId  = null;
    public string $deliverTargetRef = '';

    // ── Restock Suggestions (cached, loaded once via wire:init) ───
    public array $restockSuggestions = [];
    public bool  $restockLoaded      = false;

    protected $queryString = [
        'panel'        => ['except' => 'requests'],
        'search'       => ['except' => '', 'as' => 'so_search'],
        'statusFilter' => ['except' => 'all', 'as' => 'so_status'],
        'startDate'    => ['except' => '', 'as' => 'so_start'],
        'endDate'      => ['except' => '', 'as' => 'so_end'],
        'activeFilter' => ['except' => 'All Time', 'as' => 'so_filter'],
    ];

    protected $listeners = ['refreshOrders' => '$refresh'];

    // ── Initialization ────────────────────────────────────────────

    public function mount(): void
    {
        $user = auth()->user();
        if (!$user || $user->role_id > 2) {
            abort(403, 'Unauthorized access.');
        }

        $mainBranch = Branch::where('is_main', true)->first();
        $this->mainBranchId = $mainBranch?->id;

        if ($this->isSuperAdmin()) {
            $this->selectedBranchId = (string) (BranchContext::getActiveBranchId() ?: '');
        } else {
            $this->selectedBranchId = (string) ($user->branch_id ?: '');
        }

        if ($this->selectedBranchId && $this->selectedBranchId != $this->mainBranchId) {
            $this->calculateEstimatedFee();
            $this->updateHeader();
            $this->loadRestockSuggestions();
        }

        // Check for ingredient query parameter from BI insights
        if (request()->has('ingredient')) {
            $this->cartIngredientId = request()->query('ingredient');
            $this->panel = 'new'; // Switch to new request panel
        }
    }

    // ── Role Helpers ──────────────────────────────────────────────
    public function isSuperAdmin(): bool { return auth()->user()->role_id === 1; }
    public function isAdmin(): bool      { return auth()->user()->role_id === 2; }

    public function updatedSelectedBranchId(): void
    {
        $this->calculateEstimatedFee();
        $this->clearCart(); // Clear cart when branch context changes to prevent cross-branch leaks
        $this->loadRestockSuggestions();
    }

    public function updatedPanel(string $value): void
    {
        $this->statusFilter = 'all';
        $this->search = '';
        $this->resetPage('req_page');
        $this->resetPage('hist_page');
    }

    public function updatingSearch(): void
    {
        $this->resetPage('req_page');
        $this->resetPage('hist_page');
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage('req_page');
        $this->resetPage('hist_page');
    }

    private function calculateEstimatedFee(): void
    {
        $branch = Branch::find($this->selectedBranchId);
        if (!$branch) {
            $this->deliveryFee = 0;
            return;
        }
        $this->branchDistance = $branch->distance_from_main ?? 0;

        $baseFee = (float)\App\Models\SystemSetting::get('logistics_base_fee', 0);
        $this->globalRate = (int)\App\Models\SystemSetting::get('logistics_global_rate', 50);
        $minFee = (float)\App\Models\SystemSetting::get('logistics_min_fee', 0);
        $maxFee = (float)\App\Models\SystemSetting::get('logistics_max_fee', 5000);
        $freeThreshold = (float)\App\Models\SystemSetting::get('logistics_free_threshold', 0);

        $itemsSubtotal = $this->getCartSubtotalProperty();

        if ($freeThreshold > 0 && $itemsSubtotal >= $freeThreshold) {
            $this->deliveryFee = 0;
        } else {
            $computedFee = $baseFee + ((float)$this->branchDistance * (float)$this->globalRate);
            $this->deliveryFee = max($minFee, min($maxFee, $computedFee));
        }
    }

    // ── Cart Management ───────────────────────────────────────────

    public function updatedCartIngredientId(string $value): void
    {
        $this->resetValidation('cartQty');
        if ($value) {
            $ing = Ingredient::find($value);
            // Default to base unit; user can change to a packaging tier
            $this->cartUnit = $ing?->unit ?? 'pcs';
            $this->cartPrice = $ing?->cost ?? 0;
        } else {
            $this->cartUnit = '';
            $this->cartPrice = 0;
        }
    }

    public function updatedCartQty($value): void
    {
        $this->resetValidation('cartQty');
        if (!$value || !$this->cartIngredientId) return;

        $ing = Ingredient::with('unitConversions')->find($this->cartIngredientId);
        if (!$ing) return;

        $selectedUnit = $this->cartUnit ?: $ing->unit;
        $qtyInBase = \App\Helpers\StockHelper::convertToBase((float)$value, $selectedUnit, $ing);

        $mainStock = BranchIngredientStock::where('branch_id', $this->mainBranchId)
            ->where('ingredient_id', $this->cartIngredientId)
            ->first()?->stock_quantity ?? 0;

        if ($qtyInBase > $mainStock) {
            $availableMsg = \App\Helpers\StockHelper::formatForDisplay($mainStock, $ing->unit);
            $msg = "Insufficient stock at Main Branch. Only {$availableMsg} available.";
            if ($selectedUnit !== $ing->unit) {
                $conversion = $ing->unitConversions()->where('unit_name', $selectedUnit)->first();
                if ($conversion && $conversion->qty_in_base > 0) {
                    $availableInUnit = floor($mainStock / $conversion->qty_in_base);
                    $msg .= " (Approx. {$availableInUnit} {$selectedUnit})";
                }
            }
            $this->addError('cartQty', $msg);
        }
    }

    /**
     * Called when the user clicks a unit button in the cart form.
     * Updates the displayed price for the selected packaging unit.
     */
    public function selectCartUnit(string $unitName): void
    {
        if (!$this->cartIngredientId) return;
        $ing = Ingredient::with('unitConversions')->find($this->cartIngredientId);
        if (!$ing) return;

        $this->cartUnit = $unitName;

        if ($unitName === $ing->unit) {
            // Base unit selected: use per-base cost
            $this->cartPrice = $ing->cost ?? 0;
        } else {
            // Packaging unit selected: look up its price
            $conversion = $ing->unitConversions()->where('unit_name', $unitName)->first();
            $this->cartPrice = $conversion?->price_per_unit ?? 0;
        }

        if ($this->cartQty) {
            $this->updatedCartQty($this->cartQty);
        }
    }

    public function addToCart(): void
    {
        $this->validate([
            'cartIngredientId' => 'required|exists:ingredients,id',
            'cartQty'          => 'required|numeric|min:0.01',
        ], [
            'cartIngredientId.required' => 'Please select an ingredient.',
            'cartQty.required'          => 'Quantity is required.',
            'cartQty.numeric'           => 'Please enter a valid number.',
            'cartQty.min'               => 'Quantity must be greater than zero.',
        ]);

        $ing = Ingredient::with('unitConversions')->find($this->cartIngredientId);
        $selectedUnit = $this->cartUnit ?: $ing->unit;
        $isBulkUnit   = $selectedUnit !== $ing->unit;

        // Convert to base units using the universal converter
        $qtyInBase = \App\Helpers\StockHelper::convertToBase((float)$this->cartQty, $selectedUnit, $ing);

        // 1. Fetch Main Branch Stock (in base units)
        $mainStock = BranchIngredientStock::where('branch_id', $this->mainBranchId)
            ->where('ingredient_id', $this->cartIngredientId)
            ->first()?->stock_quantity ?? 0;

        // 2. Strict Check: Can't order more than Main Branch stock
        if ($qtyInBase > $mainStock) {
            $availableMsg = \App\Helpers\StockHelper::formatForDisplay($mainStock, $ing->unit);
            $msg = "Insufficient stock at Main Branch. Only {$availableMsg} available.";
            if ($isBulkUnit) {
                $conversion = $ing->unitConversions()->where('unit_name', $selectedUnit)->first();
                if ($conversion && $conversion->qty_in_base > 0) {
                    $availableInUnit = floor($mainStock / $conversion->qty_in_base);
                    $msg .= " (Approx. {$availableInUnit} {$selectedUnit})";
                }
            }
            $this->addError('cartQty', $msg);
            $this->notify('error', $msg);
            return;
        }

        // Prevent duplicate in cart
        if ($this->isInCart($this->cartIngredientId)) {
            $this->notify('warning', 'This item is already in your cart.');
            return;
        }

        $stock = BranchIngredientStock::where('branch_id', $this->selectedBranchId)
            ->where('ingredient_id', $ing->id)
            ->first();

        $this->cartItems[] = [
            'ingredient_id'   => $ing->id,
            'ingredient_name' => $ing->name,
            'unit'            => $selectedUnit,    // the unit the user sees (e.g. 'box')
            'order_unit'      => $selectedUnit,    // stored for admin reference
            'quantity'        => $this->cartQty,
            'qty_in_base'     => $qtyInBase,       // always in base units
            'unit_price'      => $this->cartPrice,
            'subtotal'        => $this->cartQty * $this->cartPrice,
            'notes'           => $this->cartNotes,
            'current_stock'   => $stock?->stock_quantity ?? 0,
            'min_stock'       => $ing->minimum_stock,
            'is_low'          => ($stock?->stock_quantity ?? 0) <= $ing->minimum_stock,
        ];

        $this->reset(['cartIngredientId', 'cartQty', 'cartNotes', 'cartUnit', 'cartPrice']);
        $this->notify('success', "{$ing->name} added to cart.");
    }

    public function removeFromCart(int $index): void
    {
        unset($this->cartItems[$index]);
        $this->cartItems = array_values($this->cartItems);
    }

    public function clearCart(): void
    {
        $this->cartItems = [];
        $this->reset(['cartIngredientId', 'cartQty', 'cartNotes', 'cartUnit', 'orderPriority', 'orderNotes']);
    }

    public function addLowStockToCart(int $ingredientId): void
    {
        if ($this->isInCart($ingredientId)) return;

        $ing = Ingredient::find($ingredientId);
        if (!$ing) return;

        $stock = BranchIngredientStock::where('branch_id', $this->selectedBranchId)
            ->where('ingredient_id', $ingredientId)
            ->first();
            
        $mainStock = BranchIngredientStock::where('branch_id', $this->mainBranchId)
            ->where('ingredient_id', $ingredientId)
            ->first()?->stock_quantity ?? 0;

        // Cap the suggestion at available main stock
        $deficit = max(0, $ing->minimum_stock - ($stock?->stock_quantity ?? 0));
        $finalQty = min($deficit, $mainStock);

        if ($finalQty <= 0) {
            $this->notify('warning', "Cannot suggest {$ing->name} - Main Branch is also out of stock.");
            return;
        }

        $this->cartItems[] = [
            'ingredient_id'   => $ing->id,
            'ingredient_name' => $ing->name,
            'unit'            => $ing->unit,
            'order_unit'      => $ing->unit,
            'quantity'        => $finalQty,
            'qty_in_base'     => $finalQty,
            'unit_price'      => $ing->cost,
            'subtotal'        => $finalQty * $ing->cost,
            'notes'           => 'Auto-replenishment for low stock.',
            'current_stock'   => $stock?->stock_quantity ?? 0,
            'min_stock'       => $ing->minimum_stock,
            'is_low'          => true,
        ];

        $this->notify('info', "{$ing->name} added to cart.");
    }

    // ── Order Operations ──────────────────────────────────────────

    public function validateBeforeSubmit(): void
    {
        if (empty($this->cartItems)) {
            $this->notify('error', 'Your cart is empty.');
            return;
        }

        $hasPending = StockOrder::where('requesting_branch_id', $this->selectedBranchId)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            $this->notify('warning', 'Wait for your current pending request to be processed.');
            return;
        }

        $this->dispatch('open-modal', name: 'confirm-submit-order');
    }

    public function submitOrder(array $items = [], ?string $priority = null, ?string $notes = null): bool
    {
        if (!empty($items)) {
            $this->cartItems = $items;
        }
        if ($priority !== null) {
            $this->orderPriority = $priority;
        }
        if ($notes !== null) {
            $this->orderNotes = $notes;
        }

        if (empty($this->cartItems)) {
            $this->notify('error', 'Your cart is empty.');
            return false;
        }

        $this->validate([
            'orderPriority' => 'required|in:normal,urgent,critical',
            'orderNotes'    => 'nullable|string|max:500',
        ], [
            'orderPriority.required' => 'Please select a priority level.',
            'orderPriority.in'       => 'Invalid priority selected.',
        ]);

        $hasPending = StockOrder::where('requesting_branch_id', $this->selectedBranchId)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            $this->notify('warning', 'Wait for your current pending request to be processed.');
            return false;
        }

        try {
            DB::transaction(function () {
                $this->calculateEstimatedFee();
                $itemsSubtotal = collect($this->cartItems)->sum('subtotal');
                $totalAmount = $itemsSubtotal + (float) $this->deliveryFee;

                $order = StockOrder::create([
                    'reference_no'          => StockOrder::generateReference(),
                    'requesting_branch_id'  => $this->selectedBranchId,
                    'source_branch_id'      => $this->mainBranchId,
                    'priority'              => $this->orderPriority,
                    'status'                => 'pending',
                    'notes'                 => $this->orderNotes ?: null,
                    'delivery_fee'          => (float) $this->deliveryFee,
                    'total_amount'          => $totalAmount,
                    'requested_by'          => auth()->id(),
                ]);

                foreach ($this->cartItems as $item) {
                    StockOrderItem::create([
                        'stock_order_id'     => $order->id,
                        'ingredient_id'      => $item['ingredient_id'],
                        'requested_quantity' => $item['quantity'],
                        'unit'               => $item['unit'],
                        'order_unit'         => $item['order_unit'] ?? $item['unit'],
                        'unit_price'         => $item['unit_price'],
                        'subtotal'           => $item['subtotal'],
                        'notes'              => $item['notes'] ?: null,
                    ]);
                }

                $this->notifyAdmins(
                    '📦 New Stock Request',
                    "New request ({$order->reference_no}) from " . Branch::find($this->selectedBranchId)?->branch_name,
                    route('stock.orders.admin', ['oa_search' => $order->reference_no]),
                    $order
                );
            });

            $this->clearCart();
            $this->panel = 'requests';
            $this->dispatch('close-modal', 'confirm-submit-order');
            $this->notify('success', 'Stock request submitted!');
            $this->dispatch('order-submitted');
            $this->updateHeader();
            $this->loadRestockSuggestions();
            $this->dispatch('update-stock-data', [
                'restockSuggestions' => $this->restockSuggestions,
            ]);

            return true;

        } catch (\Exception $e) {
            $this->notify('error', 'Failed to submit order: ' . $e->getMessage());
            return false;
        }
    }

    public function cancelOrder(): void
    {
        if (!$this->cancelTargetId) return;

        $order = StockOrder::where('requesting_branch_id', $this->selectedBranchId)->findOrFail($this->cancelTargetId);
        if ($order->isPending()) {
            $order->update(['status' => 'cancelled']);
            $this->notify('success', 'Request cancelled.');
        } else {
            $this->notify('error', 'Only pending requests can be cancelled.');
        }

        $this->reset(['cancelTargetId', 'cancelTargetRef']);
        $this->dispatch('close-modal', 'confirm-cancel-order');
        $this->dispatch('close-modal', 'order-details');
    }

    public function markDelivered(): void
    {
        if (!$this->deliverTargetId) return;

        $order = StockOrder::where('requesting_branch_id', $this->selectedBranchId)->findOrFail($this->deliverTargetId);
        if ($order->isInTransit()) {
            $order->update(['status' => 'delivered', 'delivered_at' => now()]);
            
            $this->notifyAdmins(
                '✅ Transfer Confirmed',
                "Order {$order->reference_no} was received by the branch.",
                route('stock.orders.admin'),
                $order
            );
            
            $this->notify('success', 'Order received and stock updated.');
        }

        $this->reset(['deliverTargetId', 'deliverTargetRef']);
        $this->dispatch('close-modal', 'confirm-delivery');
        $this->dispatch('close-modal', 'order-details');
    }

    public function getCartTotalProperty(): float
    {
        return collect($this->cartItems)->sum('subtotal') + (float) $this->deliveryFee;
    }

    public function getCartSubtotalProperty(): float
    {
        return collect($this->cartItems)->sum('subtotal');
    }

    // ── Helpers & Internal Logic ──────────────────────────────────

    private function isInCart(int $id): bool
    {
        return collect($this->cartItems)->contains('ingredient_id', $id);
    }

    private function notify(string $type, string $message): void
    {
        $this->dispatch('notify', type: $type, message: $message);
    }

    private function notifyAdmins(string $title, string $message, string $link, $order = null): void
    {
        $admins = User::whereIn('role_id', [1, 2])
            ->where(function ($q) {
                $q->where('role_id', 1)->orWhere('branch_id', $this->mainBranchId);
            })
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id'   => $admin->id,
                'branch_id' => $this->mainBranchId,
                'type'      => 'stock_order',
                'title'     => $title,
                'message'   => $message,
                'link'      => $link,
            ]);
        }
    }

    private function updateHeader(): void
    {
        $this->dispatch('setHeader', 
            icon: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            title: 'Stock Requests',
            breadcrumbs: [['label' => 'Inventory', 'url' => '#'], ['label' => 'Stock Requests', 'url' => route('stock.orders')]]
        );
    }

    // ── Rendering ─────────────────────────────────────────────────

    public function render()
    {
        if (!$this->hasOperatingBranch() || empty($this->selectedBranchId)) {
            return view('components.operating-branch-required', [
                'title' => 'Operating Branch Required for Supplies',
                'message' => 'Request Supplies lets physical branches order stock replenishments from General Headquarters. Please select an operating sub-branch context to proceed.',
                'actionText' => 'Configure in Settings',
                'actionRoute' => route('settings.index'),
                'icon' => 'branch',
            ])->layout('layouts.app');
        }

        if ($this->selectedBranchId == $this->mainBranchId) {
            return view('components.operating-branch-required', [
                'title' => 'Main Branch Cannot Request Supplies',
                'message' => 'The Main Branch functions as the primary distribution hub supplying other locations. To request external supplier inventory, please use the Supplier Ordering or Purchase Orders module, or switch to a sub-branch context.',
                'actionText' => 'Open Branch Requests Inbox',
                'actionRoute' => route('stock.orders.admin'),
                'icon' => 'building',
            ])->layout('layouts.app');
        }

        $this->calculateEstimatedFee();

        $ingredients = Ingredient::with('unitConversions')
            ->orderBy('name')
            ->get()
            ->map(fn($i) => [
                'id' => (int)$i->id,
                'name' => $i->name,
                'unit' => $i->unit,
                'cost' => (float)($i->cost ?? 0),
                'minimum_stock' => (float)($i->minimum_stock ?? 0),
                'unit_conversions' => $i->unitConversions->map(fn($c) => [
                    'unit_name' => $c->unit_name,
                    'qty_in_base' => (float)$c->qty_in_base,
                ])->values()->all(),
            ])
            ->values()
            ->all();
        $branchStock = BranchIngredientStock::where('branch_id', $this->selectedBranchId)->pluck('stock_quantity', 'ingredient_id');
        $mainStock = BranchIngredientStock::where('branch_id', $this->mainBranchId)->pluck('stock_quantity', 'ingredient_id');

        return view('livewire.branch-stock-ordering', [
            'requestOrders'   => $this->requestOrders,
            'historyOrders'   => $this->historyOrders,
            'kpis'            => $this->getKpis(),
            'ingredients'     => $ingredients,
            'branchStock'     => $branchStock,
            'mainStock'          => $mainStock,
            'restockSuggestions' => $this->restockSuggestions,
            'logisticsConfig'    => [
                'baseFee'        => (float)\App\Models\SystemSetting::get('logistics_base_fee', 0),
                'globalRate'     => (int)\App\Models\SystemSetting::get('logistics_global_rate', 50),
                'minFee'         => (float)\App\Models\SystemSetting::get('logistics_min_fee', 0),
                'maxFee'         => (float)\App\Models\SystemSetting::get('logistics_max_fee', 5000),
                'freeThreshold'  => (float)\App\Models\SystemSetting::get('logistics_free_threshold', 0),
                'branchDistance' => (float)$this->branchDistance,
            ],
        ])->layout('layouts.app');
    }

    public function getRequestOrdersProperty()
    {
        $activeStatuses = ['pending', 'approved', 'preparing', 'in_transit'];
        $statusFilter = ($this->statusFilter !== 'all' && in_array($this->statusFilter, $activeStatuses))
            ? $this->statusFilter : null;

        return StockOrder::with(['items.ingredient', 'sourceBranch', 'approver', 'requester'])
            ->where('requesting_branch_id', $this->selectedBranchId)
            ->whereIn('status', $activeStatuses)
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($this->search, fn($q) => $q->where('reference_no', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate($this->perPage, ['*'], 'req_page');
    }

    public function getHistoryOrdersProperty()
    {
        $historyStatuses = ['delivered', 'rejected', 'cancelled'];
        $statusFilter = ($this->statusFilter !== 'all' && in_array($this->statusFilter, $historyStatuses))
            ? $this->statusFilter : null;

        return StockOrder::with(['items.ingredient', 'sourceBranch', 'approver', 'requester'])
            ->where('requesting_branch_id', $this->selectedBranchId)
            ->whereIn('status', $historyStatuses)
            ->when($statusFilter, fn($q) => $q->where('status', $statusFilter))
            ->when($this->startDate && $this->endDate, function($q) {
                $q->whereBetween('created_at', [
                    $this->startDate . ' 00:00:00',
                    $this->endDate . ' 23:59:59'
                ]);
            })
            ->when($this->search, fn($q) => $q->where('reference_no', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate($this->perPage, ['*'], 'hist_page');
    }

    private function getKpis(): array
    {
        $stats = StockOrder::where('requesting_branch_id', $this->selectedBranchId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'pending'    => $stats['pending'] ?? 0,
            'approved'   => ($stats['approved'] ?? 0) + ($stats['preparing'] ?? 0),
            'in_transit' => $stats['in_transit'] ?? 0,
            'delivered'  => StockOrder::where('requesting_branch_id', $this->selectedBranchId)
                ->where('status', 'delivered')
                ->whereMonth('created_at', now()->month)
                ->count(),
        ];
    }

    public function loadRestockSuggestions(): void
    {
        if (!$this->selectedBranchId) return;

        $ingredients = Ingredient::where('minimum_stock', '>', 0)->get();
        $branchStock = BranchIngredientStock::where('branch_id', $this->selectedBranchId)
            ->whereIn('ingredient_id', $ingredients->pluck('id'))
            ->pluck('stock_quantity', 'ingredient_id');

        $this->restockSuggestions = $ingredients->map(function ($ing) use ($branchStock) {
            $currentStock = $branchStock[$ing->id] ?? 0;
            if ($currentStock <= $ing->minimum_stock) {
                return [
                    'id'      => $ing->id,
                    'name'    => $ing->name,
                    'unit'    => $ing->unit,
                    'deficit' => max(1, $ing->minimum_stock - $currentStock),
                ];
            }
            return null;
        })->filter()->values()->toArray();

        $this->restockLoaded = true;
    }
    #[\Livewire\Attributes\Computed]
    public function getAnalyticsDataProperty(): array
    {
        $branchId = $this->selectedBranchId;
        
        $cacheKey = "branch_analytics_v2_{$branchId}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addMinutes(5), function () use ($branchId) {

        // 1. Core KPIs
        $totalSpent = StockOrder::where('requesting_branch_id', $branchId)
            ->whereIn('status', ['approved', 'preparing', 'in_transit', 'delivered'])
            ->sum('total_amount');

        $totalRequests = StockOrder::where('requesting_branch_id', $branchId)->count();

        $avgLeadTime = StockOrder::where('requesting_branch_id', $branchId)
            ->where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, delivered_at)) as avg_hours')
            ->first()->avg_hours;

        $activeRequests = StockOrder::where('requesting_branch_id', $branchId)
            ->whereIn('status', ['pending', 'approved', 'preparing', 'in_transit'])
            ->count();

        // 2. Status Breakdown
        $statusCounts = StockOrder::where('requesting_branch_id', $branchId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 3. Priority Breakdown
        $priorityCounts = StockOrder::where('requesting_branch_id', $branchId)
            ->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->pluck('count', 'priority')
            ->toArray();

        // 4. Top Ingredients
        $topIngredients = StockOrderItem::join('stock_orders', 'stock_order_items.stock_order_id', '=', 'stock_orders.id')
            ->join('ingredients', 'stock_order_items.ingredient_id', '=', 'ingredients.id')
            ->where('stock_orders.requesting_branch_id', $branchId)
            ->whereIn('stock_orders.status', ['approved', 'preparing', 'in_transit', 'delivered'])
            ->select(
                'ingredients.name',
                DB::raw('SUM(stock_order_items.requested_quantity) as total_qty'),
                'stock_order_items.unit',
                DB::raw('SUM(stock_order_items.subtotal) as total_spent')
            )
            ->groupBy('ingredients.id', 'ingredients.name', 'stock_order_items.unit')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get()
            ->toArray();

        // 5. Daily Spend Trend (past 30 days)
        $trendData = StockOrder::where('requesting_branch_id', $branchId)
            ->whereIn('status', ['approved', 'preparing', 'in_transit', 'delivered'])
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $dates = [];
        $totals = [];
        $counts = [];

        for ($i = 29; $i >= 0; $i--) {
            $dateObj = now()->subDays($i);
            $dateStr = $dateObj->format('Y-m-d');
            $dates[] = $dateObj->format('M d');

            $found = $trendData->first(fn($item) => $item->date === $dateStr);
            $totals[] = $found ? (float)$found->total : 0.0;
            $counts[] = $found ? (int)$found->count : 0;
        }

        return [
            'kpis' => [
                'total_spent' => $totalSpent,
                'total_requests' => $totalRequests,
                'avg_lead_time' => $avgLeadTime ? round($avgLeadTime, 1) : null,
                'active_requests' => $activeRequests,
            ],
            'status_counts' => $statusCounts,
            'priority_counts' => $priorityCounts,
            'top_ingredients' => $topIngredients,
            'trend' => [
                'dates' => $dates,
                'totals' => $totals,
                'counts' => $counts,
            ]
        ];
        });
    }

    public function viewOrder(int $id): void
    {
        $this->selectedOrderId = $id;
        $this->selectedOrder = StockOrder::with(['items.ingredient', 'requestingBranch', 'sourceBranch', 'requester', 'approver'])->findOrFail($id);
        $this->dispatch('open-modal', name: 'order-details');
    }

    public function confirmCancel(int $id): void
    {
        $order = StockOrder::where('requesting_branch_id', $this->selectedBranchId)->findOrFail($id);
        if ($order->isPending()) {
            $this->cancelTargetId = $id;
            $this->cancelTargetRef = $order->reference_no;
            $this->dispatch('open-modal', name: 'confirm-cancel-order');
        }
    }

    public function confirmDelivery(int $id): void
    {
        $order = StockOrder::where('requesting_branch_id', $this->selectedBranchId)->findOrFail($id);
        if ($order->isInTransit()) {
            $this->deliverTargetId = $id;
            $this->deliverTargetRef = $order->reference_no;
            $this->dispatch('open-modal', name: 'confirm-delivery');
        }
    }
}
