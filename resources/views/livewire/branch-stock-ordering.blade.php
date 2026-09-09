<div class="px-2 py-2 space-y-6" x-data="slidingTabs({ panel: @entangle('panel') }, ['panel'])" wire:ignore.self wire:init="loadRestockSuggestions" wire:poll.30s>
    {{-- ════════════════ DYNAMIC HEADER ════════════════ --}}
    <div class="px-1 pt-2">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight leading-none mb-1">Request Supplies</h2>
                <p class="text-[11px] text-gray-500 font-medium">Branch Logistics: <span class="text-indigo-600 font-bold">{{ $kpis['pending'] + $kpis['approved'] + $kpis['in_transit'] }} active</span></p>
            </div>
            <div x-show="panel === 'requests' || panel === 'history'" class="animate-fadeIn">
                <x-report-dropdown module="Branch Stock Report" />
            </div>
        </div>

        <x-sliding-tabs model="panel" class="mb-6">
            <x-sliding-tab model="panel" value="requests">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg></x-slot>
                Active Requests
                @if($kpis['pending'] > 0)
                    <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-amber-500 text-white text-[9px] font-black ml-1.5 animate-pulse">{{ $kpis['pending'] }}</span>
                @endif
            </x-sliding-tab>
            <x-sliding-tab model="panel" value="new">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg></x-slot>
                New Request
            </x-sliding-tab>
            <x-sliding-tab model="panel" value="history">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot>
                History
            </x-sliding-tab>
            <x-sliding-tab model="panel" value="analytics">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></x-slot>
                Analytics
            </x-sliding-tab>
        </x-sliding-tabs>
    </div>

    <!-- KPI Metrics (Premium Redesigned) -->
    <div x-show="panel !== 'analytics'" class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
        @foreach([
            ['title' => 'Pending', 'value' => $kpis['pending'], 'color' => 'amber', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'meta' => 'Awaiting HQ approval'],
            ['title' => 'Processing', 'value' => $kpis['approved'], 'color' => 'indigo', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', 'meta' => 'Being prepared/packed'],
            ['title' => 'In Transit', 'value' => $kpis['in_transit'], 'color' => 'cyan', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'meta' => 'Dispatched from warehouse'],
            ['title' => 'Delivered', 'value' => $kpis['delivered'], 'color' => 'emerald', 'icon' => 'M5 13l4 4L19 7', 'meta' => 'Received by branch']
        ] as $kpi)
            <div class="p-3 sm:p-4 bg-gradient-to-br from-{{ $kpi['color'] }}-500/10 via-{{ $kpi['color'] }}-500/5 to-white border border-{{ $kpi['color'] }}-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ $kpi['title'] }}</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-{{ $kpi['color'] }}-100 flex items-center justify-center text-{{ $kpi['color'] }}-600 shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $kpi['icon'] }}"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($kpi['value']) }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">{{ $kpi['meta'] }}</p>
            </div>
        @endforeach
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="relative min-h-[500px]">
        
        {{-- PANEL: New Request --}}
        <div wire:key="panel-new" x-show="panel === 'new'" class="space-y-4 animate-fadeIn px-1">
            

            {{-- Restock Suggestions (always visible, preloaded) --}}
            <div class="bg-slate-900 rounded-3xl p-4 text-white overflow-hidden">
                <div class="flex items-center justify-between mb-3 px-2">
                    <h3 class="text-[11px] font-black uppercase tracking-[0.1em] flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                        Restock Suggestions
                    </h3>
                    <span class="text-[9px] text-white/40 font-bold uppercase italic">Quick-add suggestions based on low stock</span>
                </div>

                {{-- Skeleton Loading --}}
                @if(!$restockLoaded)
                    <div wire:key="suggestions-skeleton" class="flex gap-3 overflow-x-auto pb-1">
                        @for($i = 0; $i < 5; $i++)
                            <div wire:key="suggestions-skeleton-item-{{ $i }}" class="flex-shrink-0 w-56 h-[62px] bg-white/10 rounded-2xl animate-pulse"></div>
                        @endfor
                    </div>

                {{-- Data Loaded: has suggestions --}}
                @elseif(count($restockSuggestions) > 0)
                    <div wire:key="suggestions-loaded" class="flex gap-3 overflow-x-auto pb-1 custom-scrollbar-slate scroll-smooth">
                        @foreach($restockSuggestions as $item)
                            @php $inCart = collect($cartItems)->contains('ingredient_id', $item['id']); @endphp
                            <div wire:key="suggestion-item-{{ $item['id'] }}-{{ $inCart ? 'in' : 'out' }}" class="flex-shrink-0 w-56 bg-white/5 hover:bg-white/10 border border-white/10 p-3 rounded-2xl flex items-center justify-between transition-all cursor-pointer group/item" wire:click="addLowStockToCart({{ $item['id'] }})">
                                <div class="min-w-0 pr-2">
                                    <p class="text-[12px] font-bold truncate">{{ $item['name'] }}</p>
                                    <p class="text-[9px] text-white/40 font-black uppercase mt-0.5">Need: <span class="text-amber-400">{{ number_format($item['deficit'], 1) }}</span></p>
                                </div>
                                <div class="p-1.5 {{ $inCart ? 'bg-emerald-500' : 'bg-white/10 group-hover/item:bg-indigo-500' }} rounded-lg transition-all">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="{{ $inCart ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4' }}"/></svg>
                                </div>
                            </div>
                        @endforeach
                    </div>

                {{-- Data Loaded: all stock healthy --}}
                @else
                    <div wire:key="suggestions-healthy" class="flex items-center justify-center gap-2 py-4 opacity-40">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-[10px] font-black uppercase">All stock levels are healthy</span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left Column: Compact Entry Form --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-[2rem] border border-slate-200/60 shadow-sm overflow-hidden flex flex-col h-full">
                        <div class="p-5 border-b border-slate-50 bg-slate-50/30">
                            <h3 class="text-[13px] font-black text-slate-900 uppercase tracking-widest flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-lg"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg></div>
                                Add Item
                            </h3>
                        </div>

                        <div class="p-5 space-y-5">
                            <div>
                                <x-input-label value="Select Ingredient" class="text-[11px]" />
                                <div class="mt-1.5">
                                    <x-dropdown align="left" width="full" containerClasses="block w-full">
                                        <x-slot name="trigger">
                                            <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 hover:border-slate-300 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all h-10">
                                                <span class="font-bold truncate">{{ $cartIngredientId ? $ingredients->firstWhere('id', $cartIngredientId)?->name : 'Choose item...' }}</span>
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            <div x-data="{ ingSearch: '' }" class="p-2">
                                                <input x-model="ingSearch" type="text" placeholder="Search..." class="w-full px-3 py-1.5 bg-slate-50 border-none rounded-lg text-[12px] font-medium focus:ring-1 focus:ring-indigo-500 placeholder-slate-400 mb-2">
                                                <div class="max-h-52 overflow-y-auto custom-scrollbar-slate">
                                                    @foreach($ingredients as $ing)
                                                        <div x-show="!ingSearch || @js($ing->name).toLowerCase().includes(ingSearch.toLowerCase())">
                                                            <x-dropdown-link href="#" wire:click.prevent="$set('cartIngredientId', {{ $ing->id }})">
                                                                <div class="flex items-center justify-between text-[12px]"><span class="font-medium text-slate-700">{{ $ing->name }}</span><span class="text-[9px] font-black text-slate-300 uppercase">{{ $ing->unit }}</span></div>
                                                            </x-dropdown-link>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </x-slot>
                                    </x-dropdown>
                                </div>
                            </div>

                            @if($cartIngredientId)
                                <div class="grid grid-cols-2 gap-2 p-1 bg-slate-50 rounded-2xl border border-slate-100">
                                    <div class="p-3 bg-white rounded-xl border border-slate-100 text-center">
                                        <span class="text-[8px] font-black text-slate-400 uppercase block mb-0.5">In Branch</span>
                                        <span class="text-[13px] font-black text-slate-900 tabular-nums">{{ number_format($branchStock[$cartIngredientId] ?? 0, 1) }}</span>
                                    </div>
                                    <div class="p-3 bg-indigo-600 rounded-xl text-center">
                                        <span class="text-[8px] font-black text-indigo-100 uppercase block mb-0.5">HQ Avail</span>
                                        <span class="text-[13px] font-black text-white tabular-nums">{{ number_format($mainStock[$cartIngredientId] ?? 0, 1) }}</span>
                                    </div>
                                </div>

                                @php $ing = $ingredients->firstWhere('id', $cartIngredientId); @endphp
                                @if($ing && $ing->unitConversions->count() > 0)
                                    <div>
                                        <x-input-label value="Packaging Tier" class="text-[11px]" />
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            <button type="button" wire:click="selectCartUnit('{{ $ing->unit }}')" class="h-8 px-3 rounded-lg border text-[10px] font-black uppercase transition-all {{ $cartUnit === $ing->unit ? 'bg-indigo-600 border-indigo-600 text-white shadow-md' : 'bg-white border-slate-200 text-slate-500' }}">{{ $ing->unit }}</button>
                                            @foreach($ing->unitConversions as $conv)
                                                <button type="button" wire:click="selectCartUnit('{{ $conv->unit_name }}')" class="h-8 px-3 rounded-lg border text-[10px] font-black uppercase transition-all {{ $cartUnit === $conv->unit_name ? 'bg-indigo-600 border-indigo-600 text-white shadow-md' : 'bg-white border-slate-200 text-slate-500' }}">
                                                    {{ $conv->unit_name }} <span class="opacity-50 font-medium">({{ number_format($conv->qty_in_base, 0) }})</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100 flex items-center justify-between">
                                    <div><span class="text-[9px] font-black text-indigo-400 uppercase">Cost / {{ $cartUnit }}</span><p class="text-[16px] font-black text-indigo-700 tabular-nums leading-none">₱{{ number_format($cartPrice, 2) }}</p></div>
                                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center text-indigo-600 shadow-sm border border-slate-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <x-input-label value="Quantity" class="text-[11px]" />
                                        <div class="mt-1.5 relative">
                                            <x-text-input wire:model.live="cartQty" type="number" step="0.01" class="w-full h-10 pr-10 font-black text-[13px]" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-400 uppercase">{{ $cartUnit }}</span>
                                        </div>
                                        <x-input-error :messages="$errors->get('cartQty')" class="mt-1" />
                                    </div>
                                    <div>
                                        <x-input-label value="Subtotal" class="text-[11px]" />
                                        <div class="mt-1.5 h-10 bg-slate-50 border border-slate-200 rounded-xl px-3 flex items-center text-[13px] font-black text-slate-900 tabular-nums italic">₱{{ number_format((float)($cartQty ?: 0) * (float)($cartPrice ?: 0), 2) }}</div>
                                    </div>
                                </div>

                                <div>
                                    <x-input-label value="Remarks" class="text-[11px]" />
                                    <textarea wire:model.live="cartNotes" class="mt-1.5 w-full bg-white border-slate-200 rounded-xl text-[12px] focus:ring-indigo-500 transition-all placeholder-slate-300 h-20 resize-none" placeholder="Optional..."></textarea>
                                </div>
                            @endif
                        </div>

                        <div class="p-5 bg-slate-50/50 border-t border-slate-100">
                            <x-primary-button wire:click="addToCart" class="w-full justify-center h-10 text-[12px] font-black uppercase tracking-widest bg-rose-600 hover:bg-rose-700 shadow-lg shadow-rose-100 transition-all">
                                Add to Cart
                            </x-primary-button>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Compact Cart --}}
                <div class="lg:col-span-2 flex flex-col h-full min-h-[500px]">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200 flex flex-col h-full">
                        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <div><h2 class="text-[14px] font-black text-slate-900">Submission Cart</h2></div>
                            @if(!empty($cartItems)) <x-secondary-button wire:click="clearCart" class="text-red-600 border-red-100 hover:bg-red-50 h-8 text-[10px]">Clear</x-secondary-button> @endif
                        </div>
                        <div class="flex-1 p-6 space-y-3 overflow-y-auto custom-scrollbar-slate">
                            @forelse($cartItems as $index => $item)
                                <div wire:key="cart-item-{{ $item['ingredient_id'] }}-{{ $index }}" class="flex items-center gap-3 p-3.5 bg-white border border-slate-100 rounded-2xl shadow-sm hover:border-indigo-200 transition-all group">
                                    <div class="w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center font-black text-[13px] group-hover:bg-indigo-600 group-hover:text-white transition-all">{{ $index + 1 }}</div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2"><h4 class="text-[13px] font-black text-slate-900 uppercase truncate">{{ $item['ingredient_name'] }}</h4></div>
                                        <div class="flex items-center gap-3 mt-0.5"><span class="text-[11px] font-bold text-slate-700">Qty: <span class="text-indigo-600">{{ number_format($item['quantity'], 2) }} {{ $item['unit'] }}</span></span><div class="w-0.5 h-0.5 rounded-full bg-slate-200"></div><span class="text-[10px] font-black text-slate-900 italic">₱{{ number_format($item['subtotal'], 2) }}</span></div>
                                    </div>
                                    <button wire:click="removeFromCart({{ $index }})" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                </div>
                            @empty <div class="flex flex-col items-center justify-center h-full py-10 opacity-30"><svg class="w-10 h-10 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg><p class="text-[11px] font-black uppercase">Cart is Empty</p></div> @endforelse
                        </div>
                        @if(!empty($cartItems))
                            <div class="p-6 bg-slate-50 border-t border-slate-100 rounded-b-[2.5rem]">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                    <div><x-input-label value="Priority" class="text-[10px]" /><div class="mt-1 flex items-center gap-1.5">@foreach(['normal' => 'slate', 'urgent' => 'amber', 'critical' => 'red'] as $v => $c) <button wire:click="$set('orderPriority', '{{ $v }}')" class="flex-1 h-10 rounded-xl border text-[9px] font-black uppercase {{ $orderPriority === $v ? "bg-{$c}-600 border-{$c}-600 text-white shadow-md" : "bg-white border-slate-200 text-slate-500" }}">{{ $v }}</button> @endforeach</div></div>
                                    <div class="bg-white rounded-xl border border-slate-200 p-3 flex flex-col justify-center">
                                        <div class="flex justify-between text-[10px] font-black text-slate-400 uppercase mb-0.5"><span>Subtotal</span><span>₱{{ number_format($this->cartSubtotal, 2) }}</span></div>
                                        <div class="flex justify-between text-[10px] font-black text-slate-400 uppercase mb-0.5">
                                            <span class="flex items-center gap-1.5">Delivery (Est.) <span class="text-[8px] px-1.5 py-0.5 bg-slate-100 rounded text-slate-500">{{ number_format($branchDistance, 1) }} KM</span></span>
                                            <span>₱{{ number_format($deliveryFee, 2) }}</span>
                                        </div>
                                        <div class="flex justify-between text-[14px] font-black text-slate-900 mt-1 pt-1 border-t border-slate-50"><span>Est. Total</span><span class="text-indigo-600">₱{{ number_format($this->cartTotal, 2) }}</span></div>
                                    </div>
                                </div>
                                <button wire:click="validateBeforeSubmit" class="w-full h-12 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-black text-[13px] uppercase tracking-[0.2em] shadow-lg shadow-indigo-100 transition-all">Submit Order Request</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- TOOLBAR: Active Requests --}}
        <div x-show="panel === 'requests'" x-cloak class="flex flex-row items-center justify-between mb-4 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
            <div class="flex flex-1 min-w-0 lg:flex-initial">
                <x-search-bar wireModel="search" placeholder="Reference..." width="w-full lg:w-64" />
            </div>
            <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-3 shrink-0">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none text-[13px] font-black">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span class="hidden sm:inline whitespace-nowrap">
                                @if($statusFilter === 'all') All Status
                                @elseif($statusFilter === 'in_transit') In Transit
                                @else {{ ucfirst($statusFilter) }}
                                @endif
                            </span>
                            <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'all')">All Status</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'pending')">Pending</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'approved')">Approved</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'preparing')">Preparing</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'in_transit')">In Transit</x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>

        {{-- TOOLBAR: History (date filter lives ONLY here) --}}
        <div x-show="panel === 'history'" x-cloak class="flex flex-row items-center justify-between mb-4 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
            <div class="flex flex-1 min-w-0 lg:flex-initial">
                <x-search-bar wireModel="search" placeholder="Reference..." width="w-full lg:w-64" />
            </div>
            <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-3 shrink-0">
                <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none text-[13px] font-black">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            <span class="hidden sm:inline whitespace-nowrap">
                                @if($statusFilter === 'all') All Status
                                @elseif($statusFilter === 'in_transit') In Transit
                                @else {{ ucfirst($statusFilter) }}
                                @endif
                            </span>
                            <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'all')">All Status</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'delivered')">Delivered</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'rejected')">Rejected</x-dropdown-link>
                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', 'cancelled')">Cancelled</x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>

        {{-- PANEL: Active Requests --}}
        <div wire:key="panel-requests" x-show="panel === 'requests'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-4">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-2.5 px-6 text-left text-[10px] font-black text-slate-500 uppercase">Reference</th>
                    <th class="py-2.5 px-6 text-left text-[10px] font-black text-slate-500 uppercase">Summary</th>
                    <th class="py-2.5 px-6 text-left text-[10px] font-black text-slate-500 uppercase">Status</th>
                    <th class="py-2.5 px-6 text-left text-[10px] font-black text-slate-500 uppercase">Date</th>
                    <th class="py-2.5 px-6 text-right text-[10px] font-black text-slate-500 uppercase">Actions</th>
                </x-slot>
                @forelse($requestOrders as $order)
                    <tr wire:key="req-row-{{ $order->id }}" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50">
                        <td class="px-6 py-3"><div class="flex flex-col"><span class="text-[13px] font-bold text-slate-900 cursor-pointer" wire:click="viewOrder({{ $order->id }})">{{ $order->reference_no }}</span><span class="text-[9px] text-slate-400 font-bold uppercase">By {{ $order->requester->last_name }}</span></div></td>
                        <td class="px-6 py-3"><div class="flex flex-col"><span class="text-[12px] font-black text-slate-700 tabular-nums">{{ $order->items->count() }} Items</span><span class="text-[10px] font-black text-indigo-600">₱{{ number_format($order->total_amount, 2) }}</span></div></td>
                        <td class="px-6 py-3">
                            @php $s = $order->statusConfig()[$order->status] @endphp
                            <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span><span class="text-[11px] font-bold text-{{ $s['color'] }}-600">{{ $s['label'] }}</span></div>
                        </td>
                        <td class="px-6 py-3 text-[11px] text-slate-500 tabular-nums font-bold">{{ $order->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($order->status === 'in_transit')
                                    <button wire:click="confirmDelivery({{ $order->id }})" wire:loading.attr="disabled" wire:target="confirmDelivery({{ $order->id }})" class="h-7 px-2.5 bg-emerald-50 text-emerald-600 text-[9px] font-black uppercase rounded-lg border border-emerald-100 hover:bg-emerald-100 transition-all flex items-center gap-1.5">
                                        <span wire:loading.remove wire:target="confirmDelivery({{ $order->id }})">Received</span>
                                        <span wire:loading wire:target="confirmDelivery({{ $order->id }})"><svg class="animate-spin h-2.5 w-2.5 text-emerald-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                                    </button>
                                @endif
                                <x-secondary-button type="button" wire:click="viewOrder({{ $order->id }})" class="h-9 px-3 whitespace-nowrap text-[11px] font-bold">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    View Details
                                </x-secondary-button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr wire:key="req-empty"><td colspan="5" class="py-12 text-center"><x-empty-state title="No Active Requests" description="Your active stock requests will appear here." icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></td></tr>
                @endforelse
            </x-data-table>
            <x-pagination :paginator="$requestOrders" />
        </div>

        {{-- PANEL: History --}}
        <div wire:key="panel-history" x-show="panel === 'history'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="space-y-4">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-2.5 px-6 text-left text-[10px] font-black text-slate-500 uppercase">Reference</th>
                    <th class="py-2.5 px-6 text-left text-[10px] font-black text-slate-500 uppercase">Summary</th>
                    <th class="py-2.5 px-6 text-left text-[10px] font-black text-slate-500 uppercase">Status</th>
                    <th class="py-2.5 px-6 text-left text-[10px] font-black text-slate-500 uppercase">Date</th>
                    <th class="py-2.5 px-6 text-right text-[10px] font-black text-slate-500 uppercase">Actions</th>
                </x-slot>
                @forelse($historyOrders as $order)
                    <tr wire:key="hist-row-{{ $order->id }}" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50">
                        <td class="px-6 py-3"><div class="flex flex-col"><span class="text-[13px] font-bold text-slate-900 cursor-pointer" wire:click="viewOrder({{ $order->id }})">{{ $order->reference_no }}</span><span class="text-[9px] text-slate-400 font-bold uppercase">By {{ $order->requester->last_name }}</span></div></td>
                        <td class="px-6 py-3"><div class="flex flex-col"><span class="text-[12px] font-black text-slate-700 tabular-nums">{{ $order->items->count() }} Items</span><span class="text-[10px] font-black text-indigo-600">₱{{ number_format($order->total_amount, 2) }}</span></div></td>
                        <td class="px-6 py-3">
                            @php $s = $order->statusConfig()[$order->status] @endphp
                            <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span><span class="text-[11px] font-bold text-{{ $s['color'] }}-600">{{ $s['label'] }}</span></div>
                        </td>
                        <td class="px-6 py-3 text-[11px] text-slate-500 tabular-nums font-bold">{{ $order->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-3 text-right">
                            <x-secondary-button type="button" wire:click="viewOrder({{ $order->id }})" class="h-9 px-3 whitespace-nowrap text-[11px] font-bold">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View Details
                            </x-secondary-button>
                        </td>
                    </tr>
                @empty
                    <tr wire:key="hist-empty"><td colspan="5" class="py-12 text-center"><x-empty-state title="No History Found" description="Your completed stock requests will be logged here." icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></td></tr>
                @endforelse
            </x-data-table>
            <x-pagination :paginator="$historyOrders" />
        </div>

        <div wire:key="panel-analytics" 
             x-show="panel === 'analytics'" 
             class="space-y-6 animate-fadeIn px-1" 
             x-data="stockOrderingAnalytics(@js($this->analyticsData))"
             x-effect="updateAnalytics(@js($this->analyticsData))">
            
            {{-- KPI Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                {{-- Card 1: Total Spend --}}
                <div class="p-5 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Total Ordered Spend</span>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱ {{ number_format($this->analyticsData['kpis']['total_spent'], 2) }}</h3>
                    <p class="text-[9px] text-slate-400 font-semibold mt-1">Sum of approved & delivered orders</p>
                </div>
                
                {{-- Card 2: Total Requests --}}
                <div class="p-5 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Total Requests Placed</span>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($this->analyticsData['kpis']['total_requests']) }}</h3>
                    <p class="text-[9px] text-slate-400 font-semibold mt-1">All lifetime stock requests</p>
                </div>

                {{-- Card 3: Avg Fulfillment Time --}}
                <div class="p-5 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Avg Lead Time</span>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">
                        {{ $this->analyticsData['kpis']['avg_lead_time'] !== null ? $this->analyticsData['kpis']['avg_lead_time'] . ' hrs' : 'N/A' }}
                    </h3>
                    <p class="text-[9px] text-slate-400 font-semibold mt-1">From request creation to delivery</p>
                </div>

                {{-- Card 4: Active Requests --}}
                <div class="p-5 bg-gradient-to-br from-cyan-500/10 via-cyan-500/5 to-white border border-cyan-500/10 rounded-2xl shadow-sm">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-wider block mb-1">Active Processing</span>
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight">{{ number_format($this->analyticsData['kpis']['active_requests']) }}</h3>
                    <p class="text-[9px] text-slate-400 font-semibold mt-1">Pending approval or in transit</p>
                </div>
            </div>

            {{-- Main charts and tables grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Left Side: Spend Trend Chart (2/3 width) --}}
                <div class="lg:col-span-2 bg-white border border-slate-200/80 shadow-sm rounded-[2rem] p-6 flex flex-col h-[400px] overflow-hidden justify-between">
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-900 tracking-tight">Stock Order Spend & Count Trend</h2>
                        <p class="text-[11px] text-gray-400 font-medium">Spend activity and order counts over the past 30 days</p>
                    </div>
                    <div wire:ignore class="relative flex-1 flex flex-col justify-end">
                        <div x-ref="trendChart" class="w-full"></div>
                    </div>
                </div>

                {{-- Right Side: Top Ingredients (1/3 width) --}}
                <div class="lg:col-span-1 bg-white border border-slate-200/80 shadow-sm rounded-[2rem] p-6 flex flex-col h-[400px] overflow-hidden justify-between">
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-900 tracking-tight">Top Requested Ingredients</h2>
                        <p class="text-[11px] text-gray-400 font-medium mb-4">Ranked by total cost spent</p>
                    </div>
                    
                    <div class="flex-grow overflow-y-auto custom-scrollbar-slate space-y-3">
                        @forelse($this->analyticsData['top_ingredients'] as $index => $item)
                            <div class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-100 rounded-2xl group hover:border-indigo-200 transition-all">
                                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-[12px] group-hover:bg-indigo-600 group-hover:text-white transition-all font-mono">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[12px] font-black text-slate-800 uppercase truncate">{{ $item['name'] }}</h4>
                                    <p class="text-[10px] text-slate-500 font-medium mt-0.5">Qty: <span class="font-bold text-slate-700">{{ number_format($item['total_qty'], 1) }} {{ $item['unit'] }}</span></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[12px] font-black text-slate-900">₱{{ number_format($item['total_spent'], 2) }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center h-full opacity-35 py-12">
                                <svg class="w-10 h-10 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span class="text-[10px] font-black uppercase">No analytical records found</span>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Bottom row charts: Status and Priority breakdowns --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                {{-- Status Breakdown --}}
                <div class="bg-white border border-slate-200/80 shadow-sm rounded-[2rem] p-6 h-[320px] flex flex-col justify-between overflow-hidden">
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-900 tracking-tight">Request Status Distribution</h2>
                        <p class="text-[11px] text-gray-400 font-medium">Breakdown of orders by fulfillment stage</p>
                    </div>
                    <div class="flex flex-1 items-center justify-between gap-4 mt-2">
                        <div wire:ignore class="w-1/2 flex items-center justify-center">
                            <div x-ref="statusChart"></div>
                        </div>
                        <div class="w-1/2 space-y-2 text-[11px] font-semibold text-slate-500">
                            @php
                                $statuses = ['pending' => 'Pending', 'approved' => 'Approved/Preparing', 'in_transit' => 'In Transit', 'delivered' => 'Delivered', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];
                                $statusColors = ['pending' => 'bg-amber-500', 'approved' => 'bg-indigo-500', 'in_transit' => 'bg-cyan-500', 'delivered' => 'bg-emerald-500', 'rejected' => 'bg-rose-500', 'cancelled' => 'bg-slate-400'];
                            @endphp
                            @foreach($statuses as $key => $label)
                                @php
                                    $count = 0;
                                    if ($key === 'approved') {
                                        $count = ($this->analyticsData['status_counts']['approved'] ?? 0) + ($this->analyticsData['status_counts']['preparing'] ?? 0);
                                    } else {
                                        $count = $this->analyticsData['status_counts'][$key] ?? 0;
                                    }
                                @endphp
                                <div class="flex justify-between items-center py-1 border-b border-slate-50">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $statusColors[$key] }}"></span>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <span class="font-black text-slate-900">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Priority Breakdown --}}
                <div class="bg-white border border-slate-200/80 shadow-sm rounded-[2rem] p-6 h-[320px] flex flex-col justify-between overflow-hidden">
                    <div>
                        <h2 class="text-[15px] font-bold text-gray-900 tracking-tight">Order Priority Share</h2>
                        <p class="text-[11px] text-gray-400 font-medium">Distribution of request urgencies</p>
                    </div>
                    <div class="flex flex-1 items-center justify-between gap-4 mt-2">
                        <div wire:ignore class="w-1/2 flex items-center justify-center">
                            <div x-ref="priorityChart"></div>
                        </div>
                        <div class="w-1/2 space-y-3 text-[11px] font-semibold text-slate-500">
                            @php
                                $priorities = ['normal' => 'Normal', 'urgent' => 'Urgent', 'critical' => 'Critical'];
                                $priorityColors = ['normal' => 'bg-slate-500', 'urgent' => 'bg-amber-500', 'critical' => 'bg-red-600'];
                            @endphp
                            @foreach($priorities as $key => $label)
                                @php $count = $this->analyticsData['priority_counts'][$key] ?? 0; @endphp
                                <div class="flex justify-between items-center py-1.5 border-b border-slate-50">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full {{ $priorityColors[$key] }}"></span>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <span class="font-black text-slate-900">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Order Details Side Panel (Simplified) --}}
    <x-side-panel name="order-details" width="max-w-md">
        @if($selectedOrder)
            <div class="flex flex-col h-full bg-white">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-md"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></div>
                        <div><h3 class="text-[14px] font-black text-slate-900">Order Review</h3><span class="text-[10px] text-indigo-600 font-black uppercase">{{ $selectedOrder->reference_no }}</span></div>
                    </div>
                    <button @click="$dispatch('close-modal', 'order-details')" class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar-slate p-5 space-y-4">
                    @foreach($selectedOrder->items as $item)
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-100 flex items-center justify-between">
                            <div class="flex-1 min-w-0"><p class="text-[12px] font-black text-slate-900 uppercase truncate">{{ $item->ingredient->name }}</p><p class="text-[9px] text-slate-400 font-bold uppercase">{{ $item->unit }}</p></div>
                            <div class="text-right"><p class="text-[13px] font-black text-slate-900 tabular-nums">{{ number_format($item->requested_quantity, 1) }}</p><p class="text-[10px] font-black text-indigo-600 italic">₱{{ number_format($item->subtotal, 2) }}</p></div>
                        </div>
                    @endforeach
                    <div class="p-5 bg-slate-900 rounded-3xl text-white space-y-2"><div class="flex justify-between opacity-50 text-[10px] font-black uppercase"><span>Items</span><span>₱{{ number_format($selectedOrder->items->sum('subtotal'), 2) }}</span></div><div class="flex justify-between opacity-50 text-[10px] font-black uppercase"><span>Delivery</span><span>₱{{ number_format($selectedOrder->delivery_fee, 2) }}</span></div><div class="pt-2 border-t border-white/10 flex justify-between text-[15px] font-black tracking-tight"><span>Total</span><span class="text-amber-400">₱{{ number_format($selectedOrder->total_amount, 2) }}</span></div></div>
                </div>
                <div class="p-5 border-t border-slate-100 bg-white">
                    @if($selectedOrder->status === 'in_transit') 
                        <x-primary-button 
                            wire:click="confirmDelivery({{ $selectedOrder->id }})" 
                            wire:loading.attr="disabled"
                            wire:target="confirmDelivery({{ $selectedOrder->id }})"
                            class="w-full h-11 justify-center bg-emerald-600 flex items-center gap-2"
                        >
                            <span wire:loading.remove wire:target="confirmDelivery({{ $selectedOrder->id }})">Confirm Receipt</span>
                            <span wire:loading wire:target="confirmDelivery({{ $selectedOrder->id }})"><svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></span>
                        </x-primary-button>
                    @elseif($selectedOrder->status === 'pending') <x-secondary-button wire:click="confirmCancel({{ $selectedOrder->id }})" class="w-full h-11 justify-center text-red-600 border-red-100">Cancel Request</x-secondary-button>
                    @else <button @click="$dispatch('close-modal', 'order-details')" class="w-full h-11 flex items-center justify-center bg-slate-900 text-white font-bold rounded-xl">Close</button> @endif
                </div>
            </div>
        @endif
    </x-side-panel>

    {{-- Confi    {{-- 1. Submit Order Modal --}}
    <x-modal name="confirm-submit-order" maxWidth="sm">
        <div class="p-6 text-center">
            <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </div>
            <h3 class="text-[15px] font-black text-slate-900 uppercase">Submit Stock Request?</h3>
            <p class="mt-2 text-[11px] text-slate-500 font-medium">This will notify HQ to process your inventory request.</p>
            <div class="flex items-center gap-3 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-submit-order')" class="flex-1 justify-center h-10 text-[11px]">Back</x-secondary-button>
                <x-primary-button 
                    @click="$dispatch('close-modal', 'confirm-submit-order'); panel = 'requests'; $wire.submitOrder();" 
                    class="flex-1 justify-center h-10 text-[11px] bg-indigo-600"
                >
                    Confirm Submit
                </x-primary-button>
            </div>
        </div>
    </x-modal>
 
    {{-- 2. Cancel Order Modal --}}
    <x-modal name="confirm-cancel-order" maxWidth="sm">
        <div class="p-6 text-center">
            <div class="w-14 h-14 bg-red-50 text-red-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <h3 class="text-[15px] font-black text-slate-900 uppercase">Cancel Request?</h3>
            <p class="mt-2 text-[11px] text-slate-500 font-medium italic">Ref: {{ $cancelTargetRef }}</p>
            <div class="flex items-center gap-3 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-cancel-order')" class="flex-1 justify-center h-10 text-[11px]">Back</x-secondary-button>
                <x-primary-button 
                    @click="$dispatch('close-modal', 'confirm-cancel-order'); $dispatch('close-modal', 'order-details'); $wire.cancelOrder();" 
                    class="flex-1 justify-center h-10 text-[11px] !bg-red-600"
                >
                    Yes, Cancel
                </x-primary-button>
            </div>
        </div>
    </x-modal>
 
    {{-- 3. Confirm Delivery Modal --}}
    <x-modal name="confirm-delivery" maxWidth="sm">
        <div class="p-6 text-center">
            <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center mx-auto mb-3">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-[15px] font-black text-slate-900 uppercase">Confirm Receipt?</h3>
            <p class="mt-2 text-[11px] text-slate-500 font-medium leading-relaxed">By confirming, you acknowledge that items from <span class="font-bold text-slate-900">{{ $deliverTargetRef }}</span> have been received and added to your stock.</p>
            <div class="flex items-center gap-3 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-delivery')" class="flex-1 justify-center h-10 text-[11px]">Back</x-secondary-button>
                <x-primary-button 
                    @click="$dispatch('close-modal', 'confirm-delivery'); $dispatch('close-modal', 'order-details'); $wire.markDelivered();" 
                    class="flex-1 justify-center h-10 text-[11px] !bg-emerald-600"
                >
                    Yes, Received
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <style> .custom-scrollbar-slate::-webkit-scrollbar { height: 4px; width: 4px; } .custom-scrollbar-slate::-webkit-scrollbar-track { background: transparent; } .custom-scrollbar-slate::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; } </style>

    @push('scripts')
    <script>
        function stockOrderingAnalytics(initialData) {
            return {
                trendChart: null,
                statusChart: null,
                priorityChart: null,
                analyticsData: initialData,

                init() {
                    // Initial draw if active
                    setTimeout(() => {
                        if (this.panel === 'analytics') {
                            this.drawCharts(this.analyticsData);
                        }
                    }, 50);

                    this.$watch('panel', (value) => {
                        if (value !== 'analytics') {
                            this.destroyCharts();
                        } else {
                            setTimeout(() => this.drawCharts(this.analyticsData), 50);
                        }
                    });

                    if (this.$cleanup) {
                        this.$cleanup(() => {
                            this.destroyCharts();
                        });
                    }
                },

                updateAnalytics(data) {
                    if (!data) return;
                    
                    const newDataStr = JSON.stringify(data.trend);
                    const oldDataStr = this.analyticsData ? JSON.stringify(this.analyticsData.trend) : null;
                    if (newDataStr === oldDataStr) return;

                    this.analyticsData = data;
                    if (this.panel === 'analytics') {
                        this.drawCharts(data);
                    }
                },

                destroyCharts() {
                    if (this.trendChart) { try { this.trendChart.destroy(); } catch(e){} this.trendChart = null; }
                    if (this.statusChart) { try { this.statusChart.destroy(); } catch(e){} this.statusChart = null; }
                    if (this.priorityChart) { try { this.priorityChart.destroy(); } catch(e){} this.priorityChart = null; }
                },

                drawCharts(data) {
                    if (typeof window.ApexCharts === 'undefined') return;

                    // If charts are already initialized, update their series and return to avoid re-rendering layout twice
                    if (this.trendChart && this.statusChart && this.priorityChart) {
                        try {
                            this.trendChart.updateOptions({
                                xaxis: { categories: data.trend.dates || [] }
                            }, false, false);
                            this.trendChart.updateSeries([
                                { name: 'Spend Amount', data: data.trend.totals || [] },
                                { name: 'Request Volume', data: data.trend.counts || [] }
                            ]);

                            const counts = data.status_counts || {};
                            const pending = parseInt(counts.pending || 0);
                            const approved = parseInt(counts.approved || 0) + parseInt(counts.preparing || 0);
                            const in_transit = parseInt(counts.in_transit || 0);
                            const delivered = parseInt(counts.delivered || 0);
                            const rejected = parseInt(counts.rejected || 0);
                            const cancelled = parseInt(counts.cancelled || 0);
                            this.statusChart.updateSeries([pending, approved, in_transit, delivered, rejected, cancelled]);

                            const priorities = data.priority_counts || {};
                            const normal = parseInt(priorities.normal || 0);
                            const urgent = parseInt(priorities.urgent || 0);
                            const critical = parseInt(priorities.critical || 0);
                            this.priorityChart.updateSeries([normal, urgent, critical]);
                            return;
                        } catch (e) {
                            console.warn("Failed updating charts in-place, falling back to full draw", e);
                            this.destroyCharts();
                        }
                    }

                    this.destroyCharts();

                    // 1. Spend Trend Chart
                    const trendEl = this.$refs.trendChart;
                    if (trendEl && data.trend) {
                        const trendOptions = {
                            series: [
                                {
                                    name: 'Spend Amount',
                                    data: data.trend.totals || []
                                },
                                {
                                    name: 'Request Volume',
                                    data: data.trend.counts || []
                                }
                            ],
                            chart: {
                                height: 310,
                                type: 'area',
                                toolbar: { show: false },
                                fontFamily: 'Outfit, Inter, sans-serif',
                                zoom: { enabled: false },
                                animations: {
                                    enabled: true,
                                    easing: 'easeinout',
                                    speed: 800,
                                    animateGradually: { enabled: false, delay: 150 },
                                    dynamicAnimation: { enabled: true, speed: 350 }
                                }
                            },
                            colors: ['#6366F1', '#10B981'],
                            dataLabels: { enabled: false },
                            stroke: {
                                width: [3, 2],
                                curve: 'smooth',
                                dashArray: [0, 8]
                            },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.25,
                                    opacityTo: 0.01,
                                    stops: [0, 90, 100]
                                }
                            },
                            xaxis: {
                                categories: data.trend.dates || [],
                                tickAmount: window.innerWidth < 640 ? 4 : 8,
                                tooltip: { enabled: false },
                                axisBorder: { show: false },
                                axisTicks: { show: false },
                                labels: {
                                    show: true,
                                    rotate: 0,
                                    rotateAlways: false,
                                    hideOverlappingLabels: true,
                                    style: { colors: '#9CA3AF', fontSize: '10px', fontWeight: 600 }
                                }
                            },
                            yaxis: [
                                {
                                    title: {
                                        text: 'Spend Amount',
                                        style: { color: '#6366F1', fontSize: '10px', fontWeight: 700, fontFamily: 'Outfit' }
                                    },
                                    labels: {
                                        style: { colors: '#9CA3AF', fontSize: '11px', fontWeight: 600 },
                                        formatter: (val) => val >= 1000 ? "₱" + (val/1000).toFixed(1) + "k" : "₱" + val
                                    }
                                },
                                {
                                    opposite: true,
                                    title: {
                                        text: 'Request Volume',
                                        style: { color: '#10B981', fontSize: '10px', fontWeight: 700, fontFamily: 'Outfit' }
                                    },
                                    labels: {
                                        style: { colors: '#9CA3AF', fontSize: '11px', fontWeight: 600 },
                                        formatter: (val) => Math.round(val)
                                    }
                                }
                            ],
                            grid: {
                                borderColor: '#f8fafc',
                                strokeDashArray: 4,
                                padding: {
                                    top: 0,
                                    right: 15,
                                    bottom: 0,
                                    left: 15
                                }
                            },
                            legend: {
                                show: true,
                                position: 'top',
                                horizontalAlign: 'right',
                                fontSize: '11px',
                                fontWeight: 700,
                                fontFamily: 'Outfit, Inter, sans-serif',
                                markers: { radius: 12, width: 8, height: 8 }
                            },
                            tooltip: {
                                theme: 'dark',
                                style: { fontSize: '12px', fontFamily: 'Outfit, Inter, sans-serif' },
                                y: [
                                    {
                                        formatter: (val) => "₱ " + val.toLocaleString(undefined, { minimumFractionDigits: 2 })
                                    },
                                    {
                                        formatter: (val) => val + " requests"
                                    }
                                ]
                            }
                        };
                        this.trendChart = new ApexCharts(trendEl, trendOptions);
                        this.trendChart.render();
                    }

                    // 2. Status Chart
                    const statusEl = this.$refs.statusChart;
                    if (statusEl) {
                        const counts = data.status_counts || {};
                        const pending = parseInt(counts.pending || 0);
                        const approved = parseInt(counts.approved || 0) + parseInt(counts.preparing || 0);
                        const in_transit = parseInt(counts.in_transit || 0);
                        const delivered = parseInt(counts.delivered || 0);
                        const rejected = parseInt(counts.rejected || 0);
                        const cancelled = parseInt(counts.cancelled || 0);

                        const statusOptions = {
                            chart: { type: 'donut', height: 180, fontFamily: 'Outfit, Inter, sans-serif' },
                            series: [pending, approved, in_transit, delivered, rejected, cancelled],
                            labels: ['Pending', 'Approved/Preparing', 'In Transit', 'Delivered', 'Rejected', 'Cancelled'],
                            colors: ['#f59e0b', '#6366F1', '#06b6d4', '#10b981', '#f43f5e', '#94a3b8'],
                            legend: { show: false },
                            dataLabels: { enabled: false },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '75%',
                                        labels: {
                                            show: true,
                                            total: {
                                                show: true,
                                                label: 'Requests',
                                                fontSize: '11px',
                                                fontWeight: 700,
                                                color: '#64748b',
                                                formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                            }
                                        }
                                    }
                                }
                            },
                            tooltip: { theme: 'dark' }
                        };
                        this.statusChart = new ApexCharts(statusEl, statusOptions);
                        this.statusChart.render();
                    }

                    // 3. Priority Chart
                    const priorityEl = this.$refs.priorityChart;
                    if (priorityEl) {
                        const priorities = data.priority_counts || {};
                        const normal = parseInt(priorities.normal || 0);
                        const urgent = parseInt(priorities.urgent || 0);
                        const critical = parseInt(priorities.critical || 0);

                        const priorityOptions = {
                            chart: { type: 'donut', height: 180, fontFamily: 'Outfit, Inter, sans-serif' },
                            series: [normal, urgent, critical],
                            labels: ['Normal', 'Urgent', 'Critical'],
                            colors: ['#64748b', '#f59e0b', '#dc2626'],
                            legend: { show: false },
                            dataLabels: { enabled: false },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '75%',
                                        labels: {
                                            show: true,
                                            total: {
                                                show: true,
                                                label: 'Requests',
                                                fontSize: '11px',
                                                fontWeight: 700,
                                                color: '#64748b',
                                                formatter: (w) => w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                            }
                                        }
                                    }
                                }
                            },
                            tooltip: { theme: 'dark' }
                        };
                        this.priorityChart = new ApexCharts(priorityEl, priorityOptions);
                        this.priorityChart.render();
                    }
                }
            };
        }
    </script>
    @endpush
</div>
