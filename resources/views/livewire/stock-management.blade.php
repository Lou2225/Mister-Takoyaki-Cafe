@php
    $user = auth()->user();
    // Primary palette
    $primaryColor = $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald');
    $primaryText = "text-{$primaryColor}-600";
    $primaryBg = "bg-{$primaryColor}-600";
@endphp

<div
    x-data="typeof window.stockManagement === 'function' ? window.stockManagement($wire) : { panel: 'list', mode: 'list', formErrors: {}, formSubmitted: false }"
    x-on:switch-panel.window="panel = $event.detail.panel; if($event.detail.mode) mode = $event.detail.mode"
    @trigger-edit-ingredient.window="$wire.showEdit($event.detail.id)"
    wire:key="stock-management-main-container"
    class="relative">

    {{-- Hidden reactive updaters: OUTSIDE wire:ignore so Livewire can update them --}}
    @php
    $ingredientSyncData = $allIngredients->map(function($i) use ($selectedBranchId, $inventoryConfig) {
        $globalLow = $inventoryConfig['low_stock_threshold'] ?? 10;
        $globalCritical = $inventoryConfig['critical_stock_threshold'] ?? 5;
        $effectiveMin = $i->minimum_stock > 0 ? $i->minimum_stock : $globalLow;
        $stock = $selectedBranchId
            ? (collect($i->branchStocks)->where('branch_id', $selectedBranchId)->first()?->stock_quantity ?? 0)
            : collect($i->branchStocks)->sum('stock_quantity');
        $status = 'healthy';
        if ($stock <= $globalCritical) $status = 'critical';
        elseif ($stock <= $effectiveMin) $status = 'low';
        return [
            'id'          => $i->id,
            'name'        => $i->name,
            'category_id' => $i->category_id,
            'status'      => $status,
        ];
    });
    @endphp
    <div x-effect="updateIngredientsList(@js($ingredientSyncData))" class="hidden" wire:key="ingredients-sync-helper"></div>
    <div x-effect="updateBatchesList(@js($allBatches->map(fn($b) => [
        'id' => $b->id,
        'ingredient_name' => $b->ingredient->name ?? '',
        'status' => $b->computed_status
    ])))" class="hidden" wire:key="batches-sync-helper"></div>

        {{-- wire:ignore wraps only the list/expiry panels, NOT the form --}}
    <div class="relative min-h-[600px]" wire:init="triggerExpiryAlerts">

        {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• DYNAMIC HEADER â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
        <div x-show="(panel === 'list' || panel === 'expiry')" x-cloak class="px-1">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Inventory Overview</h2>
                    <p class="text-[12px] text-gray-500 font-medium">Managing <span class="text-indigo-600 font-bold">{{ $totalIngredients }} catalog assets</span></p>
                </div>
                <div class="flex items-center gap-3">
                    <x-report-dropdown module="Stock Report" />
                    @if(!$this->isStaff() && $this->isSuperAdmin())
                        <x-primary-button type="button" @click="showCreate()" class="h-10 !px-3 sm:!px-4">
                            <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            <span class="hidden sm:inline">Add Ingredient</span>
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
                    @php 
                        $expiredBadge = \App\Models\StockBatch::where('current_quantity', '>', 0)
                            ->where('expiry_date', '<', today())
                            ->when($selectedBranchId, fn($q) => $q->where('branch_id', $selectedBranchId))
                            ->count(); 
                    @endphp
                    @if($expiredBadge > 0)
                        <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-red-500 text-white text-[9px] font-black ml-1">{{ $expiredBadge }}</span>
                    @endif
                </x-sliding-tab>
            </x-sliding-tabs>
        </div>

        {{-- ═══════════════ PANEL 1 – LIST ═══════════════ --}}
        <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">

            {{-- KPI Dashboard (User Management Aesthetic) --}}
            {{-- KPI Dashboard (Compact Glassmorphic Theme) --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
                {{-- Total Assets --}}
                <div class="bg-gradient-to-br from-indigo-50 to-white border border-indigo-100 rounded-2xl p-4 shadow-[0_8px_30px_rgba(0,0,0,0.02)] transition-all hover:shadow-md hover:scale-[1.01] duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-indigo-700/80 uppercase tracking-widest leading-none">Total Catalog</span>
                        <div class="w-7 h-7 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-600 border border-indigo-500/10">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-[20px] font-black text-slate-900 tracking-tight leading-none">{{ number_format($totalIngredients) }}</span>
                        <span class="text-[9px] font-bold text-slate-400">items</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 mt-1.5 leading-none">Active catalog assets</p>
                </div>
                
                {{-- Low Stock Alerts --}}
                @php
                    $isLowStockActive = $lowStockWarnings > 0;
                    $lowStockTheme = $isLowStockActive ? 'amber' : 'emerald';
                    $lowStockGradient = $isLowStockActive ? 'from-amber-50 border-amber-100 text-amber-700/80' : 'from-emerald-50 border-emerald-100 text-emerald-700/80';
                    $lowStockIconBg = $isLowStockActive ? 'bg-amber-500/10 text-amber-600 border-amber-500/10' : 'bg-emerald-500/10 text-emerald-600 border-emerald-500/10';
                    $lowStockValColor = $isLowStockActive ? 'text-amber-600' : 'text-emerald-600';
                @endphp
                <div class="bg-gradient-to-br {{ $lowStockGradient }} to-white border rounded-2xl p-4 shadow-[0_8px_30px_rgba(0,0,0,0.02)] transition-all hover:shadow-md hover:scale-[1.01] duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest leading-none">Low Stock</span>
                        <div class="w-7 h-7 rounded-lg {{ $lowStockIconBg }} flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-[20px] font-black {{ $lowStockValColor }} tracking-tight leading-none">{{ $lowStockWarnings }}</span>
                        <span class="text-[9px] font-bold text-slate-400">alerts</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 mt-1.5 leading-none">{{ $isLowStockActive ? 'Needs replenishment' : 'Stock levels healthy' }}</p>
                </div>

                {{-- Expiring --}}
                @php
                    $isExpiringActive = $expiringCount > 0;
                    $expTheme = $isExpiringActive ? 'rose' : 'slate';
                    $expGradient = $isExpiringActive ? 'from-rose-50 border-rose-100 text-rose-700/80' : 'from-slate-50 border-slate-100 text-slate-700/80';
                    $expIconBg = $isExpiringActive ? 'bg-rose-500/10 text-rose-600 border-rose-500/10' : 'bg-slate-500/10 text-slate-500 border-slate-500/10';
                    $expValColor = $isExpiringActive ? 'text-rose-600' : 'text-slate-800';
                @endphp
                <div class="bg-gradient-to-br {{ $expGradient }} to-white border rounded-2xl p-4 shadow-[0_8px_30px_rgba(0,0,0,0.02)] transition-all hover:shadow-md hover:scale-[1.01] duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black uppercase tracking-widest leading-none">Expiring</span>
                        <div class="w-7 h-7 rounded-lg {{ $expIconBg }} flex items-center justify-center">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-[20px] font-black {{ $expValColor }} tracking-tight leading-none">{{ $expiringCount }}</span>
                        <span class="text-[9px] font-bold text-slate-400">batches</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 mt-1.5 leading-none">{{ $isExpiringActive ? 'Requires immediate action' : 'All batches fresh' }}</p>
                </div>

                {{-- Monthly Procurement --}}
                <div class="bg-gradient-to-br from-emerald-50 to-white border border-emerald-100 rounded-2xl p-4 shadow-[0_8px_30px_rgba(0,0,0,0.02)] transition-all hover:shadow-md hover:scale-[1.01] duration-300">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-black text-emerald-700/80 uppercase tracking-widest leading-none">Procurement</span>
                        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 border border-emerald-500/10">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-[20px] font-black text-slate-900 tracking-tight leading-none">₱{{ number_format($monthlyProcurement, 0) }}</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 mt-1.5 leading-none">Current month total cost</p>
                </div>
            </div>


            {{-- macOS Style Unified Toolbar --}}
<div class="relative z-20 flex flex-row items-center justify-between mb-6 gap-2 sm:gap-3 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
    
    {{-- Left: Search Bar --}}
    <div class="flex-1 min-w-0 lg:flex-initial">
        <x-search-bar x-model.debounce.50ms="stockSearch" placeholder="Find ingredient..." width="w-full lg:w-80" />
    </div>

    {{-- Right: Filters --}}
    <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">

        {{-- Status Filter --}}
        <x-dropdown align="left" width="48">
            <x-slot name="trigger">
                <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span class="hidden sm:inline text-[12px]" x-text="{'': 'All Status', 'healthy': 'Healthy', 'low': 'Low Stock', 'critical': 'Critical'}[stockStatusFilter] || 'All Status'"></span>
                    <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </x-secondary-button>
            </x-slot>
            <x-slot name="content">
                <x-dropdown-link href="#" x-on:click.prevent="stockStatusFilter = ''; stockCurrentPage = 1; dropdownOpen = false;">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-slate-300"></span> All Status</div>
                </x-dropdown-link>
                <x-dropdown-link href="#" x-on:click.prevent="stockStatusFilter = 'healthy'; stockCurrentPage = 1; dropdownOpen = false;">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Healthy</div>
                </x-dropdown-link>
                <x-dropdown-link href="#" x-on:click.prevent="stockStatusFilter = 'low'; stockCurrentPage = 1; dropdownOpen = false;">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Low Stock</div>
                </x-dropdown-link>
                <x-dropdown-link href="#" x-on:click.prevent="stockStatusFilter = 'critical'; stockCurrentPage = 1; dropdownOpen = false;">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-red-500"></span> Critical</div>
                </x-dropdown-link>
            </x-slot>
        </x-dropdown>

        {{-- Category Filter --}}
        <x-dropdown align="left" width="48">
            <x-slot name="trigger">
                <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    <span class="hidden sm:inline text-[12px] truncate max-w-[100px]" x-text="stockCategoryFilter ? (stockCategoryLabels[stockCategoryFilter] || 'Category') : 'All Categories'">All Categories</span>
                    <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </x-secondary-button>
            </x-slot>
            <x-slot name="content">
                <div class="max-h-60 overflow-y-auto custom-scrollbar">
                    <x-dropdown-link href="#" x-on:click.prevent="stockCategoryFilter = ''; stockCurrentPage = 1; dropdownOpen = false;">
                        All Categories
                    </x-dropdown-link>
                    <hr class="border-slate-50">
                    @foreach($allIngredientCategories as $cat)
                        <x-dropdown-link href="#" x-on:click.prevent="stockCategoryFilter = '{{ $cat->id }}'; stockCurrentPage = 1; dropdownOpen = false;">
                            {{ $cat->name }}
                        </x-dropdown-link>
                    @endforeach
                </div>
            </x-slot>
        </x-dropdown>

        {{-- Branch Switcher --}}
        @if($this->isSuperAdmin())
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        <span class="hidden sm:inline text-[12px] truncate max-w-[120px]">{{ $selectedBranchId ? ($branches->firstWhere('id', $selectedBranchId)->branch_name ?? 'Select Branch') : 'Select Branch' }}</span>
                        <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
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
            <div class="inline-flex items-center justify-center sm:justify-between gap-2 px-2.5 sm:px-3 bg-emerald-50 border border-emerald-100 rounded-lg font-bold text-[11px] text-emerald-700 h-10 shrink-0">
                <svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="hidden sm:inline">Branch Stock</span>
            </div>
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

                    <tbody class="divide-y divide-slate-100/80" wire:key="stock-list-body-{{ $selectedBranchId }}">
                        @forelse($allIngredients as $ing)
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
                            <tr wire:key="stock-row-{{ $ing->id }}" x-show="isIngredientVisible({{ $ing->id }})" x-cloak class="hover:bg-slate-50/50 transition-colors group/row">
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
                                        @if($this->isSuperAdmin())
                                            <x-secondary-button type="button" @click="showEdit({{ $ing->id }})" class="h-8 px-3 text-[11px] font-bold bg-white hover:bg-slate-50 border-slate-200">
                                                Edit
                                            </x-secondary-button>
                                        @endif
                                        
                                        @if(!$this->isStaff())
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
                        <tr x-show="filteredIngredientIds.length === 0 && stockSearch.trim() !== ''" x-cloak>
                            <td colspan="5" class="py-12">
                                <x-empty-state title="No ingredients match your search" description="Try a different name or clear your search." />
                            </td>
                        </tr>
                    </tbody>
                </x-data-table>
                <div class="mt-4 px-1">
                    <template x-if="filteredIngredientIds.length > 0 || stockSearch.trim() === ''">
                        <div class="flex flex-col lg:flex-row items-center justify-between px-4 py-4 bg-white border-t border-gray-100 lg:px-6 gap-6">
                            <div class="flex flex-col sm:flex-row items-center justify-between w-full lg:w-auto gap-4 sm:gap-8">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:gap-1.5 leading-[0.9] sm:leading-none">
                                        <span class="text-[11px] sm:text-[12px] text-gray-400 font-black uppercase tracking-tighter sm:tracking-widest">Row</span>
                                        <span class="text-[9px] sm:text-[12px] text-gray-400/70 font-black uppercase tracking-tighter sm:tracking-widest">per page</span>
                                    </div>
                                    <div>
                                        <x-dropdown align="top" width="20" containerClasses="block">
                                            <x-slot name="trigger">
                                                <button type="button" class="inline-flex items-center justify-between min-w-[70px] px-3 py-1.5 text-[13px] font-black text-gray-900 bg-slate-50 border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none transition-all h-10 gap-2 shadow-sm">
                                                    <span x-text="stockPerPage"></span>
                                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <template x-for="option in [5, 10, 15, 30, 50, 100]">
    <x-dropdown-link href="#" x-on:click.prevent="stockPerPage = option; stockCurrentPage = 1; dropdownOpen = false;">
        <span x-text="option"></span>
    </x-dropdown-link>
</template>
                                            </x-slot>
                                        </x-dropdown>
                                    </div>
                                </div>
                                <div class="text-[11px] text-gray-400 font-bold uppercase tracking-widest whitespace-nowrap">
                                    <span class="text-gray-900" x-text="Math.min(filteredIngredientIds.length, (stockCurrentPage - 1) * stockPerPage + 1)"></span>
                                    <span class="mx-0.5 text-gray-300">-</span>
                                    <span class="text-gray-900" x-text="Math.min(filteredIngredientIds.length, stockCurrentPage * stockPerPage)"></span>
                                    <span class="mx-1 text-gray-300 lowercase italic font-medium">of</span>
                                    <span class="text-indigo-600" x-text="filteredIngredientIds.length"></span>
                                    <span class="ml-1 text-gray-300 lowercase italic font-medium">results</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 w-full lg:w-auto justify-center lg:justify-end border-t border-gray-50 pt-4 lg:border-0 lg:pt-0">
                                <x-secondary-button @click="stockCurrentPage = 1" ::disabled="stockCurrentPage === 1" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="stockCurrentPage === 1 ? 'opacity-30 pointer-events-none' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                    </svg>
                                </x-secondary-button>
                                <x-secondary-button @click="stockCurrentPage = Math.max(1, stockCurrentPage - 1)" ::disabled="stockCurrentPage === 1" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="stockCurrentPage === 1 ? 'opacity-30 pointer-events-none' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </x-secondary-button>
                                <div class="flex items-center gap-1.5 px-2">
                                    <template x-for="page in stockPageNumbers">
                                        <div class="flex items-center gap-1.5">
                                            <template x-if="page === stockCurrentPage">
                                                <x-primary-button class="!p-0 w-9 h-9 items-center justify-center !rounded-xl bg-gray-900 text-[13px] font-black shadow-none ring-0">
                                                    <span x-text="page"></span>
                                                </x-primary-button>
                                            </template>
                                            <template x-if="page !== stockCurrentPage">
                                                <x-secondary-button @click="stockCurrentPage = page" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                                                    <span x-text="page"></span>
                                                </x-secondary-button>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="Math.ceil(filteredIngredientIds.length / stockPerPage) > stockCurrentPage + 1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-gray-300 font-bold mx-1">...</span>
                                            <x-secondary-button @click="stockCurrentPage = Math.ceil(filteredIngredientIds.length / stockPerPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                                                <span x-text="Math.ceil(filteredIngredientIds.length / stockPerPage)"></span>
                                            </x-secondary-button>
                                        </div>
                                    </template>
                                </div>
                                <x-secondary-button @click="stockCurrentPage = Math.min(Math.ceil(filteredIngredientIds.length / stockPerPage), stockCurrentPage + 1)" ::disabled="stockCurrentPage >= Math.ceil(filteredIngredientIds.length / stockPerPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="stockCurrentPage >= Math.ceil(filteredIngredientIds.length / stockPerPage) ? 'opacity-30 pointer-events-none' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </x-secondary-button>
                                <x-secondary-button @click="stockCurrentPage = Math.ceil(filteredIngredientIds.length / stockPerPage) || 1" ::disabled="stockCurrentPage >= Math.ceil(filteredIngredientIds.length / stockPerPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="stockCurrentPage >= Math.ceil(filteredIngredientIds.length / stockPerPage) ? 'opacity-30 pointer-events-none' : ''">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                    </svg>
                                </x-secondary-button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>{{-- end panel 1 --}}

        {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• PANEL 4 — FORM (CREATE/EDIT) â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
        <div x-show="panel === 'form'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight" x-text="mode === 'edit' ? 'Update Ingredient' : 'Add New Ingredient'"></h2>
                    <p class="text-[12px] text-gray-500 font-medium" x-text="mode === 'edit' ? 'Ingredient specifications and availability' : 'New ingredient catalog entry'"></p>
                </div>
                <x-secondary-button type="button" @click="backToList()" class="h-10">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to List
                </x-secondary-button>
            </div>

            {{-- Skeleton Preloader (displays while loading edit data) --}}
            <div x-show="isLoadingData" x-cloak class="animate-pulse space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Main Column Skeleton --}}
                    <div class="lg:col-span-2 space-y-6">
                        {{-- Core Config Skeleton --}}
                        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm space-y-6">
                            <div class="flex items-center gap-2 mb-6">
                                <div class="w-2 h-2 rounded-full bg-slate-200"></div>
                                <div class="h-3.5 bg-slate-200 rounded w-36"></div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <div class="h-3 bg-slate-200 rounded w-28"></div>
                                    <div class="h-11 bg-slate-100 rounded-xl"></div>
                                </div>
                                <div class="space-y-2">
                                    <div class="h-3 bg-slate-200 rounded w-24"></div>
                                    <div class="h-11 bg-slate-100 rounded-xl"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <div class="h-3 bg-slate-200 rounded w-36"></div>
                                    <div class="h-11 bg-slate-100 rounded-xl"></div>
                                </div>
                                <div class="space-y-2">
                                    <div class="h-3 bg-slate-200 rounded w-36"></div>
                                    <div class="h-11 bg-slate-100 rounded-xl"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Bulk Packaging Skeleton --}}
                        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm space-y-5">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-50">
                                <div class="space-y-1.5">
                                    <div class="h-4 bg-slate-200 rounded w-32"></div>
                                    <div class="h-3 bg-slate-100 rounded w-60"></div>
                                </div>
                                <div class="h-9 w-28 bg-slate-100 rounded-lg"></div>
                            </div>
                            <div class="border border-slate-100 rounded-2xl p-4 space-y-3">
                                <div class="flex items-center justify-between pb-2 border-b border-slate-50">
                                    <div class="h-5 w-20 bg-slate-200 rounded-lg"></div>
                                    <div class="h-5 w-8 bg-slate-100 rounded-lg"></div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3.5 pt-2">
                                    <div class="sm:col-span-5 h-10 bg-slate-100 rounded-xl"></div>
                                    <div class="sm:col-span-4 h-10 bg-slate-100 rounded-xl"></div>
                                    <div class="sm:col-span-3 h-10 bg-slate-100 rounded-xl"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sidebar Column Skeleton --}}
                    <div class="space-y-6">
                        <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm space-y-5">
                            <div class="h-4 bg-slate-200 rounded w-32 mb-6"></div>
                            <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="w-12 h-12 bg-slate-200 rounded-xl"></div>
                                <div class="space-y-2 flex-1">
                                    <div class="h-4 bg-slate-200 rounded w-3/4"></div>
                                    <div class="h-3 bg-slate-200 rounded w-1/3"></div>
                                </div>
                            </div>
                            <div class="h-16 bg-slate-100 rounded-2xl"></div>
                            <div class="h-12 bg-slate-200 rounded-xl"></div>
                            <div class="h-11 bg-slate-100 rounded-xl"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="!isLoadingData" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                                    <x-text-input wire:model.live.debounce.400ms="ingredientName" 
                                        @input="if (typeof formErrors !== 'undefined' && formErrors) delete formErrors.name"
                                        class="w-full mt-1.5 h-11 font-medium" 
                                        placeholder="e.g. Octopus Bits" 
                                        inputFilter="name" 
                                        ::class="(typeof formErrors !== 'undefined' && formErrors && formErrors.name) ? '!border-red-400 focus:!border-red-400 focus:!ring-red-300 !bg-red-50/30' : ''"
                                        :hasError="$errors->has('ingredientName')" />
                                    <div x-show="typeof formErrors !== 'undefined' && formErrors && formErrors.name" 
                                        class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200" 
                                        x-cloak>
                                        <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <ul class="space-y-0.5">
                                            <li x-text="(typeof formErrors !== 'undefined' && formErrors && formErrors.name) || ''"></li>
                                        </ul>
                                    </div>
                                    <x-input-error :messages="$errors->get('ingredientName')" class="mt-1" />
                                </div>
                                <div>
                                    <x-input-label value="Item Category" />
                                    <div class="flex gap-2 mt-1.5">
                                        <div class="flex-1 relative"
                                            x-data="{
                                                open: false,
                                                dropUp: false,
                                                search: '',
                                                selectedId: $wire.entangle('ingredientCategoryId'),
                                                get categoriesList() {
                                                    return @js($allIngredientCategories->map(fn($c) => ['id' => $c->id, 'name' => $c->name]));
                                                },
                                                get selectedItem() {
                                                    if (!this.selectedId) return null;
                                                    return this.categoriesList.find(c => Number(c.id) === Number(this.selectedId)) || null;
                                                },
                                                get filteredItems() {
                                                    if (!this.search.trim()) return this.categoriesList;
                                                    const q = this.search.toLowerCase().trim();
                                                    return this.categoriesList.filter(c => (c.name || '').toLowerCase().includes(q));
                                                },
                                                openDropdown() {
                                                    this.checkFlip();
                                                    this.open = true;
                                                    this.search = '';
                                                },
                                                closeDropdown() {
                                                    this.open = false;
                                                    this.search = '';
                                                },
                                                checkFlip() {
                                                    if (!this.$refs.categoryCombobox) return;
                                                    const rect = this.$refs.categoryCombobox.getBoundingClientRect();
                                                    const spaceBelow = window.innerHeight - rect.bottom;
                                                    this.dropUp = spaceBelow < 250 && rect.top > 250;
                                                },
                                                select(id) {
                                                    this.selectedId = id ? Number(id) : '';
                                                    this.search = '';
                                                    this.open = false;
                                                },
                                                clear() {
                                                    this.selectedId = '';
                                                    this.search = '';
                                                    this.open = false;
                                                }
                                            }"
                                            x-ref="categoryCombobox"
                                            @click.outside="closeDropdown()"
                                            @keydown.escape.window="closeDropdown()">

                                            {{-- Search / Select Input Box --}}
                                            <div class="relative flex items-center">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                    </svg>
                                                </div>

                                                <input 
                                                    type="text"
                                                    :value="open ? search : (selectedItem ? selectedItem.name : 'Uncategorized')"
                                                    @input="search = $event.target.value; open = true"
                                                    @focus="openDropdown()"
                                                    @click="openDropdown()"
                                                    placeholder="Search or select category..."
                                                    class="w-full pl-10 pr-10 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-11"
                                                    :class="selectedId && !open ? 'font-medium text-indigo-700 bg-indigo-50/20 border-indigo-200' : 'text-slate-800 font-medium'"
                                                    autocomplete="off"
                                                />

                                                {{-- Clear button only (NO up/down arrow!) --}}
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <button type="button" 
                                                        x-show="(selectedId !== '' && selectedId !== null && selectedId !== undefined) || (search && search.length > 0)"
                                                        x-cloak
                                                        @click.stop="clear()"
                                                        title="Clear selection"
                                                        class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Dropdown Results Menu with auto-flip --}}
                                            <div x-show="open" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-60 overflow-y-auto custom-scrollbar">
                                                
                                                {{-- Uncategorized option (default) --}}
                                                <template x-if="!search.trim() || 'uncategorized'.includes(search.toLowerCase().trim())">
                                                    <div>
                                                        <button type="button" 
                                                            @click="select('')"
                                                            class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between group"
                                                            :class="!selectedId ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                            <span class="text-[13px] font-medium group-hover:font-semibold">Uncategorized</span>
                                                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-400 group-hover:bg-indigo-100 group-hover:text-indigo-600 shrink-0 ml-2">Default</span>
                                                        </button>
                                                        <div class="my-1 border-t border-slate-100"></div>
                                                    </div>
                                                </template>

                                                <template x-for="cat in filteredItems" :key="cat.id">
                                                    <button type="button" 
                                                        @click="select(cat.id)"
                                                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between group"
                                                        :class="Number(selectedId) === Number(cat.id) ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span class="text-[13px] font-medium group-hover:font-semibold truncate" x-text="cat.name"></span>
                                                    </button>
                                                </template>
                                                
                                                <template x-if="filteredItems.length === 0 && search.trim() && !'uncategorized'.includes(search.toLowerCase().trim())">
                                                    <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">
                                                        No categories found
                                                    </div>
                                                </template>
                                            </div>
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
                                    <div class="mt-1.5"
                                        x-data="{
                                            open: false,
                                            dropUp: false,
                                            availableUnits: @js($availableUnits),
                                            openDropdown() {
                                                if (this.$refs.unitBtn) {
                                                    const rect = this.$refs.unitBtn.getBoundingClientRect();
                                                    this.dropUp = (window.innerHeight - rect.bottom) < 180 && rect.top > 180;
                                                }
                                                this.open = true;
                                            },
                                            closeDropdown() {
                                                this.open = false;
                                            },
                                            select(key) {
                                                ingredientUnit = key;
                                                $wire.selectIngredientUnit(key);
                                                this.open = false;
                                            }
                                        }"
                                        @click.outside="closeDropdown()"
                                        @keydown.escape.window="closeDropdown()">
                                        <div class="relative">
                                            <button type="button" x-ref="unitBtn"
                                                @click="open ? closeDropdown() : openDropdown()"
                                                class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                <span class="font-bold text-slate-700" x-text="availableUnits[ingredientUnit] || 'Select Unit'"></span>
                                                <svg class="w-4 h-4 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                            <div x-show="open" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                class="absolute left-0 right-0 z-50 bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-60 overflow-y-auto custom-scrollbar">
                                                <template x-for="(label, key) in availableUnits" :key="key">
                                                    <button type="button"
                                                        @click="select(key)"
                                                        class="w-full text-left px-3.5 py-2 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between text-[12px]"
                                                        :class="ingredientUnit === key ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span x-text="label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('ingredientUnit')" class="mt-1" />
                                </div>
                                
                                <div>
                                    <x-input-label value="Low Stock Alert Threshold *" />
                                    <div class="mt-1.5">
                                        <x-text-input wire:model.live="ingredientMinStock" 
                                            @input="if (typeof formErrors !== 'undefined' && formErrors) delete formErrors.minStock"
                                            class="w-full h-11 font-medium" 
                                            placeholder="0.00" 
                                            inputFilter="price" 
                                            ::class="(typeof formErrors !== 'undefined' && formErrors && formErrors.minStock) ? '!border-red-400 focus:!border-red-400 focus:!ring-red-300 !bg-red-50/30' : ''"
                                            :hasError="$errors->has('ingredientMinStock')" />
                                    </div>
                                    <div x-show="typeof formErrors !== 'undefined' && formErrors && formErrors.minStock" 
                                        class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200" 
                                        x-cloak>
                                        <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <ul class="space-y-0.5">
                                            <li x-text="(typeof formErrors !== 'undefined' && formErrors && formErrors.minStock) || ''"></li>
                                        </ul>
                                    </div>
                                    <x-input-error :messages="$errors->get('ingredientMinStock')" class="mt-1" />
                                </div>
                            </div>
                                                </div>
                    </div>

                    {{-- Bulk Packaging & Conversions --}}
                    <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-50 pb-4 mb-6">
                            <div>
                                <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-widest">Bulk Packaging</h3>
                                <p class="text-[11px] text-slate-400 font-medium mt-1">Define how cases, sacks, or bottles translate to your base unit.</p>
                            </div>
                            @if($this->isSuperAdmin())
                            <button type="button" @click="addConversionRow()" class="h-9 px-4 bg-indigo-50 text-indigo-600 text-[11px] font-black uppercase tracking-widest rounded-lg hover:bg-indigo-100 transition-all flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                Add Bulk Unit
                            </button>
                            @endif
                        </div>

                        {{-- Bulk Packaging Validation Error Banner --}}
                        <div x-show="typeof formErrors !== 'undefined' && formErrors && formErrors.conversionRows" 
                            class="text-[11px] font-medium text-red-500 mb-4 p-3 bg-red-50/50 border border-red-200/80 rounded-xl flex items-start gap-2 animate-in fade-in slide-in-from-top-1 duration-200" 
                            x-cloak>
                            <svg class="w-4 h-4 shrink-0 mt-0.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span x-text="(typeof formErrors !== 'undefined' && formErrors && formErrors.conversionRows) || ''"></span>
                        </div>

                        {{-- Empty State --}}
                        <div x-show="!conversionRows || conversionRows.length === 0" class="py-12 border-2 border-dashed border-slate-100 rounded-2xl text-center">
                            <p class="text-[13px] text-slate-400 font-medium">No bulk units defined for this ingredient.</p>
                        </div>

                        {{-- Conversion Rows List --}}
                        <div x-show="conversionRows && conversionRows.length > 0" class="space-y-4">
                            <template x-for="(row, i) in conversionRows" :key="i">
                                <div class="relative bg-white border border-slate-200/80 hover:border-indigo-200 rounded-2xl p-4 sm:p-5 shadow-sm transition-all group">
                                    {{-- Card Header: Tier Badge, Yield, Best Value Tag, and Delete Button --}}
                                    <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
                                        <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg text-[11px] font-black uppercase tracking-wider">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                <span x-text="'Tier ' + (i + 1)"></span>
                                            </span>

                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100/60 rounded-lg text-[11px] font-bold font-mono">
                                                <span class="text-indigo-400 font-sans font-semibold text-[10px] uppercase tracking-wider">Yield:</span>
                                                <span x-text="(parseFloat(row.qty_in_base) || 0).toFixed(2) + ' ' + (ingredientUnit || 'PCS').toUpperCase()"></span>
                                            </div>

                                            <template x-if="optimalPackaging.bestCost !== null && parseFloat(row.qty_in_base) > 0 && parseFloat(row.price_per_unit) > 0 && Math.abs((parseFloat(row.price_per_unit) / parseFloat(row.qty_in_base)) - optimalPackaging.bestCost) < 0.001">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-lg text-[10px] font-black uppercase tracking-wider">
                                                    <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                    Best Value
                                                </span>
                                            </template>
                                        </div>

                                        @if($this->isSuperAdmin())
                                        <button type="button" @click="removeConversionRow(i)" 
                                            title="Remove packaging tier"
                                            class="w-8 h-8 inline-flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg border border-transparent hover:border-rose-100 transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                        @endif
                                    </div>

                                    {{-- Card Inputs Grid: Packaging Type, Pack Ratio, Procurement Cost --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4 items-start">
                                        {{-- 1. Packaging Type Combobox --}}
                                        <div class="sm:col-span-5 relative"
                                            x-data="{
                                                open: false,
                                                dropUp: false,
                                                search: '',
                                                get bulkUnitsList() {
                                                    return getBulkUnitsForBase(ingredientUnit);
                                                },
                                                get selectedItem() {
                                                    if (!row.unit_name) return null;
                                                    return this.bulkUnitsList.find(u => u.key === row.unit_name) || null;
                                                },
                                                get filteredItems() {
                                                    if (!this.search.trim()) return this.bulkUnitsList;
                                                    const q = this.search.toLowerCase().trim();
                                                    return this.bulkUnitsList.filter(u => u.label.toLowerCase().includes(q) || u.key.toLowerCase().includes(q));
                                                },
                                                openDropdown() {
                                                    this.checkFlip();
                                                    this.open = true;
                                                    this.search = '';
                                                },
                                                closeDropdown() {
                                                    this.open = false;
                                                    this.search = '';
                                                },
                                                checkFlip() {
                                                    if (!this.$refs.pkgCombobox) return;
                                                    const rect = this.$refs.pkgCombobox.getBoundingClientRect();
                                                    const spaceBelow = window.innerHeight - rect.bottom;
                                                    this.dropUp = spaceBelow < 220 && rect.top > 220;
                                                },
                                                select(key) {
                                                    row.unit_name = key;
                                                    this.search = '';
                                                    this.open = false;
                                                },
                                                clear() {
                                                    row.unit_name = '';
                                                    this.search = '';
                                                    this.open = false;
                                                }
                                            }"
                                            x-ref="pkgCombobox"
                                            @click.outside="closeDropdown()"
                                            @keydown.escape.window="closeDropdown()">

                                            <x-input-label value="Packaging Type *" class="mb-1.5 text-[11px] font-bold text-slate-700" />
                                            <div class="relative flex items-center">
                                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                                    </svg>
                                                </div>
                                                <input 
                                                    type="text"
                                                    :value="open ? search : (selectedItem ? selectedItem.label : '')"
                                                    @input="search = $event.target.value; open = true"
                                                    @focus="openDropdown()"
                                                    @click="openDropdown()"
                                                    placeholder="Search or select packaging..."
                                                    class="w-full pl-10 pr-10 py-2 bg-white border border-slate-200 rounded-xl text-[12px] text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-10"
                                                    :class="row.unit_name && !open ? 'font-bold text-slate-900 bg-slate-50/20' : 'text-slate-700 font-medium'"
                                                    autocomplete="off"
                                                />
                                                {{-- Clear button with generous right spacing (pr-3) so it never touches border! --}}
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                    <button type="button" 
                                                        x-show="!!row.unit_name || (search && search.length > 0)"
                                                        x-cloak
                                                        @click.stop="clear()"
                                                        title="Clear packaging"
                                                        class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-full transition-colors">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Dropdown results --}}
                                            <div x-show="open" x-cloak
                                                x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="opacity-0 scale-95"
                                                x-transition:enter-end="opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="opacity-100 scale-100"
                                                x-transition:leave-end="opacity-0 scale-95"
                                                :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                class="absolute left-0 right-0 z-50 min-w-[200px] bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-52 overflow-y-auto custom-scrollbar">
                                                <template x-for="item in filteredItems" :key="item.key">
                                                    <button type="button" 
                                                        @click="select(item.key)"
                                                        class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between text-[12px]"
                                                        :class="row.unit_name === item.key ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                                        <span x-text="item.label"></span>
                                                    </button>
                                                </template>
                                                <template x-if="filteredItems.length === 0">
                                                    <div class="px-3.5 py-2.5 text-[11px] text-slate-400 italic text-center">
                                                        No packaging units found
                                                    </div>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- 2. Pack Size & Linked To (Pack Ratio Group) --}}
                                        <div class="sm:col-span-4">
                                            <x-input-label value="Contains (Pack Ratio) *" class="mb-1.5 text-[11px] font-bold text-slate-700" />
                                            <div class="flex items-center gap-1.5">
                                                <div class="w-24 shrink-0">
                                                    <input 
                                                        type="number" 
                                                        min="0" 
                                                        step="0.0001" 
                                                        x-model="row.chain_multiplier"
                                                        @input="updateConversionMultiplier(i)"
                                                        class="w-full h-10 text-[12px] font-black text-center border-slate-200 bg-white rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 shadow-sm" 
                                                        placeholder="Qty"
                                                    />
                                                </div>
                                                <span class="text-slate-400 font-bold text-sm select-none px-0.5">&times;</span>
                                                <div class="flex-1 min-w-0 relative"
                                                    x-data="{
                                                        open: false,
                                                        dropUp: false,
                                                        checkFlip() {
                                                            if (!this.$refs.linkedTrigger) return;
                                                            const rect = this.$refs.linkedTrigger.getBoundingClientRect();
                                                            const spaceBelow = window.innerHeight - rect.bottom;
                                                            this.dropUp = spaceBelow < 180 && rect.top > 180;
                                                        },
                                                        openDropdown() {
                                                            this.checkFlip();
                                                            this.open = true;
                                                        },
                                                        closeDropdown() {
                                                            this.open = false;
                                                        }
                                                    }"
                                                    @click.outside="closeDropdown()"
                                                    @keydown.escape.window="closeDropdown()">

                                                    <template x-if="i === 0 || !hasChainingOptions(i)">
                                                        <div class="h-10 w-full border border-slate-200/80 bg-slate-50/50 rounded-xl flex items-center px-3 text-[11px] font-black text-slate-500 italic">
                                                            1 <span class="uppercase ml-1" x-text="ingredientUnit || 'PCS'"></span>
                                                        </div>
                                                    </template>

                                                    <template x-if="i > 0 && hasChainingOptions(i)">
                                                        <div>
                                                            <button type="button" x-ref="linkedTrigger"
                                                                @click="open ? closeDropdown() : openDropdown()"
                                                                class="flex items-center justify-between w-full px-3 py-1 bg-white border border-slate-200 rounded-xl text-[11px] font-bold text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 transition-all h-10">
                                                                <span class="truncate mr-1.5" x-text="getChainDisplay(row)"></span>
                                                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                                            </button>

                                                            <div x-show="open" x-cloak
                                                                x-transition:enter="transition ease-out duration-100"
                                                                x-transition:enter-start="opacity-0 scale-95"
                                                                x-transition:enter-end="opacity-100 scale-100"
                                                                x-transition:leave="transition ease-in duration-75"
                                                                x-transition:leave-start="opacity-100 scale-100"
                                                                x-transition:leave-end="opacity-0 scale-95"
                                                                :class="dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5'"
                                                                class="absolute left-0 right-0 z-50 min-w-[200px] bg-white rounded-xl shadow-xl border border-slate-100 p-1.5 max-h-48 overflow-y-auto custom-scrollbar">
                                                                
                                                                <button type="button" 
                                                                    @click="setConversionLink(i, 'base'); closeDropdown()"
                                                                    class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors text-[11px] font-bold"
                                                                    :class="(row.chain_from_index === 'base' || row.chain_from_index === null || row.chain_from_index === '') ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-600'">
                                                                    <span>1 <span class="uppercase" x-text="ingredientUnit || 'PCS'"></span></span>
                                                                </button>
                                                                <div class="my-1 border-t border-slate-100"></div>

                                                                <template x-for="(prevRow, j) in conversionRows.slice(0, i)" :key="j">
                                                                    <template x-if="prevRow.unit_name">
                                                                        <button type="button" 
                                                                            @click="setConversionLink(i, j); closeDropdown()"
                                                                            class="w-full text-left px-3 py-2 rounded-lg hover:bg-slate-50 transition-colors text-[11px] font-bold"
                                                                            :class="Number(row.chain_from_index) === j ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-600'">
                                                                            <span class="truncate" x-text="(prevRow.unit_name || '').toUpperCase() + ' (' + (parseFloat(prevRow.qty_in_base) || 0).toFixed(2) + ' ' + (ingredientUnit || 'PCS').toUpperCase() + ')'"></span>
                                                                        </button>
                                                                    </template>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- 3. Unit Cost (Read Only) --}}
                                        <div class="sm:col-span-3">
                                            <div class="flex items-center justify-between mb-1.5">
                                                <x-input-label value="Procurement Cost" class="text-[11px] font-bold text-slate-700" />
                                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tight">Auto-cost</span>
                                            </div>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                    <span class="text-[11px] font-black uppercase font-mono">₱</span>
                                                </div>
                                                <input type="text"
                                                    :value="(parseFloat(row.price_per_unit) || 0).toFixed(2)"
                                                    class="w-full h-10 pl-7 pr-3 text-[12px] font-black font-mono text-right bg-slate-50 text-slate-600 border border-slate-200 rounded-xl cursor-not-allowed shadow-sm"
                                                    placeholder="0.00"
                                                    readonly
                                                />
                                            </div>
                                            <template x-if="parseFloat(row.qty_in_base) > 0 && parseFloat(row.price_per_unit) > 0">
                                                <div class="text-[10px] text-slate-400 font-semibold mt-1 text-right font-mono"
                                                    x-text="'≈ ₱' + (parseFloat(row.price_per_unit) / parseFloat(row.qty_in_base)).toFixed(2) + ' / ' + (ingredientUnit || 'pcs').toLowerCase()">
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
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
                                <span x-text="ingredientName ? ingredientName.charAt(0).toUpperCase() : '?'"></span>
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-[14px] font-bold text-slate-900 truncate leading-tight" x-text="ingredientName || 'Untitled Ingredient'"></h4>
                                <span class="text-[10px] font-black text-indigo-500/80 uppercase tracking-widest block mt-1" x-text="(ingredientUnit || 'PCS').toUpperCase()"></span>
                            </div>
                        </div>

                        <div class="p-4 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 flex items-center justify-between transition-all hover:scale-[1.02] relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/5 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                            <div class="relative z-10">
                                <span class="text-[10px] font-black text-indigo-100 uppercase tracking-widest block mb-0.5">Calculated Base Cost</span>
                                <span class="text-[9px] font-bold text-indigo-200/60 uppercase tracking-tight italic">Auto-synced from bulk tiers</span>
                            </div>
                            <div class="text-right relative z-10">
                                <span class="block text-[18px] font-black text-white leading-none italic font-mono" x-text="'₱' + (optimalPackaging.bestCost !== null ? optimalPackaging.bestCost.toFixed(2) : (parseFloat(ingredientCost) || 0).toFixed(2))"></span>
                                <span class="text-[10px] font-black text-indigo-200 mt-1.5 block tracking-widest" x-text="'PER ' + (ingredientUnit || 'PCS').toUpperCase()"></span>
                            </div>
                        </div>

                        <template x-if="optimalPackaging.bestCost !== null && optimalPackaging.bestUnit">
                            <div class="p-4 bg-emerald-50/50 border border-emerald-100 rounded-xl">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <span class="text-[11px] font-black text-emerald-800 uppercase tracking-widest">Optimal Value</span>
                                </div>
                                <p class="text-[12px] text-emerald-700 font-medium leading-relaxed">
                                    Buying in <strong class="text-emerald-900" x-text="optimalPackaging.bestUnit.toUpperCase()"></strong> provides the lowest base cost for your recipes.
                                </p>
                            </div>
                        </template>

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
                                                <span class="text-[9px] font-black text-slate-400 uppercase ml-0.5" x-text="ingredientUnit"></span>
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
                        <x-primary-button type="button" @click="saveIngredient()" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                            <span x-text="mode === 'edit' ? 'Update Catalog' : 'Add to Catalog'"></span>
                        </x-primary-button>
                        <x-secondary-button @click="backToList()" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest border-slate-200 text-slate-500">
                            Discard Draft
                        </x-secondary-button>
                    </div>

                    @if($editIngredientId && $this->isSuperAdmin())
                        <div class="bg-red-50 border border-red-100 rounded-2xl p-6 shadow-sm mt-8">
                            <h2 class="text-[13px] font-bold text-red-600 uppercase tracking-wider mb-2">Danger Zone</h2>
                            <p class="text-[12px] text-gray-500 mb-4 leading-relaxed">Permanently remove this ingredient from the system.</p>
                            <x-danger-button type="button" @click="confirmDelete(editIngredientId, ingredientName)" class="w-full justify-center h-11">
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
                            <h4 class="text-[13px] font-bold text-white mb-2 italic">How to Setup Packaging?</h4>
                            <p class="text-[12px] text-slate-400 leading-relaxed mb-3">
                                Build up from your <span class="text-indigo-300 font-bold">Base Unit</span> (e.g. ML). Define the <span class="text-emerald-400 font-bold">Pack Size</span> and what it is <span class="text-indigo-300 font-bold">Linked To</span> to create chains.
                            </p>
                            <div class="p-3 bg-slate-800/50 rounded-xl border border-slate-700/50 space-y-2">
                                <p class="text-[11px] text-slate-300 font-mono">
                                    <span class="text-indigo-400 font-black">STEP 1:</span> 1 Bottle = <span class="text-emerald-400 font-black">250</span> ML<br>
                                    <span class="text-slate-500 ml-4">↳ Size: 250, Link to: 1 ML (Base)</span>
                                </p>
                                <p class="text-[11px] text-slate-300 font-mono border-t border-slate-700/50 pt-2">
                                    <span class="text-indigo-400 font-black">STEP 2:</span> 1 Box = <span class="text-emerald-400 font-black">24</span> Bottles<br>
                                    <span class="text-slate-500 ml-4">↳ Size: 24, Link to: BOTTLE</span>
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
                <x-text-input id="new_ing_category_name" wire:model.live.debounce.400ms="newCategoryName" type="text" class="block w-full h-11 mt-1.5" placeholder="e.g. Seafood & Frozen" autofocus inputFilter="categoryName" :hasError="$errors->has('newCategoryName')" />
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

    {{-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• PANEL — EXPIRY TRACKING â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• --}}
    <div x-show="panel === 'expiry'"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-cloak class="px-1">

        {{-- Expiry System Metrics (Compact Glassmorphic Theme) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
            {{-- Expired --}}
            @php
                $hasExpiredBatches = $expiryStats['expired'] > 0;
            @endphp
            <div @click="expiryStatus = (expiryStatus === 'expired' ? 'all' : 'expired'); expiryCurrentPage = 1"
                 :class="expiryStatus === 'expired' ? 'bg-gradient-to-br from-rose-100 via-rose-50 to-white border-rose-300 shadow-md scale-[1.01]' : 'bg-gradient-to-br from-rose-50 to-white border-rose-100 shadow-[0_8px_30px_rgba(0,0,0,0.02)]'"
                 class="relative overflow-hidden cursor-pointer transition-all duration-300 hover:scale-[1.01] rounded-2xl p-4 hover:shadow-md border">
                @if($hasExpiredBatches)
                    <span class="absolute top-2.5 right-2.5 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-rose-500"></span>
                    </span>
                @endif
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-rose-700/80 uppercase tracking-widest leading-none">Expired</span>
                    <div class="w-7 h-7 rounded-lg bg-rose-500/10 flex items-center justify-center text-rose-600 border border-rose-500/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-[20px] font-black {{ $hasExpiredBatches ? 'text-rose-600' : 'text-slate-800' }} tracking-tight leading-none">{{ $expiryStats['expired'] }}</span>
                    <span class="text-[9px] font-bold text-slate-400">batches</span>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-1.5 leading-none">Needs disposal</p>
            </div>

            {{-- Expiring Soon --}}
            @php
                $hasExpiringBatches = $expiryStats['expiring'] > 0;
            @endphp
            <div @click="expiryStatus = (expiryStatus === 'expiring' ? 'all' : 'expiring'); expiryCurrentPage = 1"
                 :class="expiryStatus === 'expiring' ? 'bg-gradient-to-br from-amber-100 via-amber-50 to-white border-amber-300 shadow-md scale-[1.01]' : 'bg-gradient-to-br from-amber-50 to-white border-amber-100 shadow-[0_8px_30px_rgba(0,0,0,0.02)]'"
                 class="relative overflow-hidden cursor-pointer transition-all duration-300 hover:scale-[1.01] rounded-2xl p-4 hover:shadow-md border">
                @if($hasExpiringBatches)
                    <span class="absolute top-2.5 right-2.5 flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                    </span>
                @endif
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-amber-700/80 uppercase tracking-widest leading-none">Expiring &le;{{ $alertDays }}d</span>
                    <div class="w-7 h-7 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-600 border border-amber-500/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-[20px] font-black {{ $hasExpiringBatches ? 'text-amber-600' : 'text-slate-800' }} tracking-tight leading-none">{{ $expiryStats['expiring'] }}</span>
                    <span class="text-[9px] font-bold text-slate-400">batches</span>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-1.5 leading-none">Expires within {{ $alertDays }} days</p>
            </div>

            {{-- Fresh --}}
            <div @click="expiryStatus = (expiryStatus === 'fresh' ? 'all' : 'fresh'); expiryCurrentPage = 1"
                 :class="expiryStatus === 'fresh' ? 'bg-gradient-to-br from-emerald-100 via-emerald-50 to-white border-emerald-300 shadow-md scale-[1.01]' : 'bg-gradient-to-br from-emerald-50 to-white border-emerald-100 shadow-[0_8px_30px_rgba(0,0,0,0.02)]'"
                 class="relative overflow-hidden cursor-pointer transition-all duration-300 hover:scale-[1.01] rounded-2xl p-4 hover:shadow-md border">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-emerald-700/80 uppercase tracking-widest leading-none">Stable Stock</span>
                    <div class="w-7 h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 border border-emerald-500/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-[20px] font-black text-emerald-600 tracking-tight leading-none">{{ $expiryStats['fresh'] }}</span>
                    <span class="text-[9px] font-bold text-slate-400">batches</span>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-1.5 leading-none">Fresh &amp; stable stock</p>
            </div>

            {{-- No Date --}}
            <div @click="expiryStatus = (expiryStatus === 'no_date' ? 'all' : 'no_date'); expiryCurrentPage = 1"
                 :class="expiryStatus === 'no_date' ? 'bg-gradient-to-br from-slate-100 via-slate-50 to-white border-slate-300 shadow-md scale-[1.01]' : 'bg-gradient-to-br from-slate-50 to-white border-slate-200 shadow-[0_8px_30px_rgba(0,0,0,0.02)]'"
                 class="relative overflow-hidden cursor-pointer transition-all duration-300 hover:scale-[1.01] rounded-2xl p-4 hover:shadow-md border">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black text-slate-700/80 uppercase tracking-widest leading-none">Non-Perishable</span>
                    <div class="w-7 h-7 rounded-lg bg-slate-500/10 flex items-center justify-center text-slate-500 border border-slate-500/10">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-1">
                    <span class="text-[20px] font-black text-slate-800 tracking-tight leading-none">{{ $expiryStats['no_date'] }}</span>
                    <span class="text-[9px] font-bold text-slate-400">batches</span>
                </div>
                <p class="text-[10px] font-bold text-slate-400 mt-1.5 leading-none">No expiration date set</p>
            </div>
        </div>

{{-- macOS Style Expiry Toolbar --}}
        <div class="relative z-20 flex flex-row items-center justify-between mb-6 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
            <div class="flex flex-1 min-w-0 lg:flex-initial">
                <x-search-bar x-model.debounce.50ms="expirySearchText" placeholder="Filter batches..." width="w-full lg:w-72" />
            </div>

            <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                            <span class="hidden sm:inline text-[12px] whitespace-nowrap" x-text="{'all': 'All Batches', 'expired': 'Expired Only', 'expiring': 'Expiring Soon', 'fresh': 'Stable Stock', 'no_date': 'Non-Perishables'}[expiryStatus] || 'Filter Status'"></span>
                            <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        @foreach(['all' => 'All Batches', 'expired' => 'Expired Only', 'expiring' => 'Expiring Soon', 'fresh' => 'Stable Stock', 'no_date' => 'Non-Perishables'] as $val => $label)
                            <x-dropdown-link href="#" @click.prevent="expiryStatus = '{{ $val }}'">
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

                <tbody class="divide-y divide-slate-100/80" wire:key="stock-expiry-body-{{ $selectedBranchId }}">
                    @foreach($allBatches as $batch)
                        @php
                            $expiry     = $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->startOfDay() : null;
                            $todayC     = \Carbon\Carbon::today();
                            $alertCutoff = $todayC->copy()->addDays($alertDays);
                            $isExpired  = $expiry && $expiry->lt($todayC);
                            $isExpiring = $expiry && !$isExpired && $expiry->lte($alertCutoff);
                            $isFresh    = $expiry && $expiry->gt($alertCutoff);
                            $daysLeft   = $expiry ? (int) $todayC->diffInDays($expiry, false) : null;
                        @endphp
                        <tr x-show="isBatchVisible({{ $batch->id }})" x-cloak class="hover:bg-slate-50/50 transition-colors {{ $isExpired ? 'bg-rose-50/30' : ($isExpiring ? 'bg-amber-50/30' : '') }}">
                            <td class="py-4 px-6 border-r border-slate-100/50 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-[14px] font-bold text-slate-900">{{ $batch->ingredient->name ?? '—' }}</span>
                                    <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest">{{ $batch->ingredient->unit ?? '' }}</span>
                                </div>
                            </td>
                            <td class="py-4 px-6 border-r border-slate-100/50 text-center whitespace-nowrap">
                                <span class="text-[12px] font-bold text-slate-600">{{ $batch->branch->branch_name ?? '—' }}</span>
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
                                    <div class="flex items-center justify-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span><span class="text-[11px] font-bold text-rose-600">EXPIRED</span></div>
                                @elseif($isExpiring)
                                    <div class="flex items-center justify-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span><span class="text-[11px] font-bold text-amber-600">{{ $daysLeft === 0 ? 'TODAY' : $daysLeft . 'D LEFT' }}</span></div>
                                @elseif($isFresh)
                                    <div class="flex items-center justify-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span><span class="text-[11px] font-bold text-emerald-600">FRESH</span></div>
                                @else
                                    <div class="flex items-center justify-center gap-2"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span><span class="text-[11px] font-bold text-slate-600">NON-PERISHABLE</span></div>
                                @endif
                            </td>
                            @if(!$this->isStaff())
                                <td class="py-4 px-6 text-right whitespace-nowrap">
                                    @if($isExpired || $isExpiring)
                                        <x-danger-button type="button" @click="confirmWaste({{ $batch->id }}, '{{ addslashes($batch->ingredient->name ?? 'Unknown') }}', {{ (float)$batch->current_quantity }}, '{{ $batch->ingredient->unit ?? 'pcs' }}')"
                                            class="h-8 !px-3 text-[10px] font-black uppercase tracking-widest bg-amber-600 hover:bg-amber-700 border-amber-600">
                                            Dispose
                                        </x-danger-button>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                        @endforeach
                    <tr x-show="filteredBatchIds.length === 0 && expirySearchText.trim() !== ''" x-cloak>
                        <td colspan="{{ $this->isStaff() ? 5 : 6 }}" class="py-12">
                            <x-empty-state title="No batches match your search" description="Try a different name or clear your search." />
                        </td>
                    </tr>
                </tbody>
            </x-data-table>
            <div class="mt-4 px-1">
                <template x-if="filteredBatchIds.length > 0 || expirySearchText.trim() === ''">
                    <div class="flex flex-col lg:flex-row items-center justify-between px-4 py-4 bg-white border-t border-gray-100 lg:px-6 gap-6">
                        <div class="flex flex-col sm:flex-row items-center justify-between w-full lg:w-auto gap-4 sm:gap-8">
                            <div class="flex items-center gap-3">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:gap-1.5 leading-[0.9] sm:leading-none">
                                    <span class="text-[11px] sm:text-[12px] text-gray-400 font-black uppercase tracking-tighter sm:tracking-widest">Row</span>
                                    <span class="text-[9px] sm:text-[12px] text-gray-400/70 font-black uppercase tracking-tighter sm:tracking-widest">per page</span>
                                </div>
                                <div>
                                    <x-dropdown align="top" width="20" containerClasses="block">
                                        <x-slot name="trigger">
                                            <button type="button" class="inline-flex items-center justify-between min-w-[70px] px-3 py-1.5 text-[13px] font-black text-gray-900 bg-slate-50 border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none transition-all h-10 gap-2 shadow-sm">
                                                <span x-text="expiryPerPage"></span>
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            <template x-for="option in [5, 10, 15, 30, 50, 100]">
    <x-dropdown-link href="#" x-on:click.prevent="expiryPerPage = option; expiryCurrentPage = 1; dropdownOpen = false;">
        <span x-text="option"></span>
    </x-dropdown-link>
</template>
                                        </x-slot>
                                    </x-dropdown>
                                </div>
                            </div>
                            <div class="text-[11px] text-gray-400 font-bold uppercase tracking-widest whitespace-nowrap">
                                <span class="text-gray-900" x-text="Math.min(filteredBatchIds.length, (expiryCurrentPage - 1) * expiryPerPage + 1)"></span>
                                <span class="mx-0.5 text-gray-300">-</span>
                                <span class="text-gray-900" x-text="Math.min(filteredBatchIds.length, expiryCurrentPage * expiryPerPage)"></span>
                                <span class="mx-1 text-gray-300 lowercase italic font-medium">of</span>
                                <span class="text-indigo-600" x-text="filteredBatchIds.length"></span>
                                <span class="ml-1 text-gray-300 lowercase italic font-medium">results</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 w-full lg:w-auto justify-center lg:justify-end border-t border-gray-50 pt-4 lg:border-0 lg:pt-0">
                            <x-secondary-button @click="expiryCurrentPage = 1" ::disabled="expiryCurrentPage === 1" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="expiryCurrentPage === 1 ? 'opacity-30 pointer-events-none' : ''">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                </svg>
                            </x-secondary-button>
                            <x-secondary-button @click="expiryCurrentPage = Math.max(1, expiryCurrentPage - 1)" ::disabled="expiryCurrentPage === 1" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="expiryCurrentPage === 1 ? 'opacity-30 pointer-events-none' : ''">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                            </x-secondary-button>
                            <div class="flex items-center gap-1.5 px-2">
                                <template x-for="page in expiryPageNumbers">
                                    <div class="flex items-center gap-1.5">
                                        <template x-if="page === expiryCurrentPage">
                                            <x-primary-button class="!p-0 w-9 h-9 items-center justify-center !rounded-xl bg-gray-900 text-[13px] font-black shadow-none ring-0">
                                                <span x-text="page"></span>
                                            </x-primary-button>
                                        </template>
                                        <template x-if="page !== expiryCurrentPage">
                                            <x-secondary-button @click="expiryCurrentPage = page" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                                                <span x-text="page"></span>
                                            </x-secondary-button>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="Math.ceil(filteredBatchIds.length / expiryPerPage) > expiryCurrentPage + 1">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-gray-300 font-bold mx-1">...</span>
                                        <x-secondary-button @click="expiryCurrentPage = Math.ceil(filteredBatchIds.length / expiryPerPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl text-[13px] font-bold">
                                            <span x-text="Math.ceil(filteredBatchIds.length / expiryPerPage)"></span>
                                        </x-secondary-button>
                                    </div>
                                </template>
                            </div>
                            <x-secondary-button @click="expiryCurrentPage = Math.min(Math.ceil(filteredBatchIds.length / expiryPerPage), expiryCurrentPage + 1)" ::disabled="expiryCurrentPage >= Math.ceil(filteredBatchIds.length / expiryPerPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="expiryCurrentPage >= Math.ceil(filteredBatchIds.length / expiryPerPage) ? 'opacity-30 pointer-events-none' : ''">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </x-secondary-button>
                            <x-secondary-button @click="expiryCurrentPage = Math.ceil(filteredBatchIds.length / expiryPerPage) || 1" ::disabled="expiryCurrentPage >= Math.ceil(filteredBatchIds.length / expiryPerPage)" class="!p-0 w-9 h-9 items-center justify-center !rounded-xl" ::class="expiryCurrentPage >= Math.ceil(filteredBatchIds.length / expiryPerPage) ? 'opacity-30 pointer-events-none' : ''">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                                </svg>
                            </x-secondary-button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <script>
        (function() {
            window.stockManagement = function($wire) {
                const tabState = (typeof slidingTabsLogic === 'function') 
                    ? slidingTabsLogic($wire.entangle('panel').live, 'panel')
                    : ((typeof window.slidingTabs === 'function') ? window.slidingTabs($wire.entangle('panel').live, 'panel') : { init() {} });
                    
                return {
                    ...tabState,
                    panel: $wire.entangle('panel').live,
                    mode: 'list',
                    editIngredientId: $wire.entangle('editIngredientId'),
                    ingredientName: $wire.entangle('ingredientName'),
                    ingredientCategoryId: $wire.entangle('ingredientCategoryId'),
                    ingredientUnit: $wire.entangle('ingredientUnit'),
                    ingredientMinStock: $wire.entangle('ingredientMinStock'),
                    ingredientCost: $wire.entangle('ingredientCost'),
                    conversionRows: $wire.entangle('conversionRows'),
                    deleteTargetId: $wire.entangle('deleteTargetId'),
                    deleteTargetName: $wire.entangle('deleteTargetName'),
                    wasteTargetId: $wire.entangle('wasteTargetId'),
                    wasteTargetName: $wire.entangle('wasteTargetName'),
                    wasteTargetQty: $wire.entangle('wasteTargetQty'),
                    wasteTargetUnit: $wire.entangle('wasteTargetUnit'),

                    formErrors: {},
                    formSubmitted: false,

                    // Client-side search and pagination
                    stockSearch: '',
                    stockStatusFilter: '',
                    stockCategoryFilter: '',
                    stockCurrentPage: 1,
                    stockPerPage: 5,
                    ingredientsList: [],

                    expirySearchText: '',
                    expiryStatus: 'all',
                    expiryCurrentPage: 1,
                    expiryPerPage: 5,
                    batchesList: [],

                    get filteredIngredientIds() {
                        const query = (this.stockSearch || '').toLowerCase().trim();
                        const status = this.stockStatusFilter;
                        const category = this.stockCategoryFilter;
                        return (this.ingredientsList || [])
                            .filter(i => {
                                const matchesSearch = !query || (i.name || '').toLowerCase().includes(query);
                                const matchesStatus = !status || i.status === status;
                                const matchesCategory = !category || String(i.category_id) === String(category);
                                return matchesSearch && matchesStatus && matchesCategory;
                            })
                            .map(i => i.id);
                    },
                    get paginatedIngredientIds() {
                        const start = (this.stockCurrentPage - 1) * this.stockPerPage;
                        return this.filteredIngredientIds.slice(start, start + this.stockPerPage);
                    },
                    get stockPageNumbers() {
                        const totalPages = Math.ceil(this.filteredIngredientIds.length / this.stockPerPage) || 1;
                        const start = Math.max(1, this.stockCurrentPage - 1);
                        const end = Math.min(totalPages, this.stockCurrentPage + 1);
                        const pages = [];
                        for (let i = start; i <= end; i++) {
                            pages.push(i);
                        }
                        return pages;
                    },
                    isIngredientVisible(id) {
                        return this.paginatedIngredientIds.includes(id);
                    },
                    updateIngredientsList(newList) {
                        this.ingredientsList = newList || [];
                    },

                    get filteredBatchIds() {
                        const query = (this.expirySearchText || '').toLowerCase().trim();
                        const status = this.expiryStatus;
                        return (this.batchesList || [])
                            .filter(b => {
                                const matchesSearch = !query || (b.ingredient_name || '').toLowerCase().includes(query);
                                const matchesStatus = status === 'all' || b.status === status;
                                return matchesSearch && matchesStatus;
                            })
                            .map(b => b.id);
                    },
                    get paginatedBatchIds() {
                        const start = (this.expiryCurrentPage - 1) * this.expiryPerPage;
                        return this.filteredBatchIds.slice(start, start + this.expiryPerPage);
                    },
                    get expiryPageNumbers() {
                        const totalPages = Math.ceil(this.filteredBatchIds.length / this.expiryPerPage) || 1;
                        const start = Math.max(1, this.expiryCurrentPage - 1);
                        const end = Math.min(totalPages, this.expiryCurrentPage + 1);
                        const pages = [];
                        for (let i = start; i <= end; i++) {
                            pages.push(i);
                        }
                        return pages;
                    },
                    isBatchVisible(id) {
                        return this.paginatedBatchIds.includes(id);
                    },
                    updateBatchesList(newList) {
                        this.batchesList = newList || [];
                    },

                    isLoadingData: false,

                    // ── Instant 0ms Actions ──
                    showCreate() {
                        this.panel = 'form';
                        this.mode = 'create';
                        this.editIngredientId = null;
                        this.isLoadingData = false;
                        this.ingredientName = '';
                        this.ingredientCategoryId = '';
                        this.ingredientUnit = 'pcs';
                        this.ingredientMinStock = '';
                        this.conversionRows = [];
                        this.formErrors = {};
                        this.formSubmitted = false;
                        this.$wire.showCreate();
                    },
                    showEdit(id) {
                        this.panel = 'form';
                        this.mode = 'edit';
                        this.editIngredientId = id;
                        this.isLoadingData = true;
                        this.formErrors = {};
                        this.formSubmitted = false;
                        const p = this.$wire.showEdit(id);
                        if (p && typeof p.then === 'function') {
                            p.then(() => { this.isLoadingData = false; }).catch(() => { this.isLoadingData = false; });
                        } else {
                            setTimeout(() => { this.isLoadingData = false; }, 300);
                        }
                    },
                    backToList() {
                        this.panel = 'list';
                        this.mode = 'list';
                        this.isLoadingData = false;
                        this.formErrors = {};
                        this.formSubmitted = false;
                        this.$wire.backToList();
                    },
                    confirmDelete(id, name) {
                        this.deleteTargetId = id;
                        this.deleteTargetName = name;
                        this.$wire.deleteTargetId = id;
                        this.$wire.deleteTargetName = name;
                        this.$dispatch('open-modal', 'confirm-delete-ingredient');
                    },
                    confirmWaste(id, name, qty, unit) {
                        this.wasteTargetId = id;
                        this.wasteTargetName = name;
                        this.wasteTargetQty = qty;
                        this.wasteTargetUnit = unit;
                        this.$wire.wasteTargetId = id;
                        this.$wire.wasteTargetName = name;
                        this.$wire.wasteTargetQty = qty;
                        this.$wire.wasteTargetUnit = unit;
                        this.$dispatch('open-modal', 'confirm-waste-batch');
                    },
                    saveIngredient() {
                        this.formErrors = {};
                        this.formSubmitted = true;
                        let hasErrors = false;

                        const name = (this.ingredientName || '').trim();
                        if (!name) {
                            this.formErrors.name = 'Ingredient name is required.';
                            hasErrors = true;
                        }
                        const minStock = parseFloat(this.ingredientMinStock);
                        if (this.ingredientMinStock === '' || this.ingredientMinStock === null || isNaN(minStock) || minStock < 0) {
                            this.formErrors.minStock = 'Minimum stock must be a non-negative number.';
                            hasErrors = true;
                        }
                        if (Array.isArray(this.conversionRows)) {
                            for (let i = 0; i < this.conversionRows.length; i++) {
                                const r = this.conversionRows[i];
                                if (!r.unit_name) {
                                    this.formErrors.conversionRows = 'Packaging unit is required for packaging tier ' + (i + 1) + '.';
                                    hasErrors = true;
                                    break;
                                }
                                if (!r.chain_multiplier || parseFloat(r.chain_multiplier) <= 0) {
                                    this.formErrors.conversionRows = 'Please specify a valid pack size for packaging tier ' + (i + 1) + '.';
                                    hasErrors = true;
                                    break;
                                }
                            }
                        }
                        if (hasErrors) {
                            return;
                        }
                        this.$dispatch('open-modal', 'confirm-save-ingredient');
                    },

                    // ── Bulk Packaging (0ms) ──
                    addConversionRow() {
                        if (this.formErrors && this.formErrors.conversionRows) delete this.formErrors.conversionRows;
                        if (!Array.isArray(this.conversionRows)) this.conversionRows = [];
                        this.conversionRows.push({
                            unit_name: '',
                            qty_in_base: '',
                            price_per_unit: 0,
                            sort_order: this.conversionRows.length,
                            chain_multiplier: '',
                            chain_from_index: 'base'
                        });
                    },
                    removeConversionRow(idx) {
                        if (!Array.isArray(this.conversionRows)) return;
                        this.conversionRows.splice(idx, 1);
                        this.conversionRows.forEach((r, i) => {
                            r.sort_order = i;
                            if (r.chain_from_index !== 'base' && r.chain_from_index !== null && r.chain_from_index !== undefined) {
                                const fi = Number(r.chain_from_index);
                                if (fi === idx) {
                                    r.chain_from_index = 'base';
                                } else if (fi > idx) {
                                    r.chain_from_index = fi - 1;
                                }
                            }
                        });
                        this.cascadeRecompute(0);
                    },
                    setConversionLink(rowIndex, fromIndex) {
                        if (!Array.isArray(this.conversionRows) || !this.conversionRows[rowIndex]) return;
                        this.conversionRows[rowIndex].chain_from_index = fromIndex === 'base' ? 'base' : Number(fromIndex);
                        this.computeQtyInBase(rowIndex);
                        this.cascadeRecompute(rowIndex + 1);
                    },
                    updateConversionMultiplier(rowIndex) {
                        if (!Array.isArray(this.conversionRows) || !this.conversionRows[rowIndex]) return;
                        const row = this.conversionRows[rowIndex];
                        const mult = parseFloat(row.chain_multiplier);
                        if (!mult || mult <= 0) {
                            row.qty_in_base = '';
                            this.cascadeRecompute(rowIndex + 1);
                            return;
                        }
                        if (row.chain_from_index === null || row.chain_from_index === undefined || row.chain_from_index === '') {
                            row.chain_from_index = 'base';
                        }
                        this.computeQtyInBase(rowIndex);
                        this.cascadeRecompute(rowIndex + 1);
                    },
                    computeQtyInBase(index) {
                        const row = this.conversionRows[index];
                        if (!row) return;
                        const mult = parseFloat(row.chain_multiplier) || 0;
                        const fromIndex = row.chain_from_index;
                        if (mult <= 0) {
                            row.qty_in_base = '';
                            return;
                        }
                        if (fromIndex === 'base' || fromIndex === null || fromIndex === undefined || fromIndex === '') {
                            row.qty_in_base = mult;
                        } else {
                            const prevRow = this.conversionRows[Number(fromIndex)];
                            if (prevRow && parseFloat(prevRow.qty_in_base) > 0) {
                                row.qty_in_base = mult * parseFloat(prevRow.qty_in_base);
                            } else {
                                row.qty_in_base = '';
                            }
                        }
                    },
                    cascadeRecompute(startIndex) {
                        if (!Array.isArray(this.conversionRows)) return;
                        for (let i = startIndex; i < this.conversionRows.length; i++) {
                            const fromIndex = this.conversionRows[i].chain_from_index;
                            if (fromIndex !== null && fromIndex !== undefined && fromIndex !== '') {
                                this.computeQtyInBase(i);
                            }
                        }
                    },
                    getChainDisplay(row) {
                        const fromIdx = row.chain_from_index;
                        if (fromIdx === 'base' || fromIdx === '' || fromIdx === null || fromIdx === undefined) {
                            return '1 ' + (this.ingredientUnit || 'PCS').toUpperCase();
                        }
                        const cRow = this.conversionRows[Number(fromIdx)];
                        if (cRow && cRow.unit_name) {
                            const prevQty = (parseFloat(cRow.qty_in_base) || 0).toFixed(2);
                            return (cRow.unit_name).toUpperCase() + ' (' + prevQty + ' ' + (this.ingredientUnit || 'PCS').toUpperCase() + ')';
                        }
                        return '1 ' + (this.ingredientUnit || 'PCS').toUpperCase();
                    },
                    hasChainingOptions(rowIndex) {
                        if (!Array.isArray(this.conversionRows) || rowIndex <= 0) return false;
                        for (let j = 0; j < rowIndex; j++) {
                            if (this.conversionRows[j] && this.conversionRows[j].unit_name) return true;
                        }
                        return false;
                    },
                    getBulkUnitsForBase(baseUnit) {
                        const all = {
                            'box': 'Box',
                            'sack': 'Sack',
                            'bottle': 'Bottle',
                            'can': 'Can',
                            'pack': 'Pack',
                            'bundle': 'Bundle',
                            'tray': 'Tray',
                            'pcs': 'Pieces (pcs)',
                            'kg': 'Kilograms (kg)',
                            'l': 'Liters (L)'
                        };
                        if (baseUnit === 'ml') {
                            return Object.entries(all)
                                .filter(([k]) => ['l', 'bottle', 'box', 'can', 'pack', 'bundle', 'tray'].includes(k))
                                .map(([k, v]) => ({ key: k, label: v }));
                        }
                        if (baseUnit === 'g') {
                            return Object.entries(all)
                                .filter(([k]) => ['kg', 'sack', 'pack', 'box', 'bundle', 'can'].includes(k))
                                .map(([k, v]) => ({ key: k, label: v }));
                        }
                        if (baseUnit === 'pcs') {
                            return Object.entries(all)
                                .filter(([k]) => ['box', 'pack', 'bundle', 'tray', 'sack'].includes(k))
                                .map(([k, v]) => ({ key: k, label: v }));
                        }
                        return Object.entries(all).map(([k, v]) => ({ key: k, label: v }));
                    },
                    get optimalPackaging() {
                        let bestCost = null;
                        let bestUnit = '';
                        if (Array.isArray(this.conversionRows) && this.conversionRows.length > 0) {
                            this.conversionRows.forEach(r => {
                                const qty = parseFloat(r.qty_in_base) || 0;
                                const price = parseFloat(r.price_per_unit) || 0;
                                if (qty > 0 && price > 0) {
                                    const c = price / qty;
                                    if (bestCost === null || c < bestCost) {
                                        bestCost = c;
                                        bestUnit = r.unit_name || '';
                                    }
                                }
                            });
                        }
                        return { bestCost, bestUnit };
                    },

                    init() {
                        if (tabState.init) tabState.init.call(this);

                        this.$watch('stockSearch', () => { this.stockCurrentPage = 1; });
                        this.$watch('stockStatusFilter', () => { this.stockCurrentPage = 1; });
                        this.$watch('stockCategoryFilter', () => { this.stockCurrentPage = 1; });
                        this.$watch('expirySearchText', () => { this.expiryCurrentPage = 1; });
                        this.$watch('expiryStatus', () => { this.expiryCurrentPage = 1; });
                        this.$watch('ingredientName', () => { if (this.formErrors && this.formErrors.name) delete this.formErrors.name; });
                        this.$watch('ingredientMinStock', () => { if (this.formErrors && this.formErrors.minStock) delete this.formErrors.minStock; });
                        this.$watch('panel', () => {
                            this.stockSearch = '';
                            this.stockStatusFilter = '';
                            this.stockCategoryFilter = '';
                            this.stockCurrentPage = 1;
                            this.expirySearchText = '';
                            this.expiryStatus = 'all';
                            this.expiryCurrentPage = 1;
                            this.formErrors = {};
                            this.formSubmitted = false;
                        });
                    }
                };
            };
        })();
    </script>
</div>
