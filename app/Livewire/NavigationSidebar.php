<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\BranchContext;
use App\Models\StockOrder;

class NavigationSidebar extends Component
{
    protected $listeners = [
        'branch-switched' => '$refresh',
        'branchContextUpdated' => '$refresh',
        'settingsUpdated' => '$refresh',
        'refreshSidebar' => '$refresh',
        'order-submitted' => '$refresh',
        'order-processed' => '$refresh',
    ];

    public function getPendingOrdersCountProperty()
    {
        // Only fetch count if user is authorized to see the Order Inbox
        $user = auth()->user();
        if (!$user) return 0;

        $mainBranch = \App\Models\Branch::where('is_main', true)->first();
        $isAuthorized = ($user->isSuperAdmin() || ($user->branch && $user->branch->is_main));

        if (!$isAuthorized) return 0;

        return StockOrder::where('status', 'pending')->count();
    }

    public function render()
    {
        return view('livewire.navigation-sidebar');
    }
}
