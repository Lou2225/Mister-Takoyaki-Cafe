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
use Illuminate\Support\Str;

class OrderApiController extends Controller
{
    protected $orderRelations = ['items.product', 'items.options.option', 'items.modifiers.modifier'];

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

        $orders = $query->orderBy('created_at', 'desc')->get();

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

                // 1. Recalculate Prices & Integrity Check
                $calculatedSubtotal = 0;
                $orderItemsToCreate = [];

                foreach ($request->items as $itemData) {
                    $product = Product::findOrFail($itemData['product_id']);
                    
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
                'data' => $order->load($this->orderRelations)
            ], 201);

        } catch (\Exception $e) {
            \Log::error("Failed to place order: " . $e->getMessage(), [
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
    public function show($id, Request $request)
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
            'data' => $order
        ]);
    }

    /**
     * Update order status (Staff/Admin usage via Dashboard)
     */
    public function updateStatus(Request $request, $id)
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
        ]);

        try {
            $order = Order::findOrFail($id);
            $order->updateStatus($request->status);

            return response()->json([
                'success' => true,
                'message' => "Order status updated to {$request->status}",
                'data' => $order->fresh($this->orderRelations)
            ]);

        } catch (\Exception $e) {
            \Log::error("Failed to update order status: " . $e->getMessage(), [
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
}
