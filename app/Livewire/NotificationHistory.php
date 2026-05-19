<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Notification;
use App\Models\StockOrder;
use App\Services\BranchContext;
use Illuminate\Support\Facades\Auth;

class NotificationHistory extends Component
{
    use WithPagination;

    public string $search  = '';
    public string $type    = '';
    public int    $perPage = 10;

    protected $listeners = ['refreshHistory' => '$refresh'];

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedType(): void   { $this->resetPage(); }

    // ── Fix #5: shared helper mirrors Topbar::activeBranchId() ──────────────
    protected function activeBranchId(): ?int
    {
        $user = Auth::user();
        return BranchContext::getActiveBranchId() ?: ($user?->branch_id);
    }

    // ── Fix #2/#5: baseQuery() now correctly scopes to user + active branch ──
    private function baseQuery()
    {
        $user     = Auth::user();
        $branchId = $this->activeBranchId();

        return Notification::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->when($this->search, fn ($q) => $q->where(
                fn ($sq) => $sq->where('title',   'like', "%{$this->search}%")
                               ->orWhere('message', 'like', "%{$this->search}%")
            ))
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->latest();
    }

    public function markAsRead(int $id): void
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->update(['is_read' => true]);
            $this->dispatch('refreshTopbar');
        }
    }

    // Fix #2/#8: markAllAsRead respects the current filters (search + type + branch)
    public function markAllAsRead(): void
    {
        // Clone the base query and restrict to unread only — respects all active filters
        $this->baseQuery()->where('is_read', false)->update(['is_read' => true]);
        $this->dispatch('refreshTopbar');
    }

    public function deleteNotification(int $id): void
    {
        Notification::destroy($id);
        $this->dispatch('refreshTopbar');
    }

    // Fix #8: clearAll() intentionally clears ALL (ignores filters) — documented explicitly
    public function clearAll(): void
    {
        // Intentionally ignores search/type filters — clears all user+branch notifications.
        // This mirrors the UI confirm text: "clear all notification history".
        $user     = Auth::user();
        $branchId = $this->activeBranchId();

        Notification::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->delete();

        $this->dispatch('refreshTopbar');
    }

    public function trace(int $id): mixed
    {
        $notification = Notification::find($id);
        if (!$notification) return null;

        $notification->update(['is_read' => true]);
        $this->dispatch('refreshTopbar');

        if (!$notification->link) return null;

        $link = $notification->link;
        $user = Auth::user();

        // ── Smart Stock Order Routing ──────────────────────────────────────────
        // Detects links to stock order pages (both admin and branch views)
        // Extracts the STR reference, looks up the real current status,
        // then routes to the correct tab (inbox/active/history) with the filter.
        if (str_contains($link, '/stock/orders')) {
            $parsedQuery = [];
            parse_str(parse_url($link, PHP_URL_QUERY), $parsedQuery);

            // Support both old & new query param keys
            $reference = $parsedQuery['oa_search']
                ?? $parsedQuery['so_search']
                ?? null;

            // If no reference in URL, try extracting from the notification message
            if (!$reference) {
                if (preg_match('/([A-Z]{2,4}-\d{8}-\d{4,})/i', $notification->message ?? '', $m)) {
                    $reference = $m[1];
                } elseif (preg_match('/([A-Z]{2,4}-\d{8}-\d{4,})/i', $notification->title ?? '', $m)) {
                    $reference = $m[1];
                }
            }

            if ($reference) {
                $order = StockOrder::where('reference_no', $reference)->first();

                if ($order) {
                    $isAdmin = in_array($user->role_id, [1, 2]);

                    // Map order status to the correct panel/tab
                    $historyStatuses = ['delivered', 'rejected', 'cancelled'];
                    $activeStatuses  = ['approved', 'preparing', 'in_transit'];

                    if (in_array($order->status, $historyStatuses)) {
                        // Completed — go to History tab
                        $link = $isAdmin
                            ? route('stock.orders.admin', ['panel' => 'history', 'oa_search' => $reference])
                            : route('stock.orders',       ['panel' => 'history', 'so_search' => $reference]);
                    } elseif (in_array($order->status, $activeStatuses)) {
                        // In-progress — go to Active tab (admin) or Requests tab (branch)
                        $link = $isAdmin
                            ? route('stock.orders.admin', ['panel' => 'active',  'oa_search' => $reference])
                            : route('stock.orders',       ['panel' => 'requests','so_search' => $reference]);
                    } else {
                        // Pending — go to Inbox tab (admin) or Requests tab (branch)
                        $link = $isAdmin
                            ? route('stock.orders.admin', ['panel' => 'inbox',   'oa_search' => $reference])
                            : route('stock.orders',       ['panel' => 'requests','so_search' => $reference]);
                    }
                }
            }
        }

        // ── Expiry Panel Rewrite ───────────────────────────────────────────────
        // Routes notifications directly to the unified Stock & Ingredients dashboard
        if (str_contains($link, 'panel=expiry')) {
            $parsedQuery = [];
            parse_str(parse_url($link, PHP_URL_QUERY), $parsedQuery);
            $ingredientName = $parsedQuery['expirySearch'] ?? '';

            $statusFilter = str_contains(strtolower($notification->title), 'expir')
                ? (str_contains(strtolower($notification->title), 'expired') ? 'expired' : 'expiring')
                : 'all';

            $link = route('stock.index', array_filter([
                'panel'              => 'expiry',
                'expirySearch'       => $ingredientName,
                'expiryStatusFilter' => $statusFilter !== 'all' ? $statusFilter : null,
            ]));
        }

        $this->redirect($link, navigate: true);
        return null;
    }

    private function statsQuery()
    {
        $user     = Auth::user();
        $branchId = $this->activeBranchId();

        return Notification::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }

    public function render()
    {
        $stats = [
            'total' => $this->statsQuery()->count(),
            'unread' => $this->statsQuery()->where('is_read', false)->count(),
            'critical' => $this->statsQuery()->whereIn('type', ['expiry', 'stock'])->where('is_read', false)->count(),
            'recent' => $this->statsQuery()->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('livewire.notification-history', [
            'notifications' => $this->baseQuery()->paginate($this->perPage),
            'stats' => $stats,
        ])->layout('layouts.app');
    }
}
