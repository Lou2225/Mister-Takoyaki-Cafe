<?php

namespace App\Services;

use App\Models\BranchIngredientStock;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Recipe;
use Illuminate\Support\Facades\DB;

/**
 * StockDeductionService
 * 
 * Handles FEFO (First-Expiry-First-Out) stock deductions for order fulfillment.
 * Ensures proper batch tracking, audit trails, and inventory integrity.
 */
class StockDeductionService
{
    /**
     * Deduct stock for an order using FEFO logic.
     * Must be called within a database transaction.
     *
     * @param int $branchId
     * @param int $ingredientId
     * @param float $quantity (in base unit)
     * @param int $orderItemId (for batch tracking)
     * @param int $userId (for audit trail)
     * @param string|null $remarks (optional)
     * @param string $movementType (default 'order')
     * @param string|null $transferRef (optional)
     * @return array ['success' => bool, 'message' => string, 'batches_used' => array]
     */
    public static function deductByFEFO(
        int $branchId,
        int $ingredientId,
        float $quantity,
        ?int $orderItemId,
        int $userId,
        ?string $remarks = null,
        string $movementType = 'order',
        ?string $transferRef = null,
        ?int $destBranchId = null  // destination branch for transfer movements
    ): array {
        try {
            // ──── STEP 1: Validate aggregate stock availability ────
            $stock = BranchIngredientStock::lockForUpdate()
                ->where('branch_id', $branchId)
                ->where('ingredient_id', $ingredientId)
                ->first();

            $unexpiredStock = (float)StockBatch::where('branch_id', $branchId)
                ->where('ingredient_id', $ingredientId)
                ->where('current_quantity', '>', 0)
                ->where(function($q) {
                    $q->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', \Carbon\Carbon::today());
                })
                ->sum('current_quantity');
 
            if (!$stock || $unexpiredStock < $quantity) {
                throw new \Exception(
                    "Insufficient unexpired stock: need {$quantity}, have " . $unexpiredStock
                );
            }

            // ──── STEP 2: Get batches ordered by expiry (FEFO) ────
            $batches = StockBatch::lockForUpdate()
                ->where('branch_id', $branchId)
                ->where('ingredient_id', $ingredientId)
                ->where('current_quantity', '>', 0)
                ->where(function($q) {
                    $q->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', \Carbon\Carbon::today());
                })
                ->orderBy('expiry_date', 'asc') // Earliest expiry first
                ->orderBy('created_at', 'asc')   // Oldest batch if same expiry
                ->get();

            if ($batches->isEmpty()) {
                throw new \Exception(
                    "No unexpired stock batches found for ingredient"
                );
            }

            // ──── STEP 3: Deduct from batches (FEFO) ────
            $remainingQuantity = $quantity;
            $batchesUsed = [];

            foreach ($batches as $batch) {
                if ($remainingQuantity <= 0) break;

                $deductAmount = min($remainingQuantity, (float)$batch->current_quantity);
                $batch->current_quantity -= $deductAmount;
                $batch->save();

                // Track batch usage
                $batchesUsed[] = [
                    'batch_id' => $batch->id,
                    'quantity_deducted' => $deductAmount,
                    'expiry_date' => $batch->expiry_date,
                    'remaining_in_batch' => $batch->current_quantity,
                ];

                // Link OrderItem to this batch if provided
                if ($orderItemId) {
                    OrderItem::where('id', $orderItemId)
                        ->update(['stock_batch_id' => $batch->id]);
                }

                $remainingQuantity -= $deductAmount;
            }

            // ──── STEP 4: Update aggregate stock ────
            $stock->stock_quantity -= $quantity;
            $stock->save();

            // ──── STEP 5: Create audit trail ────
            StockMovement::create([
                'branch_id'          => $branchId,
                'ingredient_id'      => $ingredientId,
                'type'               => $movementType,
                'quantity'           => $quantity,
                'reference_id'       => $orderItemId ?: $transferRef,
                'user_id'            => $userId,
                'remarks'            => $remarks ?? 'Order fulfillment (FEFO)',
                'transfer_reference' => $transferRef,
                'from_branch_id'     => $destBranchId, // stores destination for transfer_out movements
            ]);

            return [
                'success' => true,
                'message' => "Deducted {$quantity} units using FEFO",
                'batches_used' => $batchesUsed,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'batches_used' => [],
            ];
        }
    }

    /**
     * Validate that stock availability for multiple ingredients.
     * Used before processing an order.
     *
     * @param int $branchId
     * @param array $ingredients [ingredient_id => quantity, ...]
     * @return array ['available' => bool, 'message' => string, 'shortages' => array]
     */
    public static function validateStockAvailability(int $branchId, array $ingredients): array
    {
        $shortages = [];

        foreach ($ingredients as $ingredientId => $requiredQty) {
            $available = (float)StockBatch::where('branch_id', $branchId)
                ->where('ingredient_id', $ingredientId)
                ->where('current_quantity', '>', 0)
                ->where(function($q) {
                    $q->whereNull('expiry_date')
                      ->orWhere('expiry_date', '>=', \Carbon\Carbon::today());
                })
                ->sum('current_quantity');
 
            if ($available < $requiredQty) {
                $shortages[] = [
                    'ingredient_id' => $ingredientId,
                    'required' => $requiredQty,
                    'available' => $available,
                    'deficit' => $requiredQty - $available,
                ];
            }
        }

        if (!empty($shortages)) {
            $message = 'Insufficient stock: ' . count($shortages) . ' ingredient(s) short';
            return [
                'available' => false,
                'message' => $message,
                'shortages' => $shortages,
            ];
        }

        return [
            'available' => true,
            'message' => 'All ingredients in stock',
            'shortages' => [],
        ];
    }

    /**
     * Calculate total ingredient requirements for cart items.
     * Used in validateStockAvailability.
     *
     * @param array $cartItems [product_id => [...], ...]
     * @param array $recipes Pre-fetched recipe data
     * @return array [ingredient_id => total_quantity, ...]
     */
    public static function calculateIngredientNeeds(array $cartItems, array $recipes): array
    {
        $needs = [];

        foreach ($cartItems as $item) {
            $productId = $item['id'];
            $quantity = $item['qty'];
            $optionIds = collect($item['options'] ?? [])->pluck('id')->toArray();
            $modifierIds = collect($item['modifiers'] ?? [])->pluck('id')->toArray();

            // Get recipes for this product + options + modifiers
            foreach ($recipes as $recipe) {
                if ($recipe['product_id'] === $productId && (
                    (empty($recipe['product_option_id']) && empty($recipe['modifier_id'])) ||
                    in_array($recipe['product_option_id'], $optionIds) ||
                    in_array($recipe['modifier_id'], $modifierIds)
                )) {
                    $ingredientId = $recipe['ingredient_id'];
                    $totalNeeded = $recipe['quantity'] * $quantity;
                    $needs[$ingredientId] = ($needs[$ingredientId] ?? 0) + $totalNeeded;
                }
            }
        }

        return $needs;
    }

    /**
     * Deduct total stock for an entire order.
     * Iterates through products, options, and modifiers.
     */
    public static function deductForOrder(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            try {
                $order->load(['items.options', 'items.modifiers']);
                $deductions = [];

                foreach ($order->items as $item) {
                    $productId = $item->product_id;
                    $optionIds = $item->options->pluck('product_option_id')->toArray();
                    $modifierIds = $item->modifiers->pluck('modifier_id')->toArray();

                    // Find all relevant recipes
                    $recipes = Recipe::where(function($q) use ($productId, $optionIds, $modifierIds) {
                        // Base product recipe
                        $q->where('product_id', $productId)
                          ->whereNull('product_option_id')
                          ->whereNull('modifier_id');
                        
                        // Option recipes
                        if (!empty($optionIds)) {
                            $q->orWhereIn('product_option_id', $optionIds);
                        }
                        
                        // Modifier recipes
                        if (!empty($modifierIds)) {
                            $q->orWhereIn('modifier_id', $modifierIds);
                        }
                    })->get();

                    foreach ($recipes as $recipe) {
                        $qtyToDeduct = $recipe->quantity * $item->quantity;
                        $label = $recipe->product_option_id ? "Option" : ($recipe->modifier_id ? "Modifier" : "Product");
                        
                        $result = self::deductByFEFO(
                            branchId: $order->branch_id,
                            ingredientId: $recipe->ingredient_id,
                            quantity: $qtyToDeduct,
                            orderItemId: $item->id,
                            userId: auth()->id() ?? $order->customer_id, // Fallback to customer if system-triggered
                            remarks: "Order #{$order->reference_no} - {$label} recipe deduction"
                        );

                        if (!$result['success']) {
                            throw new \Exception("Stock deduction failed: {$result['message']}");
                        }
                        
                        $deductions[] = $result;
                    }
                }

                return [
                    'success' => true,
                    'message' => 'All order stock successfully deducted.',
                    'deductions' => $deductions
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        });
    }

    /**
     * Rollback stock deductions for an order (e.g., if order is cancelled).
     *
     * @param int $branchId
     * @param int $orderItemId
     * @return bool
     */
    public static function reverseDeduction(int $branchId, int $orderItemId): bool
    {
        try {
            return DB::transaction(function () use ($branchId, $orderItemId) {
                $movement = StockMovement::where('reference_id', $orderItemId)
                    ->where('type', 'order')
                    ->where('branch_id', $branchId)
                    ->first();

                if (!$movement) {
                    return false;
                }

                $stock = BranchIngredientStock::where('branch_id', $branchId)
                    ->where('ingredient_id', $movement->ingredient_id)
                    ->first();

                if ($stock) {
                    $stock->stock_quantity += $movement->quantity;
                    $stock->save();
                }

                // Update batch
                $orderItem = OrderItem::where('id', $orderItemId)->first();
                if ($orderItem && $orderItem->stock_batch_id) {
                    $batch = StockBatch::find($orderItem->stock_batch_id);
                    if ($batch) {
                        $batch->current_quantity += $movement->quantity;
                        $batch->save();
                    }
                }

                // Log reversal
                StockMovement::create([
                    'branch_id' => $branchId,
                    'ingredient_id' => $movement->ingredient_id,
                    'type' => 'order_reversal',
                    'quantity' => $movement->quantity,
                    'reference_id' => $orderItemId,
                    'user_id' => auth()->id(),
                    'remarks' => 'Order cancellation',
                ]);

                return true;
            });
        } catch (\Exception $e) {
            return false;
        }
    }
}
