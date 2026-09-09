<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    protected $productRelations = ['category', 'optionGroups.options', 'modifiers'];

    /**
     * Get all favorite products for the authenticated user.
     * GET /api/favorites
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user.',
                ], 401);
            }

            $branchId = $request->query('branch_id');

            // Fetch favorite product IDs directly from the favorites table
            $favoriteIds = DB::table('favorites')
                ->where('user_id', $user->id)
                ->pluck('product_id')
                ->map(fn($id) => (int)$id)
                ->toArray();

            if (empty($favoriteIds)) {
                return response()->json([
                    'success'      => true,
                    'data'         => [],
                    'favorite_ids' => [],
                ], 200);
            }

            $favoriteProducts = Product::with($this->productRelations)
                ->whereIn('id', $favoriteIds)
                ->where('is_active', true)
                ->get()
                ->map(fn($p) => $this->formatProduct($p, (int)$branchId));

            return response()->json([
                'success'      => true,
                'data'         => $favoriteProducts,
                'favorite_ids' => $favoriteIds,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load favorites: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get only the array of favorited product IDs for fast caching.
     * GET /api/favorites/ids
     */
    public function getFavoriteIds(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user.',
                ], 401);
            }

            $favoriteIds = DB::table('favorites')
                ->where('user_id', $user->id)
                ->pluck('product_id')
                ->map(fn($id) => (int)$id)
                ->toArray();

            return response()->json([
                'success' => true,
                'data'    => $favoriteIds,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get favorite IDs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add a product to the authenticated user's favorites.
     * POST /api/favorites
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user.',
                ], 401);
            }

            $productId = (int)$validated['product_id'];

            // Check if already favorited
            $exists = DB::table('favorites')
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->exists();

            if (!$exists) {
                DB::table('favorites')->insert([
                    'user_id'    => $user->id,
                    'product_id' => $productId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return response()->json([
                'success'     => true,
                'message'     => 'Product added to favorites',
                'product_id'  => $productId,
                'is_favorite' => true,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add favorite: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a product from the authenticated user's favorites.
     * DELETE /api/favorites/{productId}
     */
    public function destroy(Request $request, int $productId)
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user.',
                ], 401);
            }

            DB::table('favorites')
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->delete();

            return response()->json([
                'success'     => true,
                'message'     => 'Product removed from favorites',
                'product_id'  => $productId,
                'is_favorite' => false,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove favorite: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle favorite state for a product.
     * POST /api/favorites/toggle
     */
    public function toggle(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user.',
                ], 401);
            }

            $productId = (int)$validated['product_id'];

            $exists = DB::table('favorites')
                ->where('user_id', $user->id)
                ->where('product_id', $productId)
                ->exists();

            if ($exists) {
                DB::table('favorites')
                    ->where('user_id', $user->id)
                    ->where('product_id', $productId)
                    ->delete();

                return response()->json([
                    'success'     => true,
                    'message'     => 'Product removed from favorites',
                    'product_id'  => $productId,
                    'is_favorite' => false,
                ], 200);
            }

            DB::table('favorites')->insert([
                'user_id'    => $user->id,
                'product_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success'     => true,
                'message'     => 'Product added to favorites',
                'product_id'  => $productId,
                'is_favorite' => true,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle favorite: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reusable formatter matching ProductApiController
     */
    private function formatProduct(Product $p, ?int $branchId): array
    {
        $avail = $p->getAvailabilityData($branchId);

        return [
            'id'                 => $p->id,
            'name'               => $p->name,
            'description'        => $p->description ?? '',
            'price'              => (float) $p->price,
            'image_url'          => $p->image_url,
            'category'           => $p->category?->name ?? 'Uncategorized',
            'category_id'        => $p->category_id,
            'is_active'          => (bool) $p->is_active,
            'is_favorite'        => true,
            'scope'              => $p->scope,
            'available_quantity' => $avail['available_quantity'],
            'is_available'       => $avail['is_available'],
            'availability_label' => $avail['availability_label'],
            'base_price'         => (float) $p->price,
            'option_groups'      => $p->optionGroups->map(fn($group) => [
                'id'          => $group->id,
                'name'        => $group->name,
                'is_required' => (bool) $group->is_required,
                'price_mode'  => $group->price_mode,
                'price_mode_label' => $group->price_mode === 'fixed' ? 'Sets the price' : 'Adds to base price',
                'min_select'  => $group->is_required ? 1 : 0,
                'max_select'  => $group->price_mode === 'additive' ? 999 : 1,
                'options'     => $group->options->map(fn($opt) => [
                    'id'           => $opt->id,
                    'name'         => $opt->name,
                    'price'        => (float) $opt->price,
                    'is_default'   => (bool) $opt->is_default,
                    'is_available' => ($avail['option_availability'][$opt->id] ?? 0) > 0,
                    'qty'          => $avail['option_availability'][$opt->id] ?? 0,
                ]),
            ]),
            'options'            => $p->optionGroups,
            'product_options'    => $p->optionGroups,
            'modifiers'          => $p->modifiers->map(fn($m) => [
                'id'           => $m->id,
                'name'         => $m->name,
                'price'        => (float) $m->price,
                'is_available' => ($avail['modifier_availability'][$m->id] ?? 0) > 0,
                'qty'          => $avail['modifier_availability'][$m->id] ?? 0,
            ]),
        ];
    }
}