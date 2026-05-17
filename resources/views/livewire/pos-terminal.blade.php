<div class="flex flex-col h-[calc(100vh-58px)] sm:h-[calc(100vh-65px)] overflow-hidden bg-gray-100" 
    wire:key="pos-terminal-root"
    x-data="{ 
        searchQuery: '',
        get filteredProductsCount() {
            if (!this.searchQuery) return Object.keys(this.productsData).length;
            const query = this.searchQuery.toLowerCase().trim();
            return Object.values(this.productsData).filter(p => p.name.toLowerCase().includes(query)).length;
        },
        isSavingDraft: false,
        cartExpanded: false,
        isEditMode: @entangle('isEditMode').live,
        cart: @entangle('cart'),
        paymentMethod: @entangle('paymentMethod').live,
        amountTendered: @entangle('amountTendered').live,
        serviceChargeRate: {{ $serviceChargeRate }},
        orderType: @entangle('orderType'),
        get subtotal() {
            return Object.values(this.cart).reduce((sum, item) => sum + (item.price * item.qty), 0);
        },
        get serviceCharge() {
            return this.orderType === 'Dine-in' ? (this.subtotal * this.serviceChargeRate) : 0;
        },
        get total() {
            return this.subtotal + this.serviceCharge;
        },
        productsData: {{ $products->keyBy('id')->toJson() }},
        activeProduct: null,
        selectedOptions: {},
        selectedModifierIds: [],
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
                this.categorySortable = new Sortable(el, {
                    draggable: '.pos-category-tab',
                    filter: '#pos_tab_all',
                    animation: 150,
                    ghostClass: 'bg-' + this.primaryColor + '-50',
                    onEnd: (evt) => {
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
                
                this.productSortable = new Sortable(el, {
                    draggable: '.product-card',
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'bg-' + this.primaryColor + '-50',
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

            // Initialize defaults
            if (product.option_groups) {
                product.option_groups.forEach(group => {
                    const def = group.options.find(o => o.is_default);
                    if (def) {
                        this.selectedOptions[group.id] = group.price_mode === 'additive' ? [def.id] : def.id;
                    } else if (group.is_required) {
                        const first = group.options[0];
                        this.selectedOptions[group.id] = group.price_mode === 'additive' ? [first.id] : first.id;
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
            if (product.option_groups) {
                product.option_groups.forEach(g => {
                    allOptions = allOptions.concat(g.options);
                });
            }
            
            const selectedOptions = allOptions.filter(o => optionIds.includes(o.id));
            const selectedModifiers = (product.modifiers || []).filter(m => selectedModifierIds.includes(m.id));

            // Logic: Fixed price groups become base, else product base price. Add additive options and modifiers.
            const hasFixed = selectedOptions.some(o => {
                const group = product.option_groups.find(g => g.id === o.product_option_group_id);
                return group && group.price_mode === 'fixed';
            });

            let basePrice = product.price; // fallback
            if (hasFixed) {
                basePrice = selectedOptions.filter(o => {
                    const group = product.option_groups.find(g => g.id === o.product_option_group_id);
                    return group && group.price_mode === 'fixed';
                }).reduce((sum, o) => sum + parseFloat(o.price), 0);
            }

            const additivePrice = selectedOptions.filter(o => {
                const group = product.option_groups.find(g => g.id === o.product_option_group_id);
                return group && group.price_mode === 'additive';
            }).reduce((sum, o) => sum + parseFloat(o.price), 0);

            const modifiersPrice = selectedModifiers.reduce((sum, m) => sum + parseFloat(m.price), 0);
            
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
        init() {
            this.$watch('cart', value => { 
                if (!value || Object.keys(value).length === 0) {
                    this.cartExpanded = false; 
                }
            });

            this.$watch('isEditMode', () => {
                this.setupProductSortable();
                this.setupCategorySortable();
            });

            const syncProducts = () => {
                const el = document.getElementById('hidden-products-data');
                if (el) {
                    try {
                        this.productsData = JSON.parse(el.getAttribute('data-products'));
                    } catch (e) {
                        console.error('Failed to parse products data', e);
                    }
                }
            };

            syncProducts();

            document.addEventListener('livewire:update', syncProducts);
            document.addEventListener('livewire:navigated', syncProducts);
        }
    }"
    @cart-expanded.window="cartExpanded = true"
    @cart-collapsed.window="cartExpanded = false"
    @cart-toggle.window="cartExpanded = !cartExpanded"
    @cart-reset.window="cartExpanded = false"
>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <div id="hidden-products-data" class="hidden" data-products="{{ $products->keyBy('id')->toJson() }}"></div>

    {{-- ══════════════════════════════════════════════
         FULL-WIDTH CATEGORY TAB CARD
    ══════════════════════════════════════════════ --}}
    <div class="px-3 pt-3 flex-shrink-0">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-3 py-2 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-2">

            {{-- Category Tabs — horizontally scrollable, no wrap --}}
            <div 
                id="category-sortable-tabs"
                class="flex items-center gap-1.5 overflow-x-auto no-scrollbar flex-1 min-w-0"
                x-init="setupCategorySortable()"
            >

                {{-- All Tab --}}
                @if($selectedCategoryId === null)
                    <x-primary-button type="button" wire:key="pos-tab-all" wire:click.prevent="$set('selectedCategoryId', null)" wire:loading.attr="disabled" wire:target="$set('selectedCategoryId', null)" id="pos_tab_all" class="pos-category-tab inline-flex gap-1.5 whitespace-nowrap shrink-0">
                        All
                        <span class="text-[10px] font-bold text-gray-100">{{ $products->count() }}</span>
                    </x-primary-button>
                @else
                    <x-secondary-button type="button" wire:key="pos-tab-all" wire:click.prevent="$set('selectedCategoryId', null)" wire:loading.attr="disabled" wire:target="$set('selectedCategoryId', null)" id="pos_tab_all" class="pos-category-tab inline-flex gap-1.5 whitespace-nowrap shrink-0">
                        All
                        <span class="text-[10px] font-bold text-gray-400">{{ $products->count() }}</span>
                    </x-secondary-button>
                @endif

                @foreach($categories as $cat)
                    @if($selectedCategoryId == $cat->id)
                        <x-primary-button type="button" wire:key="pos-tab-{{ $cat->id }}" wire:click.prevent="$set('selectedCategoryId', {{ $cat->id }})" wire:loading.attr="disabled" wire:target="$set('selectedCategoryId', {{ $cat->id }})" id="pos_tab_cat_{{ $cat->id }}" class="pos-category-tab inline-flex gap-1.5 whitespace-nowrap shrink-0 transition-all {{ $isEditMode ? 'border border-dashed border-gray-300 cursor-move' : '' }}" data-id="{{ $cat->id }}">
                            {{ $cat->name }}
                            <span class="text-[10px] font-bold text-gray-100">{{ $cat->products_count }}</span>
                        </x-primary-button>
                    @else
                        <x-secondary-button type="button" wire:key="pos-tab-{{ $cat->id }}" wire:click.prevent="$set('selectedCategoryId', {{ $cat->id }})" wire:loading.attr="disabled" wire:target="$set('selectedCategoryId', {{ $cat->id }})" id="pos_tab_cat_{{ $cat->id }}" class="pos-category-tab inline-flex gap-1.5 whitespace-nowrap shrink-0 transition-all {{ $isEditMode ? 'border border-dashed border-gray-300 cursor-move' : '' }}" data-id="{{ $cat->id }}">
                            {{ $cat->name }}
                            <span class="text-[10px] font-bold text-gray-400">{{ $cat->products_count }}</span>
                        </x-secondary-button>
                    @endif
                @endforeach
            </div>

            {{-- Search + Held Orders --}}
            <div class="flex items-center gap-2 shrink-0">
                <div class="relative">
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
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 transition-opacity duration-300"
                    id="product-sortable-grid"
                    wire:key="pos-grid-{{ $selectedCategoryId ?? 'all' }}"
                    x-init="setupProductSortable()"
                    wire:loading.class="opacity-60 pointer-events-none"
                    wire:target="selectedCategoryId">
                    @foreach($products as $product)
                        @php
                            $pid      = $product->id;
                            $stocks   = $product->prefetched_stocks ?? null;
                            $availability = $branchId ? $product->availabilityAt((int)$branchId, $stocks) : 'available';
                            $isAvailable  = in_array($availability, ['available', 'low_stock']);
                            $maxAvailable = $branchId ? $product->getMaxAvailableQuantity((int)$branchId, $stocks) : 0;
                            $hasOptions   = count($product->optionGroups) > 0 || count($product->modifiers) > 0;
                            $clickAction  = "openQuickOptions($pid)";
                        @endphp

                        <div wire:key="pos-product-{{ $pid }}"
                            data-id="{{ $pid }}"
                            x-show="!searchQuery || '{{ strtolower(addslashes($product->name)) }}'.includes(searchQuery.toLowerCase().trim())"
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
                                    @if($availability === 'unavailable' || $maxAvailable <= 0)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/90 text-red-600 shadow-sm border border-red-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>Out of Stock
                                        </span>
                                    @elseif($availability === 'low_stock')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/90 text-amber-600 shadow-sm border border-amber-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shrink-0"></span>Low ({{ $maxAvailable }} left)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-white/90 text-emerald-600 shadow-sm border border-emerald-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>{{ $maxAvailable }} left
                                        </span>
                                    @endif
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
                                            x-show="getCartQty({{ $pid }}) >= {{ $maxAvailable }}">
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            Max Qty Reached
                                        </x-danger-button>

                                        {{-- Add More --}}
                                        <x-secondary-button type="button" 
                                            @click="!isEditMode && {{ $clickAction }}" 
                                            x-show="getCartQty({{ $pid }}) > 0 && getCartQty({{ $pid }}) < {{ $maxAvailable }}"
                                            :class="isEditMode ? 'opacity-50 cursor-not-allowed' : ''"
                                            id="pos_addmore_{{ $pid }}" class="w-full justify-center">
                                            Add More (<span x-text="getCartQty({{ $pid }})"></span>/{{ $maxAvailable }})
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
             class="fixed inset-0 bg-gray-900/60 z-[115] backdrop-blur-sm lg:hidden"
             x-cloak>
        </div>

        {{-- RIGHT: Order Summary Card --}}
        <div 
            :class="[
                isMobile ? 'fixed inset-x-0 bottom-0 z-[120] transition-transform duration-500 ease-in-out' : 'w-full md:w-[300px] xl:w-[320px] flex-shrink-0 flex flex-col min-h-0',
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
                        <span class="text-[11px] font-black text-gray-900 font-mono tracking-tighter uppercase">#{{ $referenceNo }}</span>
                        <button type="button" title="Hold Order" class="p-1.5 rounded-lg text-amber-500 hover:bg-amber-50 transition-all" :disabled="Object.keys(cart).length === 0" @click.stop="isSavingDraft = true; $wire.saveDraft().finally(() => { isSavingDraft = false; cartExpanded = false })">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                        </button>
                        <div class="flex items-center md:hidden ml-2">
                             <span class="text-[14px] font-black text-indigo-600 font-mono" x-show="!cartExpanded">{{ $currencySymbol }}{{ number_format($total, 2) }}</span>
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
                                    <img :src="item.image ? '{{ asset('storage') }}/' + item.image : '{{ asset('images/placeholder-product.png') }}'" class="w-full h-full object-cover" :alt="item.name">
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
                                            <button type="button" @click="$wire.editCartItemId = key; $wire.editCartItemQty = cart[key].qty; $wire.editCartItemNotes = cart[key].instructions || ''; $wire.applyRegularDiscount = cart[key].apply_regular_discount || false; $wire.applySeniorDiscount = cart[key].apply_senior_discount || false; $dispatch('open-modal', 'edit-cart-item')" class="relative p-1.5 text-gray-400 hover:text-amber-500 transition-all rounded-lg hover:bg-amber-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                                <template x-if="item.instructions">
                                                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-amber-500 rounded-full border border-white"></span>
                                                </template>
                                            </button>
                                            <button @click="delete cart[key]" class="p-1.5 text-gray-300 hover:text-red-500 transition-all rounded-lg hover:bg-red-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-2.5">
                                        <div class="flex items-center gap-1">
                                            <button @click="if(cart[key].qty > 1) { cart[key].qty-- } else { delete cart[key] }" 
                                                class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all active:scale-95">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                            </button>
                                            <span class="px-1 text-[12px] font-black text-gray-900 min-w-[20px] text-center font-mono" x-text="item.qty"></span>
                                            <button @click="cart[key].qty++" 
                                                class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all active:scale-95">
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
                            <span class="text-[12px] text-gray-500">Service Charge ({{ round($serviceChargeRate * 100) }}%)</span>
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
                                        {{ $orderType }}
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @foreach($orderTypes as $type)
                                    <x-dropdown-link href="#" wire:click.prevent="$set('orderType', '{{ $type }}')" wire:loading.attr="disabled">{{ $type }}</x-dropdown-link>
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
                    <x-primary-button type="button" @click="$wire.amountTendered = total; $wire.paymentReference = ''; $dispatch('open-modal', 'pos-payment')" x-bind:disabled="Object.keys(cart).length === 0" id="pos_confirm_payment_btn" class="w-full mt-2 justify-center">
                        <span x-show="Object.keys(cart).length > 0">Proceed to Payment</span>
                        <span x-show="Object.keys(cart).length === 0">Add Items to Order</span>
                    </x-primary-button>


                    <div x-show="Object.keys(cart).length > 0">
                        <x-secondary-button type="button" @click="cart = {}; $wire.clearCart()" wire:loading.attr="disabled" id="pos_clear_cart_btn" class="w-full mt-1.5 justify-center text-red-600">
                            Clear Order
                        </x-secondary-button>
                    </div>
                </div>

            </div>
        </div>{{-- end RIGHT --}}

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
                 x-show="localProduct" class="p-8 relative">
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
                <div class="space-y-8 mb-8" x-show="localProduct && localProduct.option_groups">
                    <template x-for="group in localProduct ? localProduct.option_groups : []" :key="group.id">
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
                                    <div @click="(localProduct.option_availability || {})[opt.id] > 0 && toggleOpt(group.id, opt.id, group.price_mode === 'additive', group.is_required)" 
                                        class="relative flex flex-col p-4 rounded-lg border-2 cursor-pointer transition-all duration-200 hover:shadow-md"
                                        :class="{
                                            'opacity-50 cursor-not-allowed border-gray-200 bg-gray-50': (localProduct.option_availability || {})[opt.id] <= 0,
                                            'border-indigo-500 bg-indigo-50 shadow-md': isSelected(group.id, opt.id),
                                            'border-gray-200 bg-white hover:border-gray-300': !isSelected(group.id, opt.id) && (localProduct.option_availability || {})[opt.id] > 0
                                        }">

                                        <span class="text-[13px] font-bold text-gray-900" x-text="opt.name"></span>
                                        <span class="text-[13px] font-black text-indigo-600 mt-2" 
                                              x-text="group.price_mode === 'fixed' ? '{{ $currencySymbol }}' + parseFloat(opt.price).toFixed(2) : (opt.price > 0 ? '+' + '{{ $currencySymbol }}' + parseFloat(opt.price).toFixed(2) : 'Free')"></span>
                                        
                                        {{-- Availability Badge --}}
                                        <div class="mt-2 flex items-center justify-between">
                                            <template x-if="(localProduct.option_availability || {})[opt.id] <= 0">
                                                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-1 rounded uppercase tracking-tighter">Out of Stock</span>
                                            </template>
                                            <template x-if="(localProduct.option_availability || {})[opt.id] > 0">
                                                <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded uppercase tracking-tighter" 
                                                      x-text="'Available: ' + (localProduct.option_availability || {})[opt.id]"></span>
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
                            <label @click="(localProduct.modifier_availability || {})[m.id] > 0 && toggleMod(m.id)" 
                                class="flex items-center justify-between p-4 rounded-lg border border-gray-200 cursor-pointer transition-all hover:bg-gray-50 hover:border-gray-300"
                                :class="(localProduct.modifier_availability || {})[m.id] <= 0 ? 'opacity-50 cursor-not-allowed bg-gray-50' : (selectedModifierIds.includes(m.id) ? 'border-indigo-500 bg-indigo-50' : '')">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" :checked="selectedModifierIds.includes(m.id)" :disabled="(localProduct.modifier_availability || {})[m.id] <= 0"
                                        class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 pointer-events-none">
                                    <div>
                                        <span class="text-[13px] font-semibold text-gray-800" x-text="m.name"></span>
                                        <template x-if="(localProduct.modifier_availability || {})[m.id] <= 0">
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
                    <x-primary-button type="button" @click="optimisticAddToCart(localProduct.id, selectedOptions, selectedModifierIds); $dispatch('close-modal', 'pos-options'); localProduct = null" class="flex-1 justify-center">
                        Add to Order
                    </x-primary-button>
                </div>
            </div>
        </x-modal>

{{-- ══════════════════════════════════════════════
     PAYMENT MODAL
══════════════════════════════════════════════ --}}
<x-modal name="pos-payment" maxWidth="4xl" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-gray-800 to-gray-600 rounded-t-xl"></div>
    
    <div class="p-6">
        <h2 class="text-[18px] font-bold text-gray-900 mb-6">Confirm Payment</h2>
        
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

                @if(!empty($cart))
                    <div class="border-t border-gray-100 px-4 py-3 bg-gray-50 space-y-2">
                        <div class="flex items-center justify-between text-[12px]">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-bold text-gray-900">{{ $currencySymbol }}{{ number_format($subtotal, 2) }}</span>
                        </div>
                        @if($discountPercent > 0 && $discountAmount > 0)
                            <div class="flex items-center justify-between text-[12px]">
                                <span class="text-gray-600">Discount</span>
                                <span class="font-bold text-emerald-600">-{{ $currencySymbol }}{{ number_format($discountAmount, 2) }}</span>
                            </div>
                        @endif
                        @if($serviceChargeAmount > 0)
                            <div class="flex items-center justify-between text-[12px]">
                                <span class="text-gray-600">Service Charge ({{ round($serviceChargeRate * 100) }}%)</span>
                                <span class="font-bold text-gray-900">{{ $currencySymbol }}{{ number_format($serviceChargeAmount, 2) }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between text-[13px] font-bold bg-indigo-100 rounded-lg p-2.5 border border-indigo-200">
                            <span class="text-indigo-900">Total</span>
                            <span class="text-indigo-900">{{ $currencySymbol }}{{ number_format($total, 2) }}</span>
                        </div>
                    </div>
                @endif
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
                            <p class="text-[13px] font-bold text-gray-900">{{ $orderType }}</p>
                        </div>
                    </div>

                    {{-- Payment Method --}}
                    <div>
                        <p class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide mb-2">Payment Method</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach($paymentMethods as $method)
                                <button type="button"
                                    @click="paymentMethod = '{{ $method }}'"
                                    :class="paymentMethod === '{{ $method }}' ? 'bg-indigo-600 text-white border-indigo-600 shadow-md' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50'"
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
                            <x-text-input id="pos_amount_tendered" wire:model.blur="amountTendered" x-model="amountTendered" type="number" min="0" step="0.01"
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

                    {{-- GCash Details (PayMongo + Static QR Fallback) --}}
                    <div x-show="paymentMethod === 'GCash'"
                         x-data="{ gcashUrl: null, gcashPaid: @entangle('gcashVerified').live, showStatic: false }"
                         @gcash-url-ready.window="gcashUrl = $event.detail.url; showStatic = false">

                        {{-- Step 1: No URL yet — show options --}}
                        <div x-show="!gcashUrl && !gcashPaid && !showStatic" class="space-y-4"
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
                                    {{-- PayMongo Option --}}
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
                        <div x-show="showStatic && !gcashPaid" class="space-y-4">
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

                            <button type="button" wire:click="verifyStaticPayment" wire:loading.attr="disabled"
                                class="w-full py-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[14px] font-black flex items-center justify-center gap-2 transition-all shadow-lg shadow-emerald-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Verify & Lock Payment
                            </button>
                        </div>

                        {{-- Step 2: URL generated — show dynamic QR --}}
                        <div x-show="gcashUrl && !gcashPaid" class="space-y-4"
                             x-init="
                                let pollInterval = null;
                                const startPolling = () => {
                                    clearInterval(pollInterval);
                                    pollInterval = setInterval(() => {
                                        if (gcashPaid) { clearInterval(pollInterval); return; }
                                        $wire.pollGCashStatus();
                                    }, 4000);
                                };
                                if (gcashUrl && !gcashPaid) startPolling();
                                $watch('gcashUrl', (url) => { url && !gcashPaid ? startPolling() : clearInterval(pollInterval); });
                                $watch('gcashPaid', (paid) => { if (paid) clearInterval(pollInterval); });
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

                                <p class="text-[12px] font-bold text-blue-900">{{ $currencySymbol }}{{ number_format($total, 2) }} due</p>
                                <p class="text-[10px] text-blue-500 mt-0.5">Order #{{ $referenceNo }}</p>

                                <a :href="gcashUrl" target="_blank"
                                   class="mt-3 text-[11px] text-blue-600 font-bold underline underline-offset-2">
                                    Open in browser instead ↗
                                </a>

                                <div class="mt-4 flex items-center gap-2 text-[11px] text-amber-600 font-semibold">
                                    <svg class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    Waiting for customer payment...
                                </div>

                                <x-input-error :messages="$errors->get('gcashVerified')" class="mt-2 text-center" />
                            </div>
                        </div>

                        {{-- Step 3: Paid —  show success --}}
                        <div x-show="gcashPaid" class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100 space-y-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <p class="text-[13px] font-black text-emerald-900 leading-none">GCash Payment Received</p>
                                    @if($isManualGcash)
                                        <p class="text-[11px] text-emerald-600 font-bold mt-1 uppercase tracking-tighter italic">Verified Manually by Cashier</p>
                                    @else
                                        <p class="text-[11px] text-emerald-600 font-bold mt-1 uppercase tracking-tighter">Verified via PayMongo</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                    </div>{{-- end GCash --}}
                </div>{{-- end payment details scrollable --}}
                {{-- Total Due Display --}}
                <div class="border-t border-gray-100 px-4 py-4 bg-gray-900 text-white text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1">Total Due</p>
                    <p class="text-[24px] font-black font-mono">{{ $currencySymbol }}{{ number_format($total, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-3">
            <x-secondary-button type="button" @click="$dispatch('close-modal', 'pos-payment')" class="flex-1 justify-center">
                Cancel
            </x-secondary-button>
            <x-primary-button type="button" wire:click.prevent="confirmPayment" wire:loading.attr="disabled" id="pos_submit_payment_btn" class="flex-1 justify-center">
                Place Order
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

        @if($editCartItemId && isset($cart[$editCartItemId]))
            <div class="mb-6 p-4 bg-gray-50 rounded-xl border border-gray-100">
                <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-1">Applying to</p>
                <p class="text-[14px] font-bold text-gray-900 leading-tight">{{ $cart[$editCartItemId]['name'] }}</p>
            </div>
        @endif

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
                <div x-data="{ isRegularDiscount: @entangle('applyRegularDiscount').live }" 
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
                                <input type="checkbox" wire:model.live="applyRegularDiscount" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>
                    </div>
                </div>
                @endif

                @if($seniorDiscountRate > 0)
                <div x-data="{ isSeniorDiscount: @entangle('applySeniorDiscount').live }" 
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
                                <input type="checkbox" wire:model.live="applySeniorDiscount" class="sr-only peer">
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
                <button @click="isModalOpen = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="max-h-[450px] overflow-y-auto pr-2 custom-scrollbar">
                @forelse($this->drafts as $draft)
                    <div wire:key="draft-item-{{ $draft->id }}" 
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
                                    <x-secondary-button wire:click="deleteDraft({{ $draft->id }})" wire:loading.attr="disabled" class="!px-3 !py-1.5 text-red-500 hover:text-red-700 bg-red-50/50 border-red-100 hover:bg-red-50 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </x-secondary-button>
                                    <x-primary-button wire:click="loadDraft({{ $draft->id }})" wire:loading.attr="disabled" class="!px-5 !py-1.5 shadow-none border-none min-w-[110px] justify-center">
                                        <span wire:loading.remove wire:target="loadDraft({{ $draft->id }})">Load Order</span>
                                        <span wire:loading wire:target="loadDraft({{ $draft->id }})" class="flex items-center justify-center gap-1.5">
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
                <x-secondary-button @click="isModalOpen = false" class="w-full justify-center py-3">
                    Close Monitor
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>{{-- end modals container --}}

</div>{{-- end outer container --}}
