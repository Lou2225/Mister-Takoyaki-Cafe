<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    public function sync(Request $request)
    {
        $token = $request->bearerToken();
        if ($token !== config('services.sync.token', 'MisterTakoyakiSync_2026!Secure')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $items = $request->input('items', []);
        $synced = [];
        $failed = [];

        foreach ($items as $item) {
            try {
                DB::transaction(function () use ($item) {
                    $payload = $item['payload'];
                    
                    $order = Order::create([
                        'reference_no' => 'OFF-' . strtoupper(Str::random(8)),
                        'branch_id' => 1, // Default branch or passed from payload
                        'total_amount' => $payload['total_amount'],
                        'status' => Order::STATUS_COMPLETED,
                        'order_type' => 'Offline POS',
                        'payment_method' => 'Cash',
                        'notes' => 'Customer: ' . ($payload['customer_name'] ?? 'Walk-in'),
                        'created_at' => $payload['created_at'] ?? now(),
                    ]);

                    foreach ($payload['items'] as $cartItem) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $cartItem['id'],
                            'quantity' => $cartItem['qty'],
                            'unit_price' => $cartItem['price'],
                            'subtotal' => $cartItem['price'] * $cartItem['qty'],
                        ]);
                    }
                });
                
                $synced[] = $item['id'];
            } catch (\Exception $e) {
                Log::error('Sync failed for item ' . $item['id'] . ': ' . $e->getMessage());
                $failed[] = [
                    'id' => $item['id'],
                    'reason' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'synced' => $synced,
            'failed' => $failed
        ]);
    }
}
