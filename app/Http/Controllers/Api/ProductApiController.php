<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    protected $productRelations = ['category', 'optionGroups.options', 'modifiers'];

    /**
     * List all active products for delivery
     */
    public function index(Request $request)
    {
        $branchId = $request->query('branch_id');
        
        $products = Product::with($this->productRelations)
            ->where('is_active', true)
            ->where('scope', 'global') // Usually app products are global
            ->get()
            ->map(fn($p) => $this->formatProduct($p, (int)$branchId));

        $categories = ProductCategory::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
                'categories' => $categories
            ]
        ]);
    }

    /**
     * Get detailed product info
     */
    public function show(int $id)
    public function show($id)
    {
        $branchId = request()->query('branch_id');
        $product = Product::with($this->productRelations)
            ->where('is_active', true)
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatProduct($product, (int)$branchId)
        ]);
    }

    /**
     * Get product customizations for the app
     */
    public function customizations(int $id)
    public function customizations($id)
    {
        $branchId = request()->query('branch_id');
        $product = Product::with($this->productRelations)
            ->where('is_active', true)
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $formatted = $this->formatProduct($product, (int)$branchId);

        return response()->json([
            'success' => true,
            'data' => [
                'product_id' => $formatted['id'],
                'product_name' => $formatted['name'],
                'base_price' => $formatted['price'],
                'option_groups' => $formatted['option_groups'],
                'modifiers' => $formatted['modifiers']
            ]
        ]);
    }

    /**
     * Reusable formatter for product response
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
