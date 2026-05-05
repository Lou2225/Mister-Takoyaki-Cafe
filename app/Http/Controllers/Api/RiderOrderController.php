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

        // Get the requested status (Handed to Rider, Out for Delivery, delivered, etc.)
        // Default to 'Handed to Rider' — the initial state when staff gives the order to the rider
        $status = $request->query('status', Order::STATUS_HANDED_TO_RIDER);

        // Query orders assigned to THIS rider
        $orders = Order::with('items.product')
            ->where('rider_id', $user->id)
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Rider orders fetched successfully',
            'data' => $orders
        ], 200);
    }
}