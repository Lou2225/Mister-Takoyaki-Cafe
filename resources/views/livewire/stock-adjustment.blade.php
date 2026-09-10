<div
    x-data="{ 
        panel: $wire.$entangle('panel', true)
    }"
    class="relative overflow-hidden">

    <div class="relative min-h-[600px]">

        {{-- ════════════════ PANEL 1 — ADJUSTMENT LIST ════════════════ --}}
        <div x-show="panel === 'list'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="px-1">

            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Stock Adjustment</h2>
                
                <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
                    @if(!$this->isStaff())
                        <x-secondary-button wire:click="exportToCsv" class="h-10 !px-2.5 sm:!px-4">
                            <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            <span class="hidden sm:inline">Export CSV</span>
                        </x-secondary-button>
<x-secondary-button @click="panel = 'bulk'; $wire.startBulkAdjustment()" class="h-10 !px-2.5 sm:!px-4">
                            <svg class="w-4 h-4 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            <span class="hidden sm:inline">Stock Reconcile</span>
                        </x-secondary-button>
<x-primary-button @click="panel = 'adjust'; $wire.handleQuickAdjustment(null)" class="h-10 !px-2.5 sm:!px-4">
                            <span class="text-lg leading-none sm:mr-2">+</span> <span class="hidden sm:inline">New Adjustment</span>
                        </x-primary-button>
                    @else
                        <div class="inline-flex items-center px-2.5 sm:px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-400 font-bold text-[12px] shadow-sm select-none h-10">
                            <svg class="w-3.5 h-3.5 sm:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            <span class="hidden sm:inline">View Only Mode</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Adjustment Stats Overview (Matching Menu Items) ── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
                {{-- Today's Logs --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ $startDate ? 'Period Logs' : "Today's Logs" }}</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($stats['today_count']) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Total changes logged</p>
                </div>
                
                {{-- Waste Count --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-rose-600/90 uppercase tracking-wider">{{ $startDate ? 'Waste (Period)' : 'Waste (Today)' }}</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">{{ number_format($stats['waste_count']) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Spoiled / discarded stock</p>
                </div>

                {{-- Restock Value --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ $startDate ? 'Restock (Period)' : 'Restock Value' }}</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight leading-none">₱{{ number_format($stats['in_value'], 0) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Cost value of items received</p>
                </div>

                {{-- Out Count --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">{{ $startDate ? 'Out (Period)' : 'Out (Today)' }}</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($stats['out_count']) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Items checked out/reduced</p>
                </div>
            </div>

            {{-- macOS Style Unified Toolbar --}}
            <div class="relative z-20 flex flex-row items-center justify-between mb-6 gap-2 sm:gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)]">
                
                {{-- Left: Search Bar --}}
                <div class="flex flex-1 min-w-0 lg:flex-initial">
                    <x-search-bar wireModel="search" placeholder="Find logs (Reference, Item, etc.)" width="w-full lg:w-80" />
                </div>

                {{-- Right: Filters --}}
                <div class="flex flex-nowrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">
                    {{-- 3-in-1 Date Filter Component --}}
                    <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" />

                    {{-- Branch Scope Filter (Super Admin only) --}}
                    @if($this->isSuperAdmin())
                        <x-dropdown align="right" width="48" wire:key="filter-branch">
                            <x-slot name="trigger">
                                <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="hidden sm:inline text-[12px] whitespace-nowrap font-bold">{{ $selectedBranchId ? $branches->firstWhere('id', $selectedBranchId)?->branch_name : 'All Branches' }}</span>
                                    <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <div class="inline-flex items-center gap-0 sm:gap-2 px-2.5 sm:px-3 h-10 text-[12px] font-black text-slate-600 bg-slate-50 border border-slate-200 rounded-lg shadow-inner shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            <span class="hidden sm:inline">{{ auth()->user()->branch->branch_name ?? 'Unknown Branch' }}</span>
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
                            $s = match($mov->type) {
                                'in', 'customer_return' => ['dot' => 'bg-emerald-500', 'color' => 'emerald', 'label' => str_replace('_', ' ', $mov->type)],
                                'out' => ['dot' => 'bg-slate-400', 'color' => 'slate', 'label' => str_replace('_', ' ', $mov->type)],
                                'waste', 'waste_expired' => ['dot' => 'bg-rose-500', 'color' => 'rose', 'label' => str_replace('_', ' ', $mov->type)],
                                'adjust', 'transfer_in' => ['dot' => 'bg-indigo-500', 'color' => 'indigo', 'label' => str_replace('_', ' ', $mov->type)],
                                'transfer_out' => ['dot' => 'bg-amber-500', 'color' => 'amber', 'label' => str_replace('_', ' ', $mov->type)],
                                default => ['dot' => 'bg-slate-400', 'color' => 'slate', 'label' => str_replace('_', ' ', $mov->type)]
                            };
                        @endphp
                        <tr wire:key="adj-{{ $mov->date }}-{{ $mov->type }}-{{ $movements->currentPage() }}" class="hover:bg-slate-50/50 transition-colors border-b border-slate-50 group">
                            <td class="py-3 px-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-[13px] font-bold text-slate-900">{{ \Carbon\Carbon::parse($mov->date)->format('M d, Y') }}</span>
                                    <span class="text-[11px] text-indigo-500 font-black tracking-tighter uppercase">
                                        {{ in_array($mov->type, ['order', 'waste', 'out', 'adjust']) ? strtoupper(str_replace('_', ' ', $mov->type)) . ' SUMMARY' : ($mov->reference_id ?: 'BATCH LOG') }}
                                    </span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span>
                                    <span class="text-[11px] font-bold text-{{ $s['color'] }}-600 uppercase">{{ $s['label'] }}</span>
                                </div>
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
                                        <p class="text-[12px] font-bold text-slate-900 leading-none">{{ $mov->user?->first_name ?? 'System' }} {{ $mov->user?->last_name ?? 'Attendant' }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                                            @if($mov->user)
                                                ID #{{ $mov->user->employee_id ?: $mov->user_id }}
                                            @else
                                                AUTO PROCESS
                                            @endif
                                        </p>
                                    </div>
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-400 border border-slate-200">
                                        {{ $mov->user ? strtoupper(substr($mov->user->first_name, 0, 1) . substr($mov->user->last_name, 0, 1)) : 'SYS' }}
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button wire:click="viewAdjustment('{{ $mov->date }}', '{{ $mov->type }}')" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-slate-200 text-slate-600 rounded-lg text-[11px] font-bold hover:bg-slate-50 transition-all shadow-sm group-hover:border-indigo-200 group-hover:text-indigo-600">
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
<x-secondary-button @click="panel = 'list'; $wire.backToList()" class="h-10">
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
                                <x-input-label value="Reference" />
                                @if(!empty($rows))
                                    <x-text-input wire:model.live.debounce.400ms="globalReference" placeholder="e.g. ADJ-{{ now()->format('Ymd') }}-01" class="mt-1.5 h-11 w-full font-medium" :hasError="$errors->has('globalReference')" />
                                    <x-input-error :messages="$errors->get('globalReference')" class="mt-1" />
                                @else
                                    <div class="mt-1.5 h-11 w-full flex items-center px-4 bg-slate-50 border border-dashed border-slate-200 rounded-xl text-[12px] text-slate-400 font-medium italic">
                                        Generated once you add the first item
                                    </div>
                                @endif
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
                                                    @php
                                                        $rowS = match($row['type']) {
                                                            'in' => ['dot' => 'bg-emerald-500', 'color' => 'emerald'],
                                                            'waste', 'out' => ['dot' => 'bg-rose-500', 'color' => 'rose'],
                                                            'adjust' => ['dot' => 'bg-indigo-500', 'color' => 'indigo'],
                                                            default => ['dot' => 'bg-slate-400', 'color' => 'slate']
                                                        };
                                                    @endphp
                                                    <div class="flex items-center gap-2">
                                                        <span class="h-1.5 w-1.5 rounded-full {{ $rowS['dot'] }}"></span>
                                                        <span class="text-[11px] font-bold text-{{ $rowS['color'] }}-600 uppercase">{{ str_replace('_', ' ', $row['type']) }}</span>
                                                    </div>
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
                    <div class="bg-white border border-slate-200/60 rounded-2xl p-6 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] space-y-4"
                        x-data="{
                            open: false,
                            dropUp: false,
                            search: '',
                            selectedId: @entangle('newItemId').live,
                            qty: @entangle('newItemQty').live,
                            formErrors: {},
                            ingredients: {{ Js::from($ingredients->map(fn($i) => ['id' => $i->id, 'name' => $i->name, 'unit' => $i->unit])) }},
                            get selectedItem() {
                                if (!this.selectedId) return null;
                                return this.ingredients.find(i => Number(i.id) === Number(this.selectedId)) || null;
                            },
                            get filteredItems() {
                                if (!this.search.trim()) return this.ingredients;
                                const q = this.search.toLowerCase().trim();
                                return this.ingredients.filter(i => (i.name || '').toLowerCase().includes(q));
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
                                if (!this.$refs.comboboxContainer) return;
                                const rect = this.$refs.comboboxContainer.getBoundingClientRect();
                                const spaceBelow = window.innerHeight - rect.bottom;
                                this.dropUp = spaceBelow < 250 && rect.top > 250;
                            },
                            select(id) {
                                this.selectedId = id;
                                this.search = '';
                                this.open = false;
                                delete this.formErrors.newItemId;
                            },
                            clear() {
                                this.selectedId = '';
                                this.search = '';
                                this.open = false;
                                delete this.formErrors.newItemId;
                            },
                            validateAndAdd() {
                                this.formErrors = {};
                                if (!this.selectedId) {
                                    this.formErrors.newItemId = 'Select an ingredient.';
                                }
                                if (!this.qty || parseFloat(this.qty) <= 0) {
                                    this.formErrors.newItemQty = 'Quantity is required.';
                                }
                                if (Object.keys(this.formErrors).length === 0) {
                                    $wire.addToQueue();
                                }
                            }
                        }">
                        <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-1 flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                            Add Movement
                        </h2>
                        
                        <div>
                            <x-input-label value="Select Ingredient" />
                            <div class="relative mt-1.5" x-ref="comboboxContainer"
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
                                        :value="open ? search : (selectedItem ? selectedItem.name : '')"
                                        @input="search = $event.target.value; open = true; delete formErrors.newItemId"
                                        @focus="openDropdown()"
                                        @click="openDropdown()"
                                        :placeholder="selectedItem ? selectedItem.name : 'Search or select ingredient...'"
                                        class="w-full pl-10 pr-10 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-800 shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all h-11"
                                        :class="{
                                            'font-bold text-indigo-700 bg-indigo-50/20 border-indigo-200': selectedItem && !open,
                                            'text-slate-800': !selectedItem || open,
                                            '!border-red-400 focus:!border-red-400 focus:!ring-red-300 !bg-red-50/30': (formErrors.newItemId || @js($errors->has('newItemId')))
                                        }"
                                        autocomplete="off"
                                    />

                                    {{-- Clear button only (NO up/down arrow!) --}}
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <button type="button" 
                                            x-show="selectedId || search.length > 0"
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
                                    <template x-for="item in filteredItems" :key="item.id">
                                        <button type="button" 
                                            @click="select(item.id)"
                                            class="w-full text-left px-3.5 py-2.5 rounded-lg hover:bg-indigo-50/70 hover:text-indigo-900 transition-colors flex items-center justify-between group"
                                            :class="Number(selectedId) === Number(item.id) ? 'bg-indigo-50 text-indigo-900 font-bold' : 'text-slate-700'">
                                            <span class="text-[13px] font-medium group-hover:font-semibold truncate" x-text="item.name"></span>
                                            <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 group-hover:bg-indigo-100 group-hover:text-indigo-600 shrink-0 ml-2" x-text="item.unit"></span>
                                        </button>
                                    </template>
                                    <template x-if="filteredItems.length === 0">
                                        <div class="px-4 py-3 text-[12px] text-slate-400 italic text-center">
                                            No ingredients found
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <template x-if="formErrors.newItemId">
                                <div class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200">
                                    <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <ul class="space-y-0.5">
                                        <li x-text="formErrors.newItemId"></li>
                                    </ul>
                                </div>
                            </template>
                            <x-input-error :messages="$errors->get('newItemId')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Quantity" />
                                <div class="relative mt-1.5">
                                    @php $qtyErrClass = $errors->has('newItemQty') ? 'border-red-400 bg-red-50/30' : ''; @endphp
                                    <input 
                                        type="text"
                                        wire:model.live="newItemQty"
                                        x-bind:class="{ 'border-red-400 bg-red-50/30': formErrors.newItemQty }"
                                        class="w-full h-11 pr-14 text-[13px] font-black border focus:ring-1 focus:ring-indigo-500/30 rounded-lg shadow-sm placeholder-gray-400 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:outline-none {{ $qtyErrClass }} {{ $qtyErrClass ? 'focus:border-red-400' : 'border-gray-200 focus:border-indigo-500' }}"
                                        placeholder="0.00"
                                        x-on:input="delete formErrors.newItemQty; restrictInput($event, 'price')"
                                    />
                                    <span class="absolute inset-y-0 right-4 flex items-center text-[10px] font-black text-slate-400 uppercase pointer-events-none">{{ $newItemSelectedUnit ?: $newItemUnit ?: '—' }}</span>
                                </div>
                                <template x-if="formErrors.newItemQty">
                                    <div class="text-[11px] font-medium text-red-500 mt-1.5 flex items-start gap-1.5 animate-in fade-in slide-in-from-top-1 duration-200">
                                        <svg class="w-3.5 h-3.5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                        <ul class="space-y-0.5">
                                            <li x-text="formErrors.newItemQty"></li>
                                        </ul>
                                    </div>
                                </template>
                                <x-input-error :messages="$errors->get('newItemQty')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Type" />
                                <div class="mt-1.5">
                                    <x-dropdown align="left" width="full" containerClasses="block w-full">
                                        <x-slot name="trigger">
                                            <button type="button" class="flex items-center justify-between w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-[13px] text-slate-700 shadow-sm hover:border-slate-300 focus:outline-none transition-all h-11">
                                                <span class="font-bold truncate uppercase">{{ $newItemType === 'in' ? 'Procurement (Stock In)' : ($newItemType === 'waste' ? 'Waste' : ($newItemType === 'out' ? 'Stock Out' : str_replace('_', ' ', $newItemType))) }}</span>
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            @if((string)$selectedBranchId === (string)$mainBranchId)
                                                <x-dropdown-link href="#" wire:click.prevent="$set('newItemType', 'in')">Procurement (Stock In)</x-dropdown-link>
                                            @endif
                                            <x-dropdown-link href="#" wire:click.prevent="$set('newItemType', 'waste')">Waste</x-dropdown-link>
                                            <x-dropdown-link href="#" wire:click.prevent="$set('newItemType', 'out')">Stock Out</x-dropdown-link>
                                        </x-slot>
                                    </x-dropdown>
                                </div>
                            </div>
                        </div>

                        {{-- Unit Selector: shown when type=in and ingredient has packaging tiers (only for main branch) --}}
                        @if($newItemType === 'in' && $newItemId && (string)$selectedBranchId === (string)$mainBranchId)
                            @php
                                $adjIng = $ingredients->firstWhere('id', $newItemId);
                                $adjConversions = $adjIng ? ($adjIng->unitConversions ?? collect()) : collect();
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

                        @if($newItemType === 'in' && (string)$selectedBranchId === (string)$mainBranchId)
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

                        <x-primary-button type="button" @click="validateAndAdd()" class="w-full justify-center h-11 text-[11px] font-black uppercase tracking-widest mt-2">
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
                            <div>
                                <x-primary-button wire:click="validateBeforeCommit" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                                    Save Adjustment
                                </x-primary-button>
                                <x-input-error :messages="$errors->get('rows')" class="mt-2 text-center" />
                            </div>
                            <x-secondary-button @click="panel = 'list'; $wire.backToList()" class="h-11 w-full justify-center text-[12px] font-black uppercase tracking-widest border-slate-200 text-slate-500">
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
                <x-secondary-button @click="panel = 'list'; $wire.backToList()" class="h-10">
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
                        <div>
                            <x-primary-button wire:click="confirmReconcile" class="w-full justify-center h-12 text-[12px] font-black uppercase tracking-widest shadow-lg shadow-indigo-100">
                                Apply Corrections
                            </x-primary-button>
                            <x-input-error :messages="$errors->get('bulkAdjustments')" class="mt-2 text-center" />
                        </div>
                        <x-secondary-button @click="panel = 'list'; $wire.backToList()" class="w-full justify-center h-11 text-[12px] font-black uppercase tracking-widest border-slate-200 text-slate-500">
                            Discard Audit
                        </x-secondary-button>
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
                // Group viewing movements by ingredient_id to combine identical items
                $combinedMovements = $viewingMovements->groupBy('ingredient_id')->map(function($movs) {
                    $first = $movs->first();
                    $totalQty = $movs->sum('quantity');
                    $unitCost = $movs->max('unit_cost') ?: ($first->ingredient->cost ?? 0);
                    
                    // Clone first movement and set calculated total quantity and unit cost
                    $combined = clone $first;
                    $combined->quantity = $totalQty;
                    $combined->unit_cost = $unitCost;
                    return $combined;
                })->values();

                $totalValue = $combinedMovements->sum(fn($m) => $m->quantity * $m->unit_cost);
                $firstMov = $viewingMovements->first();
                $type = $firstMov?->type ?? 'adjust';
                
                if (in_array($type, ['in', 'customer_return'])) {
                    $themeBg = 'bg-emerald-600';
                    $themeShadow = 'shadow-emerald-100';
                    $themeText = 'text-emerald-600';
                    $themeBadge = 'bg-emerald-100 text-emerald-700';
                    $themeTextSec = 'text-emerald-200';
                } elseif (in_array($type, ['out', 'waste', 'waste_expired', 'return_to_supplier'])) {
                    $themeBg = 'bg-rose-600';
                    $themeShadow = 'shadow-rose-100';
                    $themeText = 'text-rose-600';
                    $themeBadge = 'bg-rose-100 text-rose-700';
                    $themeTextSec = 'text-rose-200';
                } else {
                    $themeBg = 'bg-indigo-600';
                    $themeShadow = 'shadow-indigo-100';
                    $themeText = 'text-indigo-600';
                    $themeBadge = 'bg-indigo-100 text-indigo-700';
                    $themeTextSec = 'text-indigo-200';
                }
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
                        <span class="px-2 py-0.5 rounded {{ $themeBadge }} text-[10px] font-black uppercase tracking-widest">{{ $viewingReferenceId }}</span>
                        <span class="text-[11px] text-slate-400 font-bold">•</span>
                        <span class="text-[11px] text-slate-400 font-bold uppercase tracking-tight">{{ $firstMov?->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                </div>

                {{-- Fixed Summary Section --}}
                <div class="shrink-0 p-6 pb-2 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-white border border-slate-100 rounded-2xl shadow-sm">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Items</p>
                            <p class="text-[20px] font-black text-slate-900">{{ count($combinedMovements) }}</p>
                        </div>
                        <div class="p-4 bg-white border border-slate-100 rounded-2xl shadow-sm">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Transaction Value</p>
                            <p class="text-[20px] font-black {{ $themeText }}">₱{{ number_format($totalValue, 2) }}</p>
                        </div>
                    </div>

                    <div class="p-4 {{ $themeBg }} rounded-2xl shadow-lg {{ $themeShadow }}">
                        <p class="text-[10px] font-black {{ $themeTextSec }} uppercase tracking-widest mb-1">Adjustment Logic</p>
                        <p class="text-[15px] font-bold text-white uppercase tracking-tight">{{ str_replace('_', ' ', $firstMov?->type) }}</p>
                        <p class="text-[11px] {{ $themeTextSec }}/80 mt-1 font-medium italic">Recorded by {{ $firstMov?->user?->first_name ?? 'System' }} {{ $firstMov?->user?->last_name ?? 'Attendant' }}</p>
                    </div>

                    <h4 class="text-[11px] font-black text-slate-900 uppercase tracking-widest mt-6 mb-2 flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $themeText }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        Detailed Breakdown
                    </h4>
                </div>

                {{-- Scrollable Item List --}}
                <div class="flex-1 overflow-y-auto px-6 py-2">
                    <div class="space-y-4">
                        @foreach($combinedMovements as $vm)
                            @php
                                $hoverBorder = in_array($vm->type, ['in', 'customer_return']) ? 'hover:border-emerald-200' : (in_array($vm->type, ['out', 'waste', 'waste_expired', 'return_to_supplier']) ? 'hover:border-rose-200' : 'hover:border-indigo-200');
                                $hoverText = in_array($vm->type, ['in', 'customer_return']) ? 'group-hover:text-emerald-600' : (in_array($vm->type, ['out', 'waste', 'waste_expired', 'return_to_supplier']) ? 'group-hover:text-rose-600' : 'group-hover:text-indigo-600');
                            @endphp
                            <div class="p-4 bg-white border border-slate-100 rounded-2xl {{ $hoverBorder }} transition-all shadow-sm group">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex flex-col">
                                        <span class="text-[14px] font-black text-slate-900 {{ $hoverText }} transition-colors">{{ $vm->ingredient->name ?? 'Deleted' }}</span>
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
                        @php
                            $remarksBg = in_array($type, ['in', 'customer_return']) ? 'bg-emerald-50/50' : (in_array($type, ['out', 'waste', 'waste_expired', 'return_to_supplier']) ? 'bg-rose-50/50' : 'bg-indigo-50/50');
                            $remarksBorder = in_array($type, ['in', 'customer_return']) ? 'border-emerald-100' : (in_array($type, ['out', 'waste', 'waste_expired', 'return_to_supplier']) ? 'border-rose-100' : 'border-indigo-100');
                            $remarksText = in_array($type, ['in', 'customer_return']) ? 'text-emerald-900' : (in_array($type, ['out', 'waste', 'waste_expired', 'return_to_supplier']) ? 'text-rose-900' : 'text-indigo-900');
                            $remarksSubtext = in_array($type, ['in', 'customer_return']) ? 'text-emerald-700' : (in_array($type, ['out', 'waste', 'waste_expired', 'return_to_supplier']) ? 'text-rose-700' : 'text-indigo-700');
                        @endphp
                        <div class="mb-4 p-4 {{ $remarksBg }} border {{ $remarksBorder }} rounded-2xl relative overflow-hidden">
                            <h4 class="text-[10px] font-black {{ $remarksText }} uppercase tracking-widest mb-1 flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>
                                Remarks
                            </h4>
                            <p class="text-[12px] {{ $remarksSubtext }} font-medium italic">"{{ $firstMov->remarks }}"</p>
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
