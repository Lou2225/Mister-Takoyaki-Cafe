<?php

namespace App\Http\Livewire;

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

class BranchStockOrdering extends Component
{
    use WithPagination, HandlesValidations;

    // ── Panel / View State ────────────────────────────────────────
    public $panel = 'requests';

    // ── Branch Context ────────────────────────────────────────────
    public $selectedBranchId = '';
    public $mainBranchId     = '';

    // ── Detail Side Panel ─────────────────────────────────────────
    public $selectedOrderId   = null;
    public $selectedOrder     = null;

    // ── Cart (new request form) ───────────────────────────────────
    public $cartItems        = [];
    public $cartIngredientId = '';
    public $cartQty          = '';
    public $cartUnit         = '';  // The unit the user picks: base ('g') or packaging ('box', 'bottle')
    public $cartPrice        = 0;
    public $cartNotes        = '';
    public $orderPriority    = 'normal';
    public $orderNotes       = '';
    public $deliveryFee      = 0;
    public $globalRate       = 50; // default PHP per KM
    public $branchDistance   = 0;

    // ── Filters ───────────────────────────────────────────────────
    public $search       = '';
    public $statusFilter = 'all';
    public $perPage      = 10;

    // ── Modal state ───────────────────────────────────────────────
    public $cancelTargetId   = null;
    public $cancelTargetRef  = '';
    public $deliverTargetId  = null;
    public $deliverTargetRef = '';

    protected $queryString = [
        'panel'        => ['except' => 'requests'],
        'search'       => ['except' => '', 'as' => 'so_search'],
        'statusFilter' => ['except' => 'all', 'as' => 'so_status'],
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
            $this->selectedBranchId = BranchContext::getActiveBranchId();
            
            // Safety: Ensure we aren't ordering FROM the main branch TO the main branch
            if (!$this->selectedBranchId || $this->selectedBranchId == $this->mainBranchId) {
                $this->selectedBranchId = Branch::where('is_main', false)->first()?->id;
            }
        } else {
            $this->selectedBranchId = $user->branch_id;
        }

        if (!$this->selectedBranchId) {
            session()->flash('error', 'No active branch context found for ordering.');
            $this->redirect(route('dashboard'));
        }

        $this->calculateEstimatedFee();
        $this->updateHeader();
    }

    // ── Role Helpers ──────────────────────────────────────────────
    public function isSuperAdmin(): bool { return auth()->user()->role_id === 1; }
    public function isAdmin(): bool      { return auth()->user()->role_id === 2; }

    public function updatedSelectedBranchId(): void
    {
        $this->calculateEstimatedFee();
        $this->clearCart(); // Clear cart when branch context changes to prevent cross-branch leaks
    }

    private function calculateEstimatedFee(): void
    {
        $branch = Branch::find($this->selectedBranchId);
        $this->branchDistance = $branch->distance_from_main ?? 0;
        $this->deliveryFee = (float)$this->branchDistance * (float)$this->globalRate;
    }

    // ── Cart Management ───────────────────────────────────────────

    public function updatedCartIngredientId($value): void
    {
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

        $this->dispatchBrowserEvent('open-modal', ['name' => 'confirm-submit-order']);
    }

    public function submitOrder(): void
    {
        if (empty($this->cartItems)) return;

        $this->validate([
            'orderPriority' => 'required|in:normal,urgent,critical',
            'orderNotes'    => 'nullable|string|max:500',
        ], [
            'orderPriority.required' => 'Please select a priority level.',
            'orderPriority.in'       => 'Invalid priority selected.',
        ]);

        try {
            DB::transaction(function () {
                $totalAmount = collect($this->cartItems)->sum('subtotal') + (float) $this->deliveryFee;

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
                        'order_unit'         => $item['order_unit'],
                        'unit_price'         => $item['unit_price'],
                        'subtotal'           => $item['subtotal'],
                        'notes'              => $item['notes'] ?: null,
                    ]);
                }

                $this->notifyAdmins(
                    '📦 New Stock Request',
                    "New request ({$order->reference_no}) from " . Branch::find($this->selectedBranchId)?->branch_name,
                    route('stock.orders.admin'),
                    $order
                );
            });

            $this->clearCart();
            $this->panel = 'requests';
            $this->dispatchBrowserEvent('close-modal', 'confirm-submit-order');
            $this->notify('success', 'Stock request submitted!');
            $this->emit('order-submitted');
            $this->updateHeader();

        } catch (\Exception $e) {
            $this->notify('error', 'Failed to submit order: ' . $e->getMessage());
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
        $this->dispatchBrowserEvent('close-modal', 'confirm-cancel-order');
        $this->dispatchBrowserEvent('close-modal', 'order-details');
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
        $this->dispatchBrowserEvent('close-modal', 'confirm-delivery');
        $this->dispatchBrowserEvent('close-modal', 'order-details');
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
        $this->dispatchBrowserEvent('notify', ['type' => $type, 'message' => $message]);
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

            if ($order) {
                try {
                    Mail::to($admin->email)->queue(new StockOrderMail($order, $title, $message));
                } catch (\Exception $e) {}
            }
        }
    }

    private function updateHeader(): void
    {
        $this->emit('setHeader', [
            'icon'        => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
            'title'       => 'Stock Requests',
            'breadcrumbs' => [['label' => 'Inventory', 'url' => '#'], ['label' => 'Stock Requests', 'url' => route('stock.orders')]],
        ]);
    }

    // ── Rendering ─────────────────────────────────────────────────

    public function render()
    {
        return view('livewire.branch-stock-ordering', [
            'orders'        => $this->getOrders(),
            'kpis'          => $this->getKpis(),
            'lowStockItems' => $this->getLowStockItems(),
            'ingredients'   => Ingredient::with('unitConversions')->orderBy('name')->get(),
            'branchStock'   => BranchIngredientStock::where('branch_id', $this->selectedBranchId)->pluck('stock_quantity', 'ingredient_id'),
            'mainStock'     => BranchIngredientStock::where('branch_id', $this->mainBranchId)->pluck('stock_quantity', 'ingredient_id'),
        ])->layout('layouts.app');
    }

    private function getOrders()
    {
        $activeStatuses  = ['pending', 'approved', 'preparing', 'in_transit'];
        $historyStatuses = ['delivered', 'rejected', 'cancelled'];

        return StockOrder::with(['items.ingredient', 'sourceBranch', 'approver', 'requester'])
            ->where('requesting_branch_id', $this->selectedBranchId)
            ->where(function($q) use ($activeStatuses, $historyStatuses) {
                if ($this->panel === 'requests') {
                    $q->whereIn('status', $activeStatuses);
                } else {
                    $q->whereIn('status', $historyStatuses);
                }
            })
            ->when($this->statusFilter !== 'all', fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn($q) => $q->where('reference_no', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate($this->perPage);
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

    private function getLowStockItems()
    {
        // Fetch all ingredients that have a minimum stock requirement
        $ingredients = Ingredient::where('minimum_stock', '>', 0)->get();
        
        // Fetch current branch stock levels for these ingredients
        $branchStock = BranchIngredientStock::where('branch_id', $this->selectedBranchId)
            ->whereIn('ingredient_id', $ingredients->pluck('id'))
            ->pluck('stock_quantity', 'ingredient_id');

        return $ingredients->map(function($ing) use ($branchStock) {
            $currentStock = $branchStock[$ing->id] ?? 0;
            
            if ($currentStock <= $ing->minimum_stock) {
                return [
                    'id'      => $ing->id,
                    'name'    => $ing->name,
                    'current' => $currentStock,
                    'deficit' => max(1, $ing->minimum_stock - $currentStock),
                    'in_cart' => $this->isInCart($ing->id),
                ];
            }
            return null;
        })->filter()->values();
    }

    public function viewOrder(int $id): void
    {
        $this->selectedOrderId = $id;
        $this->selectedOrder = StockOrder::with(['items.ingredient', 'requestingBranch', 'sourceBranch', 'requester', 'approver'])->findOrFail($id);
        $this->dispatchBrowserEvent('open-modal', ['name' => 'order-details']);
    }

    public function confirmCancel(int $id): void
    {
        $order = StockOrder::where('requesting_branch_id', $this->selectedBranchId)->findOrFail($id);
        if ($order->isPending()) {
            $this->cancelTargetId = $id;
            $this->cancelTargetRef = $order->reference_no;
            $this->dispatchBrowserEvent('open-modal', ['name' => 'confirm-cancel-order']);
        }
    }

    public function confirmDelivery(int $id): void
    {
        $order = StockOrder::where('requesting_branch_id', $this->selectedBranchId)->findOrFail($id);
        if ($order->isInTransit()) {
            $this->deliverTargetId = $id;
            $this->deliverTargetRef = $order->reference_no;
            $this->dispatchBrowserEvent('open-modal', ['name' => 'confirm-delivery']);
        }
    }
}
