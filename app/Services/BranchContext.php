<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Session;

class BranchContext
{
    /**
     * Get the active branch ID for the current session.
     * 
     * For Super Admins (Role 1), it returns the session-overridden ID if set.
     * For other roles, it returns their fixed branch_id.
     */
    public static function getActiveBranchId()
    {
        $user = auth()->user();

        if (!$user) {
            return null;
        }

        // Super Admin (Role 1)
        if ($user->role_id === 1) {
            return Session::get('active_branch_id') ?? $user->branch_id;
        }

        // Admin/Staff (Roles 2, 3)
        return $user->branch_id;
    }

    /**
     * Get the active branch model instance.
     */
    public static function getActiveBranch()
    {
        $id = self::getActiveBranchId();
        
        if (!$id) {
            return null;
        }

        return Branch::find($id);
    }

    /**
     * Set the active branch ID in session (Super Admin only).
     */
    public static function setActiveBranch($branchId)
    {
        if (auth()->user()?->role_id === 1) {
            if ($branchId === 'all' || !$branchId) {
                Session::forget('active_branch_id');
            } else {
                Session::put('active_branch_id', $branchId);
            }
        }
    }
}
