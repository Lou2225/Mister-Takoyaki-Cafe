<div id="pos-terminal-root" class="flex flex-col h-[calc(100vh-58px)] sm:h-[calc(100vh-65px)] overflow-hidden bg-gray-100" 
    wire:key="pos-terminal-root"
    @cart-loaded.window="cart = $event.detail.cart || {}"
    @cart-reset.window="cart = {}; cartExpanded = false"
    @pos-category-sortable-init.window="setupCategorySortable()"
    x-data="{ 
        isMobile: window.matchMedia('(max-width: 767px)').matches,
        searchQuery: '',
        activeCategoryId: sessionStorage.getItem('pos_active_category') ? parseInt(sessionStorage.getItem('pos_active_category')) : null,
        selectCategory(id) {
            this.activeCategoryId = id;
        },
        get filteredProductsCount() {
            const query = this.searchQuery ? this.searchQuery.toLowerCase().trim() : '';
            return Object.values(this.productsData).filter(p => {
                const matchesCategory = this.activeCategoryId === null || p.category_id == this.activeCategoryId;
                const matchesSearch = !query || p.name.toLowerCase().includes(query);
                return matchesCategory && matchesSearch;
            }).length;
        },
        isSavingDraft: false,
        isSubmitting: false,
        cartExpanded: false,
        isEditMode: @entangle('isEditMode').live,
        cart: @js($cart),
        paymentMethod: @entangle('paymentMethod'),
        gcashVerified: @entangle('gcashVerified').live,
        amountTendered: @entangle('amountTendered'),
        serviceChargeRate: @entangle('serviceChargeRate').live,
        discountPercent: @entangle('discountPercent').live,
        seniorDiscountRate: @entangle('seniorDiscountRate').live,
        orderType: @entangle('orderType'),
        get subtotal() {
            return Object.values(this.cart).reduce((sum, item) => sum + (item.price * item.qty), 0);
        },
        get serviceCharge() {
            return this.orderType === 'Dine-in' ? (this.subtotal * this.serviceChargeRate) : 0;
        },
        get discountTotal() {
            return Object.values(this.cart).reduce((sum, item) => {
                const itemTotal = item.price * item.qty;
                let discount = 0;
                if (item.apply_regular_discount) discount += itemTotal * this.discountPercent;
                if (item.apply_senior_discount) discount += itemTotal * this.seniorDiscountRate;
                return sum + discount;
            }, 0);
        },
                get total() {
            return (this.subtotal - this.discountTotal) + this.serviceCharge;
        },
        get cartLocked() {
            return this.paymentMethod === 'GCash' && this.gcashVerified;
        },
                productsData: {},
            cartUsageCacheKey: '',
            cartUsageCache: {},
            stockData: @js($stockData),
activeProduct: null,
editingItem: null,
pendingDeleteDraftId: null,
hiddenDraftIds: [],
selectedOptions: {},
        selectedModifierIds: [],
        pendingDeleteKey: null,
        categorySortable: null,
        categorySortableTimeout: null,
        setupCategorySortable() {
    if (this.categorySortableTimeout) clearTimeout(this.categorySortableTimeout);
    if (!this.isEditMode) {
        if (this.categorySortable) {
            try { this.categorySortable.destroy(); } catch(e) {}
            this.categorySortable = null;
        }
        return;
    }
    this.categorySortableTimeout = setTimeout(() => {
        const el = document.getElementById('category-sortable-tabs');
        if (!el || this.categorySortable) return;
        this.categorySortable = Sortable.create(el, {
            filter: '#pos_tab_all',
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: () => {
                const ids = Array.from(el.querySelectorAll('.pos-category-tab'))
                    .map(tab => tab.dataset.id)
                    .filter(id => id);
                setTimeout(() => { $wire.reorderCategories(ids); }, 100);
            }
        });
    }, 50);
},
        primaryColor: @js($primaryColor),
        productSortable: null,
        productSortableTimeout: null,
        setupProductSortable() {
    if (this.productSortableTimeout) clearTimeout(this.productSortableTimeout);
    if (!this.isEditMode) {
        if (this.productSortable) {
            try { this.productSortable.destroy(); } catch(e) {}
            this.productSortable = null;
        }
        return;
    }
    this.productSortableTimeout = setTimeout(() => {
        const el = document.getElementById('product-sortable-grid');
        if (!el || this.productSortable) return;
        this.productSortable = Sortable.create(el, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'opacity-50',
            chosenClass: 'shadow-2xl',
            dragClass: 'opacity-0',
        });
    }, 50);
},
        openQuickOptions(pid) {
            const product = this.productsData[pid];
            if (!product) return;

            // If no options/modifiers, just add to cart directly
            const hasOpts = (product.option_groups && product.option_groups.length > 0) || (product.modifiers && product.modifiers.length > 0);
            if (!hasOpts) {
                this.optimisticAddToCart(pid);
                return;
            }

            // Prepare active product state
            this.activeProduct = product;
            this.selectedOptions = {};
            this.selectedModifierIds = [];

            const availability = product.option_availability || {};
            const isAvailable = (opt, group) => group.no_recipe_required || (availability[opt.id] ?? 0) > 0;

            // Initialize defaults — never pre-select an option that's out
            // of stock or has no ingredients mapped to it (unless its
            // group is flagged 'No Recipe Required').
            const productOptionGroups = product.option_groups || product.optionGroups || [];
            if (productOptionGroups) {
                productOptionGroups.forEach(group => {
                    const def = group.options.find(o => o.is_default && isAvailable(o, group));
                    if (def) {
                        this.selectedOptions[group.id] = group.price_mode === 'additive' ? [def.id] : def.id;
                    } else if (group.is_required) {
                        const firstAvailable = group.options.find(o => isAvailable(o, group));
                        this.selectedOptions[group.id] = firstAvailable
                            ? (group.price_mode === 'additive' ? [firstAvailable.id] : firstAvailable.id)
                            : (group.price_mode === 'additive' ? [] : null);
                    } else {
                        this.selectedOptions[group.id] = group.price_mode === 'additive' ? [] : null;
                    }
                });
            }
            
            // State is handled entirely by Alpine now for instant reactivity
            this.$dispatch('open-options-modal', product);
            this.$dispatch('open-modal', 'pos-options');
        },
        getCartQty(pid) {
            return Object.values(this.cart)
                .filter(item => item.id == pid)
                .reduce((sum, item) => sum + item.qty, 0);
        },
        getProductRecipes(product, optionIds = [], modifierIds = []) {
            const recipes = product?.recipes || [];
            return recipes.filter(recipe => {
                if (!recipe.product_option_id && !recipe.modifier_id) return true;
                if (recipe.product_option_id) return optionIds.includes(Number(recipe.product_option_id));
                if (recipe.modifier_id) return modifierIds.includes(Number(recipe.modifier_id));
                return false;
            });
        },
        getCartIngredientUsage(excludeProductId = null) {
            const cartSignature = Object.entries(this.cart || {})
                .map(([key, item]) => `${key}:${item.qty}`)
                .join('|');

            if (this.cartUsageCacheKey !== cartSignature) {
                this.cartUsageCacheKey = cartSignature;
                this.cartUsageCache = {};
            }

            const cacheKey = excludeProductId === null ? 'all' : String(excludeProductId);
            if (Object.prototype.hasOwnProperty.call(this.cartUsageCache, cacheKey)) {
                return this.cartUsageCache[cacheKey];
            }

            const usage = {};

            Object.values(this.cart || {}).forEach(item => {
            if (excludeProductId !== null && Number(item.id) === Number(excludeProductId)) return;

                const product = this.productsData[item.id];
                if (!product) return;

                const optionIds = (item.options || []).map(option => Number(option.id));
                const modifierIds = (item.modifiers || []).map(modifier => Number(modifier.id));
                this.getProductRecipes(product, optionIds, modifierIds).forEach(recipe => {
                    const ingredientId = recipe.ingredient_id;
                    usage[ingredientId] = (usage[ingredientId] || 0) + (Number(recipe.quantity) * Number(item.qty || 0));
                });
            });

            this.cartUsageCache[cacheKey] = usage;
            return usage;
        },
        remainingStock(pid) {
            const product = this.productsData[pid];
            if (!product) return 0;

            const recipes = this.getProductRecipes(product);
            const stock = this.stockData || {};
            const usage = this.getCartIngredientUsage(pid);

            if (recipes.length === 0) {
                const maxAvailable = Number(product.max_available ?? product.available_quantity ?? 0);
                return Math.max(0, maxAvailable - this.getCartQty(pid));
            }

            const maxAvailable = recipes.reduce((maximum, recipe) => {
                const recipeQuantity = Number(recipe.quantity);
                if (recipeQuantity <= 0) return maximum;

                const availableIngredient = Math.max(0, Number(stock[recipe.ingredient_id] || 0) - Number(usage[recipe.ingredient_id] || 0));
                return Math.min(maximum, Math.floor(availableIngredient / recipeQuantity));
            }, Number.MAX_SAFE_INTEGER);

            return Math.max(0, maxAvailable - this.getCartQty(pid));
        },
        maxAvailableForProduct(pid) {
            return this.getCartQty(pid) + this.remainingStock(pid);
        },
                remainingOptionStock(product, optionId) {
            if (!product) return 0;
            const groups = product?.option_groups || product?.optionGroups || [];
            const owningGroup = groups.find(g => (g.options || []).some(o => o.id === optionId));
            if (owningGroup && owningGroup.no_recipe_required) {
                return Infinity;
            }

            const recipes = (product?.recipes || []).filter(recipe => Number(recipe.product_option_id) === Number(optionId));
            if (recipes.length === 0) return 0;

            const stock = this.stockData || {};
            const usage = this.getCartIngredientUsage();
            return Math.max(0, recipes.reduce((maximum, recipe) => {
                const recipeQuantity = Number(recipe.quantity);
                if (recipeQuantity <= 0) return maximum;

                const availableIngredient = Math.max(0, Number(stock[recipe.ingredient_id] || 0) - Number(usage[recipe.ingredient_id] || 0));
                return Math.min(maximum, Math.floor(availableIngredient / recipeQuantity));
            }, Number.MAX_SAFE_INTEGER));
        },
        remainingModifierStock(product, modifierId) {
            if (!product) return 0;
            const recipes = (product?.recipes || []).filter(recipe => Number(recipe.modifier_id) === Number(modifierId));
            if (recipes.length === 0) return 0;

            const stock = this.stockData || {};
            const usage = this.getCartIngredientUsage();
            return Math.max(0, recipes.reduce((maximum, recipe) => {
                const recipeQuantity = Number(recipe.quantity);
                if (recipeQuantity <= 0) return maximum;

                const availableIngredient = Math.max(0, Number(stock[recipe.ingredient_id] || 0) - Number(usage[recipe.ingredient_id] || 0));
                return Math.min(maximum, Math.floor(availableIngredient / recipeQuantity));
            }, Number.MAX_SAFE_INTEGER));
        },
        toggleOpt(groupId, optId, isAdditive, isRequired) {
            if (isAdditive) {
                if (!this.selectedOptions[groupId]) this.selectedOptions[groupId] = [];
                const idx = this.selectedOptions[groupId].indexOf(optId);
                if (idx > -1) this.selectedOptions[groupId].splice(idx, 1);
                else this.selectedOptions[groupId].push(optId);
            } else {
                this.selectedOptions[groupId] = (this.selectedOptions[groupId] === optId && !isRequired) ? null : optId;
            }
        },
        toggleMod(modId) {
            const idx = this.selectedModifierIds.indexOf(modId);
            if (idx > -1) this.selectedModifierIds.splice(idx, 1);
            else this.selectedModifierIds.push(modId);
        },
        optimisticAddToCart(pid, selectedOptionsObj = {}, selectedModifierIds = []) {
            const product = this.productsData[pid];
            if (!product) return;

            const maxAvailable = Number(product.max_available ?? product.available_quantity ?? 999);
            const currentCartQtyForProduct = Object.values(this.cart)
                .filter(item => item.id == pid)
                .reduce((sum, item) => sum + Number(item.qty || 0), 0);

            if (this.remainingStock(pid) <= 0 || maxAvailable <= 0) {
                return;
            }

            if ((currentCartQtyForProduct + 1) > maxAvailable) {
                return;
            }

            // Flatten options IDs
            let optionIds = [];
            Object.values(selectedOptionsObj).forEach(val => {
                if (Array.isArray(val)) optionIds = optionIds.concat(val);
                else if (val) optionIds.push(val);
            });

            // 1. Generate Key
            const optKey = optionIds.length > 0 ? '-' + optionIds.sort().join(',') : '';
            const modKey = selectedModifierIds.length > 0 ? '-' + selectedModifierIds.sort().join(',') : '';
            const key = pid + optKey + modKey;

            // 2. Calculate Price (Replicate PHP logic)
            // Need to find the actual option/modifier objects from product data
            let allOptions = [];
            const productOptionGroups = product.option_groups || product.optionGroups || [];
            if (productOptionGroups) {
                productOptionGroups.forEach(g => {
                    allOptions = allOptions.concat(g.options);
                });
            }
            
            const selectedOptions = allOptions.filter(o => optionIds.includes(o.id));
            const selectedModifiers = (product.modifiers || []).filter(m => selectedModifierIds.includes(m.id));

            // Logic: Fixed price groups become base, else product base price. Add additive options and modifiers.
            const hasFixed = selectedOptions.some(o => {
                const group = productOptionGroups.find(g => g.id === o.group_id);
                return group && group.price_mode === 'fixed';
            });

            let basePrice = parseFloat(product.price || 0); // fallback
            if (hasFixed) {
                basePrice = selectedOptions.filter(o => {
                    const group = productOptionGroups.find(g => g.id === o.group_id);
                    return group && group.price_mode === 'fixed';
                }).reduce((sum, o) => sum + parseFloat(o.price || 0), 0);
            }

            const additivePrice = selectedOptions.filter(o => {
                const group = productOptionGroups.find(g => g.id === o.group_id);
                return group && group.price_mode === 'additive';
            }).reduce((sum, o) => sum + parseFloat(o.price || 0), 0);

            const modifiersPrice = selectedModifiers.reduce((sum, m) => sum + parseFloat(m.price || 0), 0);
            
            const finalPrice = basePrice + additivePrice + modifiersPrice;

            // 3. Update Alpine Cart
            if (this.cart[key]) {
                this.cart[key].qty++;
            } else {
                this.cart[key] = {
                    id: pid,
                    name: product.name,
                    price: finalPrice,
                    qty: 1,
                    image: product.image,
                    options: selectedOptions.map(o => ({ id: o.id, name: o.name, price: parseFloat(o.price) })),
                    modifiers: selectedModifiers.map(m => ({ id: m.id, name: m.name, price: parseFloat(m.price) })),
                    instructions: '',
                    apply_regular_discount: false,
                    apply_senior_discount: false
                };
            }

            // Force reactivity for the entangled object
            this.cart = { ...this.cart };

            // 5. UI Effects
            this.cartExpanded = true;
        },
        isSelected(groupId, optId) {
            if (!this.selectedOptions[groupId]) return false;
            if (Array.isArray(this.selectedOptions[groupId])) {
                return this.selectedOptions[groupId].includes(optId);
            }
            return this.selectedOptions[groupId] === optId;
        },
        canAddToOrder(product) {
            if (!product) return false;

            const maxAvailable = this.remainingStock(product.id);
            if (maxAvailable <= 0) return false;

            const groups = product.option_groups || product.optionGroups || [];
            // Every required group must have a real, in-stock selection.
            return groups.every(group => {
                if (!group.is_required) return true;
                const sel = this.selectedOptions[group.id];
                const hasSelection = Array.isArray(sel) ? sel.length > 0 : !!sel;
                return hasSelection;
            });
        },
        init() {
            const syncViewport = () => {
                this.isMobile = window.innerWidth < 768;
            };
            syncViewport();
            window.addEventListener('resize', syncViewport);
            window.visualViewport?.addEventListener('resize', syncViewport);

            this.$el.addEventListener('alpine:destroy', () => {
                window.removeEventListener('resize', syncViewport);
                window.visualViewport?.removeEventListener('resize', syncViewport);
            });

            this.$watch('cart', value => { 
                if (!value || Object.keys(value).length === 0) {
                    this.cartExpanded = false; 
                }
            });

            this.$watch('isEditMode', () => {
                this.setupProductSortable();
                this.setupCategorySortable();
            });

            const applySearchFilter = () => {
    const query = this.searchQuery ? this.searchQuery.toLowerCase().trim() : '';
    requestAnimationFrame(() => {
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.dataset.searchName || '';
            const catId = card.dataset.categoryId;

            const matchesCategory = this.activeCategoryId === null || catId == this.activeCategoryId;
            const matchesSearch = !query || name.includes(query);
            const visible = matchesCategory && matchesSearch;

            card.style.display = visible ? '' : 'none';
        });
    });
};

            this.$watch('searchQuery', applySearchFilter);
            this.$watch('activeCategoryId', (val) => {
    if (val === null) {
        sessionStorage.removeItem('pos_active_category');
    } else {
        sessionStorage.setItem('pos_active_category', val);
    }
    applySearchFilter();
});

            const syncProducts = () => {
    const el = document.getElementById('hidden-products-data');
    if (el) {
        try {
            this.productsData = JSON.parse(el.textContent || '{}');
        } catch (e) {
            console.error('Failed to parse products data', e);
        }
    }
    // Run twice: once immediately, once after DOM fully paints
    applySearchFilter();
    requestAnimationFrame(() => applySearchFilter());
};

            syncProducts();

            // wire:navigate can restore this page from Livewire's own
            // in-memory navigate cache instead of hitting the server.
            // Keep one global listener, while replacing the sync callback
            // whenever the POS component is initialized again.
            window.__posSyncProducts = syncProducts;
            window.__posWire = this.$wire;
            const refreshCurrentPos = () => {
                if (!document.getElementById('pos-terminal-root')) return;
                const refreshResult = window.__posWire?.refreshPosData?.();
                Promise.resolve(refreshResult).then(() => window.__posSyncProducts?.());
            };

            if (!window.__posNavigatedListenerAttached) {
                window.__posNavigatedListenerAttached = true;
                document.addEventListener('livewire:navigated', refreshCurrentPos);

                // Browser back/forward can restore this page from bfcache
                // without firing livewire:navigated.
                window.addEventListener('pageshow', (event) => {
                    if (event.persisted) refreshCurrentPos();
                });
            }
            
            const setupHook = () => {
                window.Livewire?.hook?.('commit', ({ succeed }) => {
                    succeed(() => {
                        setTimeout(() => window.__posSyncProducts?.(), 50);
                        requestAnimationFrame(() => window.__posSyncProducts?.());
                    });
                });
            };

            if (!window.__posCommitHookAttached) {
                window.__posCommitHookAttached = true;
                if (window.Livewire) setupHook();
                else document.addEventListener('livewire:initialized', setupHook, { once: true });
            }
        },
        destroy() {
            if (window.__posWire === this.$wire) {
                window.__posWire = null;
                window.__posSyncProducts = null;
            }
        }
    }"
    @cart-expanded.window="cartExpanded = true"
    @cart-collapsed.window="cartExpanded = false"
    @cart-toggle.window="cartExpanded = !cartExpanded"
    @close-modal.window="
        if ($event.detail === 'pos-payment' && cartLocked) {
            $nextTick(() => $dispatch('open-modal', 'pos-payment'));
            $dispatch('notify', { type: 'warning', message: 'This payment is already verified — you must Place the Order to complete it.' });
        }
    "
>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
    <script id="hidden-products-data" type="application/json">@json($productData)</script>

        <script>
        window.thermalReceiptPopupFeatures = window.thermalReceiptPopupFeatures || 'width=450,height=700,menubar=no,toolbar=no,location=no,status=no';

        // The order is saved through an asynchronous Livewire request. Browsers
        // can block a popup opened after that request has completed, so reserve
        // an empty receipt window during the cashier's click and navigate it
        // only after the order ID is returned.
        window.prepareThermalReceiptWindow = window.prepareThermalReceiptWindow || (() => {
            const existing = window.pendingThermalReceiptWindow;
            if (existing && !existing.closed) {
                existing.focus();
                return existing;
            }

            const receiptWindow = window.open('about:blank', 'thermal_receipt_pending', window.thermalReceiptPopupFeatures);
            if (!receiptWindow) return null;

            receiptWindow.document.title = 'Preparing receipt…';
            receiptWindow.document.body.innerHTML = '<p style="font-family: sans-serif; padding: 24px;">Preparing receipt…</p>';
            window.pendingThermalReceiptWindow = receiptWindow;

            window.setTimeout(() => {
                if (window.pendingThermalReceiptWindow === receiptWindow && !receiptWindow.closed) {
                    receiptWindow.close();
                    window.pendingThermalReceiptWindow = null;
                }
            }, 30000);

                        return receiptWindow;
        });

        // wire:navigate re-executes this inline <script> block on every visit
        // to this page (POS or Order Management, once added there too), and
        // addEventListener has no built-in dedupe — without this guard,
        // repeated navigation would stack up duplicate listeners and cause
        // one placed/accepted order to trigger multiple print jobs.
        if (!window.__thermalPrintListenerAttached) {
        window.__thermalPrintListenerAttached = true;
        window.addEventListener('send-thermal-print', async (e) => {
            const { order_id, receipt_type = 'all' } = e.detail || {};
            if (!order_id) return;

            console.log('📋 POS Terminal: Print event triggered', { order_id, receipt_type });

            try {
                // 1. If Web Bluetooth thermal printer is connected, print directly over Bluetooth
                if (window.thermalBluetoothPrinter && window.thermalBluetoothPrinter.characteristic) {
                    console.log('📋 POS Terminal: Bluetooth printer connected, fetching receipt data...');
                    const res = await fetch(`/pos/orders/${order_id}/receipt-data`);
                    if (!res.ok) throw new Error('Could not load receipt data.');
                    const data = await res.json();
                    await window.thermalBluetoothPrinter.printReceipt(data.order, data.settings, data.receipts || []);
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { type: 'success', message: 'Receipt printed via Bluetooth.' }
                    }));
                    return;
                }

                // 2. If Bluetooth is not connected, open and print the thermal-receipt.blade template
                const receiptUrl = `/receipts/${order_id}/thermal?autoprint=1`;
                const pendingWindow = window.pendingThermalReceiptWindow;
                const printWindow = pendingWindow && !pendingWindow.closed
                    ? pendingWindow
                    : window.open(receiptUrl, 'thermal_receipt_' + order_id, window.thermalReceiptPopupFeatures);

                window.pendingThermalReceiptWindow = null;

                if (pendingWindow && printWindow) {
                    printWindow.name = 'thermal_receipt_' + order_id;
                    printWindow.location.replace(receiptUrl);
                    printWindow.focus();
                }

                if (!printWindow || printWindow.closed || typeof printWindow.closed === 'undefined') {
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { type: 'info', message: 'Order placed! Popup was blocked — please allow popups to auto-open receipt.' }
                    }));
                }
            } catch (err) {
                console.error('Receipt print failed:', err);
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { type: 'error', message: 'Print failed: ' + err.message }
                }));
            }
        });
        }
    </script>

    {{-- ══════════════════════════════════════════════
         FULL-WIDTH CATEGORY TAB CARD
    ══════════════════════════════════════════════ --}}
    <div class="px-3 pt-3 flex-shrink-0">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-3 py-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-2">

            {{-- Category Tabs — horizontally scrollable, no wrap --}}
            <div 
                id="category-sortable-tabs"
                class="flex items-center gap-1.5 overflow-x-auto no-scrollbar flex-1 min-w-0"
                x-init="$dispatch('pos-category-sortable-init')"
            >

                {{-- All Tab --}}
                {{-- All Tab --}}
                <button type="button" 
                    @click="selectCategory(null)"
                    id="pos_tab_all" 
                    class="pos-category-tab inline-flex items-center justify-center px-4 py-2 border rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 gap-1.5 whitespace-nowrap shrink-0"
                    :class="activeCategoryId === null ? 'bg-{{ $primaryColor }}-600 text-white border-transparent hover:bg-{{ $primaryColor }}-700 focus:bg-{{ $primaryColor }}-700 active:bg-{{ $primaryColor }}-800 focus:ring-{{ $primaryColor }}-500' : 'bg-white text-gray-700 border-gray-300 shadow-sm hover:bg-gray-50 focus:ring-{{ $primaryColor }}-500 disabled:opacity-25'">
                    All
                    <span class="text-[10px] font-bold" :class="activeCategoryId === null ? 'text-white' : 'text-gray-400'">{{ $products->count() }}</span>
                </button>

                @foreach($categories as $cat)
                    <button type="button" 
                        @click="selectCategory({{ $cat->id }})"
                        id="pos_tab_cat_{{ $cat->id }}" 
                        class="pos-category-tab inline-flex items-center justify-center px-4 py-2 border rounded-md font-semibold text-xs uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150 gap-1.5 whitespace-nowrap shrink-0 {{ $isEditMode ? 'border-dashed border-gray-300 cursor-move' : '' }}" 
                        data-id="{{ $cat->id }}"
                        :class="activeCategoryId === {{ $cat->id }} ? 'bg-{{ $primaryColor }}-600 text-white border-transparent hover:bg-{{ $primaryColor }}-700 focus:bg-{{ $primaryColor }}-700 active:bg-{{ $primaryColor }}-800 focus:ring-{{ $primaryColor }}-500' : 'bg-white text-gray-700 border-gray-300 shadow-sm hover:bg-gray-50 focus:ring-{{ $primaryColor }}-500 disabled:opacity-25'">
                        {{ $cat->name }}
                        <span class="text-[10px] font-bold" :class="activeCategoryId === {{ $cat->id }} ? 'text-white' : 'text-gray-400'">{{ $cat->products_count }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Search + Held Orders --}}
            <div class="flex items-center gap-2 shrink-0">
                <div class="relative" wire:ignore>
                    <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                    <label for="pos_search" class="sr-only">Search Menu</label>
                    <input id="pos_search" x-model="searchQuery" type="text" placeholder="Search Menu"
                        class="pl-8 pr-3 py-1.5 w-full sm:w-36 text-[12px] font-medium text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-0 focus:border-gray-300 placeholder-gray-400 transition-all sm:focus:w-44">
                </div>

                                <button type="button" @click="$dispatch('open-modal', 'pos-drafts-list')" title="Held Orders" class="relative p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-all border border-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    @if($this->drafts->count() > 0)
                        <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white shadow-sm ring-2 ring-white animate-bounce">{{ $this->drafts->count() }}</span>
                    @endif
                </button>

                {{-- Bluetooth Thermal Printer connect/status --}}
                                <div x-data="{
                        connected: !!(window.thermalBluetoothPrinter && window.thermalBluetoothPrinter.characteristic),
                        connecting: false,
                        supported: 'bluetooth' in navigator,
                        printerName: localStorage.getItem('thermal_printer_name') || ''
                    }"
                    x-init="window.addEventListener('thermal-bt-disconnected', () => { connected = false; })"
                    class="shrink-0">
                    <button type="button"
                        :disabled="!supported || connecting"
                        :title="!supported ? 'Bluetooth printing needs Chrome/Edge (not supported in this browser)' : (connected ? ('Connected: ' + printerName) : 'Connect Bluetooth Printer')"
                        @click="
                            connecting = true;
                            window.thermalBluetoothPrinter.connect()
                                .then(name => { connected = true; printerName = name; $dispatch('notify', { type: 'success', message: 'Connected to ' + name }); })
                                .catch(err => { $dispatch('notify', { type: 'error', message: err.message }); })
                                .finally(() => connecting = false);
                        "
                        class="relative p-2 rounded-lg transition-all border flex items-center justify-center disabled:opacity-40 disabled:cursor-not-allowed"
                        :class="connected ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-gray-50 text-gray-500 border-gray-200 hover:bg-gray-100'">
                        <svg x-show="!connecting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.5 6.5l11 11L12 23V1l5.5 5.5-11 11" /></svg>
                        <svg x-show="connecting" x-cloak class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-show="connected" class="absolute -top-1 -right-1 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-2 ring-white"></span>
                    </button>
                </div>

                @if(auth()->user()->role_id === 1 || auth()->user()->role_id === 2)
                {{-- Done button: visible in edit mode --}}
                <button type="button"
                    x-show="isEditMode"
                    x-cloak
                    @click="const el = document.getElementById('product-sortable-grid'); if(el) { const ids = Array.from(el.querySelectorAll('.product-card')).map(c => c.dataset.id); $wire.saveLayout(ids); isEditMode = false; } else { isEditMode = false; }"
                    title="Save Layout"
                    class="h-9 px-3 rounded-lg transition-all border flex items-center gap-2 bg-emerald-500 text-white border-emerald-600 shadow-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                    <span class="text-[11px] font-black uppercase tracking-wider">Done</span>
                </button>
                {{-- Sort button: visible when NOT in edit mode --}}
                <button type="button"
                    x-show="!isEditMode"
                    @click="isEditMode = true"
                    title="Edit Layout"
                    class="h-9 px-3 rounded-lg transition-all border flex items-center gap-2 bg-white text-slate-600 hover:bg-slate-50 border-slate-200 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <span class="text-[11px] font-black uppercase tracking-wider">Sort</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         CONTENT ROW — Product Grid + Order Summary
    ══════════════════════════════════════════════ --}}
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden gap-3 p-3 min-h-0">

        {{-- LEFT: Product Grid --}}
        <div class="flex-1 overflow-y-auto min-w-0 pb-20 md:pb-0">

            @if($products->isEmpty())
                <div class="flex flex-col items-center justify-center h-full text-gray-400 py-16">
                    <svg class="w-12 h-12 mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-[14px] font-semibold">No products found</p>
                    <p class="text-[12px] mt-1">Try a different category or search term</p>
                </div>
            @else
                @php
                    $gradients = ['from-amber-400 to-orange-500','from-emerald-400 to-teal-500','from-rose-400 to-pink-500','from-indigo-400 to-blue-500','from-purple-400 to-violet-500'];
                @endphp
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3"
                    id="product-sortable-grid"
                    wire:key="pos-grid-static"
                    x-init="setupProductSortable()">
                    @foreach($products as $product)
                        @php
                            $pid      = $product->id;
                            $maxAvailable = $branchId ? (int)($product->max_available ?? 0) : 999;
                            $availability = $maxAvailable <= 0 ? 'unavailable' : ($maxAvailable <= 10 ? 'low_stock' : 'available');
                            $isAvailable  = in_array($availability, ['available', 'low_stock']);
                            $hasOptions   = count($product->optionGroups) > 0 || count($product->modifiers) > 0;
                            $clickAction  = "openQuickOptions($pid)";
                        @endphp

                        <div wire:key="pos-product-{{ $pid }}"
                            data-id="{{ $pid }}"
                            data-category-id="{{ $product->category_id }}"
                            data-search-name="{{ strtolower(addslashes($product->name)) }}"
                            class="product-card group relative bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col transition-all duration-300 hover:shadow-md {{ !$isAvailable ? 'opacity-75' : '' }}"
                            :class="isEditMode ? 'opacity-90 grayscale-[0.2] scale-[0.98]' : ''">

                            {{-- Product Image --}}
                            <div class="relative h-[120px] overflow-hidden bg-gray-100">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                    class="w-full h-full object-cover {{ !$isAvailable ? 'grayscale' : '' }}">

                                <div class="drag-handle absolute top-2 left-2 z-20 bg-white/90 backdrop-blur-md p-2 rounded-lg shadow-lg border border-gray-200 cursor-move hover:scale-110 transition-transform flex items-center justify-center"
                                    x-show="isEditMode" x-cloak>
                                    <svg class="w-5 h-5 text-{{ $primaryColor }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                </div>

                                {{-- Availability Badge --}}
                                <div class="absolute top-2 right-2">
                                    <span x-show="remainingStock({{ $pid }}) <= 0" x-cloak class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/90 text-red-600 shadow-sm border border-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>Out of Stock
                                    </span>
                                    <span x-show="remainingStock({{ $pid }}) > 0 && remainingStock({{ $pid }}) <= 10" x-cloak class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/90 text-amber-600 shadow-sm border border-amber-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>Low (<span x-text="remainingStock({{ $pid }})"></span> left)
                                    </span>
                                    <span x-show="remainingStock({{ $pid }}) > 10" x-cloak class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/90 text-emerald-600 shadow-sm border border-emerald-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span><span x-text="remainingStock({{ $pid }})"></span> left
                                    </span>
                                </div>
                            </div>

                            {{-- Product Info --}}
                            <div class="p-3 flex-1 flex flex-col">
                                <div class="flex items-start justify-between mb-2 gap-1">
                                    <span class="text-[13px] font-semibold text-gray-800 leading-tight line-clamp-1">{{ $product->name }}</span>
                                    <span class="text-[13px] font-bold text-gray-900 shrink-0 font-mono">{{ $currencySymbol }}{{ number_format($product->getPriceAt((int)$branchId), 2) }}</span>
                                </div>

                                {{-- Cart Button --}}
                                <div class="mt-auto">
                                    {{-- Out of Stock --}}
                                    @if($maxAvailable <= 0)
                                        <x-danger-button type="button" disabled class="w-full justify-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Out of Stock
                                        </x-danger-button>
                                    @else
                                        {{-- Max Qty Reached --}}
                                        <x-danger-button type="button" disabled class="w-full justify-center" 
                                            x-show="remainingStock({{ $pid }}) <= 0">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Max Qty Reached
                                        </x-danger-button>

                                        {{-- Add More --}}
                                        <x-secondary-button type="button" 
                                            @click="!isEditMode && {{ $clickAction }}" 
                                            x-show="getCartQty({{ $pid }}) > 0 && remainingStock({{ $pid }}) > 0"
                                            :class="isEditMode ? 'opacity-50 cursor-not-allowed' : ''"
                                            id="pos_addmore_{{ $pid }}" class="w-full justify-center">
                                            Add More (<span x-text="getCartQty({{ $pid }})"></span>/<span x-text="maxAvailableForProduct({{ $pid }})"></span>)
                                        </x-secondary-button>

                                        {{-- Add to Cart --}}
                                        <x-primary-button type="button" 
                                            @click="!isEditMode && {{ $clickAction }}" 
                                            x-show="getCartQty({{ $pid }}) == 0"
                                            :class="isEditMode ? 'opacity-50 cursor-not-allowed' : ''"
                                            id="pos_addcart_{{ $pid }}" class="w-full justify-center">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Add to Cart
                                        </x-primary-button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Alpine No Results Placeholder --}}
                    <div x-show="filteredProductsCount === 0" x-cloak class="col-span-full py-16 flex flex-col items-center justify-center text-gray-400 bg-white/50 border border-dashed border-gray-200 rounded-2xl">
                        <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <p class="text-[14px] font-bold text-gray-500">No products match your search</p>
                        <p class="text-[12px] mt-1 text-gray-400">Try typing a different name or checking your category</p>
                    </div>
                </div>
            @endif
        </div>{{-- end LEFT --}}

        {{-- Mobile Cart Backdrop --}}
        <div x-show="isMobile && cartExpanded" 
             x-transition.opacity.duration.300ms
             @click="cartExpanded = false"
             class="fixed inset-0 bg-gray-900/60 z-30 backdrop-blur-sm md:hidden"
             x-cloak>
        </div>

        {{-- RIGHT: Order Summary Card --}}
        <div 
            :class="[
                isMobile ? 'fixed inset-x-0 bottom-0 z-40 transition-transform duration-500 ease-in-out' : 'w-full md:w-[300px] xl:w-[320px] flex-shrink-0 flex flex-col min-h-0',
                isMobile ? (cartExpanded ? 'translate-y-0' : 'translate-y-[calc(100%-65px)]') : ''
            ]"
            class="flex flex-col h-[85vh] md:h-auto"
        >
            <div class="flex flex-col bg-white rounded-t-3xl md:rounded-2xl border border-gray-200 shadow-2xl md:shadow-sm overflow-hidden flex-1 min-h-0">

                {{-- Pull Handle (Mobile Only) --}}
                <div class="md:hidden flex justify-center pt-2 pb-1" @click="cartExpanded = !cartExpanded">
                    <div class="w-10 h-1 rounded-full bg-gray-200"></div>
                </div>

                {{-- Card Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 flex-shrink-0 bg-gray-50/20 cursor-pointer md:cursor-default" @click="if(isMobile) cartExpanded = !cartExpanded">
                    <div class="flex items-center gap-2">
                        <h2 class="text-[15px] font-black text-gray-900 tracking-tight">Order Summary</h2>
                        <template x-if="Object.keys(cart).length > 0">
                            <span class="md:hidden flex h-5 w-5 items-center justify-center rounded-full bg-indigo-600 text-[10px] font-black text-white" x-text="Object.values(cart).reduce((sum, item) => sum + item.qty, 0)"></span>
                        </template>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-black text-gray-900 font-mono tracking-tighter uppercase">{{ $referenceNo ? '#' . $referenceNo : 'New Order' }}</span>
<button type="button" title="Hold Order" class="p-1.5 rounded-lg text-amber-500 hover:bg-amber-50 transition-all disabled:opacity-40 disabled:cursor-not-allowed" :disabled="Object.keys(cart).length === 0 || cartLocked" @click.stop="!cartLocked && (isSavingDraft = true, $wire.cart = cart, $wire.saveDraft().finally(() => { isSavingDraft = false; cartExpanded = false }))">                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                        </button>
                        <div class="flex items-center md:hidden ml-2">
                             <span class="text-[14px] font-black text-indigo-600 font-mono" x-show="!cartExpanded">{{ $currencySymbol }}<span x-text="total.toFixed(2)"></span></span>
                             <svg class="w-4 h-4 ml-1 text-gray-400 transition-transform duration-300" :class="cartExpanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                        </div>
                    </div>
                </div>

                                 {{-- Cart Items --}}
                <div class="flex-1 overflow-y-auto px-3 py-1 divide-y divide-gray-50 min-h-0 relative">
                    {{-- Saving Overlay --}}
                    <div x-show="isSavingDraft" class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-10 backdrop-blur-sm">
                        <svg class="animate-spin w-8 h-8 text-amber-500 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-[12px] font-bold text-amber-600">Holding Order...</span>
                    </div>

                    <div x-show="Object.keys(cart).length > 0 && !isSavingDraft">
                        <template x-for="(item, key) in cart" :key="key">
                            <div class="flex items-start gap-2.5 py-3">
                                {{-- Thumbnail --}}
                                <div class="w-11 h-11 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-100">
                                    <img :src="item.image ? (item.image.startsWith('http') ? item.image : '{{ asset('storage') }}/' + item.image.replace(/^\/?storage\//, '')) : '{{ asset('images/placeholder-product.png') }}'" class="w-full h-full object-cover" :alt="item.name">
                                </div>

                                {{-- Details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-1">
                                        <div class="min-w-0">
                                            <p class="text-[12px] font-bold text-gray-800 leading-tight" x-text="item.name"></p>
                                            
                                            <template x-if="item.options && item.options.length > 0">
                                                <p class="text-[10px] font-bold text-indigo-500 mt-0.5 uppercase tracking-tighter" x-text="item.options.map(o => o.name).join(', ')"></p>
                                            </template>
                                            
                                            <template x-for="mod in (item.modifiers || [])">
                                                <p class="text-[9px] text-gray-400 italic font-medium" x-text="'+ ' + mod.name"></p>
                                            </template>
                                            
                                            <template x-if="item.instructions">
                                                <p class="text-[10px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded border border-amber-100 mt-1 italic leading-tight" x-text="'&quot;' + item.instructions + '&quot;'"></p>
                                            </template>
                                        </div>
                                        <div class="flex items-center gap-0.5 shrink-0">
    <button type="button" @click="!cartLocked && (editingItem = item, $dispatch('open-modal', 'edit-cart-item'), $wire.openEditItem(key, item))" class="relative p-1.5 text-gray-400 hover:text-amber-500 transition-all rounded-lg hover:bg-amber-50 disabled:opacity-40" :disabled="cartLocked">        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
        <template x-if="item.instructions">
            <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-amber-500 rounded-full border border-white"></span>
        </template>
    </button>
    <button @click="!cartLocked && (pendingDeleteKey = key, $dispatch('open-modal', 'confirm-delete-item'))" class="p-1.5 text-gray-300 hover:text-red-500 transition-all rounded-lg hover:bg-red-50 disabled:opacity-40" :disabled="cartLocked">        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </button>
</div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-2.5">
                                        <div class="flex items-center gap-1">
                                            <button @click="!cartLocked && (item.qty > 1 ? (item.qty--, cart = {...cart}) : (delete cart[key], cart = {...cart}))"
                                                class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all active:scale-95 disabled:opacity-40" :disabled="cartLocked">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                            </button>
                                            <span class="px-1 text-[12px] font-black text-gray-900 min-w-[20px] text-center font-mono" x-text="item.qty"></span>
                                            <button @click="
                                                if (cartLocked) { $dispatch('notify', { type: 'warning', message: 'Order is locked — payment already verified.' }); return; }
                                                const productQty = getCartQty(item.id);
                                                const maxQty = maxAvailableForProduct(item.id);
                                                if (remainingStock(item.id) > 0 && productQty < maxQty) { item.qty++; cart = {...cart} }
                                                else { $dispatch('notify', { type: 'warning', message: 'Maximum available stock reached' }) }"
                                                class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all active:scale-95 disabled:opacity-40" :disabled="cartLocked">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                            </button>
                                        </div>
                                        <div class="flex items-center">
                                            <span class="text-[13px] font-black text-gray-900 font-mono tracking-tight" x-text="'{{ $currencySymbol }}' + (item.qty * item.price).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    
                    <div x-show="Object.keys(cart).length === 0" class="flex flex-col items-center justify-center py-10 text-gray-300">
                        <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <p class="text-[13px] font-medium text-gray-400">Cart is empty</p>
                        <p class="text-[11px] text-gray-300 mt-0.5">Add items from the menu</p>
                    </div>
                </div>

                {{-- Totals + Order Meta --}}
                <div x-show="Object.keys(cart).length > 0" class="border-t border-gray-100 px-4 pt-3 pb-4 space-y-2 flex-shrink-0">

                    <div class="flex items-center justify-between">
                        <span class="text-[12px] text-gray-500">Subtotal</span>
                        <span class="text-[12px] font-bold text-gray-900 font-mono">{{ $currencySymbol }}<span x-text="subtotal.toFixed(2)">{{ number_format($subtotal, 2) }}</span></span>
                    </div>

                    @if($discountAmount > 0)
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[12px] text-emerald-600 font-medium">Discount Applied</span>
                            <span class="text-[12px] font-bold text-emerald-600 font-mono">-{{ $currencySymbol }}{{ number_format($discountAmount, 2) }}</span>
                        </div>
                    @endif


                    <template x-if="serviceCharge > 0">
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[12px] text-gray-500" x-text="'Service Charge (' + Math.round(serviceChargeRate * 100) + '%)'">Service Charge ({{ round($serviceChargeRate * 100) }}%)</span>
                            <span class="text-[12px] font-bold text-gray-900 font-mono">{{ $currencySymbol }}<span x-text="serviceCharge.toFixed(2)">{{ number_format($serviceChargeAmount, 2) }}</span></span>
                        </div>
                    </template>

                    <div class="h-px bg-gray-50 my-1"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-[13px] font-bold text-gray-900">Total Amount</span>
                        <span class="text-[14px] font-black text-gray-900 font-mono">{{ $currencySymbol }}<span x-text="total.toFixed(2)">{{ number_format($total, 2) }}</span></span>
                    </div>

                    {{-- Order Meta: Type + Table --}}
                    <div class="pt-2 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] text-gray-500">Order Method</span>
                            <x-dropdown align="right" width="48">
                                <x-slot name="trigger">
                                    <button id="pos_order_type_trigger" type="button"
    class="inline-flex items-center gap-1 text-[12px] font-semibold text-gray-800 focus:outline-none hover:text-gray-900 transition-colors">
    <span x-text="orderType">{{ $orderType }}</span>
    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
    </svg>
</button>
                                </x-slot>
                                <x-slot name="content">
                                    @foreach($orderTypes as $type)
                                    <button type="button"
                                        @click.stop="dropdownOpen = false; orderType = @js($type)"
                                        class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                        {{ $type }}
                                    </button>
                                    @endforeach
                                </x-slot>
                            </x-dropdown>
                        </div>

                        {{-- Table input — dynamic, not hardcoded --}}
                        <div class="flex items-center justify-between">
                            <span class="text-[12px] text-gray-500">Table / Ref</span>
                            <input type="text" wire:model.live.debounce.400ms="tableNumber" placeholder="Table #"
                                inputFilter="name_basic"
                                class="text-[12px] font-bold text-gray-800 bg-transparent border-0 p-0 focus:ring-0 text-right w-24 placeholder-gray-300 {{ $errors->has('tableNumber') ? 'text-red-500 placeholder-red-300' : '' }}">
                        </div>

                                          {{-- Confirm Payment --}}
                    <x-primary-button type="button"
                        @click="
                            if (Object.keys(cart).length === 0) return;
                            amountTendered = total;
                            $wire.cart = cart;
                            $wire.paymentReference = '';
                            $dispatch('open-modal', 'pos-payment');
                            $wire.openPaymentModal();
                        "
                        x-bind:disabled="Object.keys(cart).length === 0" id="pos_confirm_payment_btn" class="w-full mt-2 justify-center">
                        <span x-show="Object.keys(cart).length > 0">Proceed to Payment</span>
                        <span x-show="Object.keys(cart).length === 0">Add Items to Order</span>
                    </x-primary-button>


                    <div x-show="Object.keys(cart).length > 0">
                                                <x-secondary-button type="button"
                            @click="$dispatch('open-modal', 'confirm-clear-order')"
                            x-bind:class="(paymentMethod === 'GCash' && gcashVerified) ? 'opacity-50 cursor-not-allowed' : ''"
                                                    id="pos_clear_cart_btn" class="w-full mt-1.5 justify-center text-red-600">
                            Clear Order
                        </x-secondary-button>
                    </div>

                    </div>{{-- close pt-2 space-y-3 order meta --}}
                </div>{{-- close border-t px-4 pt-3 pb-4 totals section --}}
            </div>{{-- close flex flex-col bg-white rounded card --}}
        </div>{{-- close RIGHT panel --}}

    </div>{{-- end content row --}}

    {{-- ══════════════════════════════════════════════
         MODALS CONTAINER
    ══════════════════════════════════════════════ --}}
    <div>
        {{-- ══════════════════════════════════════════════
             PRODUCT OPTIONS MODAL
        ══════════════════════════════════════════════ --}}
        <x-modal name="pos-options" maxWidth="2xl" focusable>
            <div x-data="{ localProduct: null }" 
                 @open-options-modal.window="localProduct = $event.detail"
                 class="relative">
                <template x-if="localProduct">
                    <div class="p-8 relative">
                        <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-blue-600 rounded-t-xl absolute top-0 left-0"></div>
                        
                        <div class="mb-8">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h2 class="text-[22px] font-black text-gray-900" x-text="localProduct ? localProduct.name : ''"></h2>
                                    <p class="text-[13px] text-gray-500 mt-1">Customize your order</p>
                                </div>
                                <template x-if="localProduct && localProduct.image_url">
                                    <img :src="localProduct.image_url" :alt="localProduct.name" 
                                        class="w-20 h-20 rounded-lg object-cover border border-gray-100 shadow-sm">
                                </template>
                            </div>
                        </div>

                        {{-- Option Groups --}}
                        <div class="space-y-8 mb-8" x-show="localProduct && (localProduct.option_groups || localProduct.optionGroups)">
                            <template x-for="group in localProduct ? (localProduct.option_groups || localProduct.optionGroups || []) : []" :key="group.id">
                                <div>
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <p class="text-[14px] font-bold text-gray-900" x-text="group.name"></p>
                                            <template x-if="group.is_required">
                                                <span class="text-[11px] font-semibold text-red-600 mt-0.5 block">Required selection</span>
                                            </template>
                                            <template x-if="group.price_mode === 'additive'">
                                                <span class="text-[11px] font-semibold text-blue-600 mt-0.5 block">Multiple allowed</span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                        <template x-for="opt in group.options" :key="opt.id">
                                            <div @click="remainingOptionStock(localProduct, opt.id) > 0 && toggleOpt(group.id, opt.id, group.price_mode === 'additive', group.is_required)"
                                                class="relative flex flex-col p-4 rounded-lg border-2 cursor-pointer transition-all duration-200 hover:shadow-md"
                                                :class="{
                                                    'opacity-50 cursor-not-allowed border-gray-200 bg-gray-50': remainingOptionStock(localProduct, opt.id) <= 0,
                                                    'border-indigo-500 bg-indigo-50 shadow-md': isSelected(group.id, opt.id),
                                                    'border-gray-200 bg-white hover:border-gray-300': !isSelected(group.id, opt.id) && remainingOptionStock(localProduct, opt.id) > 0
                                                }">

                                                <span class="text-[13px] font-bold text-gray-900" x-text="opt.name"></span>
                                                <span class="text-[13px] font-black text-indigo-600 mt-2" 
                                                      x-text="group.price_mode === 'fixed' ? '{{ $currencySymbol }}' + parseFloat(opt.price).toFixed(2) : (opt.price > 0 ? '+' + '{{ $currencySymbol }}' + parseFloat(opt.price).toFixed(2) : 'Free')"></span>
                                                
                                                {{-- Availability Badge --}}
                                                <div class="mt-2 flex items-center justify-between">
                                                    <template x-if="!group.no_recipe_required && remainingOptionStock(localProduct, opt.id) <= 0">
                                                        <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-1 rounded uppercase tracking-tighter">Out of Stock</span>
                                                    </template>
                                                    <template x-if="!group.no_recipe_required && remainingOptionStock(localProduct, opt.id) > 0">
                                                        <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded uppercase tracking-tighter" 
                                                              x-text="'Available: ' + remainingOptionStock(localProduct, opt.id)"></span>
                                                    </template>
                                                    <template x-if="group.no_recipe_required">
                                                        <span class="text-[10px] font-bold text-sky-600 bg-sky-50 px-2 py-1 rounded uppercase tracking-tighter">Always Available</span>
                                                    </template>
                                                </div>
                                                
                                                <template x-if="isSelected(group.id, opt.id)">
                                                    <div class="absolute top-2 right-2">
                                                        <div class="w-5 h-5 bg-indigo-500 rounded flex items-center justify-center shadow-md"
                                                             :class="group.price_mode === 'additive' ? 'rounded' : 'rounded-full'">
                                                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Modifiers --}}
                        <div class="mb-8" x-show="localProduct && localProduct.modifiers && localProduct.modifiers.length > 0">
                            <p class="text-[14px] font-bold text-gray-900 mb-4">Add-ons (Optional)</p>
                            <div class="space-y-2">
                                <template x-for="m in localProduct ? localProduct.modifiers : []" :key="m.id">
                                    <label @click="remainingModifierStock(localProduct, m.id) > 0 && toggleMod(m.id)"
                                        class="flex items-center justify-between p-4 rounded-lg border border-gray-200 cursor-pointer transition-all hover:bg-gray-50 hover:border-gray-300"
                                        :class="remainingModifierStock(localProduct, m.id) <= 0 ? 'opacity-50 cursor-not-allowed bg-gray-50' : (selectedModifierIds.includes(m.id) ? 'border-indigo-500 bg-indigo-50' : '')">
                                        <div class="flex items-center gap-3">
                                            <input type="checkbox" :checked="selectedModifierIds.includes(m.id)" :disabled="remainingModifierStock(localProduct, m.id) <= 0"
                                                class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 pointer-events-none">
                                            <div>
                                                <span class="text-[13px] font-semibold text-gray-800" x-text="m.name"></span>
                                                <template x-if="remainingModifierStock(localProduct, m.id) <= 0">
                                                    <span class="ml-2 text-[9px] font-black bg-red-100 text-red-600 px-1.5 py-0.5 rounded uppercase tracking-widest">Out of Stock</span>
                                                </template>
                                            </div>
                                        </div>
                                        <span class="text-[12px] font-bold text-gray-900 font-mono" x-text="'+{{ $currencySymbol }}' + parseFloat(m.price).toFixed(2)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-2 border-t border-gray-100">
                            <x-secondary-button type="button" @click="$dispatch('close-modal', 'pos-options'); localProduct = null" class="flex-1 justify-center">
                                Cancel
                            </x-secondary-button>
                            <x-primary-button type="button"
                                x-bind:disabled="!canAddToOrder(localProduct)"
                                x-bind:class="!canAddToOrder(localProduct) ? 'opacity-50 cursor-not-allowed' : ''"
                                @click="canAddToOrder(localProduct) && (optimisticAddToCart(localProduct.id, selectedOptions, selectedModifierIds), $dispatch('close-modal', 'pos-options'), localProduct = null)"
                                class="flex-1 justify-center">
                                Add to Order
                            </x-primary-button>
                        </div>
                        <p x-show="!canAddToOrder(localProduct)" class="text-[11px] text-red-500 font-semibold mt-2 text-center">
                            A required option is out of stock — please choose an available selection.
                        </p>
                    </div>
                </template>
            </div>
        </x-modal>

{{-- ══════════════════════════════════════════════
     PAYMENT MODAL
══════════════════════════════════════════════ --}}
<x-modal name="pos-payment" maxWidth="4xl" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-gray-800 to-gray-600 rounded-t-xl"></div>
    
    <div class="p-6">
        <h2 class="text-[18px] font-bold text-gray-900 mb-6">Confirm Payment</h2>

        <template x-if="cartLocked">
            <div class="mb-6 flex items-start gap-3 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <div>
                    <p class="text-[13px] font-black text-emerald-800">Payment Already Verified via GCash</p>
                    <p class="text-[12px] text-emerald-700 mt-0.5 leading-snug">The money has already been received. This order is locked and can no longer be edited or cancelled — please click <span class="font-bold">Place Order</span> below to complete it.</p>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6">
            {{-- LEFT CARD: Order Summary --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-[13px] font-bold text-gray-900">Order Summary</h3>
                </div>
                
                <div class="flex-1 overflow-y-auto px-4 py-3 max-h-[30vh] md:max-h-[50vh]">
                    <div x-show="Object.keys(cart).length > 0" class="space-y-3">
                        <template x-for="(item, key) in cart" :key="key">
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 hover:border-gray-200 transition-all">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[12px] font-bold text-gray-900 truncate" x-text="item.name"></p>
                                        <template x-if="item.options && item.options.length > 0">
                                            <p class="text-[10px] text-gray-500 mt-0.5 line-clamp-2" x-text="item.options.map(o => o.name).join(', ')"></p>
                                        </template>
                                        <template x-if="item.modifiers && item.modifiers.length > 0">
                                            <p class="text-[9px] text-gray-400 mt-0.5 italic" x-text="item.modifiers.map(m => '+ ' + m.name).join(', ')"></p>
                                        </template>
                                        <div class="flex items-center justify-between mt-2 text-[11px] text-gray-600">
                                            <span x-text="'× ' + item.qty"></span>
                                            <span class="font-bold text-gray-900" x-text="'{{ $currencySymbol }}' + (item.price * item.qty).toFixed(2)"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div x-show="Object.keys(cart).length === 0" class="text-center py-8 text-gray-400">
                        <p class="text-[12px]">No items in cart</p>
                    </div>
                </div>

                    <div x-show="Object.keys(cart).length > 0" class="border-t border-gray-100 px-4 py-3 bg-gray-50 space-y-2">
                        <div class="flex items-center justify-between text-[12px]">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-bold text-gray-900">{{ $currencySymbol }}<span x-text="subtotal.toFixed(2)"></span></span>
                        </div>
                            <div x-show="discountTotal > 0" class="flex items-center justify-between text-[12px]">
                                <span class="text-gray-600">Discount</span>
                                <span class="font-bold text-emerald-600">-{{ $currencySymbol }}<span x-text="discountTotal.toFixed(2)"></span></span>
                            </div>
                            <div x-show="serviceCharge > 0" class="flex items-center justify-between text-[12px]">
                                <span class="text-gray-600" x-text="'Service Charge (' + Math.round(serviceChargeRate * 100) + '%)'">Service Charge ({{ round($serviceChargeRate * 100) }}%)</span>
                                <span class="font-bold text-gray-900">{{ $currencySymbol }}<span x-text="serviceCharge.toFixed(2)"></span></span>
                            </div>
                        <div class="flex items-center justify-between text-[13px] font-bold bg-indigo-100 rounded-lg p-2.5 border border-indigo-200">
                            <span class="text-indigo-900">Total</span>
                            <span class="text-indigo-900">{{ $currencySymbol }}<span x-text="total.toFixed(2)"></span></span>
                        </div>
                    </div>
            </div>

            {{-- RIGHT CARD: Payment Details --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-[13px] font-bold text-gray-900">Payment Details</h3>
                </div>

                <div class="flex-1 overflow-y-auto px-4 py-4 space-y-5">
                    {{-- Order Type --}}
                    <div>
                        <p class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide mb-2">Order Type</p>
                        <div class="px-3 py-2.5 bg-gray-50 rounded-lg border border-gray-200">
                            <p class="text-[13px] font-bold text-gray-900" x-text="orderType">{{ $orderType }}</p>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div>
                        <p class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide mb-2">Payment Method</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($paymentMethods as $method)
                                <button type="button"
                                    data-payment-method="{{ $method }}"
                                    @click="(!gcashVerified || $el.dataset.paymentMethod === 'GCash') && (paymentMethod = $el.dataset.paymentMethod)"
                                    :disabled="gcashVerified && $el.dataset.paymentMethod !== 'GCash'"
                                    :class="paymentMethod === $el.dataset.paymentMethod ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : (gcashVerified ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50')"
                                    id="pos_pay_method_{{ strtolower($method) }}"
                                    class="flex-1 justify-center py-2 px-4 text-[13px] font-bold rounded-lg border transition-all">
                                    {{ $method }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Cash: Amount Tendered + Change --}}
                    <div x-show="paymentMethod === 'Cash'" class="space-y-4">
                        <div>
                            <x-input-label for="pos_amount_tendered" class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide mb-2 block">Amount Tendered</x-input-label>
                            <x-text-input id="pos_amount_tendered" x-model="amountTendered" type="number" min="0" step="0.01"
                                class="block w-full text-[14px] py-2.5 px-3 font-mono font-bold" placeholder="0.00"/>
                        </div>
                        
                        {{-- Quick Tenders --}}
                        <div class="grid grid-cols-2 xs:grid-cols-4 gap-2">
                            <button type="button" @click="amountTendered = total" class="py-2.5 px-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-[12px] font-bold rounded-lg transition-colors">Exact</button>
                            <button type="button" @click="amountTendered = 100" class="py-2.5 px-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-[12px] font-bold font-mono rounded-lg transition-colors">{{ $currencySymbol }}100</button>
                            <button type="button" @click="amountTendered = 500" class="py-2.5 px-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-[12px] font-bold font-mono rounded-lg transition-colors">{{ $currencySymbol }}500</button>
                            <button type="button" @click="amountTendered = 1000" class="py-2.5 px-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-[12px] font-bold font-mono rounded-lg transition-colors">{{ $currencySymbol }}1000</button>
                        </div>

                        <div x-show="amountTendered >= total && total > 0" class="flex items-center justify-between bg-emerald-50 rounded-lg px-3 py-3 border border-emerald-200">
                            <span class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Change</span>
                            <span class="text-[14px] font-black text-emerald-700 font-mono" x-text="'{{ $currencySymbol }}' + (amountTendered - total).toFixed(2)"></span>
                        </div>
                    </div>

                    {{-- Custom Payment Method: Reference Number --}}
                    <div x-show="paymentMethod !== 'Cash' && paymentMethod !== 'GCash'" class="space-y-4">
                        <div>
                            <x-input-label for="pos_payment_reference" class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide mb-2 block">Payment Reference / Confirmation No.</x-input-label>
                            <x-text-input id="pos_payment_reference" wire:model.blur="paymentReference" type="text" class="block w-full text-[14px] py-2.5 px-3 font-mono font-bold" placeholder="e.g. Transaction ID" />
                            <x-input-error :messages="$errors->get('paymentReference')" class="mt-1" />
                        </div>
                    </div>

                    {{-- GCash Details (PayMongo + Static QR Fallback) --}}
                    <div x-show="paymentMethod === 'GCash'"
                         wire:key="gcash-panel-{{ $referenceNo ?: 'new' }}"
                         x-data="{ gcashUrl: null, showStatic: false }"
                         @gcash-url-ready.window="gcashUrl = $event.detail.url; showStatic = false"
                         @gcash-reset.window="gcashUrl = null; showStatic = false">

                        {{-- Step 1: No URL yet — show options --}}
                        <div x-show="!gcashUrl && !gcashVerified && !showStatic" class="space-y-4"
                             x-data="{ isGenerating: false }"
                             @gcash-url-ready.window="isGenerating = false"
                             @notify.window="if($event.detail.type === 'error') isGenerating = false">
                            <div class="bg-blue-50 rounded-2xl p-6 border border-blue-100 flex flex-col items-center text-center">
                                <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                                    <svg class="w-8 h-8 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                                </div>
                                <p class="text-[13px] font-bold text-blue-900 mb-1">Pay via GCash</p>
                                <p class="text-[11px] text-blue-500 mb-5">Choose how you want to collect the payment.</p>
                                
                                <div class="w-full space-y-3">
                                                                        {{-- PayMongo Option — disabled for now, kept for future production use.
                                         Re-enable by removing the @if(false)/@endif wrapper below. --}}
                                    @if(false)
                                    <button type="button" 
                                        @click="isGenerating = true; $wire.initiateGCashPayment()" 
                                        :disabled="isGenerating"
                                        class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[13px] font-bold flex items-center justify-center gap-2 transition-all shadow-lg shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span x-show="!isGenerating" class="flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1M4 12H3m18 0h-1M6.343 6.343l-.707-.707m12.728 12.728l-.707-.707M6.343 17.657l-.707.707M17.657 6.343l-.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                                            Generate Dynamic QR (PayMongo)
                                        </span>
                                        <span x-show="isGenerating" x-cloak class="flex items-center gap-2">
                                            <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Generating...
                                        </span>
                                    </button>
                                    @endif

                                    {{-- Static QR Fallback --}}
                                    <button type="button" @click="showStatic = true"
                                        class="w-full py-3 bg-white hover:bg-gray-50 text-blue-600 border-2 border-blue-100 rounded-xl text-[13px] font-bold flex items-center justify-center gap-2 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1M4 12H3m18 0h-1M6.343 6.343l-.707-.707m12.728 12.728l-.707-.707M6.343 17.657l-.707.707M17.657 6.343l-.707.707M12 8a4 4 0 100 8 4 4 0 000-8z"/></svg>
                                        Use Personal Static QR
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Static QR Display Panel --}}
                        <div x-show="showStatic && !gcashVerified" class="space-y-4">
                            <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100 flex flex-col items-center text-center">
                                <p class="text-[11px] font-black text-blue-600 uppercase tracking-widest mb-3">Scan Personal QR</p>

                                {{-- Uploaded Static QR --}}
                                <div class="bg-white p-3 rounded-2xl shadow-sm border border-blue-200 mb-3">
                                    @if($gcashQrImage)
                                        <img src="{{ asset('storage/' . $gcashQrImage) }}" 
                                             alt="GCash Static QR" class="w-[180px] h-[180px] mx-auto object-contain">
                                    @else
                                        <div class="w-[180px] h-[180px] flex flex-col items-center justify-center bg-gray-100 rounded-xl text-gray-400 p-4">
                                            <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <p class="text-[10px] font-bold">No QR Uploaded in Settings</p>
                                        </div>
                                    @endif
                                </div>

                                <p class="text-[12px] font-bold text-blue-900">{{ $gcashAccountName }}</p>
                                <p class="text-[11px] text-blue-500 font-mono">{{ $gcashAccountNumber }}</p>

                                <button type="button" @click="showStatic = false"
                                    class="mt-4 text-[10px] text-gray-400 font-bold uppercase tracking-widest hover:text-gray-600 transition-colors">
                                    ← Back to options
                                </button>
                            </div>

                            <button type="button" wire:click="verifyStaticPayment" wire:loading.attr="disabled" wire:target="verifyStaticPayment"
                                class="w-full h-14 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[14px] font-black transition-all shadow-lg shadow-emerald-200 disabled:opacity-70 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Verify & Lock Payment
                            </button>
                        </div>

                                                {{-- Step 2: URL generated — show dynamic QR — disabled for now, kept for
                             future production use. Re-enable by removing the @if(false)/@endif wrapper. --}}
                        @if(false)
                        <div x-show="gcashUrl && !gcashVerified" class="space-y-4"
                             x-init="
                                let pollInterval = null;
                                const startPolling = () => {
                                    clearInterval(pollInterval);
                                    pollInterval = setInterval(() => {
                                        if (gcashVerified) { clearInterval(pollInterval); return; }
                                        $wire.pollGCashStatus();
                                    }, 4000);
                                };
                                if (gcashUrl && !gcashVerified) startPolling();
                                $watch('gcashUrl', (url) => { url && !gcashVerified ? startPolling() : clearInterval(pollInterval); });
                                $watch('gcashVerified', (paid) => { if (paid) clearInterval(pollInterval); });
                             ">
                            <div class="bg-blue-50 rounded-2xl p-5 border border-blue-100 flex flex-col items-center text-center">
                                <p class="text-[11px] font-black text-blue-600 uppercase tracking-widest mb-3">Scan to Pay with GCash</p>

                                {{-- Dynamic QR --}}
                                <div class="bg-white p-3 rounded-2xl shadow-sm border border-blue-200 mb-3">
                                    <template x-if="gcashUrl">
                                        <img :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${encodeURIComponent(gcashUrl)}`"
                                             alt="GCash QR" class="w-[180px] h-[180px] mx-auto">
                                    </template>
                                </div>

                                <p class="text-[12px] font-bold text-blue-900">{{ $currencySymbol }}<span x-text="total.toFixed(2)">{{ number_format($this->total, 2) }}</span> due</p>
                                <p class="text-[10px] text-blue-500 mt-0.5">Order #{{ $referenceNo }}</p>

                                <a :href="gcashUrl" target="_blank"
                                   class="mt-3 text-[11px] text-blue-600 font-bold underline underline-offset-2">
                                    Open in browser instead ↗
                                </a>

                                <div class="mt-4 flex items-center gap-2 text-[11px] text-amber-600 font-semibold">
                                    <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Waiting for customer payment...
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- Step 3: Paid — show success (x-if so the draw-in animation replays every time) --}}
                        <template x-if="gcashVerified">
                            <div class="bg-emerald-50 rounded-2xl p-6 border border-emerald-100 flex flex-col items-center text-center gcash-success-pop">
                                <svg class="w-16 h-16 mb-3" viewBox="0 0 52 52">
                                    <circle class="gcash-success-circle" cx="26" cy="26" r="24" fill="none" stroke="#059669" stroke-width="3"/>
                                    <path class="gcash-success-check" fill="none" stroke="#059669" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" d="M14 27l7 7 17-17"/>
                                </svg>
                                <p class="text-[14px] font-black text-emerald-900 leading-none">Confirmed Payment</p>
                                @if(!$isManualGcash)
                                    <p class="text-[11px] text-emerald-600 font-bold mt-1.5 uppercase tracking-tighter">Verified</p>
                                @endif
                            </div>
                        </template>

                    <x-input-error :messages="$errors->get('gcashVerified')" class="mt-3 text-center" />

                        <style>
                            .gcash-success-pop { animation: gcashPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
                            @keyframes gcashPop { from { opacity: 0; transform: scale(0.85); } to { opacity: 1; transform: scale(1); } }
                            .gcash-success-circle {
                                stroke-dasharray: 151; stroke-dashoffset: 151;
                                animation: gcashCircle 0.5s cubic-bezier(0.65, 0, 0.45, 1) 0.1s forwards;
                            }
                            .gcash-success-check {
                                stroke-dasharray: 36; stroke-dashoffset: 36;
                                animation: gcashCheck 0.35s ease-out 0.55s forwards;
                            }
                            @keyframes gcashCircle { to { stroke-dashoffset: 0; } }
                            @keyframes gcashCheck { to { stroke-dashoffset: 0; } }
                        </style>
                    </div>{{-- end GCash --}}
                </div>{{-- end payment details scrollable --}}
                {{-- Total Due Display --}}
                <div class="border-t border-gray-100 px-4 py-4 bg-gray-900 text-white text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1">Total Due</p>
                    <p class="text-[24px] font-black font-mono">{{ $currencySymbol }}<span x-text="total.toFixed(2)">{{ number_format($this->total, 2) }}</span></p>
                </div>
            </div>
        </div>

        @if($errors->has('gcashCancel'))
            <div class="mb-3 flex items-start gap-2.5 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <p class="text-[12px] font-semibold text-amber-800 leading-snug">{{ $errors->first('gcashCancel') }}</p>
            </div>
        @endif

        {{-- Action Buttons --}}
        <div class="flex gap-3">
<x-secondary-button type="button"
    @click="if (!gcashVerified) { $dispatch('close-modal', 'pos-payment'); $dispatch('gcash-reset'); } else { $wire.cancelPaymentModal(); }"
    x-bind:class="cartLocked ? '!bg-gray-100 !text-gray-400 !border-gray-200 cursor-not-allowed' : ''"
    x-bind:title="cartLocked ? 'This payment is verified and locked — place the order to complete it.' : ''"
    class="flex-1 justify-center">
    <svg x-show="cartLocked" class="w-3.5 h-3.5 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
    <span x-text="cartLocked ? 'Locked' : 'Cancel'"></span>
</x-secondary-button>
            <x-primary-button type="button"
@click.capture="if (!isSubmitting && !window.thermalBluetoothPrinter?.characteristic) window.prepareThermalReceiptWindow?.()"
@click.prevent="
    if (isSubmitting) return;
    isSubmitting = true;
    $wire.confirmPayment(JSON.parse(JSON.stringify(cart)))
        .finally(() => { isSubmitting = false; });
"
                x-bind:disabled="isSubmitting"
                wire:loading.attr="disabled"
                id="pos_submit_payment_btn"
                class="flex-1 justify-center">
                <span x-show="!isSubmitting">Place Order</span>
                <span x-show="isSubmitting" x-cloak>Processing...</span>
            </x-primary-button>
        </div>
    </div>
</x-modal>

{{-- ══════════════════════════════════════════════
     EDIT CART ITEM MODAL
══════════════════════════════════════════════ --}}
<x-modal name="edit-cart-item" maxWidth="md" focusable>
    <div class="p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-500 shadow-sm border border-amber-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            </div>
            <div>
                <h2 class="text-[18px] font-black text-gray-900">Special Instructions</h2>
                <p class="text-[12px] text-gray-500">Add notes or preferences for this item</p>
            </div>
        </div>

        <template x-if="editingItem">
    <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
        <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Applying to</p>
        <p class="text-[14px] font-bold text-gray-900 leading-tight" x-text="editingItem?.name"></p>
    </div>
</template>

        <div class="space-y-4 mb-8">
            <label for="pos_edit_item_notes" class="sr-only">Instructions</label>
            <textarea id="pos_edit_item_notes" wire:model.live.debounce.400ms="editCartItemNotes" rows="4" 
                placeholder="e.g., No spicy sauce, extra napkins, separate bag..."
                inputFilter="name_basic"
                class="w-full px-4 py-3 text-[13px] font-medium text-gray-700 bg-white border border-gray-200 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all placeholder-gray-400 resize-none {{ $errors->has('editCartItemNotes') ? 'border-red-500 focus:ring-red-500/10 focus:border-red-500' : '' }}"
            ></textarea>
            <x-input-error :messages="$errors->get('editCartItemNotes')" class="mt-1" />
            <p class="text-[11px] text-gray-400 flex items-center gap-1.5 ml-1 mb-2">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                This will be printed on the kitchen ticket.
            </p>

            {{-- Discount Selection for this item --}}
            <div class="space-y-2">
                @if($discountPercent > 0)
<div x-data="{ isRegularDiscount: @entangle('applyRegularDiscount') }"
                     class="bg-gray-50 rounded-xl px-4 py-3 border border-blue-100 shadow-sm transition-all duration-300"
                     :class="!isRegularDiscount ? 'opacity-60 grayscale' : 'ring-2 ring-indigo-500/20'">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 border border-current/10"
                                 :class="isRegularDiscount ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-200 text-gray-500'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M17 17h.01M7 17L17 7"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[12px] font-black tracking-tighter uppercase truncate"
                                   :class="isRegularDiscount ? 'text-indigo-600' : 'text-gray-500'">
                                    {{ round($discountPercent * 100) }}% REGULAR DISCOUNT
                                </p>
                                <p class="text-[10px] text-gray-400 font-bold truncate">Apply to this item</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <label class="inline-flex relative items-center cursor-pointer scale-90">
                                <input type="checkbox" wire:model="applyRegularDiscount" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>
                    </div>
                </div>
                @endif

                @if($seniorDiscountRate > 0)
                <div x-data="{ isSeniorDiscount: @entangle('applySeniorDiscount') }"
                     class="bg-gray-50 rounded-xl px-4 py-3 border border-purple-100 shadow-sm transition-all duration-300"
                     :class="!isSeniorDiscount ? 'opacity-60 grayscale' : 'ring-2 ring-purple-500/20'">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 border border-current/10"
                                 :class="isSeniorDiscount ? 'bg-purple-100 text-purple-600' : 'bg-gray-200 text-gray-500'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[12px] font-black tracking-tighter uppercase truncate"
                                   :class="isSeniorDiscount ? 'text-purple-600' : 'text-gray-500'">
                                    {{ round($seniorDiscountRate * 100) }}% SENIOR / PWD DISCOUNT
                                </p>
                                <p class="text-[10px] text-gray-400 font-bold truncate">Apply to this item (VAT-Exempt)</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center">
                            <label class="inline-flex relative items-center cursor-pointer scale-90">
                                <input type="checkbox" wire:model="applySeniorDiscount" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-500"></div>
                            </label>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
            <button type="button" @click="$dispatch('close-modal', 'edit-cart-item')" class="w-full py-3 rounded-2xl text-[13px] font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all">
                Discard
            </button>
            <x-primary-button type="button" wire:click.prevent="saveEditItem" class="w-full justify-center py-3 shadow-indigo-200/50 shadow-lg !border-none">
                Save Note
            </x-primary-button>
        </div>
    </div>
</x-modal>
    {{-- ══════════════════════════════════════════════
         CONFIRM DELETE ITEM MODAL
    ══════════════════════════════════════════════ --}}
    <x-modal name="confirm-delete-item" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-rose-500 to-red-600 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Remove Item</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                        Remove <span class="font-bold text-gray-800" x-text="cart[pendingDeleteKey]?.name || 'this item'"></span> from the order?
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="pendingDeleteKey = null; $dispatch('close-modal', 'confirm-delete-item')" class="h-10">Cancel</x-secondary-button>
                <button type="button"
                    @click="delete cart[pendingDeleteKey]; cart = {...cart}; pendingDeleteKey = null; $dispatch('close-modal', 'confirm-delete-item')"
                    class="h-10 px-4 inline-flex items-center justify-center rounded-lg bg-red-600 hover:bg-red-700 text-white text-[13px] font-bold transition-colors">
                    Remove Item
                </button>
            </div>
        </div>
    </x-modal>

    {{-- ══════════════════════════════════════════════
         CONFIRM CLEAR ORDER MODAL
    ══════════════════════════════════════════════ --}}
    <x-modal name="confirm-clear-order" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-rose-500 to-red-600 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Clear Order</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">This will remove all items from the current order. This can't be undone.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-clear-order')" class="h-10">Cancel</x-secondary-button>
                <button type="button"
                    @click="(paymentMethod === 'GCash' && gcashVerified) ? $wire.clearCart() : (cart = {}, $wire.clearCart()); $dispatch('close-modal', 'confirm-clear-order')"
                    class="h-10 px-4 inline-flex items-center justify-center rounded-lg bg-red-600 hover:bg-red-700 text-white text-[13px] font-bold transition-colors">
                    Clear Order
                </button>
            </div>
        </div>
    </x-modal>
                <x-modal name="pos-drafts-list" maxWidth="2xl" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-t-xl"></div>
        <div class="p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-[22px] font-black text-gray-900 flex items-center gap-2">
                        <span class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 shadow-sm border border-indigo-100">📋</span>
                        Saved Drafts
                    </h2>
                    <p class="text-[13px] text-gray-500 mt-1">Review or reload previously saved orders for this branch.</p>
                </div>
                <button @click="$dispatch('close-modal', 'pos-drafts-list')" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="max-h-[450px] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($this->drafts as $draft)
    <div wire:key="draft-item-{{ $draft->id }}" 
        x-show="!hiddenDraftIds.includes({{ $draft->id }})"
        class="mb-3 bg-white border border-gray-100 p-5 rounded-2xl shadow-sm hover:shadow-md hover:border-indigo-100 transition-all group/item">
                        <div class="flex items-start justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="text-[14px] font-black text-indigo-600 font-mono tracking-tight">#{{ $draft->reference_no }}</span>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-indigo-50 text-indigo-500 uppercase tracking-widest border border-indigo-100/50">DRAFT</span>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-[13px] font-bold text-gray-800 flex items-center gap-1.5 leading-tight">
                                        {{ $draft->table_number ? 'Table: ' . $draft->table_number : 'No Table Reference' }}
                                    </p>
                                    <p class="text-[11px] text-gray-500 font-medium line-clamp-1 italic">"{{ $draft->notes }}"</p>
                                    <div class="flex items-center gap-3 pt-1">
                                        <p class="text-[11px] font-bold text-gray-400 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 11-8 0m8 4v5a9 9 0 11-18 0v-5m18 0h-18" /></svg>
                                            {{ $draft->items->count() }} Items
                                        </p>
                                        <p class="text-[11px] font-bold text-gray-400 flex items-center gap-1 lowercase">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            {{ $draft->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-3 shrink-0">
                                <div class="text-right">
                                    <p class="text-[16px] font-black text-gray-900 font-mono tracking-tight leading-none">{{ $currencySymbol }}{{ number_format($draft->total_amount, 2) }}</p>
                                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mt-1">Order value</p>
                                </div>
                                <div class="flex gap-2">
                                    <x-secondary-button type="button" @click="pendingDeleteDraftId = {{ $draft->id }}; $dispatch('open-modal', 'confirm-delete-draft')" class="!px-3 !py-1.5 text-red-500 hover:text-red-700 bg-red-50/50 border-red-100 hover:bg-red-50 transition-all">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
</x-secondary-button>
                                    <x-primary-button type="button"
                                        x-data="{ loading: false }"
                                        @click="loading = true; $wire.loadDraft({{ $draft->id }}).finally(() => loading = false)"
                                        x-bind:disabled="loading"
                                        class="!px-5 !py-1.5 shadow-none border-none min-w-[110px] justify-center">
                                        <span x-show="!loading">Load Order</span>
                                        <span x-show="loading" x-cloak class="flex items-center justify-center gap-1.5">
                                            <svg class="animate-spin w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Loading...
                                        </span>
                                    </x-primary-button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-4 text-gray-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></svg>
                        </div>
                        <h3 class="text-[15px] font-bold text-gray-800">No drafts found</h3>
                        <p class="text-[12px] text-gray-500 mt-1 max-w-[240px]">All saved drafts for this branch will appear here for quick recovery.</p>
                    </div>
                @endforelse
            </div>

                        <div class="mt-8 pt-6 border-t border-gray-100">
                                <x-secondary-button @click="$dispatch('close-modal', 'pos-drafts-list')" class="w-full justify-center py-3">
                    Close Monitor
                </x-secondary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirm-delete-draft" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-rose-500 to-red-600 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Delete Draft</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">This will permanently delete this saved order. This can't be undone.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="pendingDeleteDraftId = null; $dispatch('close-modal', 'confirm-delete-draft')" class="h-10">Cancel</x-secondary-button>
                <button type="button"
                    @click="hiddenDraftIds.push(pendingDeleteDraftId); $wire.deleteDraft(pendingDeleteDraftId); $dispatch('close-modal', 'confirm-delete-draft'); pendingDeleteDraftId = null"
                    class="h-10 px-4 inline-flex items-center justify-center rounded-lg bg-red-600 hover:bg-red-700 text-white text-[13px] font-bold transition-colors">
                    Delete Draft
                </button>
            </div>
        </div>
    </x-modal>

    {{-- ══════════════════════════════════════════════
         TEAR-OFF CONFIRMATION MODAL (multi-slip printing)
    ══════════════════════════════════════════════ --}}
    <div x-data="{
        show: false,
        sectionType: '',
        secondsLeft: 0,
        countdownTimer: null,
        get label() { return this.sectionType === 'barista' ? 'Barista Slip' : 'Kitchen Slip'; },
        startCountdown(timeoutMs) {
            this.secondsLeft = Math.ceil((timeoutMs || 15000) / 1000);
            clearInterval(this.countdownTimer);
            this.countdownTimer = setInterval(() => {
                this.secondsLeft = Math.max(0, this.secondsLeft - 1);
                if (this.secondsLeft <= 0) clearInterval(this.countdownTimer);
            }, 1000);
        }
    }"
    x-init="
        window.addEventListener('thermal-print-waiting', (e) => {
            sectionType = e.detail.sectionType;
            startCountdown(e.detail.timeoutMs);
            show = true;
        });
        window.addEventListener('thermal-print-resumed', () => {
            show = false;
            clearInterval(countdownTimer);
        });
    "
    x-show="show" x-cloak
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">
        <div class="w-14 h-14 mx-auto rounded-full bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500 mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
        </div>
        <h3 class="text-[16px] font-black text-gray-900 mb-1" x-text="label + ' Printed'"></h3>
        <p class="text-[13px] text-gray-500 mb-4">Tear off the slip, then tap Continue to print the next one.</p>
        <p class="text-[12px] font-bold text-amber-600 mb-6">
            Auto-continuing in <span x-text="secondsLeft" class="font-mono"></span>s…
        </p>
        <button type="button"
            @click="window.thermalBluetoothPrinter.confirmContinue()"
            class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-[14px] font-black transition-all active:scale-[0.98]">
            Continue Printing
        </button>
    </div>
</div>
</div>{{-- end modals container --}}

</div>{{-- end outer container --}}
