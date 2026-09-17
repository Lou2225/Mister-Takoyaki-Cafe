<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BranchApiController extends Controller
{
    /**
     * GET /api/branches
     * Returns all active branches with lat/lng parsed from JSON address field.
     */
    public function index(): JsonResponse
    {
        $branches = Branch::where('status', 1)->get()->map(function ($branch) {
            // The address field is a JSON string — parse it for lat/lng
            $addressData = is_string($branch->address)
                ? json_decode($branch->address, true)
                : $branch->address;

            // Ensure addressData is an array if json_decode failed or it was already an array/object
            if (!is_array($addressData)) {
                $addressData = [];
            }

            return [
                'id'          => $branch->id,
                'name'        => $branch->branch_name,
                'branch_code' => $branch->branch_code,
                'address'     => $addressData['formatted'] ?? $branch->address ?? '',
                'phone'       => $branch->phone,
                'email'       => $branch->email,
                'latitude'    => $addressData['lat'] ?? null,
                'longitude'   => $addressData['lng'] ?? null,
                'status'      => $branch->status,
                'is_open'     => (bool) $branch->status,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $branches,
        ]);
    }

    /**
     * GET /api/branches/{id}
     * Returns a single branch detail.
     */
    public function show(int $id): JsonResponse
    {
        $branch = Branch::where('status', 1)->findOrFail($id);
        $addressData = is_string($branch->address)
            ? json_decode($branch->address, true)
            : $branch->address;

        if (!is_array($addressData)) {
            $addressData = [];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'          => $branch->id,
                'name'        => $branch->branch_name,
                'branch_code' => $branch->branch_code,
                'address'     => $addressData['formatted'] ?? $branch->address ?? '',
                'latitude'    => $addressData['lat'] ?? null,
                'longitude'   => $addressData['lng'] ?? null,
                'phone'       => $branch->phone,
                'email'       => $branch->email,
                'is_open'     => (bool) $branch->status,
            ],
        ]);
    }

    /**
     * GET /api/menu?branch_id={id}
     * Returns products available at the specified branch.
     *
     * Logic:
     *  - scope = 'global'  → available to ALL branches
     *  - scope = 'branch'  → only available if listed in branch_product pivot
     */
    public function menu(Request $request): JsonResponse
    {
        $request->validate(['branch_id' => 'required|integer|exists:branches,id']);
        $branchId = (int) $request->branch_id;

        // Products available at branch, respecting branch_product.is_active override
        $query = Product::with(['category', 'optionGroups.options', 'modifiers'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating');

        $query->where(function ($q) use ($branchId) {
            $q->where('scope', 'global')
                ->orWhereHas('branches', fn($bq) => $bq->where('branches.id', $branchId));
        });

        $query->select('products.*');

        $query->leftJoin('branch_product', function($join) use ($branchId) {
            $join->on('products.id', '=', 'branch_product.product_id')
                 ->where('branch_product.branch_id', '=', $branchId);
        });

        // Effective visibility: branch override if present, else fallback to global is_active
        $query->where(function ($q) {
            $q->where(function ($sub) {
                $sub->whereNull('branch_product.is_active')
                    ->where('products.is_active', true);
            })->orWhere('branch_product.is_active', 1);
        });

        $allProducts = $query->get();

        $formatted = $allProducts->map(fn($p) => $this->formatProduct($p, $branchId))->values();

        // Group by category for easier Flutter rendering
        $grouped = $formatted->groupBy('category')->map(fn($items, $cat) => [
            'category' => $cat,
            'items'    => $items->values(),
        ])->values();

        return response()->json([
            'success'   => true,
            'branch_id' => $branchId,
            'data'      => $grouped,
        ]);
    }

    private function formatProduct(Product $p, int $branchId): array
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
            'scope'              => $p->scope,
            'available_quantity' => $avail['available_quantity'],
            'is_available'       => $avail['is_available'],
            'availability_label' => $avail['availability_label'],
            'base_price'         => (float) $p->price,
            'average_rating'     => $p->average_rating,
            'review_count'       => $p->review_count,
            'rating'             => $p->average_rating,
            'reviews'            => $p->review_count,
            'option_groups'      => $p->optionGroups->map(fn($group) => [
                'id'               => $group->id,
                'name'             => $group->name,
                'is_required'      => (bool) $group->is_required,
                'price_mode'       => $group->price_mode,
                'price_mode_label' => $group->price_mode === 'fixed' ? 'Sets the price' : 'Adds to base price',
                'min_select'       => $group->is_required ? 1 : 0,
                'max_select'       => 1,
                'options'          => $group->options->map(fn($opt) => [
                    'id'           => $opt->id,
                    'name'         => $opt->name,
                    'price'        => (float) $opt->price,
                    'is_default'   => (bool) $opt->is_default,
                    'is_available' => ($avail['option_availability'][$opt->id] ?? 0) > 0,
                    'qty'          => $avail['option_availability'][$opt->id] ?? 0,
                ]),
            ]),
            'options'            => $p->optionGroups, // Alias for some app templates
            'product_options'    => $p->optionGroups, // Alias for some app templates
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
