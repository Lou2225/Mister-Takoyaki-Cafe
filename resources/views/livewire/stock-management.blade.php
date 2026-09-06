@php
    $user = auth()->user();
    // Primary palette
    $primaryColor = $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald');
    $primaryText = "text-{$primaryColor}-600";
    $primaryBg = "bg-{$primaryColor}-600";
@endphp

<div
    x-data="window.stockManagement($wire)"
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
                        <x-primary-button wire:click="showCreate" class="h-10 !px-3 sm:!px-4">
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
                                            <x-secondary-button wire:click="showEdit({{ $ing->id }})" class="h-8 px-3 text-[11px] font-bold bg-white hover:bg-slate-50 border-slate-200">
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
                                                                <x-dropdown-link href="#" wire:click.prevent="selectIngredientCategory('')">Uncategorized</x-dropdown-link>
                                                                <hr class="border-slate-50">
                                                            @endif
                                                            @forelse($ingredientCategories as $cat)
                                                                <x-dropdown-link href="#" wire:click.prevent="selectIngredientCategory({{ $cat->id }})">{{ $cat->name }}</x-dropdown-link>
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
                                        <x-dropdown align="left" width="full" containerClasses="block w-full">
                                            <x-slot name="trigger">
                                                <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                    <span class="font-bold text-slate-700">{{ !empty($ingredientUnit) && isset($availableUnits[$ingredientUnit]) ? $availableUnits[$ingredientUnit] : 'Select Unit' }}</span>
                                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                                </button>
                                            </x-slot>
                                            <x-slot name="content">
                                                <div class="p-2">
                                                    <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                        @foreach($availableUnits as $key => $label)
                                                            <x-dropdown-link href="#" wire:click.prevent="selectIngredientUnit('{{ $key }}')">{{ $label }}</x-dropdown-link>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </x-slot>
                                        </x-dropdown>
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
                        {{-- Height-limited + scrollable. Because this makes the container
                             an overflow-y-auto box, the Packaging Type and Linked To
                             dropdowns inside each row are teleported to <body> instead of
                             using <x-dropdown>, so they can't be clipped by this box. --}}
                        <div class="space-y-3 max-h-[420px] overflow-y-auto pr-2 custom-scrollbar">
                            @foreach($conversionRows as $i => $row)
                                @php
                                    $qtyInBase   = (float)($row['qty_in_base'] ?? 0);
                                    $unitCost    = (float)($row['price_per_unit'] ?? 0);
                                    $costPerBase = ($qtyInBase > 0 && $unitCost > 0) ? $unitCost / $qtyInBase : null;
                                @endphp
                                <div wire:key="conv-row-{{ $i }}" class="relative border border-slate-100 rounded-2xl shadow-sm group" style="z-index: {{ 100 - $i }};">
                                    <div class="flex items-center justify-between p-3 bg-slate-50/50 border-b border-slate-100 rounded-t-2xl">
                                        <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3 flex-1">
                                                                                        <div class="w-full sm:w-40">
                                                <x-input-label value="Packaging Type" class="mb-1 text-[10px]" />
                                                <div class="relative"
                                                    x-data="{
                                                        open: false,
                                                        openUpward: false,
                                                        top: 0, left: 0,
                                                        rafId: null,
                                                        // Fixed, wider panel width — independent of the
                                                        // trigger's own w-40, so long unit labels never
                                                        // get squished.
                                                        panelWidth: 260,
                                                        position() {
                                                            const trigger = this.$refs.packagingTrigger;
                                                            const panel = this.$refs.packagingPanel;
                                                            if (!trigger || !panel) return;

                                                            const r = trigger.getBoundingClientRect();
                                                            const gap = 6;
                                                            const panelHeight = panel.offsetHeight;
                                                            const spaceBelow = window.innerHeight - r.bottom;
                                                            const spaceAbove = r.top;

                                                            this.openUpward = spaceBelow < (panelHeight + gap) && spaceAbove > spaceBelow;

                                                            this.top = this.openUpward
                                                                ? (r.top + window.scrollY - panelHeight - gap)
                                                                : (r.bottom + window.scrollY + gap);

                                                            let left = r.left + window.scrollX;
                                                            const maxLeft = window.scrollX + window.innerWidth - this.panelWidth - 8;
                                                            this.left = Math.max(8, Math.min(left, maxLeft));
                                                        },
                                                        startTracking() {
                                                            const loop = () => {
                                                                if (!this.open) { this.rafId = null; return; }
                                                                this.position();
                                                                this.rafId = requestAnimationFrame(loop);
                                                            };
                                                            this.rafId = requestAnimationFrame(loop);
                                                        },
                                                        async openDropdown() {
                                                            this.open = true;
                                                            await this.$nextTick();
                                                            this.position();
                                                            this.startTracking();
                                                        },
                                                        closeDropdown() {
                                                            this.open = false;
                                                            if (this.rafId) { cancelAnimationFrame(this.rafId); this.rafId = null; }
                                                        },
                                                        destroy() {
                                                            if (this.rafId) cancelAnimationFrame(this.rafId);
                                                        }
                                                    }"
                                                    @click.outside="closeDropdown()"
                                                    @keydown.escape.window="closeDropdown()">

                                                    <button type="button" x-ref="packagingTrigger"
                                                        @click="open ? closeDropdown() : openDropdown()"
                                                        class="flex items-center justify-between w-full px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-[12px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-9">
                                                        <span class="font-bold text-slate-700 truncate mr-2">
                                                            {{ !empty($row['unit_name']) && isset($availableBulkUnits[$row['unit_name']]) ? $availableBulkUnits[$row['unit_name']] : 'Select Packaging...' }}
                                                        </span>
                                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                                    </button>

                                                    {{-- Teleported to <body>: escapes the row's overflow-y-auto
                                                         ancestor, and uses a fixed wider panelWidth so labels
                                                         like longer unit names are never squished to w-40. --}}
                                                    <template x-teleport="body">
                                                        <div x-show="open" x-cloak x-ref="packagingPanel"
                                                            x-transition:enter="transition ease-out duration-100"
                                                            x-transition:enter-start="opacity-0"
                                                            x-transition:enter-end="opacity-100"
                                                            x-transition:leave="transition ease-in duration-75"
                                                            x-transition:leave-start="opacity-100"
                                                            x-transition:leave-end="opacity-0"
                                                            :style="`position:absolute; top:${top}px; left:${left}px; width:${panelWidth}px;`"
                                                            class="z-[9999] bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 p-2">
                                                            <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                                <a href="#" wire:click.prevent="$set('conversionRows.{{ $i }}.unit_name', '')" @click="closeDropdown()"
                                                                    class="block px-4 py-2 text-[12px] rounded-lg hover:bg-slate-50 transition-colors text-slate-700">
                                                                    Select Packaging...
                                                                </a>
                                                                <hr class="border-slate-50 my-1">
                                                                @foreach($availableBulkUnits as $key => $label)
                                                                    <a href="#" wire:click.prevent="$set('conversionRows.{{ $i }}.unit_name', '{{ $key }}')" @click="closeDropdown()"
                                                                        class="block px-4 py-2 text-[12px] rounded-lg hover:bg-slate-50 transition-colors text-slate-700">
                                                                        {{ $label }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="w-full sm:w-40">
                                                <x-input-label value="Unit Cost (₱)" class="mb-1 text-[10px]" />
                                                <div class="relative">
                                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                                        <span class="text-[10px] font-black uppercase tracking-tighter">Cost ₱</span>
                                                    </div>
                                                    <x-text-input value="{{ number_format((float)($row['price_per_unit'] ?? 0), 2) }}" 
                                                        class="w-full h-9 pl-14 text-[12px] font-black text-right bg-slate-50 text-slate-400 cursor-not-allowed" 
                                                        placeholder="0.00" 
                                                        readonly 
                                                    />
                                                </div>
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
                                    <div class="p-3 bg-white rounded-b-2xl">
                                        <div class="flex items-end gap-3">
                                            <div class="w-20 shrink-0 relative group/hint">
                                                <x-input-label value="Pack Size" class="mb-1 text-[10px]" />
                                                <x-text-input 
                                                    wire:model.live="conversionRows.{{ $i }}.chain_multiplier" 
                                                    wire:change="updateConversionMultiplier({{ $i }})"
                                                    class="w-full h-8 text-[12px] font-black text-center border-indigo-100 bg-indigo-50/30" 
                                                    placeholder="Size" 
                                                    type="number"
                                                    min="0"
                                                    step="0.0001"
                                                    oninput="this.value = !!this.value && Math.abs(this.value) >= 0 ? Math.abs(this.value) : null"
                                                    onkeypress="return (event.charCode >= 48 && event.charCode <= 57) || event.charCode == 46"
                                                />
                                            </div>
                                            <span class="text-slate-300 font-bold text-md mb-1.5">&times;</span>
                                            <div class="flex-1 min-w-[100px]">
                                                <x-input-label value="Linked To" class="mb-1 text-[10px]" />
                                                @php
                                                    $hasChainingOptions = false;
                                                    foreach($conversionRows as $j => $prevRow) {
                                                        if($j < $i && ($prevRow['unit_name'] ?? '')) {
                                                            $hasChainingOptions = true; break;
                                                        }
                                                    }
                                                @endphp

                                                @if($hasChainingOptions)
                                                    @php
                                                        $selectedChainIdx = $row['chain_from_index'] ?? 'base';
                                                        if ($selectedChainIdx === 'base' || $selectedChainIdx === '') {
                                                            $chainDisplay = '1 ' . strtoupper($ingredientUnit);
                                                        } else {
                                                            $cRow = $conversionRows[$selectedChainIdx] ?? null;
                                                            if ($cRow) {
                                                                $chainDisplay = strtoupper($cRow['unit_name'] ?? '') . ' (' . number_format((float)($cRow['qty_in_base'] ?? 0), 2) . ' ' . strtoupper($ingredientUnit) . ')';
                                                            } else {
                                                                $chainDisplay = '1 ' . strtoupper($ingredientUnit);
                                                            }
                                                        }
                                                    @endphp
                                                                                                        <div class="relative"
                                                        x-data="{
                                                            open: false,
                                                            openUpward: false,
                                                            top: 0, left: 0, width: 0,
                                                            rafId: null,
                                                            position() {
                                                                const trigger = this.$refs.linkedToTrigger;
                                                                const panel = this.$refs.linkedToPanel;
                                                                if (!trigger || !panel) return;

                                                                const r = trigger.getBoundingClientRect();
                                                                const gap = 6;
                                                                const panelHeight = panel.offsetHeight;
                                                                const spaceBelow = window.innerHeight - r.bottom;
                                                                const spaceAbove = r.top;

                                                                this.openUpward = spaceBelow < (panelHeight + gap) && spaceAbove > spaceBelow;

                                                                this.top = this.openUpward
                                                                    ? (r.top + window.scrollY - panelHeight - gap)
                                                                    : (r.bottom + window.scrollY + gap);

                                                                let left = r.left + window.scrollX;
                                                                const maxLeft = window.scrollX + window.innerWidth - r.width - 8;
                                                                this.left = Math.max(8, Math.min(left, maxLeft));
                                                                this.width = r.width;
                                                            },
                                                            startTracking() {
                                                                const loop = () => {
                                                                    if (!this.open) { this.rafId = null; return; }
                                                                    this.position();
                                                                    this.rafId = requestAnimationFrame(loop);
                                                                };
                                                                this.rafId = requestAnimationFrame(loop);
                                                            },
                                                            async openDropdown() {
                                                                this.open = true;
                                                                await this.$nextTick();
                                                                this.position();
                                                                this.startTracking();
                                                            },
                                                            closeDropdown() {
                                                                this.open = false;
                                                                if (this.rafId) { cancelAnimationFrame(this.rafId); this.rafId = null; }
                                                            },
                                                            destroy() {
                                                                if (this.rafId) cancelAnimationFrame(this.rafId);
                                                            }
                                                        }"
                                                        @click.outside="closeDropdown()"
                                                        @keydown.escape.window="closeDropdown()">

                                                        <button type="button" x-ref="linkedToTrigger"
                                                            @click="open ? closeDropdown() : openDropdown()"
                                                            class="flex items-center justify-between w-full px-2.5 py-1 bg-slate-50/50 border border-slate-200 rounded-lg text-[11px] font-bold text-slate-600 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-8">
                                                            <span class="truncate mr-2">{{ $chainDisplay }}</span>
                                                            <svg class="w-3 h-3 text-slate-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                                                        </button>

                                                        <template x-teleport="body">
                                                            <div x-show="open" x-cloak x-ref="linkedToPanel"
                                                                x-transition:enter="transition ease-out duration-100"
                                                                x-transition:enter-start="opacity-0"
                                                                x-transition:enter-end="opacity-100"
                                                                x-transition:leave="transition ease-in duration-75"
                                                                x-transition:leave-start="opacity-100"
                                                                x-transition:leave-end="opacity-0"
                                                                :style="`position:absolute; top:${top}px; left:${left}px; width:${width}px;`"
                                                                class="z-[9999] bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 p-1.5">
                                                                <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                                    <a href="#" wire:click.prevent="setConversionLink({{ $i }}, 'base')" @click="closeDropdown()"
                                                                        class="block px-3 py-1.5 rounded-lg hover:bg-slate-50 transition-colors">
                                                                        <span class="text-[11px] font-bold text-slate-600">1 {{ strtoupper($ingredientUnit) }}</span>
                                                                    </a>
                                                                    @foreach($conversionRows as $j => $prevRow)
                                                                        @if($j < $i && ($prevRow['unit_name'] ?? ''))
                                                                            <a href="#" wire:click.prevent="setConversionLink({{ $i }}, {{ $j }})" @click="closeDropdown()"
                                                                                class="block px-3 py-1.5 rounded-lg hover:bg-slate-50 transition-colors">
                                                                                <span class="text-[11px] font-bold text-slate-600">{{ strtoupper($prevRow['unit_name'] ?? '') }} ({{ number_format((float)($prevRow['qty_in_base'] ?? 0), 2) }} {{ strtoupper($ingredientUnit) }})</span>
                                                                            </a>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </template>
                                                    </div>
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
                                        <x-danger-button wire:click="confirmWasteBatch({{ $batch->id }})"
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
                const tabState = window.slidingTabs($wire.entangle('panel').live, 'panel');
                return {
                    ...tabState,
                    panel: $wire.entangle('panel').live,
                    mode: 'list',

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
                    stockCategoryLabels: @js($allIngredientCategories->pluck('name', 'id')),

                    get filteredIngredientIds() {
                        const query = this.stockSearch.toLowerCase().trim();
                        const status = this.stockStatusFilter;
                        const category = this.stockCategoryFilter;
                        return this.ingredientsList
                            .filter(i => {
                                const matchesSearch = !query || i.name.toLowerCase().includes(query);
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
                        const oldIds = this.ingredientsList.map(i => i.id).join(',');
                        const newIds = newList.map(i => i.id).join(',');
                        if (oldIds !== newIds) {
                            this.ingredientsList = newList;
                            this.stockCurrentPage = 1;
                        }
                    },

                    get filteredBatchIds() {
                        const query = this.expirySearchText.toLowerCase().trim();
                        const status = this.expiryStatus;
                        return this.batchesList
                            .filter(b => {
                                const matchesSearch = !query || b.ingredient_name.toLowerCase().includes(query);
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
                        const oldIds = this.batchesList.map(b => b.id).join(',');
                        const newIds = newList.map(b => b.id).join(',');
                        if (oldIds !== newIds) {
                            this.batchesList = newList;
                            this.expiryCurrentPage = 1;
                        }
                    },

                    init() {
                        // Run slidingTabs init for panel indicator
                        if (tabState.init) tabState.init.call(this);

                        // Reset page on search
                        this.$watch('stockSearch', () => {
                            this.stockCurrentPage = 1;
                        });
                        this.$watch('stockStatusFilter', () => {
                            this.stockCurrentPage = 1;
                        });
                        this.$watch('stockCategoryFilter', () => {
                            this.stockCurrentPage = 1;
                        });
                        this.$watch('expirySearchText', () => {
                            this.expiryCurrentPage = 1;
                        });
                        this.$watch('expiryStatus', () => {
                            this.expiryCurrentPage = 1;
                        });
                        this.$watch('panel', () => {
                            this.stockSearch = '';
                            this.stockStatusFilter = '';
                            this.stockCategoryFilter = '';
                            this.stockCurrentPage = 1;
                            this.expirySearchText = '';
                            this.expiryStatus = 'all';
                            this.expiryCurrentPage = 1;
                        });
                    }
                };
            };
        })();
    </script>
</div>
