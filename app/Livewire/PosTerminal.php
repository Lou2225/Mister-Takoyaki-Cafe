<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\BranchIngredientStock;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\OrderItemModifier;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\Modifier;
use App\Models\Recipe;
use App\Models\SystemSetting;
use App\Models\FinancialLedger;
use App\Models\BranchCategorySort;
use App\Services\StockDeductionService;
use App\Services\PayMongoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;

class PosTerminal extends Component
{
    use HandlesValidations;
    // ─── Filters ───────────────────────────────────────────────────────────
    public ?int $selectedCategoryId = null;
    public $search = '';

    // ─── Cart ──────────────────────────────────────────────────────────────
    // Structure: [ product_id => ['name', 'price', 'qty', 'image', 'available'] ]
    public array $cart = [];

    // ─── Order Meta ────────────────────────────────────────────────────────
    public string $orderType = '';
    public string $tableNumber = '';
    public string $paymentMethod = '';
    public string $paymentReference = '';
    public float $amountTendered = 0;

    // ─── Branch / Settings ─────────────────────────────────────────────────
    public ?int $branchId = null;
    public float $taxRate = 0;          // decimal (e.g. 0.12)
    public float $serviceChargeRate = 0; // decimal (e.g. 0.05)
    public float $discountPercent = 0;  // decimal (e.g. 0.10)
    public float $seniorDiscountRate = 0; // decimal (e.g. 0.20)
    public string $currencySymbol = '₱';
    public string $referenceNo = '';

    // ─── Draft Order Meta (fetched from settings) ──────────────────────────
    public array $orderTypes = [];
    public array $paymentMethods = [];

    // ─── UI State ──────────────────────────────────────────────────────────
    public ?string $editCartItemId = null;
    public $editCartItemQty = 1;
    public string $editCartItemNotes = '';
    public bool $applyRegularDiscount = false;
    public bool $applySeniorDiscount = false;
    public array $branches = [];
    public bool $showDraftsModal = false;
    public string $gcashAccountName = '';
    public string $gcashAccountNumber = '';
    public string $gcashQrImage = '';
    public bool $isEditMode = false;
    public string $primaryColor = 'indigo';

    // ─── Options Modal ─────────────────────────────────────
    public ?int $showingOptionsId = null;
    public array $selectedOptions = []; // [groupId => [optionId, optionId, ...]] for additive; [groupId => [optionId]] for fixed
    public array $selectedModifierIds = [];
    protected ?Product $currentProduct = null;
    public array $optionAvailability = [];
    public array $modifierAvailability = []; // [modifierId => quantity_available]
    public bool $isVerifyingGCash = false;
    public bool $gcashVerified = false;
    public array $gcashTransactionDetails = [];
    // PayMongo GCash payment state
    public ?string $gcashPaymentUrl = null;
    public ?string $gcashPaymentIntentId = null;
    public bool $gcashPolling = false;
    public bool $isManualGcash = false; // To distinguish verification types

    public function updatedPaymentMethod()
    {
        $this->gcashVerified = false;
        $this->isManualGcash = false;
        $this->paymentReference = '';
        $this->gcashTransactionDetails = [];
    }

    // ─── Mount ─────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = \App\Services\BranchContext::getActiveBranchId() ?: $user->branch_id;

        // Load settings from database
        $this->taxRate           = 0;
        $this->serviceChargeRate = (float) SystemSetting::get('service_charge', 0);
        $this->discountPercent   = (float) SystemSetting::get('discount_rate', 0);
        $this->seniorDiscountRate = (float) SystemSetting::get('senior_discount_rate', 0.20);
        $this->currencySymbol    = (string) SystemSetting::get('currency_symbol', '₱');
        
        $this->orderTypes      = (array) SystemSetting::get('pos_order_types', ['Dine-in', 'Take-out']);
        $this->paymentMethods = (array) SystemSetting::get('pos_payment_methods', ['Cash', 'GCash']);

        $userTheme = $user->getRoleTheme();
        $this->primaryColor = $userTheme['primary'] ?? 'indigo';
        
        $this->gcashAccountName   = (string) SystemSetting::get('gcash_account_name', 'Mister Takoyaki Cafe');
        $this->gcashAccountNumber = (string) SystemSetting::get('gcash_account_number', '');
        $this->gcashQrImage = (string) SystemSetting::get('gcash_qr_image', '');

        // Set defaults
        $this->orderType     = $this->orderTypes[0] ?? 'Dine-in';
        $this->paymentMethod = $this->paymentMethods[0] ?? 'Cash';
        
        // Generate a draft reference number
        $this->referenceNo = $this->generateReferenceNo();

        // Load branches (for super admin)
        if ($user->role_id === 1) {
            $this->branches = Branch::orderBy('branch_name')->get(['id', 'branch_name'])->toArray();
            if (!$this->branchId && count($this->branches)) {
                $this->branchId = $this->branches[0]['id'];
            }
        }

        // Check for draft order restoration from order management
        if (session()->has('draft_order_id')) {
            $draftOrderId = session()->pull('draft_order_id');
            $this->loadDraft($draftOrderId);
        }
    }

    public function updatedEditCartItemNotes()
    {
        $this->validateFieldLive('editCartItemNotes', ['nullable', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME_BASIC], ValidationHelper::commonMessages());
    }


    public function updatedTableNumber()
    {
        $this->validateFieldLive('tableNumber', ['nullable', 'string', 'max:20', 'regex:' . ValidationHelper::REGEX_NAME_BASIC], ValidationHelper::commonMessages());
    }

    public function toggleEditMode(): void
    {
        if (!$this->canEditLayout()) {
            $this->dispatch('notify', type: 'error', message: 'Unauthorized to edit layout.');
            return;
        }
        $this->isEditMode = !$this->isEditMode;
        if ($this->isEditMode) {
            $this->dispatch('notify', type: 'info', message: 'Layout Edit Mode Enabled. Drag items to reorder.');
        }
    }

    private function canEditLayout(): bool
    {
        $roleId = auth()->user()->role_id;
        return $roleId === 1 || $roleId === 2;
    }

    public function saveLayout(?array $orderedIds = null): void
    {
        if (!$this->canEditLayout()) return;

        if ($orderedIds && $this->branchId) {
            DB::transaction(function() use ($orderedIds) {
                foreach ($orderedIds as $index => $id) {
                    DB::table('branch_product')->updateOrInsert(
                        ['branch_id' => $this->branchId, 'product_id' => $id],
                        ['sort_order' => $index]
                    );
                }
            });
            $this->dispatch('notify', type: 'success', message: 'Branch menu layout saved.');
        }

        $this->isEditMode = false;
    }

    public function reorderCategories(array $orderedIds): void
    {
        if (!$this->canEditLayout()) return;
        if (!$this->branchId) return;

        DB::transaction(function() use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                BranchCategorySort::updateOrCreate(
                    ['branch_id' => $this->branchId, 'category_id' => $id],
                    ['sort_order' => $index]
                );
            }
        });

        $this->dispatch('notify', type: 'success', message: 'Branch category layout updated.');
    }

    // ─── Computed: Products ────────────────────────────────────────────────
    protected ?Collection $productsCache = null;
    public function getProductsProperty()
    {
        if ($this->productsCache !== null) return $this->productsCache;

        $query = Product::with(['category', 'recipes', 'optionGroups.options', 'modifiers'])
            ->where('is_active', true);

        // Scope to branch
        if ($this->branchId) {
            $query->where(function ($q) {
                $q->where('scope', 'global')
                    ->orWhereHas('branches', fn($bq) => $bq->where('branches.id', $this->branchId));
            });
        }

        // Joint-based sorting requires manual selects to avoid ID collisions
        $query->select('products.*');

        if ($this->branchId) {
            $query->leftJoin('branch_product', function($join) {
                $join->on('products.id', '=', 'branch_product.product_id')
                     ->where('branch_product.branch_id', '=', (int)$this->branchId);
            })->addSelect('branch_product.sort_order as branch_sort_order');
        }

        $query->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
              ->addSelect('product_categories.sort_order as category_sort_order');

        // Client-side filtering is used for 0ms category switching.



        // Sorting
        $query->orderBy('category_sort_order', 'asc');
        
        if ($this->branchId) {
            $query->orderByRaw('COALESCE(branch_product.sort_order, products.sort_order) asc');
        } else {
            $query->orderBy('products.sort_order', 'asc');
        }

        $query->orderBy('products.name', 'asc');

        $products = $query->get();

        // Performance Optimization: Bulk fetch stocks for all products in this view
        if ($this->branchId && $products->isNotEmpty()) {
            $ingredientIds = $products->flatMap(function($p) {
                return $p->recipes->pluck('ingredient_id');
            })->unique();

            $stocks = Product::getUnexpiredStocks((int)$this->branchId, $ingredientIds);

            foreach ($products as $product) {
                // Pre-calculate availability for the instant modal
                $product->option_availability = $product->getOptionAvailability((int)$this->branchId, $stocks);
                $product->modifier_availability = $product->getModifierAvailability((int)$this->branchId, $stocks);
                $product->prefetched_stocks = $stocks;
            }

            // SORT: Available items first, then Out of Stock
            $products = $products->sortBy(function($product) {
                $isAvailable = $product->getMaxAvailableQuantity((int)$this->branchId, $product->prefetched_stocks) > 0;
                return [
                    $isAvailable ? 0 : 1, // Available (0) first, OOS (1) last
                    $product->category->sort_order ?? 0,
                    $product->branch_sort_order ?? $product->sort_order,
                    $product->name
                ];
            });
        }

        return $this->productsCache = $products;
    }

    public function getDraftsProperty()
    {
        return Order::where('status', Order::STATUS_DRAFTED)
            ->where('branch_id', $this->branchId)
            ->with(['items'])
            ->latest()
            ->get();
    }

    // ─── Computed: Categories ──────────────────────────────────────────────
    protected ?Collection $categoriesCache = null;
    public function getCategoriesProperty()
    {
        if ($this->categoriesCache !== null) return $this->categoriesCache;

        $query = ProductCategory::withCount(['products' => function ($q) {
            $q->where('is_active', true);
        }]);

        if ($this->branchId) {
            $query->leftJoin('branch_category_sort', function($join) {
                $join->on('product_categories.id', '=', 'branch_category_sort.category_id')
                     ->where('branch_category_sort.branch_id', '=', $this->branchId);
            })
            ->select('product_categories.*', 'branch_category_sort.sort_order as branch_sort_order')
            ->orderBy(DB::raw('COALESCE(branch_category_sort.sort_order, product_categories.sort_order)'), 'asc');
        } else {
            $query->orderBy('sort_order', 'asc');
        }

        // Only add secondary name sort if not overriding the COALESCE sort
        if (!$this->branchId) {
            $query->orderBy('name', 'asc');
        }

        return $this->categoriesCache = $query->get();
    }

    // ─── Computed: Totals ──────────────────────────────────────────────────
    #[Computed]
    public function subtotal(): float
    {
        return array_sum(array_map(fn($item) => ($item['price'] ?? 0) * ($item['qty'] ?? 0), array_filter($this->cart ?: [])));
    }

    #[Computed]
    public function taxAmount(): float
    {
        return 0;
    }

    #[Computed]
    public function serviceChargeAmount(): float
    {
        if ($this->orderType === 'Dine-in') {
            return round($this->subtotal * $this->serviceChargeRate, 2);
        }
        return 0;
    }

    #[Computed]
    public function discountAmount(): float
    {
        $totalDiscount = 0;
        foreach (array_filter($this->cart ?: []) as $item) {
            $isSenior = $item['apply_senior_discount'] ?? false;
            $isRegular = $item['apply_regular_discount'] ?? false;

            if (!$isSenior && !$isRegular) continue;

            $itemTotal = ($item['price'] ?? 0) * ($item['qty'] ?? 0);
            
            // Base for discount is now simply the item total (tax-free)
            $baseForDiscount = $itemTotal;
            
            if ($isSenior) {
                $totalDiscount += ($baseForDiscount * $this->seniorDiscountRate);
            }
            if ($isRegular) {
                $totalDiscount += ($baseForDiscount * $this->discountPercent);
            }
        }
        return round($totalDiscount, 2);
    }

    #[Computed]
    public function total(): float
    {
        $total = 0;
        foreach (array_filter($this->cart ?: []) as $item) {
            $isSenior = $item['apply_senior_discount'] ?? false;
            $isRegular = $item['apply_regular_discount'] ?? false;
            
            $itemTotal = ($item['price'] ?? 0) * ($item['qty'] ?? 0);
            
            if ($isSenior || $isRegular) {
                $baseAmount = $itemTotal;
                
                $discount = 0;
                if ($isSenior) $discount += ($baseAmount * $this->seniorDiscountRate);
                if ($isRegular) $discount += ($baseAmount * $this->discountPercent);
                
                $total += ($baseAmount - $discount);
            } else {
                $total += $itemTotal;
            }
        }
        
        return round($total + $this->serviceChargeAmount, 2);
    }

    #[Computed]
    public function change(): float
    {
        return max(0, $this->amountTendered - $this->total);
    }

    private function generateDiscountNotes(): ?string
    {
        $types = [];
        foreach ($this->cart as $item) {
            if ($item['apply_regular_discount'] ?? false) {
                $types['Regular'] = true;
            }
            if ($item['apply_senior_discount'] ?? false) {
                $types['Senior'] = true;
            }
        }
        
        $notes = [];
        if (!empty($types)) {
            $typesList = implode(' & ', array_keys($types));
            $notes[] = "{$typesList} Discount Applied";
        }

        if ($this->gcashVerified && !empty($this->gcashTransactionDetails)) {
            $notes[] = "GCash Verified: {$this->gcashTransactionDetails['timestamp']} | Status: {$this->gcashTransactionDetails['status']}";
        }

        return empty($notes) ? null : implode("\n", $notes);
    }

    public function getCartCountProperty(): int
    {
        return array_sum(array_column($this->cart, 'qty'));
    }

    // ─── Cart Actions ──────────────────────────────────────────────────────

    public function decrementCart(string $key): void
    {
        if (!isset($this->cart[$key])) return;

        if ($this->cart[$key]['qty'] > 1) {
            $this->cart[$key]['qty']--;
        } else {
            unset($this->cart[$key]);
        }
    }

    public function incrementCart(string $key): void
    {
        if (!isset($this->cart[$key])) return;
        $this->cart[$key]['qty']++;
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
    }


    public function saveEditItem(): void
    {
        $key = $this->editCartItemId;
        if (!isset($this->cart[$key])) return;

        $this->cart[$key]['qty'] = $this->editCartItemQty;
        $this->cart[$key]['instructions'] = $this->editCartItemNotes;
        $this->cart[$key]['apply_regular_discount'] = $this->applyRegularDiscount;
        $this->cart[$key]['apply_senior_discount'] = $this->applySeniorDiscount;

        if ($this->editCartItemQty <= 0) {
            unset($this->cart[$key]);
        }

        $this->closeEditItemModal();
    }

    public function closeEditItemModal(): void
    {
        $this->editCartItemId = null;
        $this->editCartItemQty = 1;
        $this->editCartItemNotes = '';
        $this->applyRegularDiscount = false;
        $this->applySeniorDiscount = false;

        $this->dispatch('close-modal', name: 'edit-cart-item');
    }

    public function decrementEditQuantity(): void
    {
        $this->editCartItemQty = max(0, $this->editCartItemQty - 1);
    }

    public function incrementEditQuantity(): void
    {
        $this->editCartItemQty = $this->editCartItemQty + 1;
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->referenceNo = $this->generateReferenceNo();
    }

    // ─── Draft Order Management ────────────────────────────────────────────
    public function saveDraft(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'warning', message: 'Cart is empty. Nothing to save.');
            return;
        }

        if (!$this->branchId) {
            $this->dispatch('notify', type: 'error', message: 'No branch assigned.');
            return;
        }

        try {
            DB::transaction(function() {
                // Create a Draft Order
                $draftOrder = Order::create([
                    'reference_no'    => $this->referenceNo,
                    'branch_id'       => $this->branchId,
                    'user_id'         => auth()->id(),
                    'total_amount'    => $this->total,
                    'tax_amount'      => $this->taxAmount,
                    'discount_amount' => $this->discountAmount,
                    'service_charge'  => $this->serviceChargeAmount,
                    'payment_method'  => $this->paymentMethod,
                    'order_type'      => $this->orderType,
                    'table_number'    => $this->tableNumber,
                    'status'          => Order::STATUS_DRAFTED,
                    'notes'           => $this->tableNumber ? "Table: {$this->tableNumber}" : null,
                ]);

                // Create Order Items (without stock deduction)
                foreach (array_filter($this->cart ?: []) as $key => $item) {
                    $productId = $item['id'];

                    $orderItem = OrderItem::create([
                        'order_id'   => $draftOrder->id,
                        'product_id' => $productId,
                        'quantity'   => $item['qty'],
                        'unit_price' => $item['price'],
                        'subtotal'   => $item['price'] * $item['qty'],
                    ]);

                    // Save Selected Options to DB
                    if (!empty($item['options'])) {
                        foreach ($item['options'] as $opt) {
                            OrderItemOption::create([
                                'order_item_id'     => $orderItem->id,
                                'product_option_id' => $opt['id'],
                                'price'             => $opt['price'],
                            ]);
                        }
                    }

                    // Save Modifiers to DB
                    if (!empty($item['modifiers'])) {
                        foreach ($item['modifiers'] as $m) {
                            OrderItemModifier::create([
                                'order_item_id' => $orderItem->id,
                                'modifier_id'   => $m['id'],
                                'unit_price'    => $m['price'],
                            ]);
                        }
                    }
                }

                $this->clearCart();
                $this->dispatch('notify', 
                    type: 'success',
                    message: "Draft order #{$draftOrder->reference_no} saved successfully!"
                );
            });
        } catch (\Exception $e) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'Failed to save draft: ' . $e->getMessage()
            );
        }
    }

    public function loadDraft(int $draftOrderId): void
    {
        try {
            $draft = Order::with(['items.product', 'items.options.option', 'items.modifiers.modifier'])
                ->where('id', $draftOrderId)
                ->where('status', Order::STATUS_DRAFTED)
                ->firstOrFail();

            // Check authorization
            if (!auth()->user()->can('manageDraft', $draft)) {
                $this->dispatch('notify', 
                    type: 'error',
                    message: 'Unauthorized to load this draft.'
                );
                return;
            }

            // Load draft data into POS
            $this->orderType = $draft->order_type;
            $this->tableNumber = $draft->table_number;
            $this->paymentMethod = $draft->payment_method;
            $this->referenceNo = $draft->reference_no;
            $this->branchId = $draft->branch_id;

            // Populate cart from order items
            $this->cart = [];
            foreach ($draft->items as $item) {
                $product = $item->product;
                
                $optionIds = $item->options->pluck('product_option_id')->sort()->toArray();
                $modifierIds = $item->modifiers->pluck('modifier_id')->sort()->toArray();

                // Create a unique key for the cart (consistent with confirmAdd)
                $optKey = !empty($optionIds) ? '-' . implode(',', $optionIds) : '';
                $modKey = !empty($modifierIds) ? '-' . implode(',', $modifierIds) : '';
                $key = $product->id . $optKey . $modKey;

                // Build options array for cart
                $options = [];
                foreach ($item->options as $itemOption) {
                    $options[] = [
                        'id' => $itemOption->product_option_id,
                        'name' => $itemOption->option->name ?? 'Unknown',
                        'price' => (float)$itemOption->price,
                    ];
                }

                // Build modifiers array for cart
                $modifiers = [];
                foreach ($item->modifiers as $itemModifier) {
                    $modifiers[] = [
                        'id' => $itemModifier->modifier_id,
                        'name' => $itemModifier->modifier->name ?? 'Unknown',
                        'price' => (float)$itemModifier->unit_price,
                    ];
                }

                $this->cart[$key] = [
                    'id'        => $product->id,
                    'key'       => $key,
                    'name'      => $product->name,
                    'price'     => (float)$item->unit_price,
                    'qty'       => $item->quantity,
                    'image'     => $product->image,
                    'available' => true,
                    'options'   => $options,
                    'modifiers' => $modifiers,
                    'instructions' => '',
                    'apply_regular_discount' => false,
                    'apply_senior_discount' => false,
                ];
            }

            $this->dispatch('close-modal', name: 'pos-drafts-list');
            
            // Delete draft ONLY after successful load into memory
            $draft->delete();

            $this->dispatch('notify', 
                type: 'success',
                message: "Draft order #{$draft->reference_no} loaded successfully!"
            );
        } catch (\Exception $e) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'Failed to load draft: ' . $e->getMessage()
            );
        }
    }

    public function deleteDraft(int $draftOrderId): void
    {
        try {
            $draft = Order::where('id', $draftOrderId)
                ->where('status', Order::STATUS_DRAFTED)
                ->firstOrFail();

            // Check authorization
            if (!auth()->user()->can('manageDraft', $draft)) {
                $this->dispatch('notify', 
                    type: 'error',
                    message: 'Unauthorized to delete this draft.'
                );
                return;
            }

            $draft->delete();

            $this->dispatch('notify', 
                type: 'success',
                message: 'Draft deleted successfully.'
            );
        } catch (\Exception $e) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'Failed to delete draft: ' . $e->getMessage()
            );
        }
    }
    public function initiateGCashPayment(): void
    {
        if (empty($this->cart) || $this->total <= 0) {
            $this->dispatch('notify', type: 'error', message: 'Cart is empty or total is invalid.');
            return;
        }

        if (empty(config('services.paymongo.secret_key'))) {
            $this->dispatch('notify', type: 'error', message: 'PayMongo is not configured. Please add API keys to .env');
            return;
        }

        $this->gcashPolling = true;
        $this->gcashPaymentUrl = null;
        $this->gcashPaymentIntentId = null;

        try {
            $service = app(PayMongoService::class);
            $result = $service->createGCashPaymentLink(
                amount: $this->total,
                referenceNo: $this->referenceNo,
                description: 'Mister Takoyaki Order #' . $this->referenceNo,
            );

            $this->gcashPaymentUrl = $result['checkout_url'];
            $this->gcashPaymentIntentId = $result['payment_intent_id'];
            $this->dispatch('gcash-url-ready', url: $this->gcashPaymentUrl);
        } catch (\Exception $e) {
            $this->gcashPolling = false;
            $this->dispatch('notify', type: 'error', message: 'GCash payment initiation failed: ' . $e->getMessage());
        }
    }

    /**
     * Called by Alpine polling every 4 seconds while the GCash QR is displayed.
     * Checks PayMongo's API directly — works without a webhook (localhost-friendly).
     */
    public function pollGCashStatus(): void
    {
        if (!$this->gcashPaymentIntentId || $this->gcashVerified) {
            return;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                config('services.paymongo.secret_key'), ''
            )->get("https://api.paymongo.com/v1/payment_intents/{$this->gcashPaymentIntentId}");

            if ($response->failed()) return;

            $status = $response->json('data.attributes.status');

            if ($status === 'succeeded') {
                $this->gcashVerified = true;
                $this->gcashPolling = false;
                $this->paymentReference = $this->gcashPaymentIntentId;
                $this->dispatch('notify', type: 'success', message: 'GCash payment received! You may now confirm the order.');
            }
        } catch (\Exception $e) {
            // Silent fail — just try again next poll
        }
    }

    /**
     * Manually verifies a static GCash payment.
     * Used when the cashier shows their own QR and checks their phone.
     */
    public function verifyStaticPayment(): void
    {
        $this->gcashVerified = true;
        $this->isManualGcash = true;
        $this->dispatch('notify', type: 'success', message: 'GCash payment manually verified!');
    }

    protected function validateStockAvailability(): array
    {
        if (empty($this->cart)) {
            return ['available' => true];
        }

        $productIds = [];
        $optionIds = [];
        $modifierIds = [];

        foreach (array_filter($this->cart ?: []) as $item) {
            $productIds[] = $item['id'];
            if (!empty($item['options'])) {
                foreach ($item['options'] as $opt) $optionIds[] = $opt['id'];
            }
            if (!empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $mod) $modifierIds[] = $mod['id'];
            }
        }

        // Single query for all possible recipes involved in this cart
        $allRecipes = Recipe::with('ingredient')
            ->whereIn('product_id', array_unique($productIds))
            ->get();

        $ingredientRequirements = [];

        foreach (array_filter($this->cart ?: []) as $item) {
            $productId = $item['id'];
            $itemOptionIds = collect($item['options'] ?? [])->pluck('id')->toArray();
            $itemModifierIds = collect($item['modifiers'] ?? [])->pluck('id')->toArray();
            $qty = $item['qty'];

            // Filter recipes that belong to this specific cart item configuration
            $itemRecipes = $allRecipes->filter(function($recipe) use ($productId, $itemOptionIds, $itemModifierIds) {
                if ($recipe->product_id != $productId) return false;
                
                // Base recipe
                if (!$recipe->product_option_id && !$recipe->modifier_id) return true;
                
                // Option recipe
                if ($recipe->product_option_id && in_array($recipe->product_option_id, $itemOptionIds)) return true;
                
                // Modifier recipe
                if ($recipe->modifier_id && in_array($recipe->modifier_id, $itemModifierIds)) return true;

                return false;
            });

            foreach ($itemRecipes as $recipe) {
                if (!$recipe->ingredient) continue;
                $ingredientId = $recipe->ingredient_id;
                $requiredQty = $recipe->quantity * $qty;

                if (!isset($ingredientRequirements[$ingredientId])) {
                     $ingredientRequirements[$ingredientId] = [
                         'required' => 0,
                         'name'     => $recipe->ingredient->name,
                     ];
                }
                $ingredientRequirements[$ingredientId]['required'] += $requiredQty;
            }
        }

        // Check each aggregated requirement against total stock in ONE query
        $ingredientIds = array_keys($ingredientRequirements);
        $stocks = Product::getUnexpiredStocks((int)$this->branchId, $ingredientIds);

        foreach ($ingredientRequirements as $ingredientId => $data) {
            $availableQty = $stocks[$ingredientId] ?? 0;

            if ($availableQty < $data['required']) {
                return [
                    'available' => false,
                    'message' => "Insufficient {$data['name']}. Available: {$availableQty}, Cart Requires: {$data['required']}"
                ];
            }
        }

        return ['available' => true];
    }


    public function confirmPayment(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Cart is empty.');
            return;
        }

        if (!$this->branchId) {
            $this->dispatch('notify', type: 'error', message: 'No branch assigned.');
            return;
        }

        if ($this->paymentMethod === 'Cash' && $this->amountTendered < $this->total) {
            $this->dispatch('notify', type: 'error', message: 'Amount tendered is less than total amount.');
            return;
        }

        if ($this->paymentMethod === 'GCash') {
            // Allows either PayMongo automated confirmation OR manual reference entry (for Static QR mode)
            if (!$this->gcashVerified && empty($this->paymentReference)) {
                $this->addError('gcashVerified', 'Please wait for GCash confirmation or enter a reference number.');
                return;
            }
        }

        $this->validate([
            'tableNumber' => ['nullable', 'string', 'max:20', 'regex:' . ValidationHelper::REGEX_NAME_BASIC]
        ], ValidationHelper::commonMessages());

        // ──── STOCK VALIDATION ────────────────────────────────────────────────
        $stockValidation = $this->validateStockAvailability();
        if (!$stockValidation['available']) {
            $this->dispatch('notify', 
                type: 'error',
                message: 'Insufficient stock: ' . $stockValidation['message']
            );
            return;
        }

        DB::transaction(function() {
            // Create Order
            // For POS orders: When payment is confirmed, status should be COMPLETED (not PREPARING)
            // Preparing/Ready are delivery/kitchen statuses, not for point-of-sale
            $order = Order::create([
                'reference_no'    => $this->referenceNo,
                'branch_id'       => $this->branchId,
                'user_id'         => auth()->id(),
                'total_amount'    => $this->total,
                'tax_amount'      => $this->taxAmount,
                'service_charge'  => $this->serviceChargeAmount,
                'discount_amount' => $this->discountAmount,
                'payment_method'  => $this->paymentMethod,
                'payment_reference'=> $this->paymentMethod === 'GCash' ? $this->paymentReference : null,
                'order_type'      => $this->orderType,
                'table_number'    => $this->tableNumber,
                'status'          => Order::STATUS_COMPLETED, // POS orders complete immediately when paid
                'payment_status'  => 'Paid',
                'source'          => 'POS',
                'notes'           => $this->generateDiscountNotes(),
            ]);

            // Create Order Items + deduct stock
            foreach (array_filter($this->cart ?: []) as $key => $item) {
                // Item details
                $productId = $item['id'];
                $optionIds = collect($item['options'] ?? [])->pluck('id')->toArray();
                $modifierIds = collect($item['modifiers'] ?? [])->pluck('id')->toArray();

                $orderItem = OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $productId,
                    'quantity'   => $item['qty'],
                    'unit_price' => $item['price'],
                    'subtotal'   => $item['price'] * $item['qty'],
                ]);

                // Save Selected Options to DB
                if (!empty($item['options'])) {
                    foreach ($item['options'] as $opt) {
                        OrderItemOption::create([
                            'order_item_id'     => $orderItem->id,
                            'product_option_id' => $opt['id'],
                            'price'             => $opt['price'],
                        ]);
                    }
                }

                // Save Modifiers to DB
                if (!empty($item['modifiers'])) {
                    foreach ($item['modifiers'] as $m) {
                        OrderItemModifier::create([
                            'order_item_id' => $orderItem->id,
                            'modifier_id'   => $m['id'],
                            'unit_price'    => $m['price'],
                        ]);
                    }
                }

                // Deduct stock: Base + Options + Modifiers
                $recipeQuery = Recipe::where('product_id', $productId);
                
                // Filter recipes for base, selected options, and selected modifiers
                $recipeQuery->where(function($q) use ($optionIds, $modifierIds) {
                    $q->whereNull('product_option_id')->whereNull('modifier_id'); // Base
                    
                    if (!empty($optionIds)) {
                        $q->orWhereIn('product_option_id', $optionIds);
                    }
                    
                    if (!empty($modifierIds)) {
                        $q->orWhereIn('modifier_id', $modifierIds);
                    }
                });
                
                $recipes = $recipeQuery->get();

                foreach ($recipes as $recipe) {
                    $baseQty = $recipe->quantity * $item['qty'];
                    
                    // 🔥 FEFO Deduction with audit trail
                    $result = StockDeductionService::deductByFEFO(
                        branchId: $this->branchId,
                        ingredientId: $recipe->ingredient_id,
                        quantity: $baseQty,
                        orderItemId: $orderItem->id,
                        userId: auth()->id(),
                        remarks: "Order #{$order->reference_no}"
                    );

                    if (!$result['success']) {
                        throw new \Exception(
                            "Stock deduction failed for {$recipe->ingredient->name}: {$result['message']}"
                        );
                    }
                }
            }

            $this->clearCart();
            $this->dispatch('close-modal', 'pos-payment');
            $this->dispatch('cart-collapsed');
            $this->dispatch('cart-reset');
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} placed successfully!");
            
            // 🖨️ AUTOMATIC RECEIPT PRINTING
            // This dispatches to browser event listener in layouts/app.blade.php
            // The receipt window will open and automatically trigger print() for the wired printer
            $receiptUrl = route('receipts.thermal', ['order' => $order->id]);
            $this->dispatch('open-receipt', url: $receiptUrl);
        });
    }

    protected function generateReferenceNo(): string
    {
        $attempts = 0;
        do {
            $ref = 'MTC-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
            if (++$attempts > 10) {
                throw new \RuntimeException('Failed to generate unique reference number after 10 attempts.');
            }
        } while (Order::where('reference_no', $ref)->exists());

        return $ref;
    }

    // ─── Render ────────────────────────────────────────────────────────────
    public function render()
    {
        return view('livewire.pos-terminal', [
            'products'            => $this->products,
            'categories'          => $this->categories,
            'branchId'            => $this->branchId,
            'subtotal'            => $this->subtotal,
            'total'               => $this->total,
            'discountAmount'      => $this->discountAmount,
            'serviceChargeAmount' => $this->serviceChargeAmount,
            'taxAmount'           => $this->taxAmount,
            'change'              => $this->change,
        ])->layout('layouts.app', ['noPadding' => true]);
    }
}
