<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class RiderOrderController extends Controller
{
    public function getOrders(Request $request)
    {
        // Get the logged in rider
        $user = $request->user();

        // We get the ID from the Role object, or fallback to role_id if the object isn't loaded
        $userRoleId = is_object($user->role) ? $user->role->id : $user->role_id;

        if ($userRoleId != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only riders can access this.'
            ], 403);
        }

        // Get the requested status or default to rider-assigned delivery stages.
        // This covers both OrderManagement handoff and KDS delivery assignment flows.
        $statusParam = $request->query('status');
        $statuses = $statusParam
            ? array_map('trim', explode(',', $statusParam))
            : [Order::STATUS_HANDED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY];

        // Query delivery orders assigned to THIS rider
        $orders = Order::with(['items.product', 'branch', 'customer'])
            ->where('rider_id', $user->id)
            ->where('order_type', Order::TYPE_DELIVERY)
            ->whereIn('status', $statuses)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $orderData = $order->toArray();
                unset($orderData['tax_amount']);
                return $orderData;
            });

        return response()->json([
            'success' => true,
            'message' => 'Rider orders fetched successfully',
            'data' => $orders
        ], 200);
    }
}