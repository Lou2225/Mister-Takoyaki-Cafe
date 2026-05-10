<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Notification;
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

        if ($notification->link) {
            return redirect($notification->link);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.notification-history', [
            'notifications' => $this->baseQuery()->paginate($this->perPage),
        ]);
    }
}
