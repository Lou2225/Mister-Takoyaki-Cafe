<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Branch;
use App\Services\BranchContext;
use App\Models\Notification;
use App\Models\BranchIngredientStock;
use App\Models\StockBatch;
use App\Models\CustomerReview;
use Carbon\Carbon;

class Topbar extends Component
{
    // ── Public properties ──────────────────────────────────────────────────
    public string $contextLabel = '';
    public string $contextValue = '';
    public string $iconColor    = 'text-indigo-500';
    public string $firstName    = '';
    public string $lastName     = '';
    public string $roleName     = '';
    public string $initials     = '';
    public string $email        = '';

    public array $notifications = [];
    public int   $unreadCount   = 0;

    protected $listeners = [
        'settingsUpdated'      => '$refresh',
        'branch-switched'      => '$refresh',
        'branchContextUpdated' => '$refresh',
        'refreshTopbar'        => '$refresh',
    ];

    public function mount(): void
    {
        $this->loadUserProfile();
    }

    protected function activeBranchId(): ?int
    {
        $user = auth()->user();
        return BranchContext::getActiveBranchId() ?: ($user?->branch_id);
    }

    private function loadUserProfile(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $branch = Branch::find($this->activeBranchId());

        $this->contextLabel = $user->isSuperAdmin() ? 'Global Context' : 'Assigned Branch';
        $this->contextValue = $branch?->branch_name
            ?? ($user->isSuperAdmin() ? 'General Headquarters' : 'No Branch Assigned');

        $this->iconColor = match ((int) $user->role_id) {
            1       => 'text-indigo-500',
            2       => 'text-rose-500',
            default => 'text-emerald-500',
        };

        $this->firstName = $user->first_name  ?? '';
        $this->lastName  = $user->last_name   ?? '';
        $this->roleName  = $user->role?->name ?? 'Admin';
        $this->email     = $user->email       ?? '';
        $this->initials  = strtoupper(substr($this->firstName, 0, 1))
                         . strtoupper(substr($this->lastName,  0, 1));
    }

    private function fetchNotifications(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $branchId = $this->activeBranchId();

        // Throttle alert generation (e.g., every 5 mins per user)
        $cacheKey = 'notifications_last_gen_' . $user->id . '_' . ($branchId ?? 'all');
        if (!cache()->has($cacheKey)) {
            $this->generateSystemAlerts($user, $branchId);
            cache()->put($cacheKey, true, now()->addMinutes(5));
        }

        $this->notifications = Notification::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->latest()
            ->take(15)
            ->get()
            ->map(fn ($n) => [
                'id'      => $n->id,
                'type'    => $n->type,
                'title'   => $n->title,
                'message' => $n->message,
                'link'    => $n->link,
                'is_read' => (bool) $n->is_read,
                'time'    => $n->created_at->diffForHumans(),
                'color'   => $this->getNotificationColor($n->type),
                'icon'    => $this->getNotificationIcon($n->type),
            ])
            ->toArray();

        $this->unreadCount = Notification::where('is_read', false)
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->count();
    }

    private function generateSystemAlerts($user, ?int $branchId): void
    {
        // 1. Low Stock
        $lowStocks = BranchIngredientStock::with(['ingredient', 'branch'])
            ->whereHas('ingredient', fn ($q) =>
                $q->whereRaw('branch_ingredient_stocks.stock_quantity < ingredients.minimum_stock')
            )
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        foreach ($lowStocks as $ls) {
            // Check if an unread notification for THIS ingredient in THIS branch already exists
            // We use the ingredient name in the title to make it a stable key
            $title = "Low Stock: {$ls->ingredient->name}";
            $msg   = "{$ls->ingredient->name} is low ({$ls->stock_quantity} remaining) in {$ls->branch->branch_name}.";

            // If an unread one exists, update it. If not, and no unread one exists, create a new one.
            // We check for 'is_read' => false in the match array to avoid duplicating unread alerts.
            Notification::updateOrCreate(
                [
                    'type'      => 'stock', 
                    'title'     => $title, 
                    'branch_id' => $ls->branch_id,
                    'is_read'   => false
                ],
                [
                    'message' => $msg,
                    'link'    => route('stock.index', ['st_search' => $ls->ingredient->name]),
                ]
            );
        }

        // 2. Expiry (within 7 days)
        $expiring = StockBatch::with(['ingredient', 'branch'])
            ->where('current_quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays(7))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->get();

        foreach ($expiring as $ex) {
            $days    = now()->diffInDays(Carbon::parse($ex->expiry_date), false);
            $status  = $days <= 0 ? 'Expired' : 'Expiring Soon';
            $title   = "{$status}: {$ex->ingredient->name}";
            $msg     = $days <= 0 ? 'Expired batch found!' : "Batch expiring in {$days} days.";
            $fullMsg = "{$ex->ingredient->name} in {$ex->branch->branch_name}: {$msg}";
            
            Notification::updateOrCreate(
                [
                    'type'      => 'expiry', 
                    'title'     => $title, 
                    'branch_id' => $ex->branch_id,
                    'is_read'   => false
                ],
                [
                    'message' => $fullMsg,
                    'link'    => route('stock.index', ['panel' => 'expiry', 'expirySearch' => $ex->ingredient->name]),
                ]
            );
        }

        // 3. New Reviews (last 24 h)
        $newReviews = CustomerReview::where('created_at', '>=', now()->subDay())->get();
        foreach ($newReviews as $rev) {
            $title = "New Review: {$rev->customer_name}";
            $msg   = "New {$rev->rating}-star review from {$rev->customer_name}.";
            
            Notification::updateOrCreate(
                [
                    'type'    => 'review', 
                    'title'   => $title,
                    'is_read' => false
                ],
                [
                    'message' => $msg,
                    'link'    => route('customers.index')
                ]
            );
        }
    }

    private function getNotificationColor(string $type): string
    {
        return match ($type) {
            'stock'  => 'amber',
            'expiry' => 'rose',
            'order'  => 'indigo',
            'review' => 'emerald',
            default  => 'slate',
        };
    }

    private function getNotificationIcon(string $type): string
    {
        return match ($type) {
            'stock'  => 'inventory',
            'expiry' => 'timer',
            'order'  => 'shopping_cart',
            'review' => 'star',
            default  => 'notifications',
        };
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function markAsRead(int $id): void
    {
        $notification = Notification::find($id);
        if ($notification) {
            $notification->update(['is_read' => true]);
        }
    }

    public function markAllAsRead(): void
    {
        $user     = auth()->user();
        $branchId = $this->activeBranchId();

        Notification::where('is_read', false)
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereNull('user_id'))
            ->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
            ->update(['is_read' => true]);
    }

    public function traceNotification(int $id): mixed
    {
        $notification = Notification::find($id);
        if (!$notification) return null;

        $notification->update(['is_read' => true]);

        if ($notification->link) {
            return redirect($notification->link);
        }

        $this->dispatchBrowserEvent('close-notifications');
        return null;
    }

    public function render()
    {
        $this->loadUserProfile();
        $this->fetchNotifications();

        return view('livewire.topbar');
    }
}
