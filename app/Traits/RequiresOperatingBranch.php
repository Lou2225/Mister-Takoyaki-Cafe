<?php

namespace App\Traits;

use App\Models\Branch;
use App\Services\BranchContext;

trait RequiresOperatingBranch
{
    /**
     * Checks if the user has an operating branch selected.
     * Returns false if a Super Admin is currently at General Headquarters (no operating branch).
     */
    protected function hasOperatingBranch(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($user->isSuperAdmin()) {
            return (bool) BranchContext::getActiveBranchId();
        }

        return (bool) $user->branch_id;
    }

    /**
     * Checks if the user is operating within the Main Branch context.
     */
    protected function isMainBranchContext(): bool
    {
        $user = auth()->user();
        if (!$user) return false;

        if ($user->isSuperAdmin()) {
            $activeBranchId = BranchContext::getActiveBranchId();
            $activeBranch = $activeBranchId ? Branch::find($activeBranchId) : null;
            return (bool) ($activeBranch && $activeBranch->is_main);
        }

        return (bool) ($user->branch && $user->branch->is_main);
    }

    /**
     * Quick-switches the active operating branch directly from the placeholder UI.
     */
    public function quickSwitchBranch(int $branchId): void
    {
        if (auth()->user()?->isSuperAdmin()) {
            BranchContext::setActiveBranch($branchId);
            $this->dispatch('branch-switched', branchId: $branchId);
            $this->dispatch('notify', type: 'success', message: 'Operating branch switched successfully.');
            if (method_exists($this, 'mount')) {
                $this->redirect(request()->header('Referer') ?: url()->current());
            }
        }
    }

    /**
     * Legacy guard retained for compatibility; checks if operating branch is available.
     */
    protected function guardOperatingBranch(): bool
    {
        return $this->hasOperatingBranch();
    }

    /**
     * Legacy guard retained for compatibility; checks if main branch is active.
     */
    protected function guardMainBranchContext(): bool
    {
        return $this->isMainBranchContext();
    }
}