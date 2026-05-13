@php
    $user = auth()->user();
    // Primary palette
    $primaryColor = $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald');
    $primaryText = "text-{$primaryColor}-600";
    $primaryBg = "bg-{$primaryColor}-600";
@endphp

<div
    x-data="{ ...slidingTabs(@entangle('panel').live, 'panel'), mode: 'list' }"
    x-on:switch-panel.window="panel = $event.detail.panel; if($event.detail.mode) mode = $event.detail.mode"
    @trigger-edit-ingredient.window="$wire.showEdit($event.detail.id)"
    class="relative">

    <div class="relative min-h-[600px]" wire:init="triggerExpiryAlerts">

        {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• DYNAMIC HEADER â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
        <div x-show="(panel === 'list' || panel === 'expiry')" x-cloak class="px-1">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Stock & Inventory</h2>
                    <p class="text-[12px] text-gray-500 font-medium">Managing <span class="text-indigo-600 font-bold">{{ $totalIngredients }} catalog assets</span></p>
                </div>
                <div class="flex items-center gap-3">
                    <x-report-dropdown module="Stock Report" />
                    @if(!$this->isStaff() && $this->isSuperAdmin())
                        <x-primary-button wire:click="showCreate" class="h-10">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            Add Ingredient
                        </x-primary-button>
                    @endif
                </div>
            </div>

            {{-- High-Fidelity Sliding Tabs --}}
            <x-sliding-tabs model="panel" ref="panelList" class="mb-6">
                <x-sliding-tab value="list" model="panel">
                    <x-slot name="icon">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </x-slot>
                    Ingredients
                </x-sliding-tab>
                <x-sliding-tab value="expiry" model="panel">
                    <x-slot name="icon">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </x-slot>
                    Expiry Tracking
                    @php $expiredBadge = \App\Models\StockBatch::where('current_quantity', '>', 0)->where('expiry_date', '<', today())->count(); @endphp
                    @if($expiredBadge > 0)
                        <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[9px] font-black ml-1">{{ $expiredBadge }}</span>
                    @endif
                </x-sliding-tab>
            </x-sliding-tabs>
        </div>

        {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• PANEL 1 â€” LIST â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
        <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">

            {{-- KPI Dashboard (User Management Aesthetic) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                {{-- Total Assets --}}
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-indigo-700/60 uppercase tracking-widest leading-none mb-1">Total Catalog</span>
                        <span class="block text-[20px] font-black text-gray-900 leading-none">{{ number_format($totalIngredients) }} Items</span>
                    </div>
                </div>
                
                {{-- Low Stock Alerts --}}
                <div class="bg-gradient-to-br {{ $lowStockWarnings > 0 ? 'from-amber-50 to-amber-100 border-amber-200' : 'from-emerald-50 to-emerald-100 border-emerald-200' }} border rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border {{ $lowStockWarnings > 0 ? 'border-amber-100 text-amber-600' : 'border-emerald-100 text-emerald-600' }} flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black {{ $lowStockWarnings > 0 ? 'text-amber-700/60' : 'text-emerald-700/60' }} uppercase tracking-widest leading-none mb-1">Low Stock Alerts</span>
                        <span class="block text-[20px] font-black {{ $lowStockWarnings > 0 ? 'text-amber-600' : 'text-emerald-600' }} leading-none">{{ $lowStockWarnings }}</span>
                    </div>
                </div>

                <div class="bg-gradient-to-br {{ $expiringCount > 0 ? 'from-rose-50 to-rose-100 border-rose-200' : 'from-slate-50 to-slate-100 border-slate-200' }} border rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border {{ $expiringCount > 0 ? 'border-rose-100 text-rose-600' : 'border-slate-100 text-slate-400' }} flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black {{ $expiringCount > 0 ? 'text-rose-700/60' : 'text-slate-700/60' }} uppercase tracking-widest leading-none mb-1">Expiring &le;{{ $alertDays }}d</span>
                        <span class="block text-[20px] font-black {{ $expiringCount > 0 ? 'text-rose-600' : 'text-gray-900' }} leading-none">{{ $expiringCount }}</span>
                    </div>
                </div>

                {{-- Monthly Procurement --}}
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-emerald-700/60 uppercase tracking-widest leading-none mb-1">Procurement (Mo)</span>
                        <span class="block text-[20px] font-black text-gray-900 leading-none">&#8369;{{ number_format($monthlyProcurement, 0) }}</span>
                    </div>
                </div>
            </div>


            {{-- macOS Style Unified Toolbar --}}
            <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
                
                {{-- Left: Search Bar --}}
                <div class="flex flex-1 w-full lg:w-auto">
                    <x-search-bar wireModel="search" wire:model.live.debounce.0ms="search" placeholder="Find ingredient..." width="w-full lg:w-72" />
                </div>

                {{-- Right: Branch Switcher --}}
                <div class="flex items-center gap-2">
                    @if($this->isSuperAdmin())
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <x-secondary-button type="button" class="gap-1.5 h-9 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    <span class="text-[12px] whitespace-nowrap">{{ $selectedBranchId ? ($branches->firstWhere('id', $selectedBranchId)->branch_name ?? 'Select Branch') : 'Select Branch' }}</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </x-secondary-button>
                            </x-slot>
                            <x-slot name="content">
                                @foreach($branches as $branch)
                                    <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', '{{ $branch->id }}')">
                                        {{ $branch->branch_name }}
                                    </x-dropdown-link>
                                @endforeach
                            </x-slot>
                        </x-dropdown>
                    @else
                        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 border border-emerald-100 rounded-lg font-bold text-[11px] text-emerald-700 h-9">
                            <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            Branch Stock
                        </span>
                    @endif
                </div>
            </div>

            {{-- â”€â”€ Unified Catalog Table â”€â”€ --}}
            <div class="relative min-h-[400px]">
                <x-data-table>
                    <x-slot name="header">
                        <th class="py-3 px-6 border-r border-slate-100/50 text-left text-[11px] font-black text-slate-500 uppercase tracking-widest">Ingredient</th>
                        <th class="py-3 px-6 border-r border-slate-100/50 text-center text-[11px] font-black text-slate-500 uppercase tracking-widest">Unit</th>
                        <th class="py-3 px-6 border-r border-slate-100/50 text-right text-[11px] font-black text-slate-500 uppercase tracking-widest">
                            {{ $selectedBranchId ? 'Branch Stock' : 'Total Stock' }}
                        </th>
                        <th class="py-3 px-6 text-right text-[11px] font-black text-slate-500 uppercase tracking-widest">Actions</th>
                    </x-slot>

                    <tbody class="divide-y divide-slate-100/80" wire:key="stock-list-body-{{ $ingredients->currentPage() }}-{{ $selectedBranchId }}" wire:loading.class="opacity-40" wire:target="search, filterType, selectedBranchId">
                        @forelse($ingredients as $ing)
                            @php
                                $stockToDisplay = 0;
                                $isLow = false;
                                $isCritical = false;
                                
                                $globalLow = $inventoryConfig['low_stock_threshold'] ?? 10;
                                $globalCritical = $inventoryConfig['critical_stock_threshold'] ?? 5;
                                $effectiveMin = $ing->minimum_stock > 0 ? $ing->minimum_stock : $globalLow;

                                if ($selectedBranchId) {
                                    $stockRecord = collect($ing->branchStocks)->where('branch_id', $selectedBranchId)->first();
                                    $stockToDisplay = $stockRecord ? $stockRecord->stock_quantity : 0;
                                } else {
                                    $stockToDisplay = collect($ing->branchStocks)->sum('stock_quantity');
                                }
                                
                                $isLow = $stockToDisplay <= $effectiveMin;
                                $isCritical = $stockToDisplay <= $globalCritical;
                                
                                $colors = ['from-indigo-400 to-violet-500', 'from-emerald-400 to-teal-500', 'from-amber-400 to-orange-500', 'from-rose-400 to-pink-500'];
                                $grad = $colors[$ing->id % count($colors)];
                            @endphp
                            <tr wire:key="stock-row-{{ $ing->id }}" class="hover:bg-slate-50/50 transition-colors group/row">
                                <td class="py-4 px-6 border-r border-slate-100/50 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{ $grad }} flex items-center justify-center text-white text-[14px] font-black shadow-lg shadow-indigo-100">
                                            {{ strtoupper(substr($ing->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-[14px] text-slate-900">{{ $ing->name }}</span>
                                                @if($ing->category)
                                                    <span class="text-[9px] font-black uppercase tracking-widest bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded-full">{{ $ing->category->name }}</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3 mt-1">
                                                <span class="text-[11px] font-medium text-slate-400">Min Threshold: {{ \App\Helpers\StockHelper::formatForDisplay($ing->minimum_stock, $ing->unit) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-6 border-r border-slate-100/50 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-slate-50 text-slate-500 border border-slate-100 uppercase">
                                        {{ \App\Helpers\StockHelper::getAbbreviation($ing->unit) }}
                                    </span>
                                </td>
                                
                                <td class="py-4 px-6 border-r border-slate-100/50 text-right whitespace-nowrap">
                                    <div class="flex flex-col items-end">
                                        <span class="text-[15px] font-black {{ $isCritical ? 'text-red-600' : ($isLow ? 'text-amber-600' : 'text-slate-900') }}">
                                            {{ \App\Helpers\StockHelper::formatForDisplay($stockToDisplay, $ing->unit) }}
                                        </span>
                                        @if($isCritical)
                                            <span class="text-[9px] font-black text-red-500 uppercase tracking-widest animate-pulse">Critical</span>
                                        @elseif($isLow)
                                            <span class="text-[9px] font-black text-amber-500 uppercase tracking-widest">Low Stock</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(!$this->isStaff())
                                            <x-secondary-button wire:click="showEdit({{ $ing->id }})" class="h-8 px-3 text-[11px] font-bold bg-white hover:bg-slate-50 border-slate-200">
                                                Edit
                                            </x-secondary-button>
                                            
                                            <a href="{{ route('stock.adjustment', ['id' => $ing->id, 'selectedBranchId' => $selectedBranchId]) }}" wire:navigate 
                                               class="h-8 px-3 inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-all text-[11px] font-black uppercase tracking-widest shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                                Adjust Stock
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12">
                                    <x-empty-state title="Empty Catalog" description="No ingredients found in this scope." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </x-data-table>
                <div class="mt-4 px-1">
                    <x-pagination :paginator="$ingredients" keyPrefix="stock-list" />
                </div>
            </div>

        </div>{{-- end panel 1 --}}

        {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• PANEL 4 â€” FORM (CREATE/EDIT) â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
        <div x-show="panel === 'form'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight" x-text="mode === 'edit' ? 'Update Ingredient' : 'Add New Ingredient'"></h2>
                    <p class="text-[12px] text-gray-500 font-medium" x-text="mode === 'edit' ? 'Ingredient specifications and availability' : 'New ingredient catalog entry'"></p>
                </div>
                <x-secondary-button wire:click="backToList" class="h-10">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to List
                </x-secondary-button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Main Form Column --}}
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm">
                        <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Core Configuration
                        </h2>
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label value="Ingredient Name *" />
                                    <x-text-input wire:model.live.debounce.400ms="ingredientName" class="w-full mt-1.5 h-11 font-medium" placeholder="e.g. Octopus Bits" inputFilter="name" :hasError="$errors->has('ingredientName')" />
                                    <x-input-error :messages="$errors->get('ingredientName')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label value="Item Category" />
                                    <div class="flex gap-2 mt-1.5">
                                        <div class="flex-1">
                                            <x-dropdown align="left" width="full" containerClasses="block w-full">
                                                <x-slot name="trigger">
                                                    <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                        <span class="font-medium text-slate-600">{{ $selectedCategoryName }}</span>
                                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                                    </button>
                                                </x-slot>
                                                <x-slot name="content">
                                                    <div class="p-2">
                                                        <div class="px-2 pb-2 mb-2 border-b border-slate-50">
                                                            <div class="relative">
                                                                <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                                                <input wire:model.live.debounce.300ms="ingredientCategorySearch" type="text" placeholder="Search categories..." 
                                                                       class="w-full pl-9 pr-4 py-2 bg-slate-50 border-none rounded-lg text-[12px] font-medium focus:ring-1 focus:ring-indigo-500 placeholder-slate-400">
                                                            </div>
                                                        </div>
                                                        <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                            @if(empty($ingredientCategorySearch))
                                                                <x-dropdown-link href="#" wire:click.prevent="$set('ingredientCategoryId', '')">Uncategorized</x-dropdown-link>
                                                                <hr class="border-slate-50">
                                                            @endif
                                                            @forelse($ingredientCategories as $cat)
                                                                <x-dropdown-link href="#" wire:click.prevent="$set('ingredientCategoryId', {{ $cat->id }})">{{ $cat->name }}</x-dropdown-link>
                                                            @empty
                                                                <div class="px-4 py-3 text-[11px] text-slate-400 text-center italic">No categories found</div>
                                                            @endforelse
                                                        </div>
                                                    </div>
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                        @if($this->isSuperAdmin())
                                        <button type="button" @click="$dispatch('open-modal', 'quick-add-ingredient-category')" class="h-11 w-11 flex items-center justify-center bg-slate-50 border border-slate-200 rounded-xl text-indigo-600 hover:bg-indigo-50 transition-all shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        </button>
                                        @endif
                                    </div>
                                    <x-input-error :messages="$errors->get('ingredientCategoryId')" class="mt-1" />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label value="Base Inventory Unit *" />
                                    <div class="mt-1.5">
                                        <select wire:model.live="ingredientUnit" 
                                            class="w-full h-11 border border-slate-200 rounded-xl text-[13px] font-bold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all">
                                            @foreach($availableUnits as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <x-input-error :messages="$errors->get('ingredientUnit')" class="mt-1" />
                                </div>
                                
                                <div>
                                    <x-input-label value="Low Stock Alert Threshold *" />
                                    <div class="mt-1.5">
                                <x-text-input wire:model.live="ingredientMinStock" class="w-full h-11 font-medium" placeholder="0.00" inputFilter="price" :hasError="$errors->has('ingredientMinStock')" />
                                    </div>
                                    <x-input-error :messages="$errors->get('ingredientMinStock')" class="mt-1" />
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bulk Packaging & Conversions --}}
                    <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm">
                        @php
                            $bestUnit = ''; $bestCost = null;
                            if (!empty($conversionRows)) {
                                foreach($conversionRows as $row) {
                                    $qtyInBase = (float)($row['qty_in_base'] ?? 0);
                                    $unitCost  = (float)($row['price_per_unit'] ?? 0);
                                    if ($qtyInBase > 0 && $unitCost > 0) {
                                        $cost = $unitCost / $qtyInBase;
                                        if ($bestCost === null || $cost < $bestCost) {
                                            $bestCost = $cost;
                                            $bestUnit = $row['unit_name'] ?? '';
                                        }
                                    }
                                }
                            }
                        @endphp

                        <div class="flex items-center justify-between border-b border-slate-50 pb-4 mb-6">
                            <div>
                                <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-widest">Bulk Packaging</h3>
                                <p class="text-[11px] text-slate-400 font-medium mt-1">Define how cases, sacks, or bottles translate to your base unit.</p>
                            </div>
                            @if($this->isSuperAdmin())
                            <button type="button" wire:click="addConversionRow" class="h-9 px-4 bg-indigo-50 text-indigo-600 text-[11px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-100 transition-all flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Add Bulk Unit
                            </button>
                            @endif
                        </div>

                        @if(empty($conversionRows))
                        <div class="py-12 border-2 border-dashed border-slate-100 rounded-2xl text-center">
                            <p class="text-[13px] text-slate-400 font-medium">No bulk units defined for this ingredient.</p>
                        </div>
                    @else
                        <div class="space-y-3 max-h-[400px] overflow-y-auto custom-scrollbar pr-2">
                            @foreach($conversionRows as $i => $row)
                                @php
                                    $qtyInBase   = (float)($row['qty_in_base'] ?? 0);
                                    $unitCost    = (float)($row['price_per_unit'] ?? 0);
                                    $costPerBase = ($qtyInBase > 0 && $unitCost > 0) ? $unitCost / $qtyInBase : null;
                                @endphp
                                <div wire:key="conv-row-{{ $i }}" class="border border-slate-100 rounded-2xl overflow-hidden shadow-sm group">
                                    <div class="flex items-center justify-between p-3 bg-slate-50/50 border-b border-slate-100">
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 flex-1">
                                            <div class="w-full sm:w-40">
                                                <select wire:model.live="conversionRows.{{ $i }}.unit_name" 
                                                    class="w-full h-9 border border-slate-200 rounded-xl text-[12px] font-bold text-slate-700 bg-white focus:ring-2 focus:ring-indigo-100 focus:border-indigo-400 transition-all">
                                                    <option value="">Select Packaging...</option>
                                                    @foreach($availableBulkUnits as $key => $label)
                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="w-full sm:w-40 relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                    <span class="text-[10px] font-black uppercase tracking-tighter">Cost ₱</span>
                                                </div>
                                                <x-text-input wire:model.live="conversionRows.{{ $i }}.price_per_unit" 
                                                    class="w-full h-9 pl-14 text-[12px] font-black text-right bg-slate-50 text-slate-400 cursor-not-allowed" 
                                                    placeholder="0.00" 
                                                    readonly 
                                                />
                                            </div>
                                            @if($costPerBase !== null)
                                                <div class="flex items-center gap-2">
                                                    <div class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg text-[9px] font-black uppercase tracking-tight whitespace-nowrap">
                                                        ₱{{ number_format($costPerBase, 2) }} / {{ $ingredientUnit }}
                                                    </div>
                                                    @if($bestCost !== null && abs($costPerBase - $bestCost) < 0.001)
                                                        <div class="px-2 py-0.5 bg-emerald-500 text-white rounded-lg text-[8px] font-black uppercase tracking-widest flex items-center gap-1">
                                                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                            Best
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        @if($this->isSuperAdmin())
                                        <button type="button" wire:click="removeConversionRow({{ $i }})" class="text-rose-400 hover:text-rose-600 p-1.5 hover:bg-rose-50 rounded-lg transition-all ml-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                        @endif
                                    </div>
                                    <div class="p-3 bg-white">
                                        <div class="flex items-center gap-3">
                                            <div class="w-20 shrink-0 relative group/hint">
                                                <x-text-input 
                                                    wire:model.live="conversionRows.{{ $i }}.chain_multiplier" 
                                                    class="w-full h-8 text-[12px] font-black text-center border-indigo-100 bg-indigo-50/30" 
                                                    placeholder="Size" 
                                                    type="number"
                                                    min="0"
                                                    oninput="this.value = !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null"
                                                    onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46"
                                                />
                                            </div>
                                            <span class="text-slate-300 font-bold text-md">&times;</span>
                                            <div class="flex-1 min-w-[100px]">
                                                @php
                                                    $hasChainingOptions = false;
                                                    foreach($conversionRows as $j => $prevRow) {
                                                        if($j < $i && ($prevRow['unit_name'] ?? '')) {
                                                            $hasChainingOptions = true; break;
                                                        }
                                                    }
                                                @endphp

                                                @if($hasChainingOptions)
                                                    <select wire:model.live="conversionRows.{{ $i }}.chain_from_index" 
                                                        class="w-full h-8 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-600 bg-slate-50/50 px-2 focus:ring-2 focus:ring-indigo-100 transition-all">
                                                        <option value="base">1 {{ strtoupper($ingredientUnit) }}</option>
                                                        @foreach($conversionRows as $j => $prevRow)
                                                            @if($j < $i && ($prevRow['unit_name'] ?? ''))
                                                                    <option value="{{ $j }}">{{ strtoupper($prevRow['unit_name'] ?? '') }} ({{ number_format((float)($prevRow['qty_in_base'] ?? 0), 2) }} {{ strtoupper($ingredientUnit) }})</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <div class="h-8 w-full border border-slate-100 bg-slate-50/30 rounded-lg flex items-center px-3 text-[11px] font-black text-slate-400 italic">
                                                        1 {{ strtoupper($ingredientUnit) }}
                                                        <input type="hidden" wire:model.live="conversionRows.{{ $i }}.chain_from_index" value="base">
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="w-px h-5 bg-slate-100 hidden sm:block"></div>
                                            <div class="flex-1 text-right">
                                                <span class="text-[12px] font-black text-indigo-600 italic font-mono truncate block">{{ number_format((float)($row['qty_in_base'] ?? 0), 2) }} {{ strtoupper($ingredientUnit) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar Column --}}
            <div class="lg:sticky lg:top-6 space-y-6 self-start">
                {{-- Catalog Summary Card --}}
                <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm">
                    <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Catalog Summary
                    </h2>
                    
                    <div class="space-y-5">
                        <div class="flex items-center gap-4 p-4 bg-slate-50/50 rounded-2xl border border-slate-100 transition-all hover:bg-white hover:shadow-md group">
                            <div class="w-12 h-12 bg-white border border-slate-100 rounded-xl flex items-center justify-center text-[18px] font-black text-slate-300 shadow-sm transition-transform group-hover:scale-110">
                                {{ $ingredientName ? strtoupper(substr($ingredientName, 0, 1)) : '?' }}
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-[14px] font-bold text-slate-900 truncate leading-tight">{{ $ingredientName ?: 'Untitled Ingredient' }}</h4>
                                <span class="text-[10px] font-black text-indigo-500/80 uppercase tracking-widest block mt-1">{{ is_string($ingredientUnit) && isset($availableUnits[$ingredientUnit]) ? $availableUnits[$ingredientUnit] : ($ingredientUnit ?: 'PCS') }}</span>
                            </div>
                        </div>

                        <div class="p-4 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 flex items-center justify-between transition-all hover:scale-[1.02] relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                            <div class="relative z-10">
                                <span class="text-[10px] font-black text-indigo-100 uppercase tracking-widest block mb-0.5">Calculated Base Cost</span>
                                <span class="text-[9px] font-bold text-indigo-200/60 uppercase tracking-tight italic">Auto-synced from bulk tiers</span>
                            </div>
                            <div class="text-right relative z-10">
                                <span class="block text-[18px] font-black text-white leading-none italic font-mono">₱{{ number_format($bestCost ?: (float)$ingredientCost, 2) }}</span>
                                <span class="text-[10px] font-black text-indigo-200 mt-1.5 block tracking-widest">PER {{ strtoupper($ingredientUnit) }}</span>
                            </div>
                        </div>

                        @if($bestCost !== null)
                        <div class="p-4 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-[11px] font-black text-emerald-800 uppercase tracking-widest">Optimal Value</span>
                            </div>
                            <p class="text-[12px] text-emerald-700 font-medium leading-relaxed">
                                Buying in <strong class="text-emerald-900">{{ strtoupper($bestUnit) }}</strong> provides the lowest base cost for your recipes.
                            </p>
                        </div>
                        @endif

                        {{-- Batch Breakdown (Read-Only) --}}
                        @if($editIngredientId)
                            @php
                                $activeBatches = \App\Models\StockBatch::where('ingredient_id', $editIngredientId)
                                    ->where('current_quantity', '>', 0)
                                    ->orderBy('expiry_date', 'asc')
                                    ->get();
                            @endphp
                            <div class="pt-6 border-t border-slate-100">
                                <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center justify-between">
                                    <span>Active Batches</span>
                                    <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-full text-[9px]">{{ $activeBatches->count() }} Batches</span>
                                </h3>
                                
                                <div class="space-y-2 max-h-[200px] overflow-y-auto custom-scrollbar pr-2">
                                    @forelse($activeBatches as $batch)
                                        <div class="p-3 bg-slate-50/50 border border-slate-100 rounded-xl flex items-center justify-between group hover:bg-white hover:shadow-sm transition-all">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[11px] font-black text-slate-900 font-mono">{{ $batch->batch_number ?: 'UNTRACKED' }}</span>
                                                    @if($batch->expiry_date)
                                                        @php $daysToExpiry = now()->diffInDays($batch->expiry_date, false); @endphp
                                                        <span class="text-[9px] font-black {{ $daysToExpiry < 7 ? 'text-rose-500' : 'text-slate-400' }} uppercase tracking-tighter">
                                                            Exp: {{ date('M d, Y', strtotime($batch->expiry_date)) }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[10px] font-medium text-slate-400 mt-0.5">{{ $batch->branch->branch_name ?? 'Unknown Branch' }}</div>
                                            </div>
                                            <div class="text-right">
                                                <span class="text-[13px] font-black text-slate-700 italic font-mono">{{ number_format($batch->current_quantity, 2) }}</span>
                                                <span class="text-[9px] font-black text-slate-400 uppercase ml-0.5">{{ $ingredientUnit }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="py-6 text-center border-2 border-dashed border-slate-50 rounded-2xl">
                                            <span class="text-[11px] text-slate-400 font-medium">No stock batches found.</span>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-8 space-y-3">
                        <x-primary-button type="button" wire:click="validateBeforeSaveIngredient" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                            <span x-text="mode === 'edit' ? 'Update Catalog' : 'Add to Catalog'"></span>
                        </x-primary-button>
                        <x-secondary-button wire:click="backToList" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest border-slate-200 text-slate-500">
                            Discard Draft
                        </x-secondary-button>
                    </div>

                    @if($editIngredientId && $this->isSuperAdmin())
                        <div class="bg-red-50 border border-red-100 rounded-2xl p-6 shadow-sm mt-8">
                            <h2 class="text-[13px] font-bold text-red-600 uppercase tracking-wider mb-2">Danger Zone</h2>
                            <p class="text-[12px] text-gray-500 mb-4 leading-relaxed">Permanently remove this ingredient from the system.</p>
                            <x-danger-button type="button" wire:click="confirmIngredientDeletion({{ $editIngredientId }})" class="w-full justify-center h-11">
                                Delete Item
                            </x-danger-button>
                        </div>
                    @endif
                </div>

                {{-- Operation Guide Card --}}
                <div class="bg-slate-900 rounded-2xl p-6 shadow-xl shadow-slate-200 overflow-hidden relative">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full -mr-16 -mt-16"></div>
                    
                    <h2 class="text-[11px] font-black text-indigo-400 uppercase tracking-widest mb-6 flex items-center gap-2 relative z-10">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Operation Guide
                    </h2>

                    <div class="space-y-6 relative z-10">
                        <div>
                            <h4 class="text-[13px] font-bold text-white mb-2 italic">What is "Multiplier"?</h4>
                            <p class="text-[12px] text-slate-400 leading-relaxed">
                                It's the <span class="text-indigo-300 font-bold">Pack Size</span>. It tells the system how many units are inside one bulk package.
                            </p>
                            <div class="mt-3 p-3 bg-slate-800/50 rounded-xl border border-slate-700/50">
                                <p class="text-[11px] text-slate-300 font-mono">
                                    <span class="text-indigo-400 font-black">EX:</span> 1 Bottle contains <span class="text-emerald-400 font-black">500</span> Grams.<br>
                                    Set Multiplier to <span class="text-emerald-400 font-black">500</span>.
                                </p>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-800">
                            <h4 class="text-[13px] font-bold text-white mb-2 italic">Automated Cost Tracking</h4>
                            <p class="text-[12px] text-slate-400 leading-relaxed">
                                Catalog costs are <span class="text-emerald-400 font-bold">Locked</span>. They auto-update whenever you perform a <span class="text-indigo-300 font-bold">Stock In</span>. This ensures your recipe margins always reflect real market prices.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Quick Tips Card --}}
                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
                    <h4 class="text-[11px] font-black text-indigo-700 uppercase tracking-widest mb-3">Quick Tips</h4>
                    <ul class="space-y-3">
                        <li class="flex gap-3 text-[12px] text-slate-600">
                            <span class="text-indigo-500 font-black">01</span>
                            <p>Always define the <strong class="text-indigo-700">Base Unit</strong> first (e.g. Grams, ML, Pcs).</p>
                        </li>
                        <li class="flex gap-3 text-[12px] text-slate-600">
                            <span class="text-indigo-500 font-black">02</span>
                            <p>The <strong class="text-indigo-700">Base Cost</strong> in the summary helps you spot the cheapest way to buy.</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>{{-- end panel form --}}

    <!-- Quick Add Ingredient Category Modal -->
    <x-modal name="quick-add-ingredient-category" :show="$errors->has('newCategoryName')" focusable>
        <div class="p-6">
            <h2 class="text-[16px] font-black text-gray-900 uppercase tracking-widest">New Category</h2>
            <p class="mt-1 text-[13px] text-gray-500 font-medium">Add a new sorting group for your kitchen supplies.</p>
            <div class="mt-5">
                <x-input-label for="new_ing_category_name" value="Category Label" />
                <x-text-input id="new_ing_category_name" wire:model.live.debounce.400ms="newCategoryName" type="text" class="block w-full h-11 mt-1.5" placeholder="e.g. Seafood & Frozen" autofocus inputFilter="name" :hasError="$errors->has('newCategoryName')" />
                <x-input-error :messages="$errors->get('newCategoryName')" class="mt-1" />
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button @click="$dispatch('close-modal', 'quick-add-ingredient-category')" class="h-11">
                    Cancel
                </x-secondary-button>
                <x-primary-button wire:click="quickAddCategory" class="h-11 font-black uppercase tracking-widest text-[11px]">
                    Create Category
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- â”€â”€ Save Ingredient Confirmation Modal â”€â”€ --}}
    <x-modal name="confirm-save-ingredient" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight" x-text="mode === 'edit' ? 'Update Ingredient' : 'Add Ingredient'"></h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed" x-text="mode === 'edit' ? 'Apply changes to this catalog item?' : 'Register this new ingredient?'"></p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-save-ingredient')" class="h-10">Cancel</x-secondary-button>
                <x-primary-button wire:click="saveIngredient" class="h-10">
                    Confirm & Save
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- â”€â”€ Permanent Deletion Confirmation Modal â”€â”€ --}}
    <x-modal name="confirm-delete-ingredient" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-red-400 to-rose-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Delete Ingredient</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                        Are you sure you want to permanently delete <span class="text-red-600 font-bold">"{{ $deleteTargetName }}"</span>? 
                        This will remove it from all branch inventories globally.
                    </p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-delete-ingredient')" class="h-10">Cancel</x-secondary-button>
                <x-danger-button wire:click="deleteIngredient" class="h-10">
                    Permanently Delete
                </x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- â”€â”€ Batch Disposal Confirmation Modal â”€â”€ --}}
    <x-modal name="confirm-waste-batch" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 leading-tight">Dispose Batch</h3>
                    <p class="mt-1 text-[13px] text-gray-500 leading-relaxed">
                        Are you sure you want to dispose <span class="text-amber-600 font-bold">{{ \App\Helpers\StockHelper::formatForDisplay($wasteTargetQty, $wasteTargetUnit) }}</span> of 
                        <span class="text-slate-900 font-bold">"{{ $wasteTargetName }}"</span>? 
                        This will log the amount as waste and remove it from inventory.
                    </p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-waste-batch')" class="h-10">Cancel</x-secondary-button>
                <x-danger-button wire:click="wasteBatch" class="h-10 bg-amber-600 hover:bg-amber-700 border-amber-600">
                    Confirm Disposal
                </x-danger-button>
            </div>
        </div>
    </x-modal>

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• PANEL â€” EXPIRY TRACKING â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div x-show="panel === 'expiry'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak class="px-1">

        {{-- Expiry System Metrics --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Expired --}}
            <div wire:click="$set('expiryStatusFilter', 'expired')" 
                class="bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all cursor-pointer relative overflow-hidden {{ $expiryStatusFilter === 'expired' ? 'ring-2 ring-rose-500 ring-offset-2' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm transition-transform group-hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-rose-700/60 uppercase tracking-widest leading-none mb-1">Expired</span>
                    <span class="block text-[20px] font-black text-rose-700 leading-none">{{ number_format($expiryStats['expired']) }}</span>
                </div>
                @if($expiryStats['expired'] > 0)
                    <div class="absolute top-2 right-2">
                        <span class="flex h-2 w-2"><span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-rose-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span></span>
                    </div>
                @endif
            </div>

            {{-- Expiring Soon --}}
            <div wire:click="$set('expiryStatusFilter', 'expiring')" 
                class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all cursor-pointer relative overflow-hidden {{ $expiryStatusFilter === 'expiring' ? 'ring-2 ring-amber-500 ring-offset-2' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm transition-transform group-hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-amber-700/60 uppercase tracking-widest leading-none mb-1">Expiring &le;{{ $alertDays }}d</span>
                    <span class="block text-[20px] font-black text-amber-700 leading-none">{{ number_format($expiryStats['expiring']) }}</span>
                </div>
                @if($expiryStats['expiring'] > 0)
                    <div class="absolute top-2 right-2">
                        <span class="flex h-2 w-2"><span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span></span>
                    </div>
                @endif
            </div>

            {{-- Fresh --}}
            <div wire:click="$set('expiryStatusFilter', 'fresh')" 
                class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all cursor-pointer relative overflow-hidden {{ $expiryStatusFilter === 'fresh' ? 'ring-2 ring-emerald-500 ring-offset-2' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm transition-transform group-hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-emerald-700/60 uppercase tracking-widest leading-none mb-1">Stable Stock</span>
                    <span class="block text-[20px] font-black text-emerald-700 leading-none">{{ number_format($expiryStats['fresh']) }}</span>
                </div>
            </div>

            {{-- No Date --}}
            <div wire:click="$set('expiryStatusFilter', 'no_date')" 
                class="bg-gradient-to-br from-slate-50 to-slate-100 border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:shadow-md transition-all cursor-pointer relative overflow-hidden {{ $expiryStatusFilter === 'no_date' ? 'ring-2 ring-slate-400 ring-offset-2' : '' }}">
                <div class="w-10 h-10 rounded-xl bg-white border border-slate-100 flex items-center justify-center text-slate-400 shadow-sm transition-transform group-hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-black text-slate-500/60 uppercase tracking-widest leading-none mb-1">Non-Perishable</span>
                    <span class="block text-[20px] font-black text-slate-700 leading-none">{{ number_format($expiryStats['no_date']) }}</span>
                </div>
            </div>
        </div>

        {{-- macOS Style Expiry Toolbar --}}
        <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
            <div class="flex flex-1 w-full lg:w-auto">
                <x-search-bar wireModel="expirySearch" placeholder="Filter batches..." width="w-full lg:w-72" />
            </div>

            <div class="flex flex-wrap items-center lg:justify-end gap-2">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-1.5 h-9 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            <span class="text-[12px] whitespace-nowrap">{{ ['all' => 'All Batches', 'expired' => 'Expired Only', 'expiring' => 'Expiring Soon', 'fresh' => 'Stable Stock', 'no_date' => 'Non-Perishables'][$expiryStatusFilter] ?? 'Filter Status' }}</span>
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        @foreach(['all' => 'All Batches', 'expired' => 'Expired Only', 'expiring' => 'Expiring Soon', 'fresh' => 'Stable Stock', 'no_date' => 'Non-Perishables'] as $val => $label)
                            <x-dropdown-link href="#" wire:click.prevent="$set('expiryStatusFilter', '{{ $val }}')">
                                <div class="flex items-center gap-2">
                                    @if($val === 'expired') <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    @elseif($val === 'expiring') <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    @elseif($val === 'fresh') <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    @endif
                                    {{ $label }}
                                </div>
                            </x-dropdown-link>
                        @endforeach
                    </x-slot>
                </x-dropdown>
            </div>
        </div>

        {{-- Batch Intelligence Table â”€â”€ --}}
        <div class="relative min-h-[400px]">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-6 border-r border-slate-100/50 text-left text-[11px] font-black text-slate-500 uppercase tracking-widest">Ingredient Batch</th>
                    <th class="py-3 px-6 border-r border-slate-100/50 text-center text-[11px] font-black text-slate-500 uppercase tracking-widest">Branch</th>
                    <th class="py-3 px-6 border-r border-slate-100/50 text-right text-[11px] font-black text-slate-500 uppercase tracking-widest">Quantity</th>
                    <th class="py-3 px-6 border-r border-slate-100/50 text-center text-[11px] font-black text-slate-500 uppercase tracking-widest">Expiry</th>
                    <th class="py-3 px-6 border-r border-slate-100/50 text-center text-[11px] font-black text-slate-500 uppercase tracking-widest">Status</th>
                    @if(!$this->isStaff())
                        <th class="py-3 px-6 text-right text-[11px] font-black text-slate-500 uppercase tracking-widest">Action</th>
                    @endif
                </x-slot>

                <tbody class="divide-y divide-slate-100/80" wire:key="stock-expiry-body-{{ $expiryQuery->currentPage() }}-{{ $expiryStatusFilter }}" wire:loading.class="opacity-40" wire:target="expirySearch, expiryStatusFilter">
                    @forelse($expiryQuery as $batch)
                        @php
                            $expiry     = $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->startOfDay() : null;
                            $todayC     = \Carbon\Carbon::today();
                            $alertCutoff = $todayC->copy()->addDays($alertDays);
                            $isExpired  = $expiry && $expiry->lt($todayC);
                            $isExpiring = $expiry && !$isExpired && $expiry->lte($alertCutoff);
                            $isFresh    = $expiry && $expiry->gt($alertCutoff);
                            $daysLeft   = $expiry ? (int) $todayC->diffInDays($expiry, false) : null;
                        @endphp
                        <tr class="hover:bg-slate-50/50 transition-colors {{ $isExpired ? 'bg-rose-50/30' : ($isExpiring ? 'bg-amber-50/30' : '') }}">
                            <td class="py-4 px-6 border-r border-slate-100/50 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-[14px] font-bold text-slate-900">{{ $batch->ingredient->name ?? 'â€”' }}</span>
                                    <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest">{{ $batch->ingredient->unit ?? '' }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-r border-slate-100/50 text-center whitespace-nowrap">
                                <span class="text-[12px] font-bold text-slate-600">{{ $batch->branch->branch_name ?? 'â€”' }}</span>
                            </td>
                            <td class="py-4 px-6 border-r border-slate-100/50 text-right whitespace-nowrap">
                                <span class="text-[15px] font-black {{ $isExpired ? 'text-rose-600' : 'text-slate-900' }}">
                                    {{ \App\Helpers\StockHelper::formatForDisplay($batch->current_quantity, $batch->ingredient->unit ?? 'pcs') }}
                                </span>
                            </td>
                            <td class="py-4 px-6 border-r border-slate-100/50 text-center whitespace-nowrap">
                                @if($batch->expiry_date)
                                    <div class="flex flex-col items-center">
                                        <span class="text-[13px] font-bold {{ $isExpired ? 'text-rose-600' : ($isExpiring ? 'text-amber-600' : 'text-slate-700') }}">
                                            {{ \Carbon\Carbon::parse($batch->expiry_date)->format('M d, Y') }}
                                        </span>
                                        <span class="text-[10px] font-medium text-slate-400">{{ \Carbon\Carbon::parse($batch->expiry_date)->diffForHumans() }}</span>
                                    </div>
                                @else
                                    <span class="text-[12px] text-slate-400 italic">No date</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 border-r border-slate-100/50 text-center whitespace-nowrap">
                                @if($isExpired)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-100 text-rose-700 text-[10px] font-black uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block animate-pulse"></span> EXPIRED
                                    </span>
                                @elseif($isExpiring)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 text-[10px] font-black uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block animate-pulse"></span>
                                        {{ $daysLeft === 0 ? 'TODAY' : $daysLeft . 'D LEFT' }}
                                    </span>
                                @elseif($isFresh)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase tracking-wider">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span> FRESH
                                    </span>
                                @else
                                    <span class="text-[11px] text-slate-400 italic font-medium tracking-tight">NON-PERISHABLE</span>
                                @endif
                            </td>
                            @if(!$this->isStaff())
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    @if($isExpired || $isExpiring)
                                        <x-danger-button wire:click="confirmWasteBatch({{ $batch->id }})"
                                            class="h-8 !px-3 text-[10px] font-black uppercase tracking-widest bg-amber-600 hover:bg-amber-700 border-amber-600">
                                            Dispose
                                        </x-danger-button>
                                    @else
                                        <span class="text-slate-300">â€”</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $this->isStaff() ? 5 : 6 }}" class="py-12">
                                <x-empty-state 
                                    title="No batches found" 
                                    description="Everything looks clear for now."
                                    icon="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </x-data-table>
            <div class="mt-4 px-1">
                <x-pagination :paginator="$expiryQuery" keyPrefix="stock-expiry" />
            </div>
        </div>
    </div>

    </div>{{-- end relative wrapper --}}
</div>

