<?php

namespace App\Traits;

use App\Models\Branch;
use App\Services\BranchContext;

trait RequiresOperatingBranch
{
    /**
     * Blocks a super admin from entering an operational module (POS, Order
     * Management, Kitchen Display) when no operating branch is selected.
     * Non-super-admins are unaffected — they're always scoped to their own
     * assigned branch_id and never hit this condition.
     */
    protected function guardOperatingBranch(): void
    {
        if (auth()->user()->isSuperAdmin() && !BranchContext::getActiveBranchId()) {
            abort(403, 'Select an operating branch in Settings before accessing this module.');
        }
    }

    /**
     * Blocks access to the HQ "Branch Requests" inbox unless the current
     * operating context is actually the Main Branch — matches the same
     * is_main-aware rule the sidebar uses to decide whether to show that link.
     */
    protected function guardMainBranchContext(): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $activeBranchId = BranchContext::getActiveBranchId();
            $activeBranch = $activeBranchId ? Branch::find($activeBranchId) : null;

            if (!$activeBranch || !$activeBranch->is_main) {
                abort(403, 'Switch your operating branch to the Main Branch to access this module.');
            }
            return;
        }

        if (!$user->branch || !$user->branch->is_main) {
            abort(403, 'This module is only available to Main Branch staff.');
        }
    }
}