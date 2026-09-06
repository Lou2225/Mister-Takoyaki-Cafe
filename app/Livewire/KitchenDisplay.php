<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use App\Models\User;
use App\Models\FinancialLedger;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class KitchenDisplay extends Component
{
    use WithPagination;

        public int $branchId = 0;
    public string $activeTab = 'active';
    public int $perPage = 5;
    public ?string $startDate = null;
    public ?string $endDate = null;
    public string $activeFilter = 'Today';
    public int $delayThresholdMinutes = 10;

    // Rider Modal State
    public bool $showRiderModal = false;
    public mixed $selectedOrderForDelivery = null;
    public array $availableRiders = [];
    public mixed $selectedRiderId = null;

        public function mount()
{
    $this->branchId = auth()->user()->branch_id;
    $this->startDate = now()->startOfDay()->format('Y-m-d');
    $this->endDate   = now()->endOfDay()->format('Y-m-d');
    $this->delayThresholdMinutes = (int) SystemSetting::get('kds_delay_threshold_minutes', 10);
    $this->updateHeader();
}

public function refreshStats()
{
    // Intentionally empty — called by wire:poll to refresh
    // stats and order counts without resetting $activeTab
        // This keeps the component reactive and updates all computed properties
    $this->resetPage();
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

    public function applyQuickDateFilter(string $filter)
    {
        $this->activeFilter = match($filter) {
            'today' => 'Today',
            'week'  => 'Last 7 Days',
            'month' => 'Last 30 Days',
            'all'   => 'All Time',
            default => ucfirst($filter),
        };

        switch ($filter) {
            case 'today':
                $this->startDate = now()->startOfDay()->format('Y-m-d');
                $this->endDate   = now()->endOfDay()->format('Y-m-d');
                break;
            case 'week':
                $this->startDate = now()->subDays(7)->startOfDay()->format('Y-m-d');
                $this->endDate   = now()->endOfDay()->format('Y-m-d');
                break;
            case 'month':
                $this->startDate = now()->subDays(30)->startOfDay()->format('Y-m-d');
                $this->endDate   = now()->endOfDay()->format('Y-m-d');
                break;
            case 'all':
                $this->startDate = null;
                $this->endDate   = null;
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
                ['label' => 'KDS',        'url' => route('kds.index')],
            ]
        );
    }

    // ─── Computed Properties ──────────────────────────────────────────────────

    public function getKdsStatsProperty()
    {
        $base = Order::where('branch_id', $this->branchId);

        return [
            'active' => (clone $base)
                ->where('status', Order::STATUS_PREPARING)
                ->count(),

                        'delayed' => (clone $base)
                ->whereIn('status', [Order::STATUS_PREPARING, Order::STATUS_READY])
                ->where('created_at', '<', now()->subMinutes($this->delayThresholdMinutes))
                ->count(),

            'ready' => (clone $base)
                ->where('status', Order::STATUS_READY)
                ->count(),

            'completed' => (clone $base)
                ->where('status', Order::STATUS_COMPLETED)
                ->where('created_at', '>=', now()->startOfDay())
                ->count(),
        ];
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
            ->whereIn('status', [
    Order::STATUS_COMPLETED,
    Order::STATUS_OUT_FOR_DELIVERY,
    Order::STATUS_HANDED_TO_RIDER,
]);

        if ($this->startDate) {
            $query->whereDate('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('created_at', '<=', $this->endDate);
        }

        return $query->orderBy('updated_at', 'desc')
            ->paginate($this->perPage);
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function acceptOrder(mixed $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $order->updateStatus(Order::STATUS_PREPARING);
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} accepted.");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "Failed to accept order: " . $e->getMessage());
        }
    }

    public function markAsReady(mixed $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $order->updateStatus(Order::STATUS_READY);
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} is ready!");
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "Error: " . $e->getMessage());
        }
    }

    public function markAsServed(mixed $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);

            if ($order->order_type === Order::TYPE_DELIVERY) {
                // Open the rider selection modal
                $this->selectedOrderForDelivery = $order->id;
                $this->availableRiders          = $this->getAvailableRidersForBranch();
                $this->showRiderModal           = true;
            } else {
                // Non-delivery: mark completed immediately
                $order->updateStatus(Order::STATUS_COMPLETED);
                $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} served.");
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: "Error: " . $e->getMessage());
        }
    }

    /**
     * Fetch riders belonging to this branch who are active.
     */
    public function getAvailableRidersForBranch(): array
    {
        return User::where('branch_id', $this->branchId)
            ->where('role_id', 5)        // 5 = Rider role
            ->where('is_active', true)
            ->select('id', 'first_name', 'last_name', 'phone')
            ->get()
            ->map(function (User $rider) {
                $activeDeliveries = Order::where('rider_id', $rider->id)
                    ->whereIn('status', [
                        Order::STATUS_OUT_FOR_DELIVERY,
                        Order::STATUS_PREPARING,
                    ])
                    ->count();

                return [
                    'id'            => $rider->id,
                    'name'          => $rider->first_name . ' ' . $rider->last_name,
                    'phone'         => $rider->phone ?? 'N/A',
                    'active_orders' => $activeDeliveries,
                ];
            })
            ->toArray();
    }

    /**
     * FIX: Save rider_id first via update(), THEN change status.
     *      The original code passed rider_id as a second argument to
     *      updateStatus() which the method does not accept, so the
     *      rider was never saved and STATUS_HANDED_TO_RIDER threw an
     *      undefined-constant error.
     */
    public function assignRiderAndDeliver(mixed $riderId)
{
    try {
        if (! $this->selectedOrderForDelivery || ! $riderId) {
            throw new \Exception('Invalid order or rider selection.');
        }

        $order = Order::findOrFail($this->selectedOrderForDelivery);
        $rider = User::findOrFail($riderId);

        // updateStatus() merges additionalData into the update payload,
        // so rider_id is saved atomically inside the same DB transaction.
        $order->updateStatus(Order::STATUS_HANDED_TO_RIDER, [
            'rider_id' => $riderId,
        ]);

        $this->closeRiderModal();

        $this->dispatch('notify',
            type: 'success',
            message: "Order #{$order->reference_no} handed to {$rider->first_name}. Waiting for rider to accept."
        );
    } catch (\Exception $e) {
        Log::error('assignRiderAndDeliver error: ' . $e->getMessage());
        $this->dispatch('notify', type: 'error', message: "Error: " . $e->getMessage());
    }
}

    public function closeRiderModal()
    {
        $this->showRiderModal            = false;
        $this->selectedOrderForDelivery  = null;
        $this->availableRiders           = [];
        $this->selectedRiderId           = null;
    }

    // ─── Render ──────────────────────────────────────────────────────────────

        public function render()
{
    try {
        return view('livewire.kitchen-display', [
            'activeOrders'          => $this->activeOrders,
            'readyOrders'           => $this->readyOrders,
            'historyOrders'         => $this->historyOrders,
            'availableRiders'       => $this->availableRiders,
            'showRiderModal'        => $this->showRiderModal,
            'delayThresholdMinutes' => $this->delayThresholdMinutes,
        ])->layout('layouts.app', ['noPadding' => true]);
    } catch (\Exception $e) {
        Log::error('KitchenDisplay render error: ' . $e->getMessage());
        throw $e;
    }
}

// Always load all orders — Alpine decides which tab to show
// so we never filter by $activeTab on the server side
}