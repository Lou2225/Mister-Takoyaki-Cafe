<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;
use App\Traits\RequiresOperatingBranch;

class OrderManagement extends Component
{
    use WithPagination, HandlesValidations, RequiresOperatingBranch;

    // View Query & State
    public $search = '';
    public $statusFilter = '';
    public $startDate, $endDate;
    public $activeFilter = 'All Time';
    public $sourceFilter = 'App'; // 'App' | 'POS'
    public $perPage = 5;
    public $selectedOrderId = null;
    public $activeTab = 'summary'; // 'summary' | 'customer' | 'activity'

    // Detail & Action State
    public $selectedOrder = null;
    public $refundAmount = 0;
    public $refundReason = '';
    public $rejectReason = '';
    
    // Rider Assignment
    public $riders = [];
    public $selectedRiderId = null;

    protected $queryString = [
        'search'       => ['except' => '', 'as' => 'o_search'],
        'statusFilter' => ['except' => '', 'as' => 'o_status'],
        'startDate'    => ['except' => '', 'as' => 'o_start'],
        'endDate'      => ['except' => '', 'as' => 'o_end'],
        'sourceFilter' => ['except' => '', 'as' => 'o_src'],
        'perPage'      => ['except' => 5, 'as' => 'o_pp'],
        'activeTab'       => ['except' => 'summary', 'as' => 'o_tab'],
        'selectedOrderId' => ['except' => null, 'as' => 'oid'],
    ];


    public function mount()
    {
        $this->guardOperatingBranch();

        $this->startDate = '';
        $this->endDate = '';
        
        // Rehydrate selected order from URL if present
        if ($this->selectedOrderId) {
            $this->selectedOrder = Order::with(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider', 'proofOfDelivery'])->find($this->selectedOrderId);
            if (!$this->selectedOrder) {
                $this->backToList();
            } else {
                $this->dispatch('open-modal', name: 'view-order-detail');
            }
        }

        $this->updateHeader();
        $this->loadRiders();
    }

    public function loadRiders()
    {
        $this->riders = \App\Models\User::where('role_id', 5)
            ->where('branch_id', auth()->user()->branch_id)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Magic method to handle all updating* methods that reset pagination.
     */
    public function __call($method, $parameters)
    {
        if (str_starts_with($method, 'updating') && !str_ends_with($method, 'Page')) {
            $this->resetAllPages();
        }
    }

    public function updatedPerPage()
    {
        $this->resetAllPages();
    }

    /**
     * resetPage() with no argument only resets Livewire's default 'page'
     * query param, but this component uses three named paginators
     * (app_page/pos_page/history_page). Reset all three so filtering,
     * searching, or acting on an order never leaves someone stranded on
     * a now-empty page of a different tab.
     */
    private function resetAllPages()
    {
        $this->resetPage('app_page');
        $this->resetPage('pos_page');
        $this->resetPage('history_page');
    }

    public function updatedRefundReason()
    {
        $this->validateFieldLive('refundReason', ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME_BASIC], ValidationHelper::commonMessages());
    }

    public function updatedRefundAmount()
    {
        $this->validateFieldLive('refundAmount', ['required', 'numeric', 'min:0.01'], ValidationHelper::commonMessages());
    }

    public function updatedSourceFilter($value)
    {
        $this->resetAllPages();
        
        $validStatuses = [];
        if ($value === 'POS') {
            $validStatuses = [Order::STATUS_PENDING, Order::STATUS_DRAFTED, Order::STATUS_COMPLETED];
        } elseif ($value === 'App') {
            $validStatuses = [Order::STATUS_PENDING, Order::STATUS_PREPARING, Order::STATUS_READY, Order::STATUS_HANDED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_VOID, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED];
        } elseif ($value === 'History') {
            $validStatuses = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_VOID, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED];
        }

        if ($value !== '' && !in_array($this->statusFilter, $validStatuses)) {
            $this->statusFilter = ''; 
        }

        // Date filtering only applies to the History tab (completed/cancelled/refunded
        // records over time). Active orders (App/POS) shouldn't be date-scoped, so
        // clear any lingering range whenever the person leaves History.
        if ($value !== 'History') {
            $this->startDate = '';
            $this->endDate = '';
            $this->activeFilter = 'All Time';
        }
    }

    public function updatedStartDate()
    {
        $this->activeFilter = 'All Time';
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->activeFilter = 'All Time';
        $this->resetPage();
    }

    public function applyQuickDateFilter($range)
    {
        switch ($range) {
            case 'today':
                $this->startDate = today()->toDateString();
                $this->endDate = today()->toDateString();
                $this->activeFilter = 'Today Only';
                break;
            case 'week':
                $this->startDate = today()->subDays(7)->toDateString();
                $this->endDate = today()->toDateString();
                $this->activeFilter = 'Last 7 Days';
                break;
            case 'month':
                $this->startDate = today()->subDays(30)->toDateString();
                $this->endDate = today()->toDateString();
                $this->activeFilter = 'Last 30 Days';
                break;
            case 'all':
                $this->startDate = '';
                $this->endDate = '';
                $this->activeFilter = 'All Time';
                break;
        }
        $this->resetAllPages();
    }

    private function buildOrdersQuery($sourceTab)
    {
        // The list only needs branch data. Full order relationships are loaded
        // on demand when a user opens an order detail or receipt.
        $query = Order::with(['branch']);

        // Must match the 1-hour void/refund window used by Order::canBeVoided()/canBeRefunded()
        $voidRefundCutoff = now()->subHour();

        // Filter by source
        if ($sourceTab === 'History') {
            $query->where(function ($q) use ($voidRefundCutoff) {
                $q->whereIn('status', [
                    Order::STATUS_CANCELLED,
                    Order::STATUS_VOID,
                    Order::STATUS_REFUNDED,
                    Order::STATUS_PARTIALLY_REFUNDED
                ])
                // Completed POS/App orders only move to History once their
                // 1-hour void/refund window has passed.
                ->orWhere(function ($q2) use ($voidRefundCutoff) {
                    $q2->where('status', Order::STATUS_COMPLETED)
                       ->where('created_at', '<', $voidRefundCutoff);
                });
            });
        } elseif ($sourceTab === 'POS') {
            $query->where('source', $sourceTab);
            $query->where(function ($q) use ($voidRefundCutoff) {
                $q->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_DRAFTED])
                  // Recently completed POS orders stay visible here while
                  // still within the void/refund window.
                  ->orWhere(function ($q2) use ($voidRefundCutoff) {
                      $q2->where('status', Order::STATUS_COMPLETED)
                         ->where('created_at', '>=', $voidRefundCutoff);
                  });
            });
        } else {
            // App / Delivery Orders — this is the active/live view. Recently
            // completed, voided, or refunded orders remain visible briefly,
            // but cancelled/rejected orders go straight to Order History.
            $query->where('source', $sourceTab);
            $query->where(function ($q) use ($voidRefundCutoff) {
                $q->whereNotIn('status', [
                        Order::STATUS_COMPLETED,
                        Order::STATUS_CANCELLED,
                        Order::STATUS_VOID,
                        Order::STATUS_REFUNDED,
                        Order::STATUS_PARTIALLY_REFUNDED,
                    ])
                    ->orWhere(function ($q2) use ($voidRefundCutoff) {
                        $q2->whereIn('status', [
                                Order::STATUS_COMPLETED,
                                Order::STATUS_VOID,
                                Order::STATUS_REFUNDED,
                                Order::STATUS_PARTIALLY_REFUNDED,
                            ])
                            ->where('created_at', '>=', $voidRefundCutoff);
                    });
            });
        }

        // Search by reference number or customer details
        if (!empty($this->search)) {
            $query->byReference($this->search);
        }

        // Filter by status
        if (!empty($this->statusFilter)) {
            $validStatuses = [];
            if ($sourceTab === 'POS') {
                $validStatuses = [Order::STATUS_PENDING, Order::STATUS_DRAFTED, Order::STATUS_COMPLETED];
            } elseif ($sourceTab === 'App') {
                $validStatuses = [Order::STATUS_PENDING, Order::STATUS_PREPARING, Order::STATUS_READY, Order::STATUS_HANDED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_VOID, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED];
            } elseif ($sourceTab === 'History') {
                $validStatuses = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_VOID, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED];
            }

            if (in_array($this->statusFilter, $validStatuses)) {
                $query->where('status', $this->statusFilter);
            }
        }

        // Filter by branch
        $query->where('branch_id', auth()->user()->branch_id);

        // Filter by date range
        if ($this->startDate && $this->endDate) {
            $query->whereBetween('created_at', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function getAppOrdersProperty()
    {
        return $this->buildOrdersQuery('App')->paginate($this->perPage, ['*'], 'app_page');
    }

    public function getPosOrdersProperty()
    {
        return $this->buildOrdersQuery('POS')->paginate($this->perPage, ['*'], 'pos_page');
    }

    public function getHistoryOrdersProperty()
    {
        return $this->buildOrdersQuery('History')->paginate($this->perPage, ['*'], 'history_page');
    }

    public function getOrdersProperty()
    {
        if ($this->sourceFilter === 'App') {
            return $this->appOrders;
        } elseif ($this->sourceFilter === 'POS') {
            return $this->posOrders;
        } else {
            return $this->historyOrders;
        }
    }

    public function openOrderDetail($orderId)
    {
        // Reload order with all necessary relationships
        $this->selectedOrder = Order::with(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider'])
            ->findOrFail($orderId);
        $this->selectedOrderId = $this->selectedOrder->id;
        $this->activeTab = 'summary';
        $this->dispatch('open-modal', name: 'view-order-detail');
        $this->dispatch('update-active-tab', activeTab: 'summary');
    }

    public function backToList()
    {
        $this->selectedOrder = null;
        $this->selectedOrderId = null;
        $this->updateHeader('list');
        $this->dispatch('close-modal', name: 'view-order-detail');
    }

    public function closeDetailModal()
    {
        $this->backToList();
        $this->refundAmount = 0;
        $this->refundReason = '';
        $this->dispatch('close-modal', 'refund-modal');
        $this->dispatch('close-modal', 'confirm-void-order');
        $this->dispatch('close-modal', 'receipt-modal');
        $this->dispatch('close-modal', 'reject-modal');
    }

    public function openRejectModal(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) return;
        if ($order->status !== Order::STATUS_PENDING) return;
        $this->selectedOrder = $order;
        $this->rejectReason = '';
        $this->dispatch('open-modal', 'reject-modal');
    }

    public function openRefundModal(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        if (!$order->canBeRefunded()) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'This order cannot be refunded.'
            );
            return;
        }

        $this->selectedOrder = $order;
        $this->refundAmount = $order->refundable_amount;
        $this->dispatch('open-modal', 'refund-modal');
    }

    public function openVoidModal(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        if (!$order->canBeVoided()) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'This order cannot be voided.'
            );
            return;
        }

        $this->selectedOrder = $order;
        $this->dispatch('open-modal', 'confirm-void-order');
    }

    public function openReceiptModal(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) return;
        $this->selectedOrder = $order;
        $this->dispatch('open-modal', 'receipt-modal');
    }

    public function confirmVoid()
    {
        $order = $this->selectedOrder;

        // Authorization check — kept consistent with openVoidModal(), which
        // only checks 'manage'. The previous extra 'void' ability check
        // caused every void to fail here even for authorized staff, since
        // the modal already opened successfully under the 'manage' gate
        // alone but this stricter second check silently blocked the confirm.
        if (!$order || !auth()->user()->can('manage', $order)) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'You do not have permission to void this order.'
            );
            return;
        }

        $order->void('Voided by ' . auth()->user()->name);

        $this->dispatch('notify', 
            type: 'success',
            message: "Order #{$order->reference_no} has been voided."
        );

        $this->dispatch('close-modal', 'confirm-void-order');
        $this->backToList();
        $this->resetAllPages();
    }

    public function cancelVoid()
    {
    }


    public function submitRefund()
    {
        $order = $this->selectedOrder;

        if (!$order || !auth()->user()->can('manage', $order) || !$order->canBeRefunded()) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'Refund not possible.'
            );
            return;
        }

        // Standardized Validation
        $this->validate([
            'refundAmount' => ['required', 'numeric', 'min:0.01', 'max:' . $order->refundable_amount],
            'refundReason' => ['required', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME_BASIC],
        ], ValidationHelper::commonMessages());

        try {
            $order->refund($this->refundAmount, $this->refundReason ?? 'Refund');

            // Refresh selected order
            $this->selectedOrder = Order::with(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider'])
                ->find($order->id);

            $this->dispatch('notify', 
                type: 'success',
                message: "Refund of ₱" . number_format($this->refundAmount, 2) . " processed."
            );

            $this->refundAmount = 0;
            $this->refundReason = '';
            $this->dispatch('close-modal', 'refund-modal');
        } catch (\Exception $e) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'Refund failed: ' . $e->getMessage()
            );
        }
    }

        public function acceptOrder(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        try {
            $order->updateStatus(Order::STATUS_PREPARING);
            // Refresh selected order if it's currently being viewed
            if ($this->selectedOrderId === $order->id) {
                $this->selectedOrder = Order::with(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider'])
                    ->find($order->id);
            }
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} accepted and preparing.");
            $this->backToList();

            // 🖨️ Auto-print, same mechanism as POS's confirmPayment(). The
            // browser-side listener decides Bluetooth vs. browser-popup based
            // on window.thermalBluetoothPrinter's live connection state —
            // whichever printer POS is currently paired with is what fires here too.
            $this->dispatch('send-thermal-print', order_id: $order->id, receipt_type: 'all');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function markAsOutForDelivery(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        try {
            $order->updateStatus(Order::STATUS_OUT_FOR_DELIVERY);
            // Refresh selected order if it's currently being viewed
            if ($this->selectedOrderId === $order->id) {
                $this->selectedOrder = Order::with(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider'])
                    ->find($order->id);
            }
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} is out for delivery.");
            $this->backToList();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function openHandToRiderModal(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) return;
        $this->selectedOrder = $order;
        $this->selectedOrderId = $order->id;
        $this->selectedRiderId = $order->rider_id;
        $this->dispatch('open-modal', 'hand-to-rider-modal');
    }

    public function handToRider()
    {
        $this->validate([
            'selectedRiderId' => 'required|exists:users,id'
        ], [
            'selectedRiderId.required' => 'Please select a rider first.'
        ]);

        try {
            $order = Order::findOrFail($this->selectedOrderId);
            if (!auth()->user()->can('manage', $order)) {
                $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
                return;
            }
            $order->updateStatus(Order::STATUS_HANDED_TO_RIDER, [
                'rider_id' => $this->selectedRiderId
            ]);

            // Refresh state
            if ($this->selectedOrderId === $order->id) {
                $this->selectedOrder = $order->load(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider']);
            }

            $this->dispatch('notify', 
                type: 'success', 
                message: "Order #{$order->reference_no} handed to {$order->rider->first_name}."
            );
            
            $this->dispatch('close-modal', 'hand-to-rider-modal');
            $this->backToList();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function markAsDelivered(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        try {
            $order->updateStatus(Order::STATUS_COMPLETED);
            // Refresh selected order if it's currently being viewed
            if ($this->selectedOrderId === $order->id) {
                $this->selectedOrder = Order::with(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider'])
                    ->find($order->id);
            }
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} marked as completed.");
            $this->backToList();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    public function cancelOrder(Order $order)
    {
        if (!auth()->user()->can('manage', $order)) return;
        if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PREPARING])) return;
        $order->update(['status' => Order::STATUS_CANCELLED]);

        // Refresh selected order if it's currently being viewed
        if ($this->selectedOrderId === $order->id) {
            $this->selectedOrder = Order::with(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider'])
                ->find($order->id);
        }

        event(new OrderStatusUpdated($order));

        $this->dispatch('notify', type: 'info', message: "Order #{$order->reference_no} cancelled.");
    }

    public function rejectOrder()
    {
        if (!$this->selectedOrder || !auth()->user()->can('manage', $this->selectedOrder)) return;
        if ($this->selectedOrder->status !== Order::STATUS_PENDING) return;
        
        $order = $this->selectedOrder;
        $reason = trim($this->rejectReason) ?: "Rejected by branch staff.";

        $order->reject($reason);

        // Refresh selected order if it's currently being viewed
        if ($this->selectedOrderId === $order->id) {
            $this->selectedOrder = $order->fresh(['branch', 'user', 'items.product', 'customer', 'refundedBy', 'rider']);
        }

        $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} rejected.");
        $this->dispatch('close-modal', 'reject-modal');
        $this->backToList();
    }

    public function restoreDraft(Order $order)
    {
        if (!auth()->user()->can('manageDraft', $order)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        if (!$order->isDrafted()) {
            return;
        }

        // Convert draft items back to cart
        $cartData = [];
        foreach ($order->items as $item) {
            $cartData[] = [
                'id' => $item->product_id,
                'name' => $item->product->name,
                'price' => $item->unit_price,
                'qty' => $item->quantity,
                'options' => $item->options->toArray(),
                'modifiers' => $item->modifiers->toArray(),
            ];
        }

        // Store in session and redirect to POS
        session(['draft_cart' => $cartData, 'draft_order_id' => $order->id]);

        return redirect()->route('pos.index')->with('draft_loaded', true);
    }

    public function deleteDraft(Order $order)
    {
        if (!auth()->user()->can('manageDraft', $order)) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized.');
            return;
        }

        if (!$order->isDrafted()) {
            return;
        }

        $refNo = $order->reference_no;
        $order->delete();

        $this->dispatch('notify', 
            type: 'success',
            message: "Draft #{$refNo} deleted."
        );

        $this->backToList();
        $this->resetAllPages();
    }

    private function updateHeader($state = 'list')
    {
        $title = 'Order Management';
        $breadcrumbs = [
            ['label' => 'Sales', 'url' => '#'],
            ['label' => 'Orders', 'url' => route('orders.index')],
        ];

        if ($state === 'detail') {
            $breadcrumbs[] = ['label' => 'Order Details', 'url' => '#'];
            $title = 'Order #' . ($this->selectedOrder?->reference_no ?? 'Details');
        }

        $this->dispatch('setHeader', 
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
            title: $title,
            breadcrumbs: $breadcrumbs
        );
    }

    public function render()
    {
        $allStatuses = [
            Order::STATUS_COMPLETED => 'Completed',
            Order::STATUS_PENDING => 'Pending',
            Order::STATUS_PREPARING => 'Preparing',
            Order::STATUS_READY => 'Ready',
            Order::STATUS_HANDED_TO_RIDER => 'Handed to Rider',
            Order::STATUS_OUT_FOR_DELIVERY => 'Out for Delivery',
            Order::STATUS_CANCELLED => 'Cancelled',
            Order::STATUS_DRAFTED => 'Draft',
            Order::STATUS_VOID => 'Voided',
            Order::STATUS_REFUNDED => 'Refunded',
            Order::STATUS_PARTIALLY_REFUNDED => 'Partially Refunded',
        ];

        $validStatuses = [];
        if ($this->sourceFilter === 'POS') {
            $validStatuses = [Order::STATUS_PENDING, Order::STATUS_DRAFTED, Order::STATUS_COMPLETED];
        } elseif ($this->sourceFilter === 'App') {
            $validStatuses = [Order::STATUS_PENDING, Order::STATUS_PREPARING, Order::STATUS_READY, Order::STATUS_HANDED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_VOID, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED];
        } elseif ($this->sourceFilter === 'History') {
            $validStatuses = [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED, Order::STATUS_VOID, Order::STATUS_REFUNDED, Order::STATUS_PARTIALLY_REFUNDED];
        }

        $filteredStatuses = [];
        foreach ($validStatuses as $status) {
            if (isset($allStatuses[$status])) {
                $filteredStatuses[$status] = $allStatuses[$status];
            }
        }

        return view('livewire.order-management', [
            'orders' => $this->orders,
            'appOrders' => $this->appOrders,
            'posOrders' => $this->posOrders,
            'historyOrders' => $this->historyOrders,
            'statuses' => $filteredStatuses,
        ])->layout('layouts.app');
    }
}
