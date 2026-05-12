<div
    x-data="{ 
        panel: $wire.$entangle('panel', true)
    }"
    class="relative overflow-hidden">

    <div class="relative min-h-[600px]">

        {{-- ════════════════ PANEL 1 — ADJUSTMENT LIST ════════════════ --}}
        <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">

            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Inventory Adjustment</h2>
                    <p class="text-[12px] text-gray-500 font-medium">History of stock movements and manual corrections</p>
                </div>
                
                <div class="flex items-center gap-3">
                    @if(!$this->isStaff())
                        <x-secondary-button wire:click="exportToCsv" class="h-10 px-4">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            Export CSV
                        </x-secondary-button>
                        <x-secondary-button wire:click="startBulkAdjustment" class="h-10 px-4">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            Stock Reconcile
                        </x-secondary-button>
                        <x-primary-button wire:click="handleQuickAdjustment(null)" class="h-10 px-4">
                            <span class="mr-2 text-lg leading-none">+</span> New Adjustment
                        </x-primary-button>
                    @else
                        <div class="inline-flex items-center px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-400 font-bold text-[12px] shadow-sm select-none h-10">
                            <svg class="w-3.5 h-3.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            View Only Mode
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Adjustment Stats Overview (Matching Menu Items) ── --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                {{-- Today's Logs --}}
                <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 border border-indigo-200 rounded-2xl p-4 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-indigo-700/60 uppercase tracking-widest leading-none mb-1">Today's Logs</span>
                        <span class="block text-[20px] font-black text-gray-900 leading-none">{{ number_format($stats['today_count']) }}</span>
                    </div>
                </div>
                
                {{-- Waste Count --}}
                <div class="bg-gradient-to-br from-rose-50 to-rose-100 border border-rose-200 rounded-2xl p-4 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-rose-700/60 uppercase tracking-widest leading-none mb-1">Waste (Today)</span>
                        <span class="block text-[20px] font-black text-rose-600 leading-none">{{ number_format($stats['waste_count']) }}</span>
                    </div>
                </div>

                {{-- Restock Value --}}
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-2xl p-4 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-emerald-700/60 uppercase tracking-widest leading-none mb-1">Restock Value</span>
                        <span class="block text-[20px] font-black text-emerald-600 leading-none">₱{{ number_format($stats['in_value'], 0) }}</span>
                    </div>
                </div>

                {{-- Out Count --}}
                <div class="bg-gradient-to-br from-amber-50 to-amber-100 border border-amber-200 rounded-2xl p-4 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] flex items-center gap-4 group hover:shadow-md transition-all">
                    <div class="w-10 h-10 rounded-xl bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black text-amber-700/60 uppercase tracking-widest leading-none mb-1">Out (Today)</span>
                        <span class="block text-[20px] font-black text-gray-900 leading-none">{{ number_format($stats['out_count']) }}</span>
                    </div>
                </div>
            </div>

            {{-- macOS Style Unified Toolbar --}}
            <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                
                {{-- Left: Search Bar --}}
                <div class="flex flex-1 w-full lg:w-auto">
                    <x-search-bar wireModel="search" placeholder="Find logs (Reference, Item, etc.)" width="w-full lg:w-80" />
                </div>

                {{-- Right: Filters --}}
                <div class="flex flex-wrap items-center lg:justify-end gap-2">
                    {{-- Branch Scope Filter (Super Admin only) --}}
                    @if($this->isSuperAdmin())
                        <x-dropdown align="right" width="48" wire:key="filter-branch">
                            <x-slot name="trigger">
                                <x-secondary-button type="button" class="gap-1.5 h-9 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-[12px] whitespace-nowrap font-bold">{{ $selectedBranchId ? $branches->firstWhere('id', $selectedBranchId)?->branch_name : 'All Branches' }}</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </x-secondary-button>
                            </x-slot>
                            <x-slot name="content">
                                @forelse($branches as $branch)
                                    <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', {{ $branch->id }})">
                                        {{ $branch->branch_name }}
                                    </x-dropdown-link>
                                @empty
                                    <div class="px-4 py-2 text-[12px] text-gray-400 italic">No branches...</div>
                                @endforelse
                            </x-slot>
                        </x-dropdown>
                    @else
                        <div class="inline-flex items-center gap-2 px-3 h-9 text-[12px] font-black text-slate-600 bg-slate-50 border border-slate-200 rounded-lg shadow-inner">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            <span>{{ auth()->user()->branch->branch_name ?? 'Unknown Branch' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Table View ── --}}
            <div class="w-full">
                <x-data-table>
                    <x-slot name="header">
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Date & Reference</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-center">Adjustment Type</th>
                        <th class="py-3 px-4 text-center text-[11px] font-bold text-slate-500 uppercase tracking-widest">Items</th>
                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-right">Branch</th>
                        <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Attendant</th>
                        <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                    </x-slot>

                    @forelse($movements as $mov)
                        @php
                            $typeColors = [
                                'in' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'customer_return' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'out' => 'bg-slate-50 text-slate-600 border-slate-100',
                                'waste' => 'bg-rose-50 text-rose-700 border-rose-100',
                                'waste_expired' => 'bg-rose-50 text-rose-700 border-rose-100',
                                'adjust' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                'transfer_in' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                'transfer_out' => 'bg-amber-50 text-amber-700 border-amber-100',
                            ];
                            $colorClass = $typeColors[$mov->type] ?? 'bg-gray-50 text-gray-600 border-gray-100';
                        @endphp
                        <tr wire:key="adj-{{ $mov->reference_id }}-{{ $movements->currentPage() }}" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 group">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-bold text-slate-900">{{ $mov->created_at->format('M d, Y') }}</span>
                                    <span class="text-[11px] text-indigo-500 font-black tracking-tighter uppercase">{{ $mov->reference_id ?: 'NO REF' }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider border {{ $colorClass }}">
                                    {{ str_replace('_', ' ', $mov->type) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-50 border border-slate-100 text-[11px] font-bold text-slate-600">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                    {{ $mov->item_count }} {{ \Illuminate\Support\Str::plural('Item', $mov->item_count) }}
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span class="text-[11px] text-slate-400 font-bold tracking-tight uppercase">{{ $mov->branch->branch_name ?? 'Global' }}</span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="text-right">
                                        <p class="text-[12px] font-bold text-slate-900 leading-none">{{ $mov->user->first_name }} {{ $mov->user->last_name }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">ID #{{ $mov->user->employee_id ?: $mov->user_id }}</p>
                                    </div>
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-400 border border-slate-200">
                                        {{ strtoupper(substr($mov->user->first_name, 0, 1) . substr($mov->user->last_name, 0, 1)) }}
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button wire:click="viewAdjustment('{{ $mov->reference_id }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-lg text-[11px] font-bold hover:bg-slate-50 transition-all shadow-sm group-hover:border-indigo-200 group-hover:text-indigo-600">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-0">
                                <x-empty-state 
                                    title="No records identified" 
                                    description="Try adjusting your search or branch filters."
                                />
                            </td>
                        </tr>
                    @endforelse
                </x-data-table>
                <div class="mt-4">
                    <x-pagination :paginator="$movements" />
                </div>
            </div>

        </div>{{-- end panel 1 --}}

        {{-- ════════════════ PANEL 2 — ADJUSTMENT FORM (Matching Menu Items Layout) ════════════════ --}}
        <div x-show="panel === 'adjust'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Stock Adjustment</h2>
                    <p class="text-[12px] text-gray-500 font-medium">Record manual stock corrections and movements</p>
                </div>
                <x-secondary-button wire:click="backToList" class="h-10">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </x-secondary-button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left: Details + Queue --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Bulk Adjustment Operation Guide --}}
                    <div x-show="panel === 'adjust'" class="flex items-start gap-4 p-4 bg-indigo-50/50 rounded-2xl border border-indigo-100/50 mb-6">
                        <div class="w-8 h-8 bg-white rounded-xl flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-[12px] font-black text-indigo-900 uppercase tracking-tight">Handling Bulk Stock-In</h4>
                            <p class="text-[11px] text-indigo-700/80 leading-relaxed font-medium">
                                When stocking in bulk (e.g., <span class="font-bold text-indigo-900">Cases, Trays, or Sacks</span>), 
                                the system automatically converts the quantity to base units for recipe tracking. 
                                <span class="font-bold text-indigo-900">Unit Cost</span> should reflect the price of the selected packaging unit.
                            </p>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Adjustment Details</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Reference *" />
                                <x-text-input wire:model.live.debounce.400ms="globalReference" placeholder="e.g. ADJ-{{ now()->format('Ymd') }}-01" class="mt-1.5 h-11 w-full font-medium" :hasError="$errors->has('globalReference')" />
                                <x-input-error :messages="$errors->get('globalReference')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Notes" />
                                <x-text-input wire:model.live="globalRemarks" placeholder="e.g. Correction for monthly audit..." class="mt-1.5 h-11 w-full font-medium" />
                            </div>
                        </div>
                    </div>

                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Adjustment Queue</h2>
                        
                        @if(empty($rows))
                            <div class="py-16 bg-slate-50/50 border border-dashed border-slate-200 rounded-2xl text-center">
                                <p class="text-[13px] text-slate-400 font-medium italic">No items added to adjustment queue.</p>
                                <p class="text-[11px] text-slate-400 mt-2">Use the sidebar form to add movements.</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($rows as $idx => $row)
                                    <div wire:key="queue-{{ $idx }}" class="flex items-center justify-between p-4 bg-white border border-slate-100 rounded-2xl shadow-sm group hover:border-indigo-100 transition-all">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-center text-[12px] font-black text-slate-400">
                                                {{ $idx + 1 }}
                                            </div>
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[14px] font-bold text-slate-900 leading-none">{{ $row['ingredient_name'] }}</span>
                                                    <span @class([
                                                        'text-[9px] px-2 py-0.5 rounded-full uppercase tracking-widest font-black',
                                                        'bg-emerald-50 text-emerald-600 border border-emerald-100' => $row['type'] === 'in',
                                                        'bg-rose-50 text-rose-600 border border-rose-100' => in_array($row['type'], ['waste', 'out']),
                                                        'bg-indigo-50 text-indigo-600 border border-indigo-100' => $row['type'] === 'adjust',
                                                    ])>
                                                        {{ str_replace('_', ' ', $row['type']) }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-3 mt-1.5">
                                                    <span class="text-[11px] font-black text-slate-900">
                                                        {{ $row['type'] === 'in' ? '+' : ($row['type'] === 'adjust' ? '≈' : '-') }}
                                                        {{ \App\Helpers\StockHelper::formatForDisplay($row['quantity'], $row['unit']) }}
                                                    </span>
                                                    @if($row['cost'])
                                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Cost: <span class="text-rose-500">₱{{ number_format($row['cost'], 2) }}</span></span>
                                                    @endif
                                                    
                                                    @if(isset($row['price']) && $row['price'])
                                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                                        <span class="text-[10px] font-bold text-slate-400 uppercase">Price: <span class="text-emerald-500">₱{{ number_format($row['price'], 2) }}</span></span>
                                                    @endif

                                                    @if(isset($row['cost'], $row['price']) && $row['cost'] && $row['price'])
                                                        <span class="w-1 h-1 rounded-full bg-slate-200"></span>
                                                        <span class="text-[9px] font-black bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded uppercase tracking-widest">
                                                            {{ number_format((($row['price'] - $row['cost']) / ($row['cost'] ?: 1)) * 100, 0) }}% Margin
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <button wire:click="removeRow({{ $idx }})" class="text-slate-300 hover:text-rose-500 transition-all p-2 hover:bg-rose-50 rounded-lg">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Right: Add Component + Sidebar Actions --}}
                <div class="space-y-6">
                    
                    {{-- Add Movement Form (Matching Menu Items "Add Component") --}}
                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] space-y-4">
                        <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-1 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Add Movement
                        </h2>
                        
                        <div>
                            <x-input-label value="Select Ingredient" />
                            <div class="mt-1.5">
                                <x-dropdown align="left" width="full" containerClasses="block w-full">
                                    <x-slot name="trigger">
                                        <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                            <span class="font-bold truncate">{{ $newItemId ? $ingredients->firstWhere('id', $newItemId)?->name : 'Choose an item...' }}</span>
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <div x-data="{ ingSearch: '' }" class="p-2">
                                            <div class="px-2 pb-2 mb-2 border-b border-slate-50">
                                                <x-search-bar x-model="ingSearch" placeholder="Search ingredients..." width="w-full" class="bg-slate-50 border-none h-10" />
                                            </div>
                                            <div class="max-h-60 overflow-y-auto custom-scrollbar">
                                                @foreach($ingredients as $ing)
                                                    <div x-show="!ingSearch || @js($ing->name).toLowerCase().includes(ingSearch.toLowerCase())">
                                                        <x-dropdown-link href="#" wire:click.prevent="$set('newItemId', {{ $ing->id }})">
                                                            <div class="flex items-center justify-between">
                                                                <span class="font-bold text-slate-700">{{ $ing->name }}</span>
                                                                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">{{ $ing->unit }}</span>
                                                            </div>
                                                        </x-dropdown-link>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                            <x-input-error :messages="$errors->get('newItemId')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Quantity" />
                                <div class="relative mt-1.5">
                                    <x-text-input wire:model.live="newItemQty" class="w-full h-11 pr-14 text-[13px] font-black" placeholder="0.00" x-on:input="restrictInput($event, 'price')" />
                                    <span class="absolute inset-y-0 right-4 flex items-center text-[10px] font-black text-slate-400 uppercase pointer-events-none">{{ $newItemSelectedUnit ?: $newItemUnit ?: '—' }}</span>
                                </div>
                                <x-input-error :messages="$errors->get('newItemQty')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Type" />
                                <div class="mt-1.5">
                                    <x-dropdown align="left" width="full" containerClasses="block w-full">
                                        <x-slot name="trigger">
                                            <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                <span class="font-bold truncate uppercase">{{ str_replace('_', ' ', $newItemType) }}</span>
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            @if((string)$selectedBranchId === (string)$mainBranchId)
                                                <x-dropdown-link href="#" wire:click.prevent="$set('newItemType', 'in')">Procurement (Stock In)</x-dropdown-link>
                                            @endif
                                            <x-dropdown-link href="#" wire:click.prevent="$set('newItemType', 'waste')">Waste</x-dropdown-link>
                                            <x-dropdown-link href="#" wire:click.prevent="$set('newItemType', 'adjust')">Manual Correction</x-dropdown-link>
                                            <x-dropdown-link href="#" wire:click.prevent="$set('newItemType', 'out')">Stock Out</x-dropdown-link>
                                        </x-slot>
                                    </x-dropdown>
                                </div>
                            </div>
                        </div>

                        {{-- Unit Selector: shown when type=in and ingredient has packaging tiers --}}
                        @if($newItemType === 'in' && $newItemId)
                            @php
                                $adjIng = $ingredients->firstWhere('id', $newItemId);
                                $adjConversions = $adjIng ? \App\Models\IngredientUnitConversion::where('ingredient_id', $adjIng->id)->orderBy('qty_in_base')->get() : collect();
                            @endphp
                            @if($adjConversions->isNotEmpty())
                            <div class="animate-fadeIn">
                                <x-input-label value="Received In" />
                                <div class="grid gap-2 mt-1.5" style="grid-template-columns: repeat(auto-fill, minmax(80px, 1fr))">
                                    {{-- Base unit option --}}
                                    <button type="button"
                                        wire:click="$set('newItemSelectedUnit', '{{ $adjIng->unit }}')"
                                        class="px-2 py-2 rounded-xl border text-[10px] font-black uppercase tracking-widest transition-all {{ $newItemSelectedUnit === $adjIng->unit ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white border-slate-200 text-slate-500 hover:border-indigo-300' }}">
                                        {{ strtoupper($adjIng->unit) }}
                                        <span class="block text-[8px] font-medium mt-0.5 {{ $newItemSelectedUnit === $adjIng->unit ? 'text-indigo-200' : 'text-slate-400' }}">base</span>
                                    </button>
                                    {{-- Bulk packaging options --}}
                                    @foreach($adjConversions as $conv)
                                    <button type="button"
                                        wire:click="$set('newItemSelectedUnit', '{{ $conv->unit_name }}')"
                                        class="px-2 py-2 rounded-xl border text-[10px] font-black uppercase tracking-widest transition-all {{ $newItemSelectedUnit === $conv->unit_name ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-100' : 'bg-white border-slate-200 text-slate-500 hover:border-indigo-300' }}">
                                        {{ ucfirst($conv->unit_name) }}
                                        <span class="block text-[8px] font-medium mt-0.5 {{ $newItemSelectedUnit === $conv->unit_name ? 'text-indigo-200' : 'text-slate-400' }}">
                                            {{ \App\Helpers\StockHelper::formatForDisplay($conv->qty_in_base, $adjIng->unit) }}
                                        </span>
                                    </button>
                                    @endforeach
                                </div>
                                @if($newItemSelectedUnit && $newItemSelectedUnit !== $adjIng->unit && $newItemQty > 0)
                                    @php
                                        $selConv = $adjConversions->firstWhere('unit_name', $newItemSelectedUnit);
                                        $previewBase = $selConv ? (float)$newItemQty * $selConv->qty_in_base : 0;
                                    @endphp
                                    <p class="mt-1.5 text-[10px] font-bold text-indigo-600">
                                        {{ number_format((float)$newItemQty, 2) }} {{ $newItemSelectedUnit }} × {{ number_format($selConv->qty_in_base, 2) }} = <strong>{{ \App\Helpers\StockHelper::formatForDisplay($previewBase, $adjIng->unit) }}</strong> added to stock
                                    </p>
                                @endif
                            </div>
                            @endif
                        @endif

                        @if(($newItemType === 'in' && (string)$selectedBranchId === (string)$mainBranchId) || $newItemType === 'adjust')
                            <div class="animate-fadeIn">
                                <x-input-label value="Procurement Cost" />
                                <div class="mt-1.5">
                                    <x-text-input wire:model.live="newItemCost" class="h-11 text-[13px] font-bold w-full" placeholder="Cost (₱)" x-on:input="restrictInput($event, 'price')" />
                                </div>
                                @if($newItemQty > 0 && $newItemCost > 0)
                                    <div class="mt-2 p-3 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Value</span>
                                        <span class="text-[14px] font-black text-slate-900">₱{{ number_format((float)$newItemQty * (float)$newItemCost, 2) }}</span>
                                    </div>
                                @endif
                                <div class="mt-3">
                                    <x-input-label value="Expiry Date" />
                                    <x-text-input type="date" wire:model.live="newItemExpiry" min="{{ now()->format('Y-m-d') }}" class="mt-1.5 h-11 text-[13px] font-medium w-full" />
                                </div>
                            </div>
                        @endif

                        <x-primary-button type="button" wire:click="addToQueue" class="w-full justify-center h-11 text-[11px] font-black uppercase tracking-widest mt-2">
                            Add to Queue
                        </x-primary-button>
                    </div>

                    {{-- Actions Card --}}
                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-5 ml-1">Finalize & Actions</h2>
                        
                        <div class="p-4 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100 flex items-center justify-between mb-6 transition-all hover:scale-[1.02]">
                            <span class="text-[11px] font-black text-indigo-100 uppercase tracking-widest">Queue Size</span>
                            <div class="text-right">
                                <span class="block text-[18px] font-black text-white leading-none italic font-mono">{{ count($rows) }}</span>
                                <span class="text-[10px] font-black text-indigo-200 mt-1.5 block tracking-widest">ITEMS PENDING</span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <x-primary-button wire:click="validateBeforeCommit" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                                Save Adjustment
                            </x-primary-button>
                            <x-secondary-button wire:click="backToList" class="h-11 w-full justify-center text-[12px] font-black uppercase tracking-widest border-slate-200 text-slate-500">
                                Discard Draft
                            </x-secondary-button>
                        </div>
                    </div>

                    {{-- Operation Guide Card --}}
                    <div class="bg-gradient-to-br from-indigo-50 to-white border border-indigo-100 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h2 class="text-[11px] font-black text-indigo-700 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Operation Guide
                        </h2>
                        <div class="space-y-4">
                            @if((string)$selectedBranchId === (string)$mainBranchId)
                                <div class="flex gap-3">
                                    <div class="shrink-0 w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center text-[10px] font-black text-emerald-600">IN</div>
                                    <div>
                                        <p class="text-[12px] font-bold text-slate-700 leading-tight">Procurement (Stock In)</p>
                                        <p class="text-[10px] text-slate-400 font-medium leading-relaxed mt-0.5">Primary intake for supplier deliveries. Exclusive to Main Branch.</p>
                                    </div>
                                </div>
                            @endif
                            <div class="flex gap-3">
                                <div class="shrink-0 w-5 h-5 rounded-full bg-rose-100 flex items-center justify-center text-[10px] font-black text-rose-600">WT</div>
                                <div>
                                    <p class="text-[12px] font-bold text-slate-700 leading-tight">Waste</p>
                                    <p class="text-[10px] text-slate-400 font-medium leading-relaxed mt-0.5">Reduces inventory due to spoilage, damage, or expiration.</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="shrink-0 w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-600">OT</div>
                                <div>
                                    <p class="text-[12px] font-bold text-slate-700 leading-tight">Stock Out</p>
                                    <p class="text-[10px] text-slate-400 font-medium leading-relaxed mt-0.5">Reduces inventory for manual consumption or internal use.</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="shrink-0 w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-[10px] font-black text-indigo-600">AD</div>
                                <div>
                                    <p class="text-[12px] font-bold text-slate-700 leading-tight">Manual Correction</p>
                                    <p class="text-[10px] text-slate-400 font-medium leading-relaxed mt-0.5">Quickly fix a single item's stock level without a full reconciliation.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 pt-4 border-t border-indigo-50">
                            <p class="text-[10px] text-indigo-400 font-medium italic">All adjustments are logged with an immutable audit trail for management review.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>{{-- end panel 2 --}}

        {{-- ════════════════ PANEL 3 — STOCK RECONCILE (Matching Bulk Edit UI) ════════════════ --}}
        <div x-show="panel === 'bulk'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Stock Reconcile</h2>
                    <p class="text-[12px] text-gray-500 font-medium">Reconcile physical counts for <span class="text-indigo-600 font-bold uppercase">{{ $branches->firstWhere('id', $selectedBranchId)->branch_name ?? 'N/A' }}</span></p>
                </div>
                <x-secondary-button wire:click="backToList" class="h-10">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to List
                </x-secondary-button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left: Audit Table --}}
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]" x-data="{ bulkSearch: '' }">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider">Physical Count Entry</h2>
                            <x-search-bar x-model="bulkSearch" placeholder="Filter items..." width="w-full md:w-64" class="h-10" />
                        </div>

                        <div class="overflow-x-auto border border-slate-100 rounded-2xl max-h-[500px] overflow-y-auto scrollbar-thin">
                            <table class="w-full text-left border-collapse text-[13px]">
                                <thead class="sticky top-0 bg-slate-50/95 backdrop-blur-sm z-10">
                                    <tr class="border-b border-slate-200">
                                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Ingredient</th>
                                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-right w-32">System Qty</th>
                                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-center w-32">Actual Qty</th>
                                        <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-right w-32">Variance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach($bulkAdjustments as $id => $item)
                                        <tr class="hover:bg-slate-50/50 transition-colors" 
                                            x-show="!bulkSearch || @js(strtolower($item['name'])).includes(bulkSearch.toLowerCase())">
                                            <td class="py-3 px-4">
                                                <span class="text-[13px] font-bold text-slate-900">{{ $item['name'] }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                <span class="text-[12px] font-bold text-slate-500">
                                                    {{ \App\Helpers\StockHelper::formatForDisplay($item['current'], $item['unit']) }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <x-text-input wire:model.live="bulkAdjustments.{{ $id }}.actual" class="h-9 text-center font-black" placeholder="--" x-on:input="restrictInput($event, 'price')" />
                                            </td>
                                            <td class="py-3 px-4 text-right">
                                                @if($item['actual'] !== '')
                                                    <span class="text-[12px] font-bold {{ $item['variance'] < 0 ? 'text-rose-600' : ($item['variance'] > 0 ? 'text-emerald-600' : 'text-slate-400') }}">
                                                        {{ $item['variance'] > 0 ? '+' : '' }}{{ \App\Helpers\StockHelper::formatForDisplay($item['variance'], $item['unit']) }}
                                                    </span>
                                                @else
                                                    <span class="text-[11px] text-slate-300 italic">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Right: Summary & Action --}}
                <div class="space-y-6">
                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                        <h2 class="text-[13px] font-semibold text-gray-700 uppercase tracking-wider mb-4">Stock Reconciliation Summary</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-[12px] text-slate-500 font-bold">Total Items</span>
                                <span class="text-[13px] font-bold text-slate-900">{{ count($bulkAdjustments) }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-[12px] text-slate-500 font-bold">Variances Detected</span>
                                <span class="text-[13px] font-bold text-rose-600 bg-rose-50 px-2.5 py-0.5 rounded-lg border border-rose-100">{{ count(array_filter($bulkAdjustments, fn($i) => $i['variance'] != 0 && $i['actual'] !== '')) }} Items</span>
                            </div>
                            <div class="pt-2">
                                <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Est. Cost Impact</span>
                                @php
                                    $netImpact = collect($bulkAdjustments)->filter(fn($i) => $i['actual'] !== '')->sum(fn($i) => $i['variance'] * $i['avg_cost']);
                                @endphp
                                <div class="p-4 bg-slate-50 border border-slate-100 rounded-2xl mt-2 flex items-center justify-between transition-all hover:scale-[1.02]">
                                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </div>
                                    <div class="text-right">
                                        <span @class([
                                            'text-[18px] font-black tracking-tight',
                                            'text-rose-600' => $netImpact < 0,
                                            'text-emerald-600' => $netImpact >= 0,
                                        ])>
                                            {{ $netImpact < 0 ? '-' : '+' }}₱{{ number_format(abs($netImpact), 2) }}
                                        </span>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mt-1">Net Valuation Impact</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <x-primary-button wire:click="confirmReconcile" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                            Apply Corrections
                        </x-primary-button>
                        <x-secondary-button wire:click="backToList" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest border-slate-200 text-slate-500">
                            Discard Audit
                        </x-secondary-button>
                    </div>
                </div>
            </div>
        </div>{{-- end panel 3 --}}
    </div>

    {{-- ── Confirmation Modals (Matching User Management Layout) ── --}}
    <x-modal name="confirm-save-adjustment" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-indigo-500 to-purple-600 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900 leading-tight">Save Adjustment Queue?</h3>
                    <p class="mt-1 text-[13px] text-slate-500 leading-relaxed">
                        You are about to commit <span class="font-bold text-indigo-600">{{ count($rows) }} items</span> to the inventory ledger. This will update batch stock levels and create an immutable audit log.
                    </p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-save-adjustment')" class="h-10">Discard Draft</x-secondary-button>
                <x-primary-button wire:click="commitAdjustment" @click="$dispatch('close-modal', 'confirm-save-adjustment')" class="h-10 bg-indigo-600 hover:bg-indigo-700">
                    Confirm & Save All
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirm-bulk-save" maxWidth="sm" focusable>
        <div class="h-1 w-full bg-gradient-to-r from-rose-500 to-amber-500 rounded-t-lg"></div>
        <div class="p-6">
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900 leading-tight">Apply Stock Corrections?</h3>
                    <p class="mt-1 text-[13px] text-slate-500 leading-relaxed">
                        This will permanently update the system stock levels to match your physical audit counts. 
                        <span class="font-bold text-rose-600">This action cannot be undone.</span>
                    </p>
                </div>
            </div>
            
            <div class="flex items-center justify-end gap-2 mt-6">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-bulk-save')" class="h-10">Cancel Audit</x-secondary-button>
                <x-primary-button wire:click="commitReconcile" @click="$dispatch('close-modal', 'confirm-bulk-save')" class="h-10 bg-rose-600 hover:bg-rose-700">
                    Apply Corrections
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- ── Adjustment Details Side Panel ── --}}
    <x-side-panel name="view-adjustment-details" width="max-w-md">
        @if($viewingReferenceId)
            @php
                $totalValue = $viewingMovements->sum(fn($m) => $m->quantity * $m->unit_cost);
                $firstMov = $viewingMovements->first();
            @endphp
            <div class="flex flex-col h-full bg-white max-h-screen overflow-hidden">
                {{-- Fixed Header --}}
                <div class="shrink-0 p-6 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-[18px] font-black text-slate-900 tracking-tight">Adjustment Record</h3>
                        <button @click="$dispatch('close-modal', 'view-adjustment-details')" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 text-[10px] font-black uppercase tracking-widest">{{ $viewingReferenceId }}</span>
                        <span class="text-[11px] text-slate-400 font-bold">•</span>
                        <span class="text-[11px] text-slate-400 font-bold uppercase tracking-tight">{{ $firstMov?->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                </div>

                {{-- Fixed Summary Section --}}
                <div class="shrink-0 p-6 pb-2 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-white border border-slate-100 rounded-2xl shadow-sm">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Items</p>
                            <p class="text-[20px] font-black text-slate-900">{{ count($viewingMovements) }}</p>
                        </div>
                        <div class="p-4 bg-white border border-slate-100 rounded-2xl shadow-sm">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Transaction Value</p>
                            <p class="text-[20px] font-black text-indigo-600">₱{{ number_format($totalValue, 2) }}</p>
                        </div>
                    </div>

                    <div class="p-4 bg-indigo-600 rounded-2xl shadow-lg shadow-indigo-100">
                        <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest mb-1">Adjustment Logic</p>
                        <p class="text-[15px] font-bold text-white uppercase tracking-tight">{{ str_replace('_', ' ', $firstMov?->type) }}</p>
                        <p class="text-[11px] text-indigo-100/80 mt-1 font-medium italic">Recorded by {{ $firstMov?->user->first_name }} {{ $firstMov?->user->last_name }}</p>
                    </div>

                    <h4 class="text-[11px] font-black text-slate-900 uppercase tracking-widest mt-6 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        Detailed Breakdown
                    </h4>
                </div>

                {{-- Scrollable Item List --}}
                <div class="flex-1 overflow-y-auto px-6 py-2">
                    <div class="space-y-4">
                        @foreach($viewingMovements as $vm)
                            <div class="p-4 bg-white border border-slate-100 rounded-2xl hover:border-indigo-100 transition-all shadow-sm group">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex flex-col">
                                        <span class="text-[14px] font-black text-slate-900 group-hover:text-indigo-600 transition-colors">{{ $vm->ingredient->name ?? 'Deleted' }}</span>
                                        <span class="text-[11px] text-slate-400 font-bold uppercase tracking-tight">{{ $vm->ingredient->sku ?? 'NO-SKU' }}</span>
                                    </div>
                                    <div @class([
                                        'px-3 py-1 rounded-lg text-[13px] font-black tracking-tighter',
                                        'bg-emerald-50 text-emerald-600' => in_array($vm->type, ['in', 'customer_return']),
                                        'bg-rose-50 text-rose-600' => in_array($vm->type, ['out', 'waste', 'waste_expired']),
                                        'bg-indigo-50 text-indigo-600' => $vm->type === 'adjust',
                                    ])>
                                        {{ in_array($vm->type, ['in', 'customer_return']) ? '+' : '-' }}
                                        {{ \App\Helpers\StockHelper::formatForDisplay($vm->quantity, $vm->ingredient?->unit ?? 'g') }}
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-50">
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Unit Cost</p>
                                        <p class="text-[12px] font-bold text-slate-700">₱{{ number_format($vm->unit_cost, 2) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Extended</p>
                                        <p class="text-[12px] font-black text-slate-900">₱{{ number_format($vm->quantity * $vm->unit_cost, 2) }}</p>
                                    </div>
                                </div>

                                @if($vm->expiry_date)
                                    <div class="mt-3 flex items-center gap-2 px-3 py-2 bg-amber-50 rounded-xl border border-amber-100">
                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        <span class="text-[11px] font-bold text-amber-700 uppercase">Expires: {{ $vm->expiry_date->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Fixed Footer & Remarks --}}
                <div class="shrink-0 p-6 border-t border-slate-100 bg-white">
                    @if($firstMov?->remarks)
                        <div class="mb-4 p-4 bg-indigo-50/50 border border-indigo-100 rounded-2xl relative overflow-hidden">
                            <h4 class="text-[10px] font-black text-indigo-900 uppercase tracking-widest mb-1 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                Remarks
                            </h4>
                            <p class="text-[12px] text-indigo-700 font-medium italic">"{{ $firstMov->remarks }}"</p>
                        </div>
                    @endif

                    <button @click="$dispatch('close-modal', 'view-adjustment-details')" class="w-full h-12 flex items-center justify-center gap-2 bg-slate-900 text-white text-[13px] font-black rounded-xl hover:bg-slate-800 transition-all shadow-lg shadow-slate-200">
                        <span>Done Reviewing</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            </div>
        @endif
    </x-side-panel>

</div>
