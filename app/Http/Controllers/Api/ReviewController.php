<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Get all reviews and aggregate summary for a specific product.
     * GET /api/products/{productId}/reviews
     */
    public function index(Request $request, $productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $reviews = Review::where('product_id', $productId)
            ->with(['user' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'avatar');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        $avgRating = (float) (Review::where('product_id', $productId)->avg('rating') ?? 0);
        $totalReviews = (int) Review::where('product_id', $productId)->count();

        // Rating breakdown (counts for 1 to 5 stars)
        $distribution = [
            5 => Review::where('product_id', $productId)->where('rating', 5)->count(),
            4 => Review::where('product_id', $productId)->where('rating', 4)->count(),
            3 => Review::where('product_id', $productId)->where('rating', 3)->count(),
            2 => Review::where('product_id', $productId)->where('rating', 2)->count(),
            1 => Review::where('product_id', $productId)->where('rating', 1)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'product_id'     => (int) $productId,
                'average_rating' => round($avgRating, 1),
                'review_count'   => $totalReviews,
                'distribution'   => $distribution,
                'reviews'        => $reviews->items(),
                'current_page'   => $reviews->currentPage(),
                'last_page'      => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * Get review status for a specific order.
     * Returns list of product IDs already reviewed for this order.
     * GET /api/orders/{orderId}/reviews
     */
    public function getOrderReviews(Request $request, $orderId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // ✅ Robust ownership check: checks customer_id (App orders), user_id (POS), or customer_phone
        $order = DB::table('orders')
            ->where(function ($q) use ($orderId) {
                $q->where('id', $orderId)
                  ->orWhere('reference_no', $orderId);
            })
            ->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id)
                  ->orWhere('user_id', $user->id);
                if (!empty($user->phone)) {
                    $q->orWhere('customer_phone', $user->phone);
                }
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'The specified order does not belong to your account.',
            ], 403);
        }

        $reviews = Review::where('user_id', $user->id)
            ->where('order_id', $order->id)
            ->get();

        $reviewedProductIds = $reviews->pluck('product_id')->map(function ($id) {
            return (int) $id;
        })->all();

        return response()->json([
            'success' => true,
            'data' => [
                'order_id'             => (int) $order->id,
                'reviewed_product_ids' => $reviewedProductIds,
                'reviews'              => $reviews,
            ],
        ]);
    }

    /**
     * Submit batch reviews for products purchased in a delivered order.
     * POST /api/orders/{orderId}/reviews
     */
    public function storeOrderReviews(Request $request, $orderId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // ✅ Robust ownership check: checks customer_id (App orders), user_id (POS), or customer_phone
        $order = DB::table('orders')
            ->where(function ($q) use ($orderId) {
                $q->where('id', $orderId)
                  ->orWhere('reference_no', $orderId);
            })
            ->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id)
                  ->orWhere('user_id', $user->id);
                if (!empty($user->phone)) {
                    $q->orWhere('customer_phone', $user->phone);
                }
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'The specified order does not belong to your account.',
            ], 403);
        }

        // Verify that the order is delivered or completed
        $terminalStatuses = ['delivered', 'completed', 'picked up', 'picked_up', 'done', 'finished'];
        $status = strtolower(trim($order->status ?? ''));
        if (!in_array($status, $terminalStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'Reviews can only be submitted for delivered or completed orders.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reviews'                 => 'required|array|min:1',
            'reviews.*.product_id'    => 'required|integer',
            'reviews.*.rating'        => 'required|integer|min:1|max:5',
            'reviews.*.comment'       => 'nullable|string|max:1000',
            'reviews.*.order_item_id' => 'nullable|integer',
            'general_comment'         => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Fetch valid product IDs from order_items
        $validProductIds = DB::table('order_items')
            ->where('order_id', $order->id)
            ->pluck('product_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $savedReviews = [];
        $generalComment = $request->input('general_comment');

        foreach ($request->input('reviews') as $reviewInput) {
            $productId = (int) $reviewInput['product_id'];

            // Skip if product was not in order
            if (!empty($validProductIds) && !in_array($productId, $validProductIds)) {
                continue;
            }

            $comment = !empty($reviewInput['comment']) ? $reviewInput['comment'] : $generalComment;

            // Prevent duplicate reviews for same order + product by updating if already exists
            $review = Review::updateOrCreate(
                [
                    'user_id'    => $user->id,
                    'product_id' => $productId,
                    'order_id'   => (int) $order->id,
                ],
                [
                    'order_item_id' => $reviewInput['order_item_id'] ?? null,
                    'rating'        => (int) $reviewInput['rating'],
                    'comment'       => $comment,
                ]
            );

            $savedReviews[] = $review;
        }

        // Return updated reviewed product IDs
        $reviewedProductIds = Review::where('user_id', $user->id)
            ->where('order_id', $order->id)
            ->pluck('product_id')
            ->map(fn($id) => (int) $id)
            ->all();

        return response()->json([
            'success' => true,
            'message' => 'Reviews submitted successfully.',
            'data'    => [
                'order_id'             => (int) $order->id,
                'reviewed_product_ids' => $reviewedProductIds,
                'reviews'              => $savedReviews,
            ],
        ], 201);
    }

    /**
     * Store or update a customer review for a product.
     * POST /api/products/{productId}/reviews
     */
    public function store(Request $request, $productId)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'rating'   => 'required|integer|min:1|max:5',
            'comment'  => 'nullable|string|max:1000',
            'order_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Check for verified purchase if order_id is provided
        $orderId = $request->input('order_id');
        $resolvedOrderId = null;
        if ($orderId) {
            // ✅ Robust ownership check: checks customer_id (App orders), user_id (POS), or customer_phone
            $order = DB::table('orders')
                ->where(function ($q) use ($orderId) {
                    $q->where('id', $orderId)
                      ->orWhere('reference_no', $orderId);
                })
                ->where(function ($q) use ($user) {
                    $q->where('customer_id', $user->id)
                      ->orWhere('user_id', $user->id);
                    if (!empty($user->phone)) {
                        $q->orWhere('customer_phone', $user->phone);
                    }
                })
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'The specified order does not belong to your account.',
                ], 403);
            }

            // Verify order status is delivered/completed
            $terminalStatuses = ['delivered', 'completed', 'picked up', 'picked_up', 'done', 'finished'];
            $status = strtolower(trim($order->status ?? ''));
            if (!in_array($status, $terminalStatuses)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reviews can only be submitted for delivered or completed orders.',
                ], 422);
            }

            // Verify product was in order
            $itemExists = DB::table('order_items')
                ->where('order_id', $order->id)
                ->where('product_id', $productId)
                ->exists();

            if (!$itemExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This product was not part of the specified order.',
                ], 422);
            }

            $resolvedOrderId = (int) $order->id;
        }

        // Prevent unlimited spam by updating if the user already reviewed this product
        $review = Review::updateOrCreate(
            [
                'user_id'    => $user->id,
                'product_id' => (int) $productId,
                'order_id'   => $resolvedOrderId,
            ],
            [
                'rating'     => (int) $request->input('rating'),
                'comment'    => $request->input('comment'),
            ]
        );

        // Load the user relation for clean response
        $review->load(['user' => function ($q) {
            $q->select('id', 'first_name', 'last_name', 'avatar');
        }]);

        // Fresh aggregates
        $avgRating = (float) (Review::where('product_id', $productId)->avg('rating') ?? 0);
        $totalReviews = (int) Review::where('product_id', $productId)->count();

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data'    => [
                'review'         => $review,
                'average_rating' => round($avgRating, 1),
                'review_count'   => $totalReviews,
            ],
        ], 201);
    }
}