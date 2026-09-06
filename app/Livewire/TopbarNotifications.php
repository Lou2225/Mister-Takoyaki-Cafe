<?php
namespace App\Livewire;
use Livewire\Component;
use Livewire\Attributes\Poll;
use App\Models\Notification;
use App\Services\BranchContext;
use Illuminate\Support\Facades\Auth;

#[Poll(5000)]  // polls every 5 seconds via Livewire, not wire:poll on the blade
class TopbarNotifications extends Component
{
    protected $listeners = [
        'refreshTopbar' => '$refresh',
        'order-submitted' => '$refresh',
        'refreshHistory' => '$refresh'
    ];

    private function activeBranchId(): ?int
    {
        $user = Auth::user();
        return BranchContext::getActiveBranchId() ?: ($user?->branch_id);
    }

    private function getNotificationsQuery()
    {
        $user = Auth::user();
        if (!$user) return null;
        $branchId = $this->activeBranchId();
        return Notification::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->latest();
    }

    public function markAsRead(int $id): void
    {
        $notification = Notification::find($id);
        if ($notification && $notification->user_id === Auth::id()) {
            $notification->update(['is_read' => true]);
            $this->dispatch('refreshHistory');
        }
    }

    public function markAllAsRead(): void
    {
        $query = $this->getNotificationsQuery();
        if ($query) {
            $query->where('is_read', false)->update(['is_read' => true]);
            $this->dispatch('refreshHistory');
        }
    }

    public function traceNotification(int $id)
    {
        $notification = Notification::find($id);
        if (!$notification) return;
        $notification->update(['is_read' => true]);
        $this->dispatch('refreshHistory');
        if ($notification->link) {
            $this->redirect($notification->link, navigate: true);
        }
    }

    public function render()
    {
        $notifications = [];
        $unreadCount = 0;
        $query = $this->getNotificationsQuery();
        if ($query) {
            $notifications = $query->take(5)->get()->map(function ($n) {
                $color = match ($n->type) {
                    'stock', 'stock_order' => 'indigo',
                    'expiry'               => 'amber',
                    default                => 'indigo',
                };
                return [
                    'id'      => $n->id,
                    'type'    => $n->type,
                    'title'   => $n->title,
                    'message' => $n->message,
                    'link'    => $n->link,
                    'is_read' => $n->is_read,
                    'time'    => $n->created_at->diffForHumans(null, true, true),
                    'color'   => $color,
                ];
            });
            $unreadCount = $this->getNotificationsQuery()->where('is_read', false)->count();
        }
        return view('livewire.topbar-notifications', [
            'notifications' => $notifications,
            'unreadCount'   => $unreadCount,
        ]);
    }
}