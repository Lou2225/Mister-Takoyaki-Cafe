<?php

namespace App\Http\Livewire;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;

class PosTerminal extends Component
{
    use HandlesValidations;
    // ─── Filters ───────────────────────────────────────────────────────────
    public $selectedCategoryId = null;
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
    public $branchId = null;
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
    public $editCartItemId = null;
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
    public $showingOptionsId = null;
    public array $selectedOptions = []; // [groupId => optionId]
    public array $selectedModifierIds = [];
    public $currentProduct = null;
    public array $optionAvailability = [];
    public array $modifierAvailability = []; // [modifierId => quantity_available]
    public bool $isVerifyingGCash = false;
    public bool $gcashVerified = false;
    public array $gcashTransactionDetails = [];

    public function updatedPaymentMethod()
    {
        $this->gcashVerified = false;
        $this->paymentReference = '';
        $this->gcashTransactionDetails = [];
    }

    // ─── Mount ─────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = \App\Services\BranchContext::getActiveBranchId() ?: $user->branch_id;

        // Load settings from database
        $this->taxRate           = (float) SystemSetting::get('vat_rate', 0);
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

    public function updatedPaymentReference()
    {
        $this->validateFieldLive('paymentReference', ['nullable', 'string', 'max:50', 'regex:' . ValidationHelper::REGEX_NAME_BASIC], ValidationHelper::commonMessages());
    }

    public function updatedTableNumber()
    {
        $this->validateFieldLive('tableNumber', ['nullable', 'string', 'max:20', 'regex:' . ValidationHelper::REGEX_NAME_BASIC], ValidationHelper::commonMessages());
    }

    public function toggleEditMode(): void
    {
        if (!$this->canEditLayout()) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Unauthorized to edit layout.']);
            return;
        }
        $this->isEditMode = !$this->isEditMode;
        if ($this->isEditMode) {
            $this->dispatchBrowserEvent('notify', ['type' => 'info', 'message' => 'Layout Edit Mode Enabled. Drag items to reorder.']);
        }
    }

    private function canEditLayout(): bool
    {
        $roleId = auth()->user()->role_id;
        return $roleId === 1 || $roleId === 2;
    }

    public function reorderProducts(array $orderedIds): void
    {
        if (!$this->canEditLayout()) return;
        if (!$this->branchId) return;

        DB::transaction(function() use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                DB::table('branch_product')->updateOrInsert(
                    ['branch_id' => $this->branchId, 'product_id' => $id],
                    ['sort_order' => $index]
                );
            }
        });

        $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => 'Branch menu layout updated.']);
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

        $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => 'Branch category layout updated.']);
    }

    // ─── Computed: Products ────────────────────────────────────────────────
    protected $productsCache = null;
    public function getProductsProperty()
    {
        if ($this->productsCache !== null) return $this->productsCache;

        $query = Product::with(['category', 'recipes', 'optionGroups.options'])
            ->where('is_active', true);

        // Scope to branch
        if ($this->branchId) {
            $query->where(function ($q) {
                $q->where('scope', 'global')
                    ->orWhereHas('branches', fn($bq) => $bq->where('branches.id', $this->branchId));
            });

            $query->leftJoin('branch_product', function($join) {
                $join->on('products.id', '=', 'branch_product.product_id')
                     ->where('branch_product.branch_id', '=', $this->branchId);
            })
            ->select('products.*', DB::raw('products.id as id'), 'branch_product.sort_order as branch_sort_order');
        }

        if ($this->selectedCategoryId) {
            $query->where('category_id', $this->selectedCategoryId);
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $orderBy = $this->branchId 
            ? DB::raw('COALESCE(branch_product.sort_order, products.sort_order)') 
            : 'sort_order';

        $products = $query->orderBy($orderBy, 'asc')
            ->orderBy('name', 'asc')
            ->get();

        // Performance Optimization: Bulk fetch stocks for all products in this view
        if ($this->branchId && $products->isNotEmpty()) {
            $ingredientIds = $products->flatMap(function($p) {
                return $p->recipes->pluck('ingredient_id');
            })->unique();

            $stocks = BranchIngredientStock::where('branch_id', $this->branchId)
                ->whereIn('ingredient_id', $ingredientIds)
                ->pluck('stock_quantity', 'ingredient_id');

            foreach ($products as $product) {
                // We'll use a dynamic property to carry the stocks to the view loop
                $product->prefetched_stocks = $stocks;
            }
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

    public function openDraftsModal(): void
    {
        $this->showDraftsModal = true;
        // Standard payload for modal component
        $this->dispatchBrowserEvent('open-modal', ['name' => 'pos-drafts-list']);
    }

    public function closeDraftsModal(): void
    {
        $this->showDraftsModal = false;
        $this->dispatchBrowserEvent('close-modal', 'pos-drafts-list');
    }

    // ─── Computed: Categories ──────────────────────────────────────────────
    protected $categoriesCache = null;
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
    public function getSubtotalProperty(): float
    {
        return array_sum(array_map(fn($item) => $item['price'] * $item['qty'], $this->cart));
    }

    public function getTaxAmountProperty(): float
    {
        return round($this->subtotal * $this->taxRate, 2);
    }

    public function getServiceChargeAmountProperty(): float
    {
        if ($this->orderType === 'Dine-in') {
            return round($this->subtotal * $this->serviceChargeRate, 2);
        }
        return 0;
    }

    public function getDiscountAmountProperty(): float
    {
        $totalDiscount = 0;
        foreach ($this->cart as $item) {
            $isSenior = $item['apply_senior_discount'] ?? false;
            $isRegular = $item['apply_regular_discount'] ?? false;

            if (!$isSenior && !$isRegular) continue;

            $itemTotal = $item['price'] * $item['qty'];
            
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

    public function getTotalProperty(): float
    {
        $total = 0;
        foreach ($this->cart as $item) {
            $isSenior = $item['apply_senior_discount'] ?? false;
            $isRegular = $item['apply_regular_discount'] ?? false;
            
            $itemTotal = $item['price'] * $item['qty'];
            
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

    public function getChangeProperty(): float
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
    public function addToCart(int $productId): void
    {
        $product = Product::with(['optionGroups.options', 'modifiers', 'recipes'])->find($productId);
        if (!$product || !$product->is_active) return;

        // Optimization: Fetch all stocks once for the entire availability check
        $stocks = null;
        if ($this->branchId) {
            $ingredientIds = $product->recipes->pluck('ingredient_id')->unique();
            $stocks = BranchIngredientStock::where('branch_id', $this->branchId)
                ->whereIn('ingredient_id', $ingredientIds)
                ->pluck('stock_quantity', 'ingredient_id');
        }

        $available = $this->branchId
            ? $product->getMaxAvailableQuantity((int)$this->branchId, $stocks) > 0
            : true;

        if (!$available) {
            $this->dispatchBrowserEvent('notify', ['type' => 'warning', 'message' => 'This item is currently unavailable (Out of Stock).']);
            return;
        }

        // If product has option groups or modifiers, open options modal
        if (count($product->optionGroups) > 0 || count($product->modifiers) > 0) {
            $this->showingOptionsId = $productId;
            $this->currentProduct = $product;
            $this->optionAvailability = $product->getOptionAvailability((int)$this->branchId, $stocks);
            $this->modifierAvailability = $product->getModifierAvailability((int)$this->branchId, $stocks);
            
            // Initialize selections: only pick if required or an explicit default exists
            $this->selectedOptions = [];
            foreach ($product->optionGroups as $group) {
                $default = $group->options->where('is_default', true)->first();
                
                if ($default) {
                    $this->selectedOptions[$group->id] = $default->id;
                } elseif ($group->is_required) {
                    // Only fall back to first if it's required
                    $first = $group->options->first();
                    $this->selectedOptions[$group->id] = $first ? $first->id : null;
                } else {
                    $this->selectedOptions[$group->id] = null;
                }
            }
            
            $this->selectedModifierIds = [];
            $this->dispatchBrowserEvent('open-modal', 'pos-options');
            return;
        }

        $this->confirmAdd($productId);
    }

    public function toggleOption(int $groupId, int $optionId): void
    {
        if (!$this->currentProduct) return;
        
        $group = $this->currentProduct->optionGroups->where('id', $groupId)->first();
        if (!$group) return;

        if (isset($this->selectedOptions[$groupId]) && $this->selectedOptions[$groupId] == $optionId) {
            // If already selected and NOT required, we can unselect it
            if (!$group->is_required) {
                $this->selectedOptions[$groupId] = null;
            }
        } else {
            // Select it
            $this->selectedOptions[$groupId] = $optionId;
        }
    }

    public function confirmAddWithOptions(): void
    {
        if (!$this->showingOptionsId) return;
        
        // Validate required options are selected
        foreach ($this->currentProduct->optionGroups as $group) {
            if ($group->is_required && empty($this->selectedOptions[$group->id])) {
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error', 
                    'message' => "Please select {$group->name} to continue."
                ]);
                return;
            }
        }

        // Optimization: Fetch all stocks once
        $this->currentProduct->loadMissing('recipes');
        $ingredientIds = $this->currentProduct->recipes->pluck('ingredient_id')->unique();
        $stocks = BranchIngredientStock::where('branch_id', $this->branchId)
            ->whereIn('ingredient_id', $ingredientIds)
            ->pluck('stock_quantity', 'ingredient_id');

        // Validate stock availability for selected options
        $optionAvail = $this->currentProduct->getOptionAvailability($this->branchId, $stocks);
        foreach ($this->selectedOptions as $groupId => $optionId) {
            if (!$optionId) continue; // Skip if no option is selected for this group
            
            if (($optionAvail[$optionId] ?? 0) <= 0) {
                $option = ProductOption::find($optionId);
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => "The selected option '" . ($option->name ?? 'Unknown') . "' is out of stock."
                ]);
                return;
            }
        }

        // Validate stock availability for selected modifiers
        $modifierAvail = $this->currentProduct->getModifierAvailability($this->branchId, $stocks);
        foreach ($this->selectedModifierIds as $modId) {
            if (($modifierAvail[$modId] ?? 0) <= 0) {
                $modifier = Modifier::find($modId);
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => "The selected add-on '{$modifier->name}' is out of stock."
                ]);
                return;
            }
        }


        // Filter out null options before adding to cart
        $filteredOptions = array_filter($this->selectedOptions);
        
        $this->confirmAdd($this->showingOptionsId, $filteredOptions, $this->selectedModifierIds);
        $this->closeOptionsModal();
    }

    public function closeOptionsModal(): void
    {
        $this->showingOptionsId = null;
        $this->selectedOptions = [];
        $this->selectedModifierIds = [];
        $this->currentProduct = null;
        $this->dispatchBrowserEvent('close-modal', 'pos-options');
    }

    public function closePaymentModal(): void
    {
        $this->paymentReference = '';
        $this->dispatchBrowserEvent('close-modal', 'pos-payment');
    }

    public function closeEditItemModal(): void
    {
        $this->editCartItemId = null;
        $this->editCartItemQty = 1;
        $this->dispatchBrowserEvent('close-modal', 'edit-cart-item');
    }

    protected function confirmAdd(int $productId, array $optionIds = [], array $modifierIds = [])
    {
        $product = Product::find($productId);
        if (!$product) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Product no longer available.']);
            return;
        }

        $modifiers = !empty($modifierIds) ? Modifier::whereIn('id', $modifierIds)->get() : collect([]);
        $selectedOptions = !empty($optionIds) ? ProductOption::with('group')->whereIn('id', array_values($optionIds))->get() : collect([]);

        // Price Logic:
        // 1. Check if any "Fixed" price mode groups are selected. If so, their option prices become the base.
        // 2. If no fixed groups, use Product Base Price.
        // 3. Add prices of all "Additive" options.
        // 4. Add prices of all modifiers.
        
        $fixedOptions = $selectedOptions->filter(fn($o) => $o->group->price_mode === 'fixed');
        $additiveOptions = $selectedOptions->filter(fn($o) => $o->group->price_mode === 'additive');

        $basePrice = $fixedOptions->isNotEmpty() ? $fixedOptions->sum('price') : (float)$product->getPriceAt($this->branchId);
        $additiveTotal = $additiveOptions->isNotEmpty() ? (float)$additiveOptions->sum('price') : 0;
        $modifierTotal = $modifiers->isNotEmpty() ? (float)$modifiers->sum('price') : 0;
        
        $finalPrice = $basePrice + $additiveTotal + $modifierTotal;

        // Create a unique key for the cart
        $optKey = !empty($optionIds) ? '-' . implode(',', collect($optionIds)->sort()->toArray()) : '';
        $modKey = !empty($modifierIds) ? '-' . implode(',', collect($modifierIds)->sort()->toArray()) : '';
        $key = $productId . $optKey . $modKey;

        $oldCart = $this->cart;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['qty']++;
        } else {
            $this->cart[$key] = [
                'id'            => $productId,
                'key'           => $key,
                'name'          => $product->name,
                'options'       => $selectedOptions->isNotEmpty() ? $selectedOptions->map(fn($o) => ['id' => $o->id, 'name' => $o->name, 'price' => (float)$o->price])->toArray() : [],
                'modifiers'     => $modifiers->isNotEmpty() ? $modifiers->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'price' => (float)$m->price])->toArray() : [],
                'price'         => $finalPrice,
                'qty'           => 1,
                'instructions'  => '',
                'image'         => $product->image,
                'category'      => $product->category?->name ?? '',
                'apply_regular_discount'=> false,
                'apply_senior_discount' => false,
            ];
        }

        $stockValidation = $this->validateStockAvailability();
        if (!$stockValidation['available']) {
            $this->cart = $oldCart;
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Cannot add item. ' . $stockValidation['message']
            ]);
            return;
        }

    }

    public function decrementCart(string $key): void
    {
        if (!isset($this->cart[$key])) return;

        if ($this->cart[$key]['qty'] > 1) {
            $this->cart[$key]['qty']--;
        } else {
            unset($this->cart[$key]);
        }

        $this->cart = array_merge($this->cart, []);
    }

    public function incrementCart(string $key): void
    {
        if (!isset($this->cart[$key])) return;

        $oldCart = $this->cart;
        $this->cart[$key]['qty']++;

        $stockValidation = $this->validateStockAvailability();
        if (!$stockValidation['available']) {
            $this->cart = $oldCart;
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Cannot add item. ' . $stockValidation['message']
            ]);
            return;
        }

        $this->cart = array_merge($this->cart, []);
    }

    public function removeFromCart(string $key): void
    {
        unset($this->cart[$key]);
    }

    public function openEditItem(string $key): void
    {
        if (!isset($this->cart[$key])) return;
        $this->editCartItemId = $key;
        $this->editCartItemQty = $this->cart[$key]['qty'];
        $this->editCartItemNotes = $this->cart[$key]['instructions'] ?? '';
        $this->applyRegularDiscount = $this->cart[$key]['apply_regular_discount'] ?? false;
        $this->applySeniorDiscount = $this->cart[$key]['apply_senior_discount'] ?? false;
        $this->dispatchBrowserEvent('open-modal', 'edit-cart-item');
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
            $this->dispatchBrowserEvent('notify', ['type' => 'warning', 'message' => 'Cart is empty. Nothing to save.']);
            return;
        }

        if (!$this->branchId) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'No branch assigned.']);
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
                foreach ($this->cart as $key => $item) {
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
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'success',
                    'message' => "Draft order #{$draftOrder->reference_no} saved successfully!"
                ]);
            });
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Failed to save draft: ' . $e->getMessage()
            ]);
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
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => 'Unauthorized to load this draft.'
                ]);
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

            $this->dispatchBrowserEvent('close-modal', ['name' => 'pos-drafts-list']);
            
            // Delete draft ONLY after successful load into memory
            $draft->delete();

            $this->dispatchBrowserEvent('notify', [
                'type' => 'success',
                'message' => "Draft order #{$draft->reference_no} loaded successfully!"
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Failed to load draft: ' . $e->getMessage()
            ]);
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
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'error',
                    'message' => 'Unauthorized to delete this draft.'
                ]);
                return;
            }

            $draft->delete();

            $this->dispatchBrowserEvent('notify', [
                'type' => 'success',
                'message' => 'Draft deleted successfully.'
            ]);
        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Failed to delete draft: ' . $e->getMessage()
            ]);
        }
    }
    public function verifyGCashPayment(): void
    {
        $this->isVerifyingGCash = true;
        
        // Remove sleep(2) to prevent session locking and improve responsiveness
        // For a mock, setting state instantly is better

        // Mock legitimate transaction data
        $this->paymentReference = 'GC' . strtoupper(Str::random(10));
        $this->gcashVerified = true;
        $this->isVerifyingGCash = false;
        $this->gcashTransactionDetails = [
            'status' => 'SUCCESS',
            'amount' => $this->total,
            'timestamp' => now()->format('M d, Y h:i A'),
            'channel' => 'GCash App',
            'reference' => $this->paymentReference
        ];

        $this->dispatchBrowserEvent('notify', [
            'type' => 'success',
            'message' => 'GCash Payment Verified Legitimate!'
        ]);
    }

    protected function validateStockAvailability(): array
    {
        if (empty($this->cart)) {
            return ['available' => true];
        }

        $productIds = [];
        $optionIds = [];
        $modifierIds = [];

        foreach ($this->cart as $item) {
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

        foreach ($this->cart as $item) {
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
        $stocks = BranchIngredientStock::where('branch_id', $this->branchId)
            ->whereIn('ingredient_id', $ingredientIds)
            ->pluck('stock_quantity', 'ingredient_id');

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

    public function openPayment(): void
    {
        if (empty($this->cart)) return;
        $this->amountTendered = $this->total;
        $this->paymentReference = '';
        $this->dispatchBrowserEvent('open-modal', 'pos-payment');
    }

    public function confirmPayment(): void
    {
        if (empty($this->cart)) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Cart is empty.']);
            return;
        }

        if (!$this->branchId) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'No branch assigned.']);
            return;
        }

        if ($this->paymentMethod === 'Cash' && $this->amountTendered < $this->total) {
            $this->dispatchBrowserEvent('notify', ['type' => 'error', 'message' => 'Amount tendered is less than total amount.']);
            return;
        }

        if ($this->paymentMethod === 'GCash') {
            $this->validate([
                'paymentReference' => ['required', 'string', 'max:50', 'regex:' . ValidationHelper::REGEX_NAME_BASIC]
            ], ValidationHelper::commonMessages());
        }

        $this->validate([
            'tableNumber' => ['nullable', 'string', 'max:20', 'regex:' . ValidationHelper::REGEX_NAME_BASIC]
        ], ValidationHelper::commonMessages());

        // ──── STOCK VALIDATION ────────────────────────────────────────────────
        $stockValidation = $this->validateStockAvailability();
        if (!$stockValidation['available']) {
            $this->dispatchBrowserEvent('notify', [
                'type' => 'error',
                'message' => 'Insufficient stock: ' . $stockValidation['message']
            ]);
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
            foreach ($this->cart as $key => $item) {
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
            $this->dispatchBrowserEvent('close-modal', 'pos-payment');
            $this->dispatchBrowserEvent('notify', ['type' => 'success', 'message' => "Order #{$order->reference_no} placed successfully!"]);
            
            // 🖨️ AUTOMATIC RECEIPT PRINTING
            // This dispatches to browser event listener in layouts/app.blade.php
            // The receipt window will open and automatically trigger print() for the wired printer
            $receiptUrl = route('receipts.thermal', ['order' => $order->id]);
            $this->dispatchBrowserEvent('open-receipt', ['url' => $receiptUrl]);
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
            'products'         => $this->products,
            'categories'       => $this->categories,
            'branchId'         => $this->branchId,
            'currencySymbol'   => $this->currencySymbol,
            'subtotal'         => $this->subtotal,
            'taxAmount'        => $this->taxAmount,
            'discountAmount'   => $this->discountAmount,
            'serviceChargeAmount' => $this->serviceChargeAmount,
            'total'            => $this->total,
            'change'           => $this->change,
            'cartCount'        => $this->cartCount,
        ])->layout('layouts.app', ['noPadding' => true]);
    }
}
