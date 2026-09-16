<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\StockOrder;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationService
{
    /**
     * Dispatch a single notification directly.
     */
    public static function send(array $data): Notification
    {
        return Notification::create([
            'user_id'   => $data['user_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'type'      => $data['type'] ?? 'general',
            'title'     => $data['title'] ?? '',
            'message'   => $data['message'] ?? '',
            'link'      => $data['link'] ?? null,
            'is_read'   => false,
        ]);
    }

    /**
     * Dispatch a notification to a specific user.
     */
    public static function sendToUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?int $branchId = null
    ): Notification {
        return self::send([
            'user_id'   => $userId,
            'branch_id' => $branchId,
            'type'      => $type,
            'title'     => $title,
            'message'   => $message,
            'link'      => $link,
        ]);
    }

    /**
     * Dispatch notifications to all active users belonging to a branch.
     * Optionally filter by specific role IDs (e.g. [2, 3] for Manager and Cashier).
     */
    public static function sendToBranch(
        int $branchId,
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?array $roleIds = null
    ): int {
        $query = User::where('branch_id', $branchId)->where('is_active', true);

        if (!empty($roleIds)) {
            $query->whereIn('role_id', $roleIds);
        }

        $users = $query->get();
        $count = 0;

        foreach ($users as $user) {
            self::send([
                'user_id'   => $user->id,
                'branch_id' => $branchId,
                'type'      => $type,
                'title'     => $title,
                'message'   => $message,
                'link'      => $link,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Dispatch notifications to Admins.
     * Super Admins (role 1) always receive it.
     * Branch Admins (role 2) receive it if matching the given branch ID (or all if branchId is null).
     */
    public static function sendToAdmins(
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        ?int $branchId = null
    ): int {
        $admins = User::whereIn('role_id', [1, 2])
            ->where('is_active', true)
            ->where(function ($q) use ($branchId) {
                $q->where('role_id', 1);
                if ($branchId) {
                    $q->orWhere(function ($sub) use ($branchId) {
                        $sub->where('role_id', 2)->where('branch_id', $branchId);
                    });
                } else {
                    $q->orWhere('role_id', 2);
                }
            })
            ->get();

        $count = 0;
        foreach ($admins as $admin) {
            self::send([
                'user_id'   => $admin->id,
                'branch_id' => $branchId ?: $admin->branch_id,
                'type'      => $type,
                'title'     => $title,
                'message'   => $message,
                'link'      => $link,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Resolve status-aware smart routing for notifications.
     */
    public static function resolveSmartLink(Notification $notification): ?string
    {
        if (!$notification->link) {
            return null;
        }

        $link = $notification->link;
        $user = Auth::user();

        // ── Smart Stock Order Routing ──────────────────────────────────────────
        if (str_contains($link, '/stock/orders')) {
            $parsedQuery = [];
            parse_str(parse_url($link, PHP_URL_QUERY) ?? '', $parsedQuery);

            $reference = $parsedQuery['oa_search']
                ?? $parsedQuery['so_search']
                ?? null;

            if (!$reference) {
                if (preg_match('/([A-Z]{2,4}-\d{8}-\d{4,})/i', $notification->message ?? '', $m)) {
                    $reference = $m[1];
                } elseif (preg_match('/([A-Z]{2,4}-\d{8}-\d{4,})/i', $notification->title ?? '', $m)) {
                    $reference = $m[1];
                }
            }

            if ($reference) {
                $order = StockOrder::where('reference_no', $reference)->first();

                if ($order) {
                    $isAdmin = $user && in_array($user->role_id, [1, 2]);

                    $historyStatuses = ['delivered', 'rejected', 'cancelled'];
                    $activeStatuses  = ['approved', 'preparing', 'in_transit'];

                    if (in_array($order->status, $historyStatuses)) {
                        $link = $isAdmin
                            ? route('stock.orders.admin', ['panel' => 'history', 'oa_search' => $reference])
                            : route('stock.orders',       ['panel' => 'history', 'so_search' => $reference]);
                    } elseif (in_array($order->status, $activeStatuses)) {
                        $link = $isAdmin
                            ? route('stock.orders.admin', ['panel' => 'active',   'oa_search' => $reference])
                            : route('stock.orders',       ['panel' => 'requests', 'so_search' => $reference]);
                    } else {
                        $link = $isAdmin
                            ? route('stock.orders.admin', ['panel' => 'inbox',    'oa_search' => $reference])
                            : route('stock.orders',       ['panel' => 'requests', 'so_search' => $reference]);
                    }
                }
            }
        }

        // ── Expiry Panel Rewrite ───────────────────────────────────────────────
        if (str_contains($link, 'panel=expiry')) {
            $parsedQuery = [];
            parse_str(parse_url($link, PHP_URL_QUERY) ?? '', $parsedQuery);
            $ingredientName = $parsedQuery['expirySearch'] ?? '';

            $statusFilter = str_contains(strtolower($notification->title), 'expir')
                ? (str_contains(strtolower($notification->title), 'expired') ? 'expired' : 'expiring')
                : 'all';

            $link = route('stock.index', array_filter([
                'panel'              => 'expiry',
                'expirySearch'       => $ingredientName,
                'expiryStatusFilter' => $statusFilter !== 'all' ? $statusFilter : null,
            ]));
        }

        return $link;
    }
}

