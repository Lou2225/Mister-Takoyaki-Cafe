<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ProofOfDelivery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RiderOrderController extends Controller
{
   public function index(Request $request)
    {
        $user = $request->user();
    
        // ✅ Check if user is a rider (role_id = 5)
        if (!$user->isRider()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only riders can access this.'
            ], 403);
        }
    
        // ✅ FIX: Use explicit mapping instead of ucwords()
        // ucwords() capitalizes EVERY word ("Handed To Rider"), but DB stores "Handed to Rider"
        $statusMap = [
            'handed_to_rider'  => Order::STATUS_HANDED_TO_RIDER,
            'out_for_delivery' => Order::STATUS_OUT_FOR_DELIVERY,
            'completed'        => Order::STATUS_COMPLETED,
            'delivered'        => Order::STATUS_COMPLETED,
            'pending'          => Order::STATUS_PENDING,
            'preparing'        => Order::STATUS_PREPARING,
            'ready'            => Order::STATUS_READY,
            'cancelled'        => Order::STATUS_CANCELLED,
        ];
    
        $statusParam = $request->query('status');
        $statuses = $statusParam
            ? array_values(array_filter(
                array_map(fn($s) => $statusMap[strtolower(trim($s))] ?? null, explode(',', $statusParam))
              ))
            : [Order::STATUS_HANDED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY];
    
        try {
            // ✅ Query orders assigned to THIS rider with all relations
            $orders = Order::with(['items.product', 'items.options.option', 'items.modifiers.modifier', 'customer', 'proofOfDelivery'])
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
    
        } catch (\Throwable $e) {
            Log::error('[RiderAPI] Failed to fetch rider orders: ' . $e->getMessage(), [
                'rider_id' => $user->id,
                'statuses' => $statuses,
                'trace' => $e->getTraceAsString(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders: ' . $e->getMessage()
            ], 500);
        }
    }
    

    /**
     * POST /api/rider/orders/{id}/accept
     * Rider accepts an order - changes status to 'Out for Delivery'
     */
    public function acceptOrder(Request $request, int $id)
    {
        $user = $request->user();

        if (!$user->isRider()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only riders can accept orders.'
            ], 403);
        }

        try {
            $order = Order::findOrFail($id);

            // ✅ Verify order is assigned to this rider
            if ($order->rider_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order is not assigned to you'
                ], 403);
            }

            // ✅ Verify status is 'Handed to Rider'
            if ($order->status !== Order::STATUS_HANDED_TO_RIDER) {
                return response()->json([
                    'success' => false,
                    'message' => "Order must be in 'Handed to Rider' status. Current: {$order->status}"
                ], 400);
            }

            // ✅ Update status to 'Out for Delivery'
            $order->updateStatus(Order::STATUS_OUT_FOR_DELIVERY);

            Log::info("[RiderAPI] Rider {$user->id} accepted order {$order->id}");

            return response()->json([
                'success' => true,
                'message' => 'Order accepted successfully',
                'data' => $order->load(['items.product', 'items.options.option', 'items.modifiers.modifier'])
                    ->makeHidden(['tax_amount'])
                    ->toArray()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("[RiderAPI] Failed to accept order: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/rider/orders/{id}/deliver
     *
     * Rider marks order as delivered - now requires Proof of Delivery.
     *
     * Flow:
     *   1. Authenticate + verify rider role (unchanged).
     *   2. Find order, verify ownership, verify 'Out for Delivery' (unchanged).
     *   3. Validate proof_photo (+ optional latitude/longitude).
     *   4. Store the photo on the 'public' disk.
     *   5. DB transaction: create ProofOfDelivery row, then call
     *      $order->updateStatus(Order::STATUS_COMPLETED) — the existing
     *      lifecycle method, untouched. Anything that fails inside rolls
     *      back the DB changes; the stored file is deleted so we never
     *      leave an orphaned upload behind.
     *   6. Return the order with its proofOfDelivery relation loaded.
     */
    public function deliverOrder(Request $request, int $id)
    {
        $user = $request->user();

        if (!$user->isRider()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only riders can deliver orders.'
            ], 403);
        }

        try {
            $order = Order::findOrFail($id);

            // ✅ Verify order is assigned to this rider
            if ($order->rider_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order is not assigned to you'
                ], 403);
            }

            // ✅ Verify status is 'Out for Delivery'
            // This single check also covers duplicate-submission protection:
            // an already-Completed order can never be 'Out for Delivery'
            // again, so a second POD submission for the same order is
            // rejected here before any validation or file handling happens.
            if ($order->status !== Order::STATUS_OUT_FOR_DELIVERY) {
                return response()->json([
                    'success' => false,
                    'message' => "Order must be 'Out for Delivery'. Current: {$order->status}"
                ], 400);
            }

            // ✅ Validate the POD photo (and optional final GPS fix)
            try {
                $validated = $request->validate([
                    'proof_photo' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'], // 5MB
                    'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
                    'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
                ], [
                    'proof_photo.required' => 'Please provide a proof of delivery photo.',
                    'proof_photo.image'    => 'Please select a valid image.',
                    'proof_photo.mimes'    => 'Please select a valid image.',
                    'proof_photo.max'      => 'Please select a valid image.',
                ]);
            } catch (ValidationException $ve) {
                return response()->json([
                    'success' => false,
                    'message' => $ve->errors()[array_key_first($ve->errors())][0] ?? 'Validation failed.',
                    'errors'  => $ve->errors(),
                ], 422);
            }

            // ✅ Store the photo BEFORE the transaction. If anything inside
            // the transaction fails, we manually delete this file in the
            // catch block below so we never leave an orphaned upload with
            // no matching ProofOfDelivery row.
            $photoPath = $request->file('proof_photo')->store('proof-of-delivery', 'public');

            try {
                $order = DB::transaction(function () use ($order, $user, $photoPath, $validated) {
                    ProofOfDelivery::create([
                        'order_id'    => $order->id,
                        'rider_id'    => $user->id,
                        'photo_path'  => $photoPath,
                        'latitude'    => $validated['latitude'] ?? null,
                        'longitude'   => $validated['longitude'] ?? null,
                        // Server-side timestamp — never trust a client-supplied
                        // delivery time for the authoritative completion record.
                        'captured_at' => now(),
                    ]);

                    // Existing lifecycle method — untouched. Handles
                    // delivered_at, COD payment_status flip, and the
                    // OrderStatusUpdated broadcast exactly as before.
                    $order->updateStatus(Order::STATUS_COMPLETED);

                    return $order;
                });
            } catch (\Throwable $e) {
                // Roll back the filesystem write too — otherwise we'd have
                // a stored photo with no ProofOfDelivery row pointing to it.
                Storage::disk('public')->delete($photoPath);
                throw $e;
            }

            Log::info("[RiderAPI] Rider {$user->id} delivered order {$order->id} with POD");

            return response()->json([
                'success' => true,
                'message' => 'Order marked as delivered',
                'data' => $order->load([
                        'items.product',
                        'items.options.option',
                        'items.modifiers.modifier',
                        'proofOfDelivery',
                    ])
                    ->makeHidden(['tax_amount'])
                    ->toArray()
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("[RiderAPI] Failed to deliver order: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Unable to upload proof of delivery. Please try again.'
            ], 500);
        }
    }
    
        /**
     * POST /api/rider/orders/{id}/location
     * Rider pings their current GPS position while actively delivering.
     * Only accepted while the order is assigned to this rider AND is in an
     * active-delivery status — prevents stale/completed orders from showing
     * a moving rider, and prevents a rider from writing to an order that
     * isn't theirs.
     */
    public function updateLocation(Request $request, int $id)
    {
        $user = $request->user();
    
        if (!$user->isRider()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only riders can update location.'
            ], 403);
        }
    
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);
    
        $order = Order::find($id);
    
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
    
        if ($order->rider_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'This order is not assigned to you'
            ], 403);
        }
    
        if (!in_array($order->status, [Order::STATUS_HANDED_TO_RIDER, Order::STATUS_OUT_FOR_DELIVERY])) {
            // Silently accept but no-op — avoids the rider app treating this
            // as a hard error if a stray ping arrives right as status flips.
            return response()->json([
                'success' => true,
                'message' => 'Order no longer active for tracking',
            ], 200);
        }
    
        // updateQuietly: this is a high-frequency ping — we deliberately skip
        // the model's booted() 'updated' hook (financial recording / rider
        // name sync checks) since neither applies here, and skip firing
        // OrderStatusUpdated since the status itself hasn't changed.
        $order->updateQuietly([
            'rider_latitude' => $request->latitude,
            'rider_longitude' => $request->longitude,
            'location_updated_at' => now(),
        ]);
    
        return response()->json(['success' => true], 200);
    }
}