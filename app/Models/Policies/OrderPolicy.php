<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        // Super admins can view any order
        if ($user->role_id === 1) {
            return true;
        }

        // Users can view orders from their own branch
        if ($user->role_id === 2) {
            return $user->branch_id === $order->branch_id;
        }

        // POS users can view orders they created
        return $user->id === $order->user_id;
    }

    /**
     * Determine whether the user can void an order.
     */
    public function void(User $user, Order $order): bool
    {
        // Only super admins and branch admins can void
        if ($user->role_id > 2) {
            return false;
        }

        // Branch admins can only void orders from their branch
        if ($user->role_id === 2) {
            return $user->branch_id === $order->branch_id && $order->canBeVoided();
        }

        // Super admins can void any order
        return $order->canBeVoided();
    }

    /**
     * Determine whether the user can refund an order.
     */
    public function refund(User $user, Order $order): bool
    {
        // Only super admins and branch admins can refund
        if ($user->role_id > 2) {
            return false;
        }

        // Branch admins can only refund orders from their branch
        if ($user->role_id === 2) {
            return $user->branch_id === $order->branch_id && $order->canBeRefunded();
        }

        // Super admins can refund any order
        return $order->canBeRefunded();
    }

    /**
     * Determine whether the user can manage draft orders.
     */
    public function manageDraft(User $user, Order $order): bool
    {
        // Only the creator or admins can manage drafts
        if ($user->id === $order->user_id) {
            return true;
        }

        // Admins can manage any draft in their branch or globally
        if ($user->role_id === 2) {
            return $user->branch_id === $order->branch_id;
        }

        return $user->role_id === 1;
    }
}
