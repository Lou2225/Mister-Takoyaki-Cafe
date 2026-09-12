<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\OrderItemModifier;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\Modifier;
use App\Models\Recipe;
use App\Models\FinancialLedger;
use App\Models\SystemSetting;
use App\Services\StockDeductionService;
use App\Events\OrderStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderApiController extends Controller
{
    protected $orderRelations = ['branch', 'items.product', 'items.options.option', 'items.modifiers.modifier', 'rider'];

    /**
     * List user's orders
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Order::with($this->orderRelations);

        if ($user->isSuperAdmin()) {
            // Super Admin sees all orders
        } elseif ($user->isRider()) {
            // Riders see orders assigned to them
            $query->where('rider_id', $user->id);
        } elseif ($user->isAdmin() || $user->isStaff()) {
            // Admins and Staff see orders for their branch
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        } else {
            // Regular customers only see their own orders
            $query->where('customer_id', $user->id);
        }

        $orders = $query->orderBy('created_at', 'desc')->get()->map(fn($order) => $this->formatOrder($order));

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Submit a new delivery order
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.options' => 'nullable|array',
            'items.*.modifiers' => 'nullable|array',
            'payment_method' => 'required|in:COD,Cash on Delivery,Online Payment',
            'order_type' => 'required|in:Delivery,Pickup',
            'delivery_address' => 'nullable|string',
            'delivery_latitude' => 'nullable|numeric|between:-90,90',    // ADD
            'delivery_longitude' => 'nullable|numeric|between:-180,180', // ADD
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'notes' => 'nullable|string',
            'delivery_fee' => 'nullable|numeric',
            'discount_amount' => 'nullable|numeric',
        ]);

        try {
            $order = DB::transaction(function () use ($request) {
                // Check delivery address if type is Delivery
                if ($request->order_type === 'Delivery' && !($request->delivery_address ?? $request->user()->address)) {
                    throw new \Exception("A delivery address is required. Please provide one or update your profile.");
                }

                $mergedItems = [];
                foreach ($request->items as $itemData) {
                    $productId = (int) ($itemData['product_id'] ?? 0);
                    $optionIds = collect($itemData['options'] ?? [])
                        ->map(fn ($optionId) => (int) $optionId)
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();
                    $modifierIds = collect($itemData['modifiers'] ?? [])
                        ->map(fn ($modifierId) => (int) $modifierId)
                        ->unique()
                        ->sort()
                        ->values()
                        ->all();

                    $key = $productId . ':' . implode(',', $optionIds) . ':' . implode(',', $modifierIds);

                    if (!isset($mergedItems[$key])) {
                        $mergedItems[$key] = [
                            'product_id' => $productId,
                            'quantity' => 0,
                            'options' => $optionIds,
                            'modifiers' => $modifierIds,
                        ];
                    }

                    $mergedItems[$key]['quantity'] += (int) ($itemData['quantity'] ?? 1);
                }

                // 1. Recalculate Prices & Integrity Check
                $calculatedSubtotal = 0;
                $orderItemsToCreate = [];

                foreach (array_values($mergedItems) as $itemData) {
                    $product = Product::with('optionGroups')->findOrFail($itemData['product_id']);

                    // Stock Validation
                    $availableQty = $product->getMaxAvailableQuantity((int)$request->branch_id);
                    if ($availableQty < $itemData['quantity']) {
                        throw new \Exception("Insufficient stock for {$product->name}. Only {$availableQty} left.");
                    }

                    $itemUnitPrice = (float) $product->getPriceAt($request->branch_id);

                    $options = [];
                    $fixedOptionsPrice = 0;
                    $additiveOptionsPrice = 0;
                    $hasFixedOption = false;

                    $dbOptions = collect();
                    if (!empty($itemData['options'])) {
                        $optionAvailabilities = $product->getOptionAvailability((int)$request->branch_id);
                        $dbOptions = ProductOption::with('group')->whereIn('id', $itemData['options'])->get();

                        foreach ($dbOptions as $opt) {
                            // Check option-specific stock
                            $optAvailable = $optionAvailabilities[$opt->id] ?? 0;
                            if ($optAvailable < $itemData['quantity']) {
                                throw new \Exception("Insufficient stock for {$product->name} ({$opt->name}). Only {$optAvailable} left.");
                            }

                            if ($opt->group->price_mode === 'fixed') {
                                $fixedOptionsPrice += (float) $opt->price;
                                $hasFixedOption = true;
                            } else {
                                $additiveOptionsPrice += (float) $opt->price;
                            }

                            $options[] = ['id' => $opt->id, 'price' => $opt->price, 'name' => $opt->name];
                        }
                    }

                    // Validate option groups selection constraints (required and max_select)
                    foreach ($product->optionGroups as $group) {
                        $selectedInGroup = $dbOptions->where('group_id', $group->id);
                        $count = $selectedInGroup->count();

                        if ($group->is_required && $count === 0) {
                            throw new \Exception("Please select an option for {$group->name}.");
                        }

                        $maxSelect = $group->max_select ?? ($group->price_mode === 'fixed' ? 1 : null);
                        if ($maxSelect !== null && $count > $maxSelect) {
                            throw new \Exception("You can only select up to {$maxSelect} option(s) for {$group->name}.");
                        }
                    }

                    $itemModifiersPrice = 0;
                    $modifiers = [];
                    if (!empty($itemData['modifiers'])) {
                        $modifierAvailabilities = $product->getModifierAvailability((int)$request->branch_id);
                        $dbModifiers = Modifier::whereIn('id', $itemData['modifiers'])->get();
                        foreach ($dbModifiers as $mod) {
                            // Check modifier-specific stock
                            $modAvailable = $modifierAvailabilities[$mod->id] ?? 0;
                            if ($modAvailable < $itemData['quantity']) {
                                throw new \Exception("Insufficient stock for {$product->name} modifier ({$mod->name}). Only {$modAvailable} left.");
                            }

                            $itemModifiersPrice += (float) $mod->price;
                            $modifiers[] = ['id' => $mod->id, 'price' => $mod->price, 'name' => $mod->name];
                        }
                    }

                    // If we have a fixed option, it REPLACES the base unit price.
                    // If multiple fixed options exist (unlikely but possible), they sum up.
                    $effectiveBasePrice = $hasFixedOption ? $fixedOptionsPrice : $itemUnitPrice;
                    $totalUnitPrice = $effectiveBasePrice + $additiveOptionsPrice + $itemModifiersPrice;

                    $lineSubtotal = $totalUnitPrice * $itemData['quantity'];
                    $calculatedSubtotal += $lineSubtotal;

                    $orderItemsToCreate[] = [
                        'product_id' => $product->id,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $totalUnitPrice,
                        'subtotal' => $lineSubtotal,
                        'options' => $options,
                        'modifiers' => $modifiers,
                    ];
                }

                // Product prices are Tax-Free.
                $taxAmount = 0;
                $deliveryFee = (float) ($request->delivery_fee ?? 0);
                $discountAmount = (float) ($request->discount_amount ?? 0);
                // Total = product prices + delivery fee - any discount (no tax added on top)
                $totalAmount = $calculatedSubtotal + $deliveryFee - $discountAmount;

                // 3. Create Order
                $refNo = 'MTC-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));

                $order = Order::create([
                    'reference_no' => $refNo,
                    'branch_id' => $request->branch_id,
                    'user_id' => null,
                    'customer_id' => $request->user()->id,
                    'customer_name' => $request->customer_name,
                    'customer_phone' => $request->customer_phone,
                    'delivery_address' => $request->delivery_address ?? $request->user()->address,
                    'delivery_latitude' => $request->delivery_latitude,   // ADD
                    'delivery_longitude' => $request->delivery_longitude, // ADD
                    'delivery_fee' => $deliveryFee,
                    'total_amount' => $totalAmount,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'payment_method' => $request->payment_method,
                    'order_type' => $request->order_type,
                    'status' => Order::STATUS_PENDING,
                    'payment_status' => in_array($request->payment_method, ['COD', 'Cash on Delivery']) ? 'Unpaid' : 'Paid',
                    'source' => 'App',
                    'notes' => $request->notes,
                ]);

                // 4. Create Order Items & Details
                foreach ($orderItemsToCreate as $itemData) {
                    $item = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal' => $itemData['subtotal'],
                    ]);

                    foreach ($itemData['options'] as $opt) {
                        OrderItemOption::create([
                            'order_item_id' => $item->id,
                            'product_option_id' => $opt['id'],
                            'price' => $opt['price'],
                        ]);
                    }

                    foreach ($itemData['modifiers'] as $mod) {
                        OrderItemModifier::create([
                            'order_item_id' => $item->id,
                            'modifier_id' => $mod['id'],
                            'unit_price' => $mod['price'],
                        ]);
                    }
                }

                // 5. Financial Audit (Automated via Order model events)

                return $order;
            });

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully. Waiting for branch approval.',
                'data' => $this->formatOrder($order->load($this->orderRelations))
            ], 201);

        } catch (\Exception $e) {
            Log::error("Failed to place order: " . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get order details
     */
    public function show(int $id, Request $request)
    {
        $user = $request->user();
        $query = Order::with($this->orderRelations);

        if ($user->isSuperAdmin()) {
            // Full access
        } elseif ($user->isRider()) {
            // Riders can only see their assigned orders
            $query->where('rider_id', $user->id);
        } elseif ($user->isAdmin() || $user->isStaff()) {
            // Staff/Admins see orders within their branch
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }
        } else {
            // Customers only see their own orders
            $query->where('customer_id', $user->id);
        }

        $order = $query->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatOrder($order)
        ]);
    }

    /**
     * Update order status (Staff/Admin usage via Dashboard)
     */

    public function updateStatus(Request $request, int $id)
    {
        $user = $request->user();
        if (!$user->isStaff() && !$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only staff or admins can update order status.'
            ], 403);
        }
    
        $request->validate([
            'status' => 'required|string|in:Pending,Preparing,Out for Delivery,Delivered,Cancelled,Completed,Void,Ready,Handed to Rider',
            'rider_id' => 'nullable|integer|exists:users,id', // ← NEW: Accept rider_id
        ]);
    
        try {
            $order = Order::findOrFail($id);
    
            // ✅ NEW: When marking as "Handed to Rider", set the rider_id and sync rider info
           $additionalData = [];

            if ($request->rider_id) {
            
                $rider = \App\Models\User::findOrFail($request->rider_id);
                
                if (empty($rider->phone)) {
                    Log::warning("[OrderStatus] Rider {$rider->id} assigned to order {$id} has no phone on file.");
                }

            
                $additionalData = [
                    'rider_id' => $request->rider_id,
                    'rider_name' => $rider->name,
                    'rider_phone' => $rider->phone,
                ];
            }
    
            $order->updateStatus($request->status, $additionalData);
    
            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$request->status}",
                'data' => $this->formatOrder($order->fresh($this->orderRelations))
            ]);
    
        } catch (\Exception $e) {
            Log::error("Failed to update order status: " . $e->getMessage(), [
                'order_id' => $id,
                'status' => $request->status,
                'trace' => $e->getTraceAsString()
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
            ], 500);
        }
    }

    
    private function formatOrder(Order $order): array
    {
        $orderData = $order->toArray();
        unset($orderData['tax_amount']);
        
        // ✅ Add branch_name from the relationship
       $orderData['branch_name'] = $order->branch?->branch_name ?? $order->branch?->name;
    
        // ✅ Ensure each item includes product name and image_url
        $orderData['items'] = $order->items->map(function ($item) {
            return [
                'id'         => $item->id,
                'order_id'   => $item->order_id,
                'product_id' => $item->product_id,
                'name'       => $item->product?->name ?? '',
                'image_url'  => $item->product?->image_url ?? null,  // uses getImageUrlAttribute()
                'quantity'   => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal'   => $item->subtotal,
                'options'    => $item->options->map(fn($opt) => [
                    'id'    => $opt->id,
                    'name'  => $opt->option?->name ?? '',
                    'price' => $opt->price,
                ])->toArray(),
            ];
        })->toArray();
    
        return $orderData;
    }
}
