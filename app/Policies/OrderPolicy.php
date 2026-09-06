<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine if the user can manage (load/delete) a draft order.
     * Cashiers/managers may only manage drafts belonging to their own branch;
     * super admins (role_id 1) may manage drafts on any branch.
     */
    public function manageDraft(User $user, Order $order): bool
    {
        if ($user->role_id === 1) {
            return true;
        }
        return $user->branch_id !== null
            && $order->branch_id === $user->branch_id;
    }

    /**
     * General guard for any state-changing action on an order (accept,
     * mark ready/delivered, cancel, hand to rider, void, refund, reject,
     * etc.). Same branch rule as manageDraft, kept separate so a future
     * change to draft-specific rules doesn't silently affect these.
     */
    public function manage(User $user, Order $order): bool
    {
        if ($user->role_id === 1) {
            return true;
        }
        return $user->branch_id !== null
            && $order->branch_id === $user->branch_id;
    }

    /**
     * Who may void an order. Currently identical to manage() — same-branch
     * staff plus super admins — but kept as its own ability so voiding can
     * later be restricted to a subset of roles (e.g. managers only)
     * without touching the broader manage() gate used everywhere else.
     */
    public function void(User $user, Order $order): bool
    {
        return $this->manage($user, $order);
    }
}