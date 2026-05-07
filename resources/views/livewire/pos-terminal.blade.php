<div class="flex flex-col h-full overflow-hidden bg-gray-100" style="min-height: calc(100vh - 65px);" wire:key="pos-terminal-root">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>

    {{-- ══════════════════════════════════════════════
         FULL-WIDTH CATEGORY TAB CARD
    ══════════════════════════════════════════════ --}}
    <div class="px-3 pt-3 flex-shrink-0">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-3 py-2 flex items-center gap-2">

            {{-- Category Tabs — horizontally scrollable, no wrap --}}
            <div 
                id="category-sortable-tabs"
                class="flex items-center gap-1.5 overflow-x-auto no-scrollbar flex-1 min-w-0"
                x-data="{ 
                    isEditMode: @entangle('isEditMode'),
                    sortable: null,
                    setup() {
                        if (!this.isEditMode) {
                            if (this.sortable) { this.sortable.destroy(); this.sortable = null; }
                            return;
                        }
                        const el = document.getElementById('category-sortable-tabs');
                        if (!el || this.sortable) return;
                        
                        this.sortable = new Sortable(el, {
                            draggable: '.pos-category-tab',
                            filter: '#pos_tab_all',
                            animation: 150,
                            ghostClass: 'bg-' + @js($primaryColor) + '-50',
                            onEnd: (evt) => {
                                const ids = Array.from(el.querySelectorAll('.pos-category-tab'))
                                    .map(tab => tab.dataset.id)
                                    .filter(id => id);
                                @this.reorderCategories(ids);
                            }
                        });
                    }
                }"
                x-init="setup(); $watch('isEditMode', () => setup())"
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
                    <input id="pos_search" wire:model="search" type="text" placeholder="Search Menu"
                        class="pl-8 pr-3 py-1.5 w-36 text-[12px] font-medium text-gray-700 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-0 focus:border-gray-300 placeholder-gray-400 transition-all focus:w-44">
                </div>

                <button wire:click.prevent="openDraftsModal" title="Held Orders" class="relative p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-all border border-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    @if($this->drafts->count() > 0)
                        <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-black text-white shadow-sm ring-2 ring-white animate-bounce">{{ $this->drafts->count() }}</span>
                    @endif
                </button>

                @if(auth()->user()->role_id === 1 || auth()->user()->role_id === 2)
                <button wire:click.prevent="toggleEditMode" title="{{ $isEditMode ? 'Save Layout' : 'Edit Layout' }}" 
                    class="h-9 px-3 rounded-lg transition-all border flex items-center gap-2 {{ $isEditMode ? 'bg-emerald-500 text-white border-emerald-600 shadow-lg' : 'bg-white text-slate-600 hover:bg-slate-50 border-slate-200 shadow-sm' }}">
                    @if($isEditMode)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        <span class="text-[11px] font-black uppercase tracking-wider">Done</span>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        <span class="text-[11px] font-black uppercase tracking-wider">Sort</span>
                    @endif
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         CONTENT ROW — Product Grid + Order Summary
    ══════════════════════════════════════════════ --}}
    <div class="flex flex-1 overflow-hidden gap-3 p-3 min-h-0">

        {{-- LEFT: Product Grid --}}
        <div class="flex-1 overflow-y-auto min-w-0">

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
                    wire:key="pos-grid-{{ $selectedCategoryId ?? 'all' }}-{{ $search }}-{{ $isEditMode ? 'edit' : 'view' }}"
                    x-data="{ 
                        isEditMode: @entangle('isEditMode'),
                        sortable: null,
                        setup() {
                            if (!this.isEditMode) {
                                if (this.sortable) { this.sortable.destroy(); this.sortable = null; }
                                return;
                            }
                            const el = document.getElementById('product-sortable-grid');
                            if (!el || this.sortable) return;
                            
                            this.sortable = new Sortable(el, {
                                draggable: '.product-card',
                                handle: '.drag-handle',
                                animation: 150,
                                ghostClass: 'bg-' + @js($primaryColor) + '-50',
                                chosenClass: 'shadow-2xl',
                                dragClass: 'opacity-0',
                                onEnd: (evt) => {
                                    const ids = Array.from(el.querySelectorAll('.product-card')).map(card => card.dataset.id);
                                    @this.reorderProducts(ids);
                                }
                            });
                        }
                    }"
                    x-init="setup(); $watch('isEditMode', () => setup())"
                    wire:loading.class="opacity-60 pointer-events-none"
                    wire:target="selectedCategoryId, search">
                    @foreach($products as $product)
                        @php
                            $pid      = $product->id;
                            $cartKey  = (string) $pid;
                            $inCart   = isset($cart[$cartKey]);
                            $cartQty  = $inCart ? $cart[$cartKey]['qty'] : 0;
                            $stocks   = $product->prefetched_stocks ?? null;
                            $availability = $branchId ? $product->availabilityAt((int)$branchId, $stocks) : 'available';
                            $isAvailable  = in_array($availability, ['available', 'low_stock']);
                            $maxAvailable = $branchId ? $product->getMaxAvailableQuantity((int)$branchId, $stocks) : 0;
                        @endphp

                        <div wire:key="pos-product-{{ $pid }}"
                            data-id="{{ $pid }}"
                            class="product-card group relative bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden flex flex-col transition-all duration-300 hover:shadow-md {{ !$isAvailable ? 'opacity-75' : '' }} {{ $isEditMode ? 'opacity-90 grayscale-[0.2] scale-[0.98]' : '' }}">

                            {{-- Product Image --}}
                            <div class="relative h-[120px] overflow-hidden bg-gray-100">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}"
                                        class="w-full h-full object-cover {{ !$isAvailable ? 'grayscale' : '' }}">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br {{ $gradients[$pid % count($gradients)] }} flex items-center justify-center text-white/40 font-black text-5xl">
                                        {{ strtoupper(substr($product->name, 0, 1)) }}
                                    </div>
                                @endif

                                @if($isEditMode)
                                    <div class="drag-handle absolute top-2 left-2 z-20 bg-white/90 backdrop-blur-md p-2 rounded-lg shadow-lg border border-gray-200 cursor-move hover:scale-110 transition-transform flex items-center justify-center">
                                        <svg class="w-5 h-5 text-{{ $primaryColor }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                    </div>
                                @endif

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
                                @if($maxAvailable <= 0)
                                    <x-danger-button type="button" disabled class="w-full justify-center mt-auto">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Out of Stock
                                    </x-danger-button>
                                @elseif($cartQty >= $maxAvailable)
                                    <x-danger-button type="button" disabled class="w-full justify-center mt-auto">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Max Qty Reached
                                    </x-danger-button>
                                @elseif($inCart)
                                    <x-secondary-button type="button" wire:click.prevent="{{ $isEditMode ? '' : 'addToCart(' . $pid . ')' }}" wire:loading.attr="disabled" wire:target="addToCart({{ $pid }})" id="pos_addmore_{{ $pid }}" class="w-full justify-center mt-auto {{ $isEditMode ? 'opacity-50 cursor-not-allowed' : '' }}">
                                        Add More ({{ $cartQty }}/{{ $maxAvailable }})
                                    </x-secondary-button>
                                @else
                                    <x-primary-button type="button" wire:click.prevent="{{ $isEditMode ? '' : 'addToCart(' . $pid . ')' }}" wire:loading.attr="disabled" wire:target="addToCart({{ $pid }})" id="pos_addcart_{{ $pid }}" class="w-full justify-center mt-auto {{ $isEditMode ? 'opacity-50 cursor-not-allowed' : '' }}">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                        </svg>
                                        Add to Cart
                                    </x-primary-button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>{{-- end LEFT --}}

        {{-- RIGHT: Order Summary Card --}}
        <div class="w-[300px] xl:w-[320px] flex-shrink-0 flex flex-col min-h-0">
            <div class="flex flex-col bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex-1 min-h-0">

                {{-- Card Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 flex-shrink-0 bg-gray-50/20">
                    <h2 class="text-[15px] font-black text-gray-900 tracking-tight">Order Summary</h2>
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-black text-gray-900 font-mono tracking-tighter uppercase">#{{ $referenceNo }}</span>
                        <button wire:click.prevent="saveDraft" title="Hold Order" class="p-1.5 rounded-lg text-amber-500 hover:bg-amber-50 transition-all" @if(empty($cart)) disabled @endif>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>
                        </button>
                    </div>
                </div>

                {{-- Cart Items --}}
                <div class="flex-1 overflow-y-auto px-3 py-1 divide-y divide-gray-50 min-h-0">
                    @forelse($cart as $key => $item)
                        @php
                            $thumbColors = ['bg-amber-100 text-amber-600','bg-emerald-100 text-emerald-600','bg-rose-100 text-rose-600','bg-indigo-100 text-indigo-600'];
                        @endphp
                        <div wire:key="cart-item-{{ $key }}" class="flex items-start gap-2.5 py-3">
                            {{-- Thumbnail --}}
                            <div class="w-11 h-11 rounded-lg overflow-hidden bg-gray-100 shrink-0 border border-gray-100">
                                @if($item['image'])
                                    <img src="{{ asset('storage/' . $item['image']) }}" class="w-full h-full object-cover" alt="{{ $item['name'] }}">
                                @else
                                    <div class="w-full h-full flex items-center justify-center font-black text-lg {{ $thumbColors[$loop->index % count($thumbColors)] }}">
                                        {{ strtoupper(substr($item['name'], 0, 1)) }}
                                    </div>
                                @endif
                            </div>

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-1">
                                    <div class="min-w-0">
                                        <p class="text-[12px] font-bold text-gray-800 leading-tight">
                                            {{ $item['name'] }}
                                        </p>
                                        @if(!empty($item['options']))
                                            @php
                                                $optionNames = collect($item['options'])->pluck('name')->join(', ');
                                            @endphp
                                            <p class="text-[10px] font-bold text-indigo-500 mt-0.5 uppercase tracking-tighter">{{ $optionNames }}</p>
                                        @endif
                                        @if(!empty($item['modifiers']))
                                            @foreach($item['modifiers'] as $mod)
                                                <p class="text-[9px] text-gray-400 italic font-medium">+ {{ $mod['name'] }}</p>
                                            @endforeach
                                        @endif
                                        
                                        {{-- Special Instructions Display --}}
                                        @if(!empty($item['instructions']))
                                            <p class="text-[10px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded border border-amber-100 mt-1 italic leading-tight">
                                                "{{ $item['instructions'] }}"
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-0.5 shrink-0">
                                        {{-- Note/Instruction Icon --}}
                                        <button wire:click.prevent="openEditItem('{{ $key }}')" class="relative p-1.5 text-gray-400 hover:text-amber-500 transition-all rounded-lg hover:bg-amber-50">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                                            @if(!empty($item['instructions']))
                                                <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-amber-500 rounded-full border border-white"></span>
                                            @endif
                                        </button>
                                        <button wire:click.prevent="removeFromCart('{{ $key }}')" wire:loading.attr="disabled" wire:target="removeFromCart('{{ $key }}')" class="p-1.5 text-gray-300 hover:text-red-500 transition-all rounded-lg hover:bg-red-50">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-between mt-2.5">
                                    {{-- Seamless Qty Stepper --}}
                                    <div class="flex items-center gap-1">
                                        <button wire:click.prevent="decrementCart('{{ $key }}')" wire:loading.attr="disabled" wire:target="decrementCart('{{ $key }}')" class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-all active:scale-95">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"/></svg>
                                        </button>
                                        <span class="px-1 text-[12px] font-black text-gray-900 min-w-[20px] text-center font-mono">{{ $item['qty'] }}</span>
                                        <button wire:click.prevent="incrementCart('{{ $key }}')" wire:loading.attr="disabled" wire:target="incrementCart('{{ $key }}')" class="w-7 h-7 flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all active:scale-95">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                    </div>

                                    <div class="flex items-center">
                                        <span class="text-[13px] font-black text-gray-900 font-mono tracking-tight">{{ $currencySymbol }}{{ number_format($item['price'] * $item['qty'], 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex flex-col items-center justify-center py-10 text-gray-300">
                            <svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="text-[13px] font-medium text-gray-400">Cart is empty</p>
                            <p class="text-[11px] text-gray-300 mt-0.5">Add items from the menu</p>
                        </div>
                    @endforelse
                </div>

                {{-- Totals + Order Meta --}}
                <div class="border-t border-gray-100 px-4 pt-3 pb-4 space-y-2 flex-shrink-0">

                    <div class="flex items-center justify-between">
                        <span class="text-[12px] text-gray-500">Subtotal</span>
                        <span class="text-[12px] font-bold text-gray-900 font-mono">{{ $currencySymbol }}{{ number_format($subtotal, 2) }}</span>
                    </div>

                    @if($discountAmount > 0)
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[12px] text-emerald-600 font-medium">Discount Applied</span>
                            <span class="text-[12px] font-bold text-emerald-600 font-mono">-{{ $currencySymbol }}{{ number_format($discountAmount, 2) }}</span>
                        </div>
                    @endif


                    @if($serviceChargeAmount > 0)
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[12px] text-gray-500">Service Charge ({{ round($serviceChargeRate * 100) }}%)</span>
                            <span class="text-[12px] font-bold text-gray-900 font-mono">{{ $currencySymbol }}{{ number_format($serviceChargeAmount, 2) }}</span>
                        </div>
                    @endif

                    <div class="h-px bg-gray-50 my-1"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-[13px] font-bold text-gray-900">Total Amount</span>
                        <span class="text-[14px] font-black text-gray-900 font-mono">{{ $currencySymbol }}{{ number_format($total, 2) }}</span>
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
                            <input type="text" wire:model.debounce.400ms="tableNumber" placeholder="Table #"
                                inputFilter="name_basic"
                                class="text-[12px] font-bold text-gray-800 bg-transparent border-0 p-0 focus:ring-0 text-right w-24 placeholder-gray-300 {{ $errors->has('tableNumber') ? 'text-red-500 placeholder-red-300' : '' }}">
                        </div>

                     {{-- Confirm Payment --}}
                    <x-primary-button type="button" wire:click.prevent="openPayment" wire:loading.attr="disabled" id="pos_confirm_payment_btn" :disabled="empty($cart)" class="w-full mt-2 justify-center">
                        {{ empty($cart) ? 'Add Items to Order' : 'Confirm Payment' }}
                    </x-primary-button>


                    @if(!empty($cart))
                        <x-secondary-button type="button" wire:click.prevent="clearCart" wire:loading.attr="disabled" id="pos_clear_cart_btn" class="w-full mt-1.5 justify-center text-red-600">
                            Clear Order
                        </x-secondary-button>
                    @endif
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
    @if($currentProduct)
        <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-blue-600 rounded-t-xl"></div>
        <div class="p-8">
            <div class="mb-8">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-[22px] font-black text-gray-900">{{ $currentProduct->name }}</h2>
                        <p class="text-[13px] text-gray-500 mt-1">Customize your order</p>
                    </div>
                    @if($currentProduct->image)
                        <img src="{{ asset('storage/' . $currentProduct->image) }}" alt="{{ $currentProduct->name }}" 
                            class="w-20 h-20 rounded-lg object-cover border border-gray-100 shadow-sm">
                    @endif
                </div>
            </div>

            {{-- Option Groups --}}
            @if(count($currentProduct->optionGroups) > 0)
                <div class="space-y-8 mb-8">
                    @foreach($currentProduct->optionGroups as $group)
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-[14px] font-bold text-gray-900">{{ $group->name }}</p>
                                    @if($group->is_required)
                                        <span class="text-[11px] font-semibold text-red-600 mt-0.5">Required selection</span>
                                    @endif
                                    @if($group->price_mode === 'additive')
                                        <span class="text-[11px] font-semibold text-blue-600 mt-0.5">Multiple allowed</span>
                                    @endif
                                </div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                @foreach($group->options as $opt)
                                    @php
                                        $optAvailable = $optionAvailability[$opt->id] ?? 0;
                                        $isOutOfStock = $optAvailable <= 0;
                                        $isSelected = is_array($selectedOptions[$group->id] ?? null) && in_array($opt->id, $selectedOptions[$group->id]);
                                    @endphp
                                    <div wire:click="toggleOption({{ $group->id }}, {{ $opt->id }})" 
                                        class="relative flex flex-col p-4 rounded-lg border-2 cursor-pointer transition-all duration-200 hover:shadow-md
                                        {{ $isOutOfStock ? 'opacity-50 cursor-not-allowed border-gray-200 bg-gray-50' : ($isSelected ? 'border-indigo-500 bg-indigo-50 shadow-md' : 'border-gray-200 bg-white hover:border-gray-300') }}">

                                        <span class="text-[13px] font-bold text-gray-900">{{ $opt->name }}</span>
                                        @if($group->price_mode === 'fixed')
                                            <span class="text-[13px] font-black text-indigo-600 mt-2">{{ $currencySymbol }}{{ number_format($opt->price, 2) }}</span>
                                        @else
                                            <span class="text-[13px] font-black text-indigo-600 mt-2">
                                                {{ $opt->price > 0 ? '+' . $currencySymbol . number_format($opt->price, 2) : 'Free' }}
                                            </span>
                                        @endif
                                        
                                        {{-- Availability Badge --}}
                                        <div class="mt-2 flex items-center justify-between">
                                            @if($isOutOfStock)
                                                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-1 rounded uppercase tracking-tighter">Out of Stock</span>
                                            @else
                                                <span class="text-[10px] font-bold text-green-600 bg-green-50 px-2 py-1 rounded uppercase tracking-tighter">Available: {{ $optAvailable }}</span>
                                            @endif
                                        </div>
                                        
                                        @if($isSelected)
                                            <div class="absolute top-2 right-2">
                                                @if($group->price_mode === 'additive')
                                                    <div class="w-5 h-5 bg-indigo-500 rounded flex items-center justify-center shadow-md">
                                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                    </div>
                                                @else
                                                    <div class="w-5 h-5 bg-indigo-500 rounded-full flex items-center justify-center shadow-md">
                                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Modifiers --}}
            @if(count($currentProduct->modifiers) > 0)
                <div class="mb-8">
                    <p class="text-[14px] font-bold text-gray-900 mb-4">Add-ons (Optional)</p>
                    <div class="space-y-2">
                        @foreach($currentProduct->modifiers as $m)
                            @php
                                $modAvailable = $modifierAvailability[$m->id] ?? 0;
                                $isModOutOfStock = $modAvailable <= 0;
                            @endphp
                            <label class="flex items-center justify-between p-4 rounded-lg border border-gray-200 cursor-pointer transition-all hover:bg-gray-50 hover:border-gray-300 {{ $isModOutOfStock ? 'opacity-50 cursor-not-allowed bg-gray-50' : '' }}">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" wire:model="selectedModifierIds" value="{{ $m->id }}" {{ $isModOutOfStock ? 'disabled' : '' }}
                                        class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <div>
                                        <span class="text-[13px] font-semibold text-gray-800">{{ $m->name }}</span>
                                        @if($isModOutOfStock)
                                            <span class="ml-2 text-[9px] font-black bg-red-100 text-red-600 px-1.5 py-0.5 rounded uppercase tracking-widest">Out of Stock</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-[12px] font-bold text-gray-900 font-mono">+{{ $currencySymbol }}{{ number_format($m->price, 2) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex gap-3 pt-2 border-t border-gray-100">
                <x-secondary-button type="button" wire:click.prevent="closeOptionsModal" wire:loading.attr="disabled" class="flex-1 justify-center">
                    Cancel
                </x-secondary-button>
                <x-primary-button type="button" wire:click.prevent="confirmAddWithOptions" wire:loading.attr="disabled" class="flex-1 justify-center">
                    Add to Order
                </x-primary-button>
            </div>
        </div>
    @endif
</x-modal>

{{-- ══════════════════════════════════════════════
     PAYMENT MODAL
══════════════════════════════════════════════ --}}
<x-modal name="pos-payment" maxWidth="4xl" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-gray-800 to-gray-600 rounded-t-xl"></div>
    
    <div class="p-6">
        <h2 class="text-[18px] font-bold text-gray-900 mb-6">Confirm Payment</h2>
        
        <div class="grid grid-cols-2 gap-6 mb-6">
            {{-- LEFT CARD: Order Summary --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                    <h3 class="text-[13px] font-bold text-gray-900">Order Summary</h3>
                </div>
                
                <div class="flex-1 overflow-y-auto px-4 py-3 max-h-[50vh]">
                    @if(!empty($cart))
                        <div class="space-y-3">
                            @foreach($cart as $key => $item)
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 hover:border-gray-200 transition-all">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-[12px] font-bold text-gray-900 truncate">{{ $item['name'] }}</p>
                                            @if(!empty($item['options']))
                                                <p class="text-[10px] text-gray-500 mt-0.5 line-clamp-2">
                                                    {{ collect($item['options'])->pluck('name')->join(', ') }}
                                                </p>
                                            @endif
                                            <div class="flex items-center justify-between mt-2 text-[11px] text-gray-600">
                                                <span>× {{ $item['qty'] }}</span>
                                                <span class="font-bold text-gray-900">{{ $currencySymbol }}{{ number_format($item['price'] * $item['qty'], 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-400">
                            <p class="text-[12px]">No items in cart</p>
                        </div>
                    @endif
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
                                @if($paymentMethod === $method)
                                    <x-primary-button type="button" wire:click.prevent="$set('paymentMethod', '{{ $method }}')" wire:loading.attr="disabled" id="pos_pay_method_{{ strtolower($method) }}" class="flex-1 justify-center">
                                        {{ $method }}
                                    </x-primary-button>
                                @else
                                    <x-secondary-button type="button" wire:click.prevent="$set('paymentMethod', '{{ $method }}')" wire:loading.attr="disabled" id="pos_pay_method_{{ strtolower($method) }}" class="flex-1 justify-center">
                                        {{ $method }}
                                    </x-secondary-button>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Cash: Amount Tendered + Change --}}
                    @if($paymentMethod === 'Cash')
                        <div class="space-y-4">
                            <div>
                                <x-input-label for="pos_amount_tendered" class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide mb-2 block">Amount Tendered</x-input-label>
                                <x-text-input id="pos_amount_tendered" wire:model.lazy="amountTendered" type="number" min="0" step="0.01"
                                    class="block w-full text-[14px] py-2.5 px-3 font-mono font-bold" placeholder="0.00"/>
                            </div>
                            
                            {{-- Quick Tenders --}}
                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" wire:click="$set('amountTendered', {{ $total }})" class="py-2 px-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-[11px] font-bold rounded-lg transition-colors">Exact</button>
                                <button type="button" wire:click="$set('amountTendered', 100)" class="py-2 px-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-[11px] font-bold font-mono rounded-lg transition-colors">{{ $currencySymbol }}100</button>
                                <button type="button" wire:click="$set('amountTendered', 500)" class="py-2 px-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-[11px] font-bold font-mono rounded-lg transition-colors">{{ $currencySymbol }}500</button>
                                <button type="button" wire:click="$set('amountTendered', 1000)" class="py-2 px-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-[11px] font-bold font-mono rounded-lg transition-colors">{{ $currencySymbol }}1000</button>
                            </div>

                            @if($amountTendered >= $total)
                                <div class="flex items-center justify-between bg-emerald-50 rounded-lg px-3 py-3 border border-emerald-200">
                                    <span class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wide">Change</span>
                                    <span class="text-[14px] font-black text-emerald-700 font-mono">{{ $currencySymbol }}{{ number_format($change, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- GCash Details --}}
                    @if($paymentMethod === 'GCash')
                        <div class="space-y-4">
                            @if(!$gcashVerified)
                                <div class="bg-blue-50 rounded-2xl p-6 border border-blue-100 flex flex-col items-center text-center">
                                    <p class="text-[11px] font-black text-blue-600 uppercase tracking-widest mb-4">Scan QR to Pay</p>
                                    
                                    <div class="bg-white p-4 rounded-2xl shadow-sm border border-blue-200 mb-4">
                                        @if($gcashQrImage)
                                            <img src="{{ Storage::url($gcashQrImage) }}" alt="GCash QR" class="w-48 h-48 object-contain">
                                        @else
                                            @php
                                                $qrData = "gcash://pay?amount=" . $total . "&merchant=" . urlencode($gcashAccountName) . "&number=" . $gcashAccountNumber . "&ref=" . $referenceNo;
                                                $qrImage = \App\Helpers\QrCodeHelper::generateDataUri($qrData, 150);
                                            @endphp
                                            <img src="{{ $qrImage }}" alt="GCash QR" class="w-32 h-32">
                                        @endif
                                    </div>
                                    
                                    <p class="text-[13px] font-bold text-blue-900 leading-tight">{{ $gcashAccountName }}</p>
                                    @if($gcashAccountNumber)
                                        <p class="text-[11px] text-blue-600 font-mono mt-1">{{ $gcashAccountNumber }}</p>
                                    @endif
                                    <p class="text-[11px] text-blue-500 font-medium mt-1">Total Due: {{ $currencySymbol }}{{ number_format($total, 2) }}</p>
                                    
                                    <div class="mt-6 w-full">
                                        <button type="button" wire:click="verifyGCashPayment" wire:loading.attr="disabled"
                                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[13px] font-bold flex items-center justify-center gap-2 transition-all shadow-lg shadow-blue-200 disabled:opacity-50">
                                            @if($isVerifyingGCash)
                                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                Checking Status...
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                Verify Transaction
                                            @endif
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="relative">
                                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                                    <div class="relative flex justify-center text-[10px] uppercase tracking-widest font-black text-gray-300 bg-white px-2">OR MANUAL ENTRY</div>
                                </div>

                                <div>
                                    <x-input-label for="pos_gcash_reference" class="text-[11px] font-bold text-gray-600 uppercase tracking-wide mb-2 block">Reference Number</x-input-label>
                                    <x-text-input id="pos_gcash_reference" wire:model.debounce.400ms="paymentReference" type="text"
                                        inputFilter="name_basic"
                                        class="block w-full text-[13px] py-2.5 px-3 font-mono font-bold border-gray-200 bg-gray-50 focus:bg-white" placeholder="Enter manually if needed"/>
                                </div>
                            @else
                                <div class="bg-emerald-50 rounded-2xl p-5 border border-emerald-100 space-y-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                        <div>
                                            <p class="text-[13px] font-black text-emerald-900 leading-none">Payment Verified</p>
                                            <p class="text-[11px] text-emerald-600 font-bold mt-1 uppercase tracking-tighter">Legitimate Transaction</p>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-white rounded-xl p-4 border border-emerald-100 space-y-2 shadow-sm">
                                        <div class="flex justify-between text-[11px]">
                                            <span class="text-gray-400 font-bold">REFERENCE</span>
                                            <span class="text-gray-900 font-mono font-black">{{ $gcashTransactionDetails['reference'] }}</span>
                                        </div>
                                        <div class="flex justify-between text-[11px]">
                                            <span class="text-gray-400 font-bold">AMOUNT PAID</span>
                                            <span class="text-emerald-600 font-black">{{ $currencySymbol }}{{ number_format($gcashTransactionDetails['amount'], 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-[11px]">
                                            <span class="text-gray-400 font-bold">TIMESTAMP</span>
                                            <span class="text-gray-700 font-bold">{{ $gcashTransactionDetails['timestamp'] }}</span>

                                        </div>
                                    </div>

                                    <button type="button" wire:click="$set('gcashVerified', false)" class="w-full py-2 text-[11px] font-bold text-gray-400 hover:text-gray-600 transition-colors uppercase tracking-widest">
                                        Reset Payment
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Total Due Display --}}
                <div class="border-t border-gray-100 px-4 py-4 bg-gray-900 text-white text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mb-1">Total Due</p>
                    <p class="text-[24px] font-black font-mono">{{ $currencySymbol }}{{ number_format($total, 2) }}</p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex gap-3">
            <x-secondary-button type="button" wire:click.prevent="closePaymentModal" wire:loading.attr="disabled" class="flex-1 justify-center">
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
            <textarea id="pos_edit_item_notes" wire:model.debounce.400ms="editCartItemNotes" rows="4" 
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
                <div class="bg-gray-50 rounded-xl px-4 py-3 border border-blue-100 shadow-sm transition-all duration-300 {{ !$applyRegularDiscount ? 'opacity-60 grayscale' : 'ring-2 ring-indigo-500/20' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg {{ $applyRegularDiscount ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-200 text-gray-500' }} flex items-center justify-center shrink-0 border border-current/10">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M17 17h.01M7 17L17 7"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[12px] font-black tracking-tighter uppercase {{ $applyRegularDiscount ? 'text-indigo-600' : 'text-gray-500' }} truncate">
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
                <div class="bg-gray-50 rounded-xl px-4 py-3 border border-purple-100 shadow-sm transition-all duration-300 {{ !$applySeniorDiscount ? 'opacity-60 grayscale' : 'ring-2 ring-purple-500/20' }}">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg {{ $applySeniorDiscount ? 'bg-purple-100 text-purple-600' : 'bg-gray-200 text-gray-500' }} flex items-center justify-center shrink-0 border border-current/10">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[12px] font-black tracking-tighter uppercase {{ $applySeniorDiscount ? 'text-purple-600' : 'text-gray-500' }} truncate">
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
            <button wire:click.prevent="closeEditItemModal" class="w-full py-3 rounded-2xl text-[13px] font-bold text-gray-500 bg-gray-100 hover:bg-gray-200 transition-all">
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
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-all">
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
                                    <x-primary-button wire:click="loadDraft({{ $draft->id }})" wire:loading.attr="disabled" class="!px-5 !py-1.5 shadow-none border-none">
                                        Load Order
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
                <x-secondary-button @click="show = false" class="w-full justify-center py-3">
                    Close Monitor
                </x-secondary-button>
            </div>
        </div>
    </x-modal>
</div>{{-- end modals container --}}

</div>{{-- end outer container --}}
