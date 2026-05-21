<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use App\Models\User;
use App\Models\FinancialLedger;
use App\Models\SystemSetting;
use Carbon\Carbon;

use Livewire\WithPagination;

class KitchenDisplay extends Component
{
    use WithPagination;
    public $branchId;
    public $activeTab = 'active'; // active | ready | history
    public $perPage = 5;
    public $startDate;
    public $endDate;
    public $activeFilter = 'Today';
    
    // Rider Modal State
    public $showRiderModal = false;
    public $selectedOrderForDelivery = null;
    public $availableRiders = [];
    public $selectedRiderId = null;

    public function mount()
    {
        $this->branchId = auth()->user()->branch_id;
        $this->startDate = now()->startOfDay()->format('Y-m-d');
        $this->endDate = now()->endOfDay()->format('Y-m-d');
        $this->updateHeader();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
    }

    public function updatedEndDate()
    {
        $this->activeFilter = 'Custom Range';
        $this->resetPage();
    }

    public function applyQuickDateFilter($filter)
    {
        $this->activeFilter = ucfirst($filter === 'all' ? 'All Time' : ($filter === 'today' ? 'Today' : ($filter === 'week' ? 'Last 7 Days' : 'Last 30 Days')));
        
        switch ($filter) {
            case 'today':
                $this->startDate = now()->startOfDay()->format('Y-m-d');
                $this->endDate = now()->endOfDay()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->subDays(7)->startOfDay()->format('Y-m-d');
                $this->endDate = now()->endOfDay()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->subDays(30)->startOfDay()->format('Y-m-d');
                $this->endDate = now()->endOfDay()->format('Y-m-d');
                break;
            case 'all':
                $this->startDate = null;
                $this->endDate = null;
                break;
        }
        $this->resetPage();
    }

    public function updateHeader()
    {
        $this->dispatch('setHeader', 
            icon: 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
            title: 'Kitchen Display',
            breadcrumbs: [
                ['label' => 'Operations', 'url' => '#'],
                ['label' => 'KDS', 'url' => route('kds.index')],
            ]
        );
    }

    public function getKdsStatsProperty()
    {
        $baseQuery = Order::where('branch_id', $this->branchId);
        
        return [
            'active' => (clone $baseQuery)->where('status', Order::STATUS_PREPARING)->count(),
            'delayed' => (clone $baseQuery)->whereIn('status', [Order::STATUS_PREPARING, Order::STATUS_READY])
                ->where('created_at', '<', now()->subMinutes(10))
                ->count(),
            'ready' => (clone $baseQuery)->where('status', Order::STATUS_READY)->count(),
            'completed' => (clone $baseQuery)->where('status', Order::STATUS_COMPLETED)
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
        ];
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
    }

    public function getActiveOrdersProperty()
    {
        return Order::with(['items.product', 'items.options.option', 'items.modifiers.modifier'])
            ->where('branch_id', $this->branchId)
            ->where('status', Order::STATUS_PREPARING)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getReadyOrdersProperty()
    {
        return Order::with(['items.product', 'items.options.option', 'items.modifiers.modifier'])
            ->where('branch_id', $this->branchId)
            ->where('status', Order::STATUS_READY)
            ->orderBy('updated_at', 'desc')
            ->take(50)
            ->get();
    }

    public function getHistoryOrdersProperty()
    {
        $query = Order::with(['items.product', 'items.options.option', 'items.modifiers.modifier'])
            ->where('branch_id', $this->branchId)
            ->whereIn('status', [Order::STATUS_COMPLETED, Order::STATUS_OUT_FOR_DELIVERY]);

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        return $query->orderBy('updated_at', 'desc')
            ->paginate($this->perPage);
    }

    public function acceptOrder($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $order->updateStatus(Order::STATUS_PREPARING);
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} accepted.");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "Failed to accept order: " . $e->getMessage());
        }
    }

    public function markAsReady($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $order->updateStatus(Order::STATUS_READY);
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} is ready!");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "Error: " . $e->getMessage());
        }
    }

    public function markAsServed($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            // Branch logic based on order type
            if ($order->order_type === Order::TYPE_DELIVERY) {
                // Show rider selection modal instead of directly marking as out for delivery
                $this->selectedOrderForDelivery = $order->id;
                $this->availableRiders = $this->getAvailableRidersForBranch();
                $this->showRiderModal = true;
                $this->dispatch('open-modal', 'assign-rider-modal');
            } else {
                $order->updateStatus(Order::STATUS_COMPLETED);
                $msg = "Order #{$order->reference_no} served.";
                $this->dispatch('notify', type: 'success', message: $msg);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "Error: " . $e->getMessage());
        }
    }

    /**
     * Get available riders for the current branch
     */
    public function getAvailableRidersForBranch()
    {
        return User::where('branch_id', $this->branchId)
            ->where('role_id', 5) // 5 = Rider role
            ->where('is_active', true)
            ->select('id', 'first_name', 'last_name', 'phone')
            ->get()
            ->map(function ($rider) {
                // Count active deliveries for this rider
                $activeDeliveries = Order::where('rider_id', $rider->id)
                    ->whereIn('status', [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_PREPARING])
                    ->count();
                
                return [
                    'id' => $rider->id,
                    'name' => $rider->first_name . ' ' . $rider->last_name,
                    'phone' => $rider->phone,
                    'active_orders' => $activeDeliveries,
                ];
            })
            ->toArray();
    }

    /**
     * Assign rider to order and mark as out for delivery
     */
    public function assignRiderAndDeliver($riderId)
    {
        try {
            if (!$this->selectedOrderForDelivery || !$riderId) {
                throw new \Exception('Invalid order or rider selection.');
            }

            $order = Order::findOrFail($this->selectedOrderForDelivery);
            
            // Assign rider and update status
            $order->update(['rider_id' => $riderId]);
            $order->updateStatus(Order::STATUS_OUT_FOR_DELIVERY);

            // Get rider name for notification
            $rider = User::find($riderId);
            $riderName = $rider->first_name . ' ' . $rider->last_name;

            // Close modal and reset
            $this->closeRiderModal();
            
            $this->dispatch('notify', 
                type: 'success',
                message: "Order #{$order->reference_no} assigned to {$riderName} and ready for delivery."
            );
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "Error: " . $e->getMessage());
        }
    }

    /**
     * Close the rider selection modal
     */
    public function closeRiderModal()
    {
        $this->showRiderModal = false;
        $this->selectedOrderForDelivery = null;
        $this->availableRiders = [];
        $this->selectedRiderId = null;
        $this->dispatch('close-modal', 'assign-rider-modal');
    }

    public function render()
    {
        return view('livewire.kitchen-display', [
            'activeOrders' => $this->activeOrders,
            'readyOrders' => $this->readyOrders,
            'historyOrders' => $this->historyOrders,
            'showRiderModal' => $this->showRiderModal,
            'availableRiders' => $this->availableRiders,
            'selectedOrderForDelivery' => $this->selectedOrderForDelivery,
        ])->layout('layouts.app', ['noPadding' => true]);
    }
}
