<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\BranchContext;
use App\Models\StockOrder;

class NavigationSidebar extends Component
{
    #[On('refreshSidebar')]
    #[On('order-submitted')]
    #[On('order-processed')]
    public function refreshSidebarState(): void
    {
        //
    }

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

    /**
     * The sidebar's own source of truth for whether POS/Orders/KDS should
     * be hidden — always recomputed fresh, never a stale stored flag.
     *
     * hide_modules on the user record is a personal preference ("I don't
     * want to see these"). For a super admin, we additionally hide the
     * moment there's no operating branch selected in BranchContext,
     * because those modules are branch-scoped and would 403 anyway if
     * clicked — showing the link at that point is a broken promise.
     */
    public function getEffectiveHideOperationalModulesProperty(): bool
    {
        $user = auth()->user();
        if (!$user) return true;

        if ($user->isSuperAdmin()) {
            return (bool) $user->hide_modules || !BranchContext::getActiveBranchId();
        }

        return (bool) $user->hide_modules;
    }

    public function getIsSubBranchProperty(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        $activeBranch = BranchContext::getActiveBranch();
        return (bool) ($activeBranch && !$activeBranch->is_main);
    }

    public function getShowOrderInboxProperty(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        $activeBranch = BranchContext::getActiveBranch();
        $isAuthorized = ($user->isSuperAdmin() || ($user->branch && $user->branch->is_main));

        return (bool) ($isAuthorized && $activeBranch && $activeBranch->is_main);
    }

    public function getEffectiveShowRequestSuppliesProperty(): bool
    {
        return !$this->effectiveHideOperationalModules && $this->isSubBranch;
    }

    public function getEffectiveShowBranchRequestsProperty(): bool
    {
        return !$this->effectiveHideOperationalModules && $this->showOrderInbox;
    }

    public function render()
    {
        return view('livewire.navigation-sidebar');
    }

    public function getContextLabelProperty(): array
    {
        $user = auth()->user();
        $branch = \App\Models\Branch::find(BranchContext::getActiveBranchId() ?: $user?->branch_id);

        return [
            'label' => $user?->isSuperAdmin() ? 'Global Context' : 'Assigned Branch',
            'value' => $branch?->branch_name ?? ($user?->isSuperAdmin() ? 'General Headquarters' : 'No Branch Assigned'),
        ];
    }
}
