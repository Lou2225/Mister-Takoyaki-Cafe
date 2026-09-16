<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Notification;
use App\Services\BranchContext;
use App\Services\NotificationService;
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

    protected function activeBranchId(): ?int
    {
        $user = Auth::user();
        return BranchContext::getActiveBranchId() ?: ($user?->branch_id);
    }

    private function baseQuery()
    {
        $user     = Auth::user();
        $branchId = $this->activeBranchId();

        return Notification::query()
            ->where(fn ($q) => $q->where('user_id', $user?->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->when($this->search, fn ($q) => $q->where(
                fn ($sq) => $sq->where('title',   'like', "%{$this->search}%")
                               ->orWhere('message', 'like', "%{$this->search}%")
            ))
            ->when($this->type, function ($q) {
                if ($this->type === 'stock') {
                    $q->whereIn('type', ['stock', 'stock_order']);
                } else {
                    $q->where('type', $this->type);
                }
            })
            ->latest();
    }

    public function markAsRead(int $id): void
    {
        $notification = $this->baseQuery()->where('id', $id)->first();
        if ($notification) {
            $notification->update(['is_read' => true]);
            $this->dispatch('refreshTopbar');
        }
    }

    public function markAllAsRead(): void
    {
        $this->baseQuery()->where('is_read', false)->update(['is_read' => true]);
        $this->dispatch('refreshTopbar');
    }

    public function deleteNotification(int $id): void
    {
        $notification = $this->baseQuery()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
            $this->dispatch('refreshTopbar');
        }
    }

    public function clearAll(): void
    {
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
        $notification = $this->baseQuery()->where('id', $id)->first();
        if (!$notification) return null;

        $notification->update(['is_read' => true]);
        $this->dispatch('refreshTopbar');

        $targetLink = NotificationService::resolveSmartLink($notification);
        if ($targetLink) {
            $this->redirect($targetLink, navigate: true);
        }

        return null;
    }

    private function statsQuery()
    {
        $user     = Auth::user();
        $branchId = $this->activeBranchId();

        return Notification::query()
            ->where(fn ($q) => $q->where('user_id', $user?->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }

    public function render()
    {
        $stats = [
            'total'    => $this->statsQuery()->count(),
            'unread'   => $this->statsQuery()->where('is_read', false)->count(),
            'critical' => $this->statsQuery()->whereIn('type', ['expiry', 'stock', 'stock_order'])->where('is_read', false)->count(),
            'recent'   => $this->statsQuery()->where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return view('livewire.notification-history', [
            'notifications' => $this->baseQuery()->paginate($this->perPage),
            'stats'         => $stats,
        ])->layout('layouts.app');
    }
}
