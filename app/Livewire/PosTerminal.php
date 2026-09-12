<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\OrderItemModifier;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Recipe;
use App\Models\SystemSetting;
use App\Models\BranchCategorySort;
use App\Services\StockDeductionService;
use App\Services\PayMongoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;
use App\Traits\RequiresOperatingBranch;


class PosTerminal extends Component
{
    use HandlesValidations, RequiresOperatingBranch;
    

    // ─── Cart ──────────────────────────────────────────────────────────────
    // Structure: [ product_id => ['name', 'price', 'qty', 'image', 'available'] ]
    public array $cart = [];

    // ─── Order Meta ────────────────────────────────────────────────────────
    public string $orderType = '';
    public string $tableNumber = '';
    public string $paymentMethod = '';
    public string $paymentReference = '';
    public $amountTendered = 0;

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
    public ?int $editCartItemProductId = null;
    public $editCartItemQty = 1;
    public string $editCartItemNotes = '';
    public array $branches = [];
    public string $gcashAccountName = '';
    public string $gcashAccountNumber = '';
    public string $gcashQrImage = '';
    public bool $isEditMode = false;
    public string $primaryColor = 'indigo';

    // ─── Options Modal ─────────────────────────────────────
    public bool $gcashVerified = false;
    public array $gcashTransactionDetails = [];
    // PayMongo GCash payment state
    public ?string $gcashPaymentUrl = null;
    public ?string $gcashPaymentIntentId = null;
    public bool $gcashPolling = false;
    public bool $isManualGcash = false; // To distinguish verification types

public function updatedPaymentMethod(): void
{
    if ($this->gcashVerified && $this->paymentMethod !== 'GCash') {
        $this->paymentMethod = 'GCash';
        $this->dispatch('notify', type: 'warning', message: 'Verified GCash payment is locked to GCash.');
        return;
    }

    $this->resetGCashState();
}

/**
 * The Amount Tendered input can be cleared to an empty string by the
 * cashier. $amountTendered is a typed float property, so an empty
 * string sent from the browser on blur would throw a TypeError when
 * Livewire tries to hydrate it. Coerce anything non-numeric to 0
 * instead of letting it reach the typed property directly.
 */
public function updatingAmountTendered($value)
{
    $this->amountTendered = is_numeric($value) ? (float) $value : 0;
}

public function updatedAmountTendered(): void
{
    $this->resetErrorBag('amountTendered');
}

protected $listeners = [
    'posSettingsUpdated' => 'handlePosSettingsUpdated',
];

/**
 * Fired when System Settings > POS Platform is saved elsewhere. Refreshes
 * the live order type / payment method lists (and GCash config) without
 * requiring the cashier to reload the page. If the currently-selected
 * order type or payment method no longer exists in the updated list,
 * fall back to the first available option and clear anything tied to
 * the old selection (e.g. an in-progress GCash verification).
 */
    public function handlePosSettingsUpdated($pos = null): void
    {
        $this->orderTypes      = (array) SystemSetting::get('pos_order_types', ['Dine-in', 'Take-out'], $this->branchId);
        $this->paymentMethods   = (array) SystemSetting::get('pos_payment_methods', ['Cash', 'GCash'], $this->branchId);
        $this->serviceChargeRate = (float) SystemSetting::get('service_charge', 0, $this->branchId);
        $this->discountPercent   = (float) SystemSetting::get('discount_rate', 0, $this->branchId);
        $this->seniorDiscountRate = (float) SystemSetting::get('senior_discount_rate', 0.20, $this->branchId);
        $this->gcashAccountName   = (string) SystemSetting::get('gcash_account_name', 'Mister Takoyaki Cafe', $this->branchId);
        $this->gcashAccountNumber = (string) SystemSetting::get('gcash_account_number', '', $this->branchId);
        $this->gcashQrImage       = (string) SystemSetting::get('gcash_qr_image', '', $this->branchId);

    if (!in_array($this->orderType, $this->orderTypes, true)) {
        $this->orderType = $this->orderTypes[0] ?? 'Dine-in';
    }

    if (!in_array($this->paymentMethod, $this->paymentMethods, true)) {
        $this->paymentMethod = $this->paymentMethods[0] ?? 'Cash';
        $this->resetGCashState();
    }

    $this->dispatch('notify', type: 'info', message: 'POS configuration was updated.');
}

public function resetGCashState(): void
{
    $this->gcashVerified = false;
    $this->gcashPolling = false;
    $this->gcashPaymentUrl = null;
    $this->gcashPaymentIntentId = null;
    $this->isManualGcash = false;
    $this->paymentReference = '';
    $this->gcashTransactionDetails = [];

    // Alpine's local `gcashPaid` (in the payment modal's x-data) is only
    // ever set once, on first init — x-show never re-runs it. Without
    // this event, clearing the server-side flag here left the UI
    // permanently showing "Confirmed Payment" for every order afterward,
    // even a brand new one that was never verified.
    $this->dispatch('gcash-reset');
}

/**
 * Called by the Cancel button on the payment modal. Once a GCash
 * payment has been verified (dynamic PayMongo or manual static QR),
 * the money has already been received — cancelling here without
 * placing the order would leave a paid transaction with no order
 * record at all. In that state the only way out is to place the
 * order, then void it afterward in Order Management if needed.
 */
        #[Renderless]
    public function cancelPaymentModal(): void
    {
        if ($this->paymentMethod === 'GCash' && $this->gcashVerified) {
            $this->addError('gcashCancel', 'This payment has already been verified and the money has already been received. You must place the order to complete it — to cancel it afterward, void the completed order in Order Management.');
            return;
        }

        $this->resetErrorBag('gcashCancel');
        $this->resetGCashState();
        $this->dispatch('close-modal', 'pos-payment');
    }

    /**
     * Public wrapper so the "Proceed to Payment" button can clear a stale
     * gcashCancel warning from a previous order before opening a fresh
     * payment modal. $wire.methodName() from Blade/Alpine can only invoke
     * public component methods — resetErrorBag() itself isn't callable
     * directly that way since it's a trait/framework method, not a
     * public action on this component.
     */
    /**
 * The modal itself is opened INSTANTLY on the client (Alpine dispatches
 * 'open-modal' directly in the "Proceed to Payment" button, before this
 * method's network round-trip even begins — see pos-terminal.blade.php).
 *
 * This method no longer owns opening the modal. It runs in the
 * background purely to re-verify the cart/prices/stock against the
 * database. If that verification fails, it closes the modal it never
 * actually needed to open in the first place.
 */
#[Renderless]
public function openPaymentModal(): void
{
    $this->resetErrorBag('gcashCancel');
    $this->resetErrorBag('amountTendered');

    if (empty($this->cart)) {
        $this->dispatch('close-modal', 'pos-payment');
        $this->dispatch('notify', type: 'error', message: 'Cart is empty.');
        return;
    }

    $this->cart = $this->buildVerifiedCart();
    $stockValidation = $this->validateStockAvailability();

    if (!$stockValidation['available']) {
        $this->dispatch('close-modal', 'pos-payment');
        $this->dispatch('notify',
            type: 'error',
            message: 'Insufficient stock: ' . $stockValidation['message']
        );
        return;
    }

    $this->amountTendered = $this->total;
}

// ─── Mount ─────────────────────────────────────────────────────────────
// ─── Mount ─────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->guardOperatingBranch();

        $user = auth()->user();
        $this->branchId = \App\Services\BranchContext::getActiveBranchId() ?: $user->branch_id;

        // Load settings from database
        $this->taxRate           = 0;
        $this->serviceChargeRate = (float) SystemSetting::get('service_charge', 0, $this->branchId);
        $this->discountPercent   = (float) SystemSetting::get('discount_rate', 0, $this->branchId);
        $this->seniorDiscountRate = (float) SystemSetting::get('senior_discount_rate', 0.20, $this->branchId);
        $this->currencySymbol    = (string) SystemSetting::get('currency_symbol', '₱');
        
        $this->orderTypes      = (array) SystemSetting::get('pos_order_types', ['Dine-in', 'Take-out'], $this->branchId);
        $this->paymentMethods = (array) SystemSetting::get('pos_payment_methods', ['Cash', 'GCash'], $this->branchId);

        $userTheme = $user->getRoleTheme();
        $this->primaryColor = $userTheme['primary'] ?? 'indigo';
        
        $this->gcashAccountName   = (string) SystemSetting::get('gcash_account_name', 'Mister Takoyaki Cafe', $this->branchId);
        $this->gcashAccountNumber = (string) SystemSetting::get('gcash_account_number', '', $this->branchId);
        $this->gcashQrImage = (string) SystemSetting::get('gcash_qr_image', '', $this->branchId);

        // Set defaults
        $this->orderType     = $this->orderTypes[0] ?? 'Dine-in';
        $this->paymentMethod = $this->paymentMethods[0] ?? 'Cash';

        // Reference number is generated lazily — see saveDraft()/confirmPayment()
        // — not here, so an abandoned session never shows a "committed" order ID
        // for an order that doesn't exist yet.
        $this->referenceNo = '';

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
        $this->validateFieldLive('tableNumber', ['nullable', 'string', 'max:2', 'regex:/^[0-9]*$/'], ValidationHelper::commonMessages());
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

    public function saveLayout(?array $orderedProductIds = null, ?array $orderedCategoryIds = null): void
    {
        if (!$this->canEditLayout()) return;

        $savedSomething = false;

        if ($orderedProductIds && $this->branchId) {
            DB::transaction(function() use ($orderedProductIds) {
                foreach ($orderedProductIds as $index => $id) {
                    DB::table('branch_product')->updateOrInsert(
                        ['branch_id' => $this->branchId, 'product_id' => $id],
                        ['sort_order' => $index]
                    );
                }
            });
            $savedSomething = true;
        }

        if ($orderedCategoryIds && $this->branchId) {
            DB::transaction(function() use ($orderedCategoryIds) {
                foreach ($orderedCategoryIds as $index => $id) {
                    BranchCategorySort::updateOrCreate(
                        ['branch_id' => $this->branchId, 'category_id' => $id],
                        ['sort_order' => $index]
                    );
                }
            });
            $savedSomething = true;
        }

        if ($savedSomething) {
            $this->refreshPosData();
            $this->dispatch('notify', type: 'success', message: 'Menu layout saved.');
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
    }

    public function refreshPosData(): void
    {
        $this->productsCache = null;
        $this->categoriesCache = null;
    }
    
    // ─── Computed: Products ────────────────────────────────────────────────
    protected ?Collection $productsCache = null;
    protected array $posStocks = [];
    public function getProductsProperty()
    {
        if ($this->productsCache !== null) return $this->productsCache;

        $query = Product::with(['category', 'recipes', 'optionGroups.options', 'modifiers']);

       // Scope to branch
        if ($this->branchId) {
            $query->where(function ($q) {
                $q->where('scope', 'global')
                    ->orWhereHas('branches', fn($bq) => $bq->where('branches.id', $this->branchId));
            });
        } else {
            // No branch context — fall back to the product's global
            // is_active flag since there's no branch_product row to check.
            $query->where('products.is_active', true);
        }

        // Joint-based sorting requires manual selects to avoid ID collisions
        $query->select('products.*');

        if ($this->branchId) {
            $query->leftJoin('branch_product', function($join) {
                $join->on('products.id', '=', 'branch_product.product_id')
                     ->where('branch_product.branch_id', '=', (int)$this->branchId);
            })->addSelect('branch_product.sort_order as branch_sort_order');

            // Effective visibility = branch override if one exists,
            // otherwise fall back to the product's global is_active flag.
            $query->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNull('branch_product.is_active')
                        ->where('products.is_active', true);
                })->orWhere('branch_product.is_active', 1);
            });
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
            $this->posStocks = $stocks->toArray();

            foreach ($products as $product) {
                // Pre-calculate availability for the instant modal
                $product->option_availability = $product->getOptionAvailability((int)$this->branchId, $stocks);
                $product->modifier_availability = $product->getModifierAvailability((int)$this->branchId, $stocks);
                $product->max_available = $product->getMaxAvailableQuantity((int)$this->branchId, $stocks);
            }

// SORT: Available items first, then Out of Stock
            $products = $products->sortBy(function($product) {
                $isAvailable = $product->max_available > 0;
                return [
                    $isAvailable ? 0 : 1, // Available (0) first, OOS (1) last
                    $product->category->sort_order ?? 0,
                    $product->branch_sort_order ?? $product->sort_order,
                    $product->name
                ];
            });
        }

        // Tag max_available onto each product for Alpine stock limiting
        foreach ($products as $product) {
            if (!$this->branchId) {
                $product->max_available = 999;
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
    return max(0, (float) $this->amountTendered - $this->total);
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

#[Renderless]
public function openEditItem(string $key, array $item): void
{
    $this->editCartItemId = $key;
    $this->editCartItemProductId = (int) ($item['id'] ?? 0);
    $this->editCartItemQty = $item['qty'] ?? 1;
    $this->editCartItemNotes = $item['instructions'] ?? '';
}

       #[Renderless]
    public function saveEditItem(bool $applyRegularDiscount = false, bool $applySeniorDiscount = false): void
    {
        $key = $this->editCartItemId;
        if (!$key || !$this->editCartItemProductId) return;

        // Guard against exceeding available stock without showing a duplicate toast
        $product = Product::find($this->editCartItemProductId);
        if ($product && $this->branchId) {
            $maxQty = $product->getMaxAvailableQuantity((int)$this->branchId);
            if ($this->editCartItemQty > $maxQty) {
                $this->editCartItemQty = max(1, $maxQty);
            }
        }

        $shouldRemove = $this->editCartItemQty <= 0;

        // Only the fields that changed in this modal are sent back — never
        // the whole cart. The client merges these into its existing item
        // (preserving name/price/image/options/modifiers) and leaves every
        // other item in the cart untouched.
        $this->dispatch('cart-item-updated',
            key: $key,
            remove: $shouldRemove,
            item: $shouldRemove ? null : [
                'qty' => $this->editCartItemQty,
                'instructions' => $this->editCartItemNotes,
                'apply_regular_discount' => $applyRegularDiscount,
                'apply_senior_discount' => $applySeniorDiscount,
            ]
        );

        $this->closeEditItemModal();
    }

    public function closeEditItemModal(): void
    {
        $this->editCartItemId = null;
        $this->editCartItemProductId = null;
        $this->editCartItemQty = 1;
        $this->editCartItemNotes = '';
        $this->dispatch('close-modal', name: 'edit-cart-item');
    }

    /**
     * @param bool $force Bypasses the verified-payment guard. Used internally
     *                     after an order has actually been placed/drafted,
     *                     where the cart legitimately needs to be emptied.
     */
    #[Renderless]
    public function clearCart(bool $force = false): void
    {
        if (!$force && $this->paymentMethod === 'GCash' && $this->gcashVerified) {
            $this->dispatch('notify', type: 'error', message: 'This order has a verified GCash payment and cannot be cleared. Place the order, then void it in Order Management if you need to cancel it.');
            return;
        }

        $this->cart = [];
        $this->referenceNo = '';
        $this->resetGCashState();
        $this->dispatch('cart-reset');
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

        if (empty($this->referenceNo)) {
            $this->referenceNo = $this->generateReferenceNo();
        }

        try {
            DB::transaction(function() {
                $this->cart = $this->buildVerifiedCart();

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
                        'order_id'              => $draftOrder->id,
                        'product_id'            => $productId,
                        'quantity'              => $item['qty'],
                        'unit_price'            => $item['price'],
                        'subtotal'              => $item['price'] * $item['qty'],
                        'special_instructions'  => !empty($item['instructions']) ? $item['instructions'] : null,
                        'apply_regular_discount' => (bool) ($item['apply_regular_discount'] ?? false),
                        'apply_senior_discount'  => (bool) ($item['apply_senior_discount'] ?? false),
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

                $this->clearCart(force: true);
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
            // Note: branchId is intentionally NOT overwritten here — drafts are
            // already scoped to the cashier's current branch in getDraftsProperty(),
            // and reassigning it forces an oversized re-render mid-modal.
            $this->orderType = $draft->order_type;
            $this->tableNumber = $draft->table_number;
            $this->paymentMethod = $draft->payment_method;
            $this->referenceNo = $draft->reference_no;

            // Populate cart from order items
            $this->cart = [];
            foreach ($draft->items as $item) {
                $product = $item->product;
                if (!$product) {
                    throw new \RuntimeException("Draft item {$item->id} references a missing product.");
                }
                
                $optionIds = $item->options->pluck('product_option_id')->sort()->toArray();
                $modifierIds = $item->modifiers->pluck('modifier_id')->sort()->toArray();
                // Create a unique key for the cart (consistent with confirmAdd).
                // Prefixed with a letter so this key is never treated as a
                // numeric array index by the client-side JS cart object.

                $optKey = !empty($optionIds) ? '-' . implode(',', $optionIds) : '';

                $modKey = !empty($modifierIds) ? '-' . implode(',', $modifierIds) : '';

                $key = 'p' . $product->id . $optKey . $modKey;

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
                    'instructions' => $item->special_instructions ?? '',
                    'apply_regular_discount' => (bool) $item->apply_regular_discount,
                    'apply_senior_discount' => (bool) $item->apply_senior_discount,
                ];
            }

            $this->dispatch('cart-loaded', cart: $this->cart);

            $this->dispatch('close-modal', name: 'pos-drafts-list');
            
            // Delete draft ONLY after successful load into memory
            $draft->delete();

            $this->dispatch('notify', 
                type: 'success',
                message: "Draft order #{$draft->reference_no} loaded successfully!"
            );
        } catch (\Throwable $e) {
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
                $this->dispatch('gcash-verified');
            }
        } catch (\Exception $e) {
            // Silent fail — just try again next poll
        }
    }

    /**
     * Manually verifies a static GCash payment.
     * Used when the cashier shows their own QR and checks their phone.
     */
        #[Renderless]
    public function verifyStaticPayment(): void
    {
        $this->gcashVerified = true;
        $this->isManualGcash = true;
        $this->dispatch('notify', type: 'success', message: 'GCash payment manually verified!');
        $this->dispatch('gcash-verified');
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

        $allRecipes = $this->filterOutNoRecipeOptions($allRecipes);

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

    /**
     * Strips out any Recipe rows tied to a product option whose group is
     * flagged "No Recipe Required" — covers products saved before this flag
     * existed, or edited outside the normal save path. Shared by both stock
     * validation and actual FEFO deduction so they can't disagree.
     */
    private function filterOutNoRecipeOptions(\Illuminate\Support\Collection $recipes): \Illuminate\Support\Collection
    {
        $touchedOptionIds = $recipes->pluck('product_option_id')->filter()->unique();
        if ($touchedOptionIds->isEmpty()) {
            return $recipes;
        }
        $groupIdByOption = \App\Models\ProductOption::whereIn('id', $touchedOptionIds)->pluck('group_id', 'id');
        $noRecipeGroupIds = \App\Models\ProductOptionGroup::whereIn('id', $groupIdByOption->unique()->values())
            ->where('no_recipe_required', true)
            ->pluck('id')->all();
        $noRecipeOptionIds = $groupIdByOption->filter(fn($gid) => in_array($gid, $noRecipeGroupIds))->keys()->all();
        return $recipes->reject(fn($r) => $r->product_option_id && in_array($r->product_option_id, $noRecipeOptionIds));
    }


    /**
     * Rebuilds the cart from trusted server-side data.
     * Never trust price/discount flags sent from the browser — only the
     * product/option/modifier IDs and quantities are taken from the client;
     * every price is re-derived from the database.
     */
    protected function buildVerifiedCart(): array
    {
        $productIds = collect($this->cart)->pluck('id')->filter()->unique()->values()->all();

        if (empty($productIds)) {
            return [];
        }

        $products = Product::with(['optionGroups.options', 'modifiers'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $verifiedCart = [];

        foreach ($this->cart as $key => $item) {
            $product = $products->get($item['id'] ?? null);
            if (!$product) {
                continue; // drop any line item referencing a product that doesn't exist
            }

            $requestedOptionIds   = collect($item['options'] ?? [])->pluck('id')->all();
            $requestedModifierIds = collect($item['modifiers'] ?? [])->pluck('id')->all();

            $allOptions = $product->optionGroups->flatMap(fn ($g) => $g->options);

            // Only keep options/modifiers that genuinely belong to this product
            $selectedOptions   = $allOptions->filter(fn ($o) => in_array($o->id, $requestedOptionIds));
            $selectedModifiers = $product->modifiers->filter(fn ($m) => in_array($m->id, $requestedModifierIds));

            // Enforce max_select limit per option group
            $groupedSelected = $selectedOptions->groupBy('group_id');
            $clampedOptions = collect();
            foreach ($groupedSelected as $groupId => $groupOpts) {
                $group = $product->optionGroups->firstWhere('id', $groupId);
                $maxSelect = $group ? ($group->max_select ?: ($group->price_mode === 'fixed' ? 1 : null)) : null;
                if ($maxSelect && $groupOpts->count() > $maxSelect) {
                    $clampedOptions = $clampedOptions->concat($groupOpts->take($maxSelect));
                } else {
                    $clampedOptions = $clampedOptions->concat($groupOpts);
                }
            }
            $selectedOptions = $clampedOptions;

            $hasFixed = $selectedOptions->contains(function ($o) use ($product) {
                $group = $product->optionGroups->firstWhere('id', $o->group_id);
                return $group && $group->price_mode === 'fixed';
            });

            if ($hasFixed) {
                $basePrice = $selectedOptions->filter(function ($o) use ($product) {
                    $group = $product->optionGroups->firstWhere('id', $o->group_id);
                    return $group && $group->price_mode === 'fixed';
                })->sum(fn ($o) => (float) $o->price);
            } else {
                $basePrice = (float) $product->getPriceAt((int) $this->branchId);
            }

            $additivePrice = $selectedOptions->filter(function ($o) use ($product) {
                $group = $product->optionGroups->firstWhere('id', $o->group_id);
                return $group && $group->price_mode === 'additive';
            })->sum(fn ($o) => (float) $o->price);

            $modifiersPrice = $selectedModifiers->sum(fn ($m) => (float) $m->price);

            $verifiedCart[$key] = [
                'id'                     => $product->id,
                'name'                   => $product->name,
                'price'                  => $basePrice + $additivePrice + $modifiersPrice,
                'qty'                    => max(1, (int) ($item['qty'] ?? 1)),
                'image'                  => $product->image,
                'options'                => $selectedOptions->map(fn ($o) => [
                    'id'    => $o->id,
                    'name'  => $o->name,
                    'price' => (float) $o->price,
                ])->values()->all(),
                'modifiers'              => $selectedModifiers->map(fn ($m) => [
                    'id'    => $m->id,
                    'name'  => $m->name,
                    'price' => (float) $m->price,
                ])->values()->all(),
                'instructions'           => $item['instructions'] ?? '',
                'apply_regular_discount' => (bool) ($item['apply_regular_discount'] ?? false),
                'apply_senior_discount'  => (bool) ($item['apply_senior_discount'] ?? false),
            ];
        }

        return $verifiedCart;
    }

        #[Renderless]
    public function confirmPayment(?array $cartData = null): void
    {
        $this->resetErrorBag('gcashCancel');

        if ($cartData !== null) {
            $this->cart = $cartData;
        }

        if ($this->gcashVerified && $this->paymentMethod !== 'GCash') {
            $this->paymentMethod = 'GCash';
            $this->addError('gcashVerified', 'This payment was verified through GCash and must be completed as GCash.');
            return;
        }

        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Cart is empty.');
            return;
        }

        if (!$this->branchId) {
            $this->dispatch('notify', type: 'error', message: 'No branch assigned.');
            return;
        }

        if (empty($this->referenceNo)) {
            $this->referenceNo = $this->generateReferenceNo();
        }

        // Overwrite the client-supplied cart with server-verified prices before
        // computing totals, validating stock, or saving anything.
        $this->cart = $this->buildVerifiedCart();

        if (empty($this->cart)) {
            $this->dispatch('notify', type: 'error', message: 'Cart is empty or contains invalid items.');
            return;
        }

        if (!in_array($this->paymentMethod, $this->paymentMethods, true)) {
            $this->dispatch('notify', type: 'error', message: 'Invalid payment method.');
            return;
        }

        if ($this->paymentMethod === 'Cash' && (float) $this->amountTendered < $this->total) {
            $this->addError('amountTendered', 'Amount tendered is less than the total amount due.');
            return;
        }

        if ($this->paymentMethod === 'GCash') {
            // Allows either PayMongo automated confirmation OR manual reference entry (for Static QR mode)
            if (!$this->gcashVerified && empty($this->paymentReference)) {
                $this->addError('gcashVerified', 'Please verify the payment first before placing an order.');
                return;
            }
        } elseif ($this->paymentMethod !== 'Cash') {
            // Any custom payment method (added via Settings) has no built-in
            // verification flow — require a manually entered reference so it
            // can't be marked Paid with zero proof of payment.
            if (empty(trim($this->paymentReference))) {
                $this->addError('paymentReference', 'Enter a payment reference or confirmation number for this payment method.');
                return;
            }
        }

        if (!in_array($this->orderType, $this->orderTypes, true)) {
            $this->dispatch('notify', type: 'error', message: 'Invalid order type.');
            return;
        }

        $this->validate([
            'tableNumber' => ['nullable', 'string', 'max:2', 'regex:/^[0-9]*$/']
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
                'payment_reference'=> $this->paymentMethod === 'Cash' ? null : $this->paymentReference,
                'amount_tendered' => $this->paymentMethod === 'Cash' ? (float) $this->amountTendered : null,
                'change_amount'   => $this->paymentMethod === 'Cash' ? $this->change : null,
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
                    'order_id'              => $order->id,
                    'product_id'            => $productId,
                    'quantity'              => $item['qty'],
                    'unit_price'            => $item['price'],
                    'subtotal'              => $item['price'] * $item['qty'],
                    'special_instructions'  => !empty($item['instructions']) ? $item['instructions'] : null,
                    'apply_regular_discount' => (bool) ($item['apply_regular_discount'] ?? false),
                    'apply_senior_discount'  => (bool) ($item['apply_senior_discount'] ?? false),
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
                $recipes = $this->filterOutNoRecipeOptions($recipes);

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

            $this->clearCart(force: true);
            $this->dispatch('close-modal', 'pos-payment');
            $this->dispatch('cart-collapsed');
            $this->dispatch('cart-reset');
            $this->dispatch('notify', type: 'success', message: "Order #{$order->reference_no} placed successfully!");
            
                        // 🖨️ AUTOMATIC RECEIPT PRINTING
            // Send print job to thermal printer (async, no user interaction needed)
            $this->dispatch('send-thermal-print', order_id: $order->id, receipt_type: 'all');
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
        $products = $this->products;
        $productData = $products->mapWithKeys(function ($product) {
            return [$product->id => [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'price' => (float) $product->getPriceAt((int) $this->branchId),
                'image' => $product->image_url,
                'image_url' => $product->image_url,
                'max_available' => (int) ($product->max_available ?? 999),
                'option_availability' => $product->option_availability ?? [],
                'modifier_availability' => $product->modifier_availability ?? [],
                'recipes' => $product->recipes->map(fn ($recipe) => [
                    'ingredient_id' => $recipe->ingredient_id,
                    'quantity' => (float) $recipe->quantity,
                    'product_option_id' => $recipe->product_option_id,
                    'modifier_id' => $recipe->modifier_id,
                ])->values()->all(),
                'option_groups' => $product->optionGroups->map(fn ($group) => [
                    'id' => $group->id,
                    'name' => $group->name,
                    'is_required' => (bool) $group->is_required,
                    'price_mode' => $group->price_mode,
                    'max_select' => $group->max_select ? (int)$group->max_select : ($group->price_mode === 'fixed' ? 1 : null),
                    'no_recipe_required' => (bool) $group->no_recipe_required,
                    'options' => $group->options->map(fn ($option) => [
                        'id' => $option->id,
                        'group_id' => $group->id,
                        'name' => $option->name,
                        'price' => (float) $option->price,
                        'is_default' => (bool) $option->is_default,
                    ])->values()->all(),
                ])->values()->all(),
                'modifiers' => $product->modifiers->map(fn ($modifier) => [
                    'id' => $modifier->id,
                    'name' => $modifier->name,
                    'price' => (float) $modifier->price,
                ])->values()->all(),
            ]];
        })->all();

        if (!$this->hasOperatingBranch()) {
            return view('components.operating-branch-required', [
                'title' => 'Operating Branch Required for POS',
                'message' => 'The POS Terminal operates within a specific physical branch to access live inventory, categories, and branch menu pricing. Please select your operating branch to launch the terminal.',
                'actionText' => 'Configure in Settings',
                'actionRoute' => route('settings.index'),
                'icon' => 'branch',
            ])->layout('layouts.app');
        }

        return view('livewire.pos-terminal', [
            'products'            => $products,
            'productData'         => $productData,
            'categories'          => $this->categories,
            'branchId'            => $this->branchId,
            'subtotal'            => $this->subtotal,
            'total'               => $this->total,
            'discountAmount'      => $this->discountAmount,
            'serviceChargeAmount' => $this->serviceChargeAmount,
            'taxAmount'           => $this->taxAmount,
            'change'              => $this->change,
            'stockData'           => $this->posStocks,
        ])->layout('layouts.app', ['noPadding' => true]);
    }
}