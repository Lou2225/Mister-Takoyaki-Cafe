<div class="px-2 py-2 space-y-6" x-data="slidingTabs({ panel: @entangle('panel').live }, ['panel'])" @if($selectedOrderId === null) wire:poll.10s @endif>
    {{-- ════════════════ DYNAMIC HEADER ════════════════ --}}
    <div class="px-1 pt-2">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight leading-none mb-1.5">Order Inbox</h2>
                <p class="text-[12px] text-gray-500 font-medium">Network Overview: <span class="text-amber-600 font-bold">{{ $kpis['pending'] }} pending requests</span></p>
            </div>
            <x-report-dropdown module="Stock Order Report" />
        </div>

        <x-sliding-tabs model="panel" class="mb-8">
            <x-sliding-tab model="panel" value="inbox">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg></x-slot>
                Inbox @if($kpis['pending'] > 0) <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-amber-500 text-white text-[9px] font-black ml-1 animate-pulse">{{ $kpis['pending'] }}</span> @endif
            </x-sliding-tab>
            <x-sliding-tab model="panel" value="active">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></x-slot>
                Active
            </x-sliding-tab>
            <x-sliding-tab model="panel" value="history">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot>
                History
            </x-sliding-tab>
            <x-sliding-tab model="panel" value="logistics">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></x-slot>
                Logistics
            </x-sliding-tab>
            <x-sliding-tab model="panel" value="analytics">
                <x-slot name="icon"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></x-slot>
                Analytics
            </x-sliding-tab>
        </x-sliding-tabs>
    </div>

    <!-- KPI Metrics (Premium Redesigned) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8" x-show="panel !== 'analytics'">
        {{-- New Requests --}}
        <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">New Requests</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($kpis['pending']) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Pending HQ review</p>
        </div>

        {{-- Active Transfers --}}
        <div class="p-3 sm:p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Active Transfers</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ number_format($kpis['active']) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Currently in transit/packing</p>
        </div>

        {{-- Delivered --}}
        <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Delivered (Mo)</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight leading-none">{{ number_format($kpis['delivered_month']) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Completed this month</p>
        </div>

        {{-- Rejected --}}
        <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-rose-600/90 uppercase tracking-wider">Rejected (Mo)</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">{{ number_format($kpis['rejected_month']) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Declined/cancelled this month</p>
        </div>
    </div>

    <!-- Analytics KPI Metrics (Premium Redesigned) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8" x-show="panel === 'analytics'">
        {{-- KPI 1: Value Dispatched --}}
        <div class="p-3 sm:p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">HQ Dispatched</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-600 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-indigo-600 tracking-tight leading-none">₱{{ number_format($analytics['totalDispatched'] ?? 0, 2) }}</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Fulfillment value within filter</p>
        </div>

        {{-- KPI 2: Avg Lead Time --}}
        <div class="p-3 sm:p-4 bg-gradient-to-br from-amber-500/10 via-amber-500/5 to-white border border-amber-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Avg Lead Time</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-amber-100 flex items-center justify-center text-amber-600 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-amber-600 tracking-tight leading-none">{{ $analytics['avgLeadTimeHours'] ?? 0 }} <span class="text-xs font-bold text-slate-400 uppercase">hrs</span></h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Order placement to delivery</p>
        </div>

        {{-- KPI 3: Fulfillment Success Rate --}}
        <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Fulfillment Rate</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight leading-none">{{ $analytics['fulfillmentRate'] ?? 100 }}%</h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Delivered vs Rejected orders</p>
        </div>

        {{-- KPI 4: HQ Inventory Alerts --}}
        <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
            <div class="flex items-center justify-between mb-1 sm:mb-2">
                <span class="text-[10px] sm:text-[11px] font-bold text-rose-600/90 uppercase tracking-wider">HQ Stock Alerts</span>
                <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                </div>
            </div>
            <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">{{ $analytics['hqLowStockCount'] ?? 0 }} <span class="text-xs font-bold text-slate-400 uppercase">alerts</span></h3>
            <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">HQ items below safety level</p>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-8 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm" x-show="panel !== 'logistics'">
        <x-search-bar wireModel="search" placeholder="Find records..." width="w-full lg:w-72" />
        <div class="flex flex-wrap items-center lg:justify-end gap-2">
            {{-- 3-in-1 Date Filter Component --}}
            <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" />

            <x-dropdown align="right" width="48" x-show="panel !== 'analytics'">
                <x-slot name="trigger">
                    <x-secondary-button class="gap-2 bg-white text-gray-700 hover:bg-gray-50 border-gray-200">
                        <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span class="text-[13px] font-black">Filter Options</span>
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </x-secondary-button>
                </x-slot>
                <x-slot name="content">
                    <div class="px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Branch Filter</div>
                    <x-dropdown-link href="#" wire:click.prevent="$set('branchFilter', '')">All Branches</x-dropdown-link>
                    @foreach($branches as $branch) <x-dropdown-link href="#" wire:click.prevent="$set('branchFilter', '{{ $branch->id }}')">{{ $branch->branch_name }}</x-dropdown-link> @endforeach
                </x-slot>
            </x-dropdown>
        </div>
    </div>

    <!-- MAIN PANELS -->
    <div class="relative min-h-[400px]">
        <!-- 1. Inbox Panel -->
        <div x-show="panel === 'inbox'" class="animate-fadeIn px-1 space-y-4">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Reference / Branch</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Summary</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Priority</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Total Amount</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Timeline</th>
                    <th class="py-3 px-6 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                </x-slot>
                @forelse($inboxOrders as $order)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-[13px] font-bold text-slate-900 group-hover:text-indigo-600 transition-colors cursor-pointer" wire:click="viewOrder({{ $order->id }})">{{ $order->reference_no }}</span>
                                <span class="text-[11px] text-indigo-500 font-black uppercase tracking-tighter">{{ $order->requestingBranch->branch_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-[12px] font-black text-slate-700 uppercase tabular-nums">{{ $order->items->count() }} line items</span>
                                <p class="text-[10px] text-slate-400 font-medium italic truncate max-w-[150px]">{{ $order->notes ?: 'No instructions' }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php $p = $order->priorityConfig()[$order->priority] @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black bg-{{ $p['color'] }}-50 text-{{ $p['color'] }}-600 uppercase tracking-widest border border-{{ $p['color'] }}-100">{{ $p['label'] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-[13px] font-black text-slate-900 tabular-nums">₱{{ number_format($order->total_amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-[11px] text-slate-500 tabular-nums font-bold">{{ $order->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-4 text-right">
                            <x-secondary-button type="button" wire:click="viewOrder({{ $order->id }})" class="h-9 px-3 whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View Details
                            </x-secondary-button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12"><x-empty-state title="Inbox Zero!" description="All stock requests have been processed." icon="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" /></td></tr>
                @endforelse
            </x-data-table>
            <x-pagination :paginator="$inboxOrders" keyPrefix="inbox" />
        </div>

        <!-- 2. Active Panel -->
        <div x-show="panel === 'active'" class="animate-fadeIn px-1 space-y-4">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Reference / Branch</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Approval Info</th>
                    <th class="py-3 px-6 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                </x-slot>
                @forelse($activeOrders as $order)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-[13px] font-bold text-slate-900 group-hover:text-indigo-600 transition-colors cursor-pointer" wire:click="viewOrder({{ $order->id }})">{{ $order->reference_no }}</span>
                                <span class="text-[11px] text-indigo-500 font-black uppercase tracking-tighter">{{ $order->requestingBranch->branch_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @php $s = $order->statusConfig()[$order->status] @endphp
                            <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span><span class="text-[11px] font-bold text-{{ $s['color'] }}-600">{{ $s['label'] }}</span></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col"><span class="text-[11px] font-black text-slate-700 uppercase">By {{ $order->approver->name ?? 'Admin' }}</span><span class="text-[10px] text-slate-400 font-bold tabular-nums">{{ $order->approved_at?->format('M d, H:i') ?? '—' }}</span></div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <x-secondary-button type="button" wire:click="viewOrder({{ $order->id }})" class="h-9 px-3 whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View Details
                            </x-secondary-button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-12"><x-empty-state title="No Active Transfers" description="Transfers will appear here once approved." icon="M13 10V3L4 14h7v7l9-11h-7z" /></td></tr>
                @endforelse
            </x-data-table>
            <x-pagination :paginator="$activeOrders" keyPrefix="active" />
        </div>

        <!-- 3. History Panel -->
        <div x-show="panel === 'history'" class="animate-fadeIn px-1 space-y-4">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Reference / Branch</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Items</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Total Value</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                    <th class="py-3 px-6 text-left text-[11px] font-bold text-slate-500 uppercase tracking-widest">Timeline</th>
                    <th class="py-3 px-6 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
                </x-slot>
                @forelse($historyOrders as $order)
                    <tr class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-[13px] font-bold text-slate-900 group-hover:text-indigo-600 transition-colors cursor-pointer" wire:click="viewOrder({{ $order->id }})">{{ $order->reference_no }}</span>
                                <span class="text-[11px] text-indigo-500 font-black uppercase tracking-tighter">{{ $order->requestingBranch->branch_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-[12px] font-black text-slate-700 tabular-nums uppercase">{{ $order->items->count() }} items</td>
                        <td class="px-6 py-4">
                            <span class="text-[13px] font-black text-slate-900 tabular-nums">₱{{ number_format($order->total_amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php $s = $order->statusConfig()[$order->status] @endphp
                            <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span><span class="text-[11px] font-bold text-{{ $s['color'] }}-600">{{ $s['label'] }}</span></div>
                        </td>
                        <td class="px-6 py-4 text-[11px] text-slate-500 tabular-nums font-bold">{{ $order->delivered_at?->format('M d, Y') ?? $order->updated_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <x-secondary-button type="button" wire:click="viewOrder({{ $order->id }})" class="h-9 px-3 whitespace-nowrap">
                                <svg class="w-4 h-4 mr-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                View Details
                            </x-secondary-button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12"><x-empty-state title="History Clear" description="Your fulfillment history will be logged here." icon="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></td></tr>
                @endforelse
            </x-data-table>
            <x-pagination :paginator="$historyOrders" keyPrefix="history" />
        </div>

        <!-- 4. Logistics Management (PROFESSIONAL REDESIGN) -->
        <div x-show="panel === 'logistics'" class="animate-fadeIn px-1 space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <div class="lg:col-span-3 space-y-6">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-200/60 overflow-hidden">
                        <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/30 flex items-center justify-between">
                            <div>
                                <h3 class="text-[16px] font-black text-slate-900 uppercase tracking-tight flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                    </div>
                                    Network Logistics Control
                                </h3>
                                <p class="text-[12px] text-slate-500 font-medium mt-1 ml-13">Configure operational distances for automated logistics billing</p>
                            </div>
                            <button wire:click="syncAllDistances" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-[11px] font-black text-slate-600 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-100 transition-all shadow-sm group">
                                <svg wire:loading.class="animate-spin" class="w-4 h-4 text-slate-400 group-hover:text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Sync Map Data
                            </button>
                        </div>
                        <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($branches as $branch)
                                @php
                                    $addr = $branch->address;
                                    if(str_starts_with($addr, '{')) {
                                        $data = json_decode($addr, true);
                                        $parts = array_filter([
                                            $data['STREET'] ?? null,
                                            $data['BARANGAY'] ?? null,
                                            $data['CITY'] ?? null,
                                            $data['PROVINCE'] ?? null
                                        ]);
                                        $displayAddr = !empty($parts) ? implode(', ', $parts) : 'Location details pending...';
                                    } else {
                                        $displayAddr = $addr ?: 'Location details pending...';
                                    }
                                @endphp
                                <div class="group relative bg-white border border-slate-100 rounded-3xl p-6 transition-all hover:border-indigo-400 hover:shadow-xl hover:shadow-indigo-50/50">
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-12 h-12 bg-slate-50 text-slate-400 rounded-2xl flex items-center justify-center font-black text-[18px] border border-slate-100 group-hover:bg-indigo-600 group-hover:text-white transition-all shadow-sm">
                                                {{ strtoupper(substr($branch->branch_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <h4 class="text-[14px] font-black text-slate-900 uppercase tracking-tight group-hover:text-indigo-600 transition-colors">{{ $branch->branch_name }}</h4>
                                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.15em] mt-0.5">Code: {{ $branch->branch_code }}</p>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end">
                                            <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest mb-1">Network Distance</span>
                                            <div class="flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-100 rounded-xl shadow-inner">
                                                <span class="text-[14px] font-black tabular-nums text-indigo-600">{{ number_format($branch->distance_from_main, 2) }}</span>
                                                <span class="text-[11px] font-black text-indigo-300 uppercase">KM</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="pt-4 border-t border-slate-50 flex items-start gap-2">
                                        <svg class="w-3.5 h-3.5 text-slate-300 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                        <p class="text-[11px] text-slate-500 font-medium leading-relaxed italic truncate" title="{{ $displayAddr }}">{{ $displayAddr }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-gradient-to-br from-slate-900 to-indigo-950 rounded-[2.5rem] shadow-2xl p-10 text-white relative overflow-hidden group">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/20 rounded-full blur-3xl transition-all duration-700 group-hover:bg-indigo-500/30"></div>
                        <div class="relative z-10">
                            <h3 class="text-[15px] font-black uppercase tracking-widest mb-8 flex items-center gap-3">
                                <div class="w-8 h-8 bg-white/10 rounded-xl flex items-center justify-center text-amber-400"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                                Logistics Rules
                            </h3>
                            <div class="space-y-6">
                                <!-- Base Fee & Rate per KM -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[9px] font-black text-indigo-300 uppercase tracking-widest mb-2">Base Fee</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-slate-400 font-bold text-sm">₱</span>
                                            </div>
                                            <input wire:model.live="baseFee" type="number" class="w-full bg-white/5 border-white/10 rounded-xl h-11 pl-9 pr-3 text-[14px] font-black text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-black text-indigo-300 uppercase tracking-widest mb-2">Rate / KM</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-slate-400 font-bold text-sm">₱</span>
                                            </div>
                                            <input wire:model.live="globalRate" type="number" class="w-full bg-white/5 border-white/10 rounded-xl h-11 pl-9 pr-3 text-[14px] font-black text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all">
                                        </div>
                                    </div>
                                </div>

                                <!-- Min & Max Caps -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[9px] font-black text-amber-300 uppercase tracking-widest mb-2">Minimum Fee</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-amber-500/50 font-bold text-sm">₱</span>
                                            </div>
                                            <input wire:model.live="minFee" type="number" class="w-full bg-white/5 border-white/10 rounded-xl h-11 pl-9 pr-3 text-[14px] font-black text-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-black text-rose-300 uppercase tracking-widest mb-2">Maximum Fee</label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-rose-500/50 font-bold text-sm">₱</span>
                                            </div>
                                            <input wire:model.live="maxFee" type="number" class="w-full bg-white/5 border-white/10 rounded-xl h-11 pl-9 pr-3 text-[14px] font-black text-white focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all">
                                        </div>
                                    </div>
                                </div>

                                <!-- Free Delivery Threshold -->
                                <div class="pt-2 border-t border-white/10">
                                    <label class="block text-[10px] font-black text-emerald-300 uppercase tracking-widest mb-3 flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Free Delivery Threshold
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                            <span class="text-emerald-500/50 font-bold text-[15px]">₱</span>
                                        </div>
                                        <input wire:model.live="freeThreshold" type="number" class="w-full bg-white/5 border-white/10 rounded-xl h-12 pl-10 pr-4 text-[15px] font-black text-emerald-400 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all" placeholder="Order amount...">
                                    </div>
                                    <p class="text-[9px] text-slate-400 font-medium italic mt-2 leading-relaxed">If total order value exceeds this threshold, delivery fee is automatically set to ₱0. Set to 0 to disable.</p>
                                </div>

                                <div class="pt-4 border-t border-white/10">
                                    <button wire:click="updateLogistics" class="w-full h-12 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-black text-[12px] uppercase tracking-widest shadow-xl shadow-indigo-900/50 transition-all flex items-center justify-center gap-2 active:scale-[0.98]">
                                        Apply Rules
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-indigo-50/50 rounded-[2rem] border border-indigo-100 p-8">
                        <h4 class="text-[12px] font-black text-indigo-900 uppercase tracking-widest mb-4">Pro-Tip</h4>
                        <p class="text-[11px] text-indigo-700/70 font-medium leading-relaxed italic">Updating these distances will only affect **new** incoming orders. Existing active transfers will retain their original billing unless manually adjusted during fulfillment.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Analytics -->
        <div x-show="panel === 'analytics'" 
             x-data="orderInboxAnalytics(@js($analytics))"
             x-effect="updateAnalytics(@js($analytics))"
             class="space-y-8 animate-fadeIn px-1">
             {{-- 30-Day Activity Trend Chart --}}
             <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-6 sm:p-8">
                 <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
                     <div>
                         <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-tight">Fulfillment Activity Trend</h3>
                         <p class="text-[11px] text-slate-400 font-medium">Daily orders counts & total dispatched value over chosen period</p>
                     </div>
                 </div>
                 <div wire:ignore class="w-full min-h-[310px] relative">
                     <div x-ref="activityTrendChart" class="w-full"></div>
                 </div>
             </div>

             {{-- Split Cards Grid: Top Ingredients vs Branch Fulfillment Distribution --}}
             <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                 {{-- Top Requested Ingredients --}}
                 <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8">
                     <div class="mb-6">
                         <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-tight">Top Demanded Ingredients</h3>
                         <p class="text-[11px] text-slate-400 font-medium mt-0.5">Most requested items by branch networks in filter period</p>
                     </div>
                     <div class="space-y-5">
                         @if(isset($analytics['topIngredients']) && count($analytics['topIngredients']) > 0)
                             @foreach($analytics['topIngredients'] as $item)
                                 @if($item->ingredient)
                                     <div class="space-y-1.5">
                                         <div class="flex justify-between text-[11px] font-black uppercase tracking-tight">
                                             <span class="text-slate-600">{{ $item->ingredient->name }}</span>
                                             <span class="text-indigo-600">{{ number_format($item->total_requested, 2) }} {{ $item->ingredient->unit }}</span>
                                         </div>
                                         <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                             @php 
                                                 $max = $analytics['topIngredients']->first()?->total_requested ?? 1; 
                                                 $pct = ($item->total_requested / max(1, $max)) * 100; 
                                             @endphp
                                             <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $pct }}%"></div>
                                         </div>
                                     </div>
                                 @endif
                             @endforeach
                         @else
                             <div class="py-12 flex flex-col items-center justify-center text-center border-2 border-dashed border-slate-100 rounded-2xl bg-slate-50/50">
                                 <p class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">No demand statistics recorded</p>
                             </div>
                         @endif
                     </div>
                 </div>

                 {{-- Branch Volume and spent Value --}}
                 <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8">
                     <div class="mb-6">
                         <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-tight">Branch Volume & Value Contribution</h3>
                         <p class="text-[11px] text-slate-400 font-medium mt-0.5">Summary of orders fulfilled and cumulative value per branch</p>
                     </div>
                     <div class="space-y-3">
                         @if(isset($analytics['branchVolume']) && count($analytics['branchVolume']) > 0)
                             @foreach($analytics['branchVolume'] as $idx => $branch)
                                 @if($branch->requestingBranch)
                                     <div class="flex items-center p-3 bg-slate-50/70 rounded-2xl border border-slate-100 hover:border-indigo-200 hover:bg-white hover:shadow-sm transition-all group">
                                         <div class="w-8 h-8 bg-white text-indigo-600 rounded-xl shadow-sm text-[11px] font-black flex items-center justify-center mr-3 border border-slate-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors">#{{ $idx + 1 }}</div>
                                         <div class="flex-1 min-w-0">
                                             <span class="text-[12px] font-black text-slate-900 uppercase tracking-tight block truncate">{{ $branch->requestingBranch->branch_name }}</span>
                                             <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">{{ $branch->total }} Orders Fulfilled</p>
                                         </div>
                                         <div class="text-right pl-2">
                                             <span class="text-[13px] font-black text-indigo-600">₱{{ number_format($branch->total_spent, 2) }}</span>
                                         </div>
                                     </div>
                                 @endif
                             @endforeach
                         @else
                             <div class="py-12 flex flex-col items-center justify-center text-center border-2 border-dashed border-slate-100 rounded-2xl bg-slate-50/50">
                                 <p class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">No branch distributions recorded</p>
                             </div>
                         @endif
                     </div>
                 </div>
             </div>
        </div>
    </div>

    <!-- Fulfillment Review -->
    <x-side-panel name="fulfillment-review" width="max-w-md">
        @if($selectedOrder)
            <div class="flex flex-col h-full bg-white">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-lg shadow-indigo-100"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg></div>
                        <div><h3 class="text-[15px] font-black text-slate-900 tracking-tight leading-none">Order Fulfillment</h3><span class="text-[11px] text-indigo-600 font-bold uppercase tracking-wider mt-1 block">{{ $selectedOrder->reference_no }}</span></div>
                    </div>
                    <button @click="$dispatch('close-modal', 'fulfillment-review')" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-600 hover:shadow-sm transition-all border border-transparent hover:border-slate-200"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <div class="p-6 bg-gradient-to-b from-slate-50/80 to-white border-b border-slate-50">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center text-indigo-600 text-lg font-black italic">{{ strtoupper(substr($selectedOrder->requestingBranch->branch_name, 0, 1)) }}</div>
                            <div class="flex-1"><h4 class="text-[16px] font-black text-slate-900 leading-tight uppercase tracking-tight">{{ $selectedOrder->requestingBranch->branch_name }}</h4><div class="flex flex-col gap-1 mt-2"><div class="flex items-center gap-2 text-[12px] text-slate-500 font-medium"><svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg><span>Requested by <span class="font-bold text-slate-700">{{ $selectedOrder->requester->name }}</span></span></div><div class="flex items-center gap-2">@php $p = $selectedOrder->priorityConfig()[$selectedOrder->priority] @endphp <span class="px-2 py-0.5 bg-{{ $p['color'] }}-50 text-{{ $p['color'] }}-600 text-[9px] font-black uppercase tracking-widest rounded border border-{{ $p['color'] }}-100">{{ $p['label'] }} Priority</span></div></div></div>
                            <div class="text-right"><span class="block text-[11px] font-black text-slate-400 uppercase tracking-widest leading-none">Date</span><span class="block text-[13px] font-bold text-slate-900 mt-1 tabular-nums">{{ $selectedOrder->created_at->format('M d, Y') }}</span></div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6"><h5 class="text-[11px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>Fulfillment Details</h5></div>
                        <div class="space-y-8 relative">
                            <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-slate-50 rounded-full"></div>
                            @foreach($selectedOrder->items as $item)
                                <div class="relative pl-7 group">
                                    <div class="absolute left-0 top-[6px] w-[16px] h-[16px] rounded-full border-4 border-white bg-slate-200 {{ $selectedOrder->isPending() ? 'group-hover:bg-indigo-400' : 'bg-indigo-400' }} transition-colors z-10"></div>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest leading-tight group-hover:text-slate-600 transition-colors">{{ $item->ingredient->name }}</label>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-black text-slate-400 uppercase tabular-nums">REQ: {{ number_format($item->requested_quantity, 2) }} {{ $item->unit }}</span>
                                            </div>
                                        </div>
                                        @if($selectedOrder->isPending())
                                            <div class="grid grid-cols-2 gap-3 bg-slate-50/50 p-3 rounded-2xl border border-slate-100/50">
                                                <div>
                                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Approved Amount</span>
                                                    <x-text-input wire:model.live="editItemQuantities.{{ $item->id }}" type="number" step="0.01" class="w-full !py-1 !px-2 !text-xs font-black tabular-nums" />
                                                </div>
                                                <div class="flex flex-col justify-center text-right">
                                                    <span class="text-[9px] font-black text-indigo-400 uppercase tracking-widest block mb-0.5">Price/{{ $item->unit }}</span>
                                                    <span class="text-[13px] font-black text-slate-900 tabular-nums">₱{{ number_format($item->unit_price, 2) }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <div class="bg-indigo-50/30 p-3 rounded-2xl border border-indigo-100/50 flex items-center justify-between">
                                                <span class="text-[11px] font-black text-indigo-600 uppercase">Final Approved</span>
                                                <div class="text-right">
                                                    <span class="block text-[13px] font-black text-slate-900 tabular-nums">{{ number_format($item->approved_quantity, 2) }} {{ $item->unit }}</span>
                                                    <span class="block text-[10px] font-bold text-slate-400 tabular-nums">₱{{ number_format($item->subtotal, 2) }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Order Summary Totals --}}
                        <div class="mt-8 p-6 bg-slate-900 rounded-[2rem] shadow-xl text-white relative overflow-hidden">
                            <div class="relative z-10 space-y-4">
                                <div class="flex items-center justify-between opacity-50 text-[10px] font-black uppercase tracking-widest">
                                    <span>Items Subtotal</span>
                                    @php 
                                        $subtotal = 0;
                                        foreach($selectedOrder->items as $it) {
                                            $qty = $editItemQuantities[$it->id] ?? ($it->approved_quantity ?? $it->requested_quantity);
                                            $subtotal += $qty * $it->unit_price;
                                        }
                                    @endphp
                                    <span class="tabular-nums">₱{{ number_format($subtotal, 2) }}</span>
                                </div>
                                
                                @if($selectedOrder->isPending())
                                    <div class="space-y-2 p-4 bg-white/5 rounded-2xl border border-white/10">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[10px] font-black text-indigo-400 uppercase tracking-widest">Delivery Fee</label>
                                            <span class="text-[9px] font-bold text-slate-500 uppercase italic">Suggested: ₱{{ number_format($suggestedFee, 2) }}</span>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">₱</span>
                                            <input wire:model.live="deliveryFee" type="number" step="0.01" class="w-full h-10 bg-white/10 border-white/10 rounded-xl pl-7 pr-3 text-[13px] font-black text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between opacity-50 text-[10px] font-black uppercase tracking-widest">
                                        <span>Delivery Fee</span>
                                        <span class="tabular-nums">₱{{ number_format($selectedOrder->delivery_fee, 2) }}</span>
                                    </div>
                                @endif

                                <div class="pt-3 border-t border-white/10 flex items-center justify-between text-[18px] font-black tracking-tight">
                                    <span>Grand Total</span>
                                    <span class="text-amber-400 tabular-nums">₱{{ number_format($subtotal + (float)$deliveryFee, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="p-5 border-t border-slate-100 bg-white">
                    @if($selectedOrder->isPending())
                        <div class="space-y-4">
                            <x-textarea wire:model.live="adminRemarks" rows="2" class="w-full text-xs font-medium resize-none" placeholder="Add fulfillment remarks..."></x-textarea>
                            <div class="flex gap-3">
                                <x-secondary-button @click="$dispatch('open-modal', { name: 'confirm-reject-order' })" class="flex-1 justify-center h-11 text-red-600 border-red-100">Reject</x-secondary-button>
                                <x-primary-button wire:click="approveOrder({{ $selectedOrder->id }})" class="flex-[2] justify-center h-11">Approve Request</x-primary-button>
                            </div>
                        </div>
                    @elseif($selectedOrder->isApproved())
                        <x-primary-button 
                            wire:click="markPreparing({{ $selectedOrder->id }})" 
                            class="w-full h-12 justify-center"
                        >
                            Mark as Preparing
                        </x-primary-button>
                    @elseif($selectedOrder->status === 'preparing')
                        <x-primary-button wire:click="dispatchOrder" class="w-full h-12 justify-center">Ship Transfer</x-primary-button>
                    @endif
                </div>
            </div>
        @endif
    </x-side-panel>

    <x-modal name="confirm-reject-order" maxWidth="sm">
        <div class="p-6 text-center">
            <div class="w-16 h-16 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
            <h3 class="text-lg font-black text-red-600 uppercase">Reject Request?</h3>
            <div class="mt-6"><x-textarea wire:model.live="rejectionReason" rows="3" class="w-full text-xs resize-none" placeholder="Reason..."></x-textarea></div>
            <div class="flex items-center gap-3 mt-8">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-reject-order')" class="flex-1 justify-center h-11">Cancel</x-secondary-button>
                <x-primary-button 
                    @click="$dispatch('close-modal', 'confirm-reject-order'); $wire.rejectOrder()" 
                    class="flex-1 justify-center h-11 !bg-red-600 shadow-red-100"
                >
                    Reject Now
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <style> .custom-scrollbar::-webkit-scrollbar { width: 5px; } .custom-scrollbar::-webkit-scrollbar-track { background: transparent; } .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; } </style>

    <script>
        window.addEventListener('play-chime', () => {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                
                // Note 1
                const osc1 = audioCtx.createOscillator();
                const gain1 = audioCtx.createGain();
                osc1.connect(gain1);
                gain1.connect(audioCtx.destination);
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
                gain1.gain.setValueAtTime(0.1, audioCtx.currentTime);
                gain1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.15);
                osc1.start(audioCtx.currentTime);
                osc1.stop(audioCtx.currentTime + 0.15);
                
                // Note 2 (Delayed higher pitch)
                setTimeout(() => {
                    const osc2 = audioCtx.createOscillator();
                    const gain2 = audioCtx.createGain();
                    osc2.connect(gain2);
                    gain2.connect(audioCtx.destination);
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(880.00, audioCtx.currentTime); // A5
                    gain2.gain.setValueAtTime(0.1, audioCtx.currentTime);
                    gain2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.25);
                    osc2.start(audioCtx.currentTime);
                    osc2.stop(audioCtx.currentTime + 0.25);
                }, 120);
            } catch (e) {
                console.error('Audio chime error:', e);
            }
        });

        // Inbox Analytics Dashboard logic
        document.addEventListener('alpine:init', () => {
            Alpine.data('orderInboxAnalytics', (initialData) => ({
                analyticsData: initialData,
                trendChart: null,

                init() {
                    // Slight delay to ensure DOM is ready
                    setTimeout(() => {
                        if (this.panel === 'analytics') {
                            this.drawCharts(this.analyticsData);
                        }
                    }, 50);

                    // Re-draw when tab is shown
                    this.$watch('panel', (val) => {
                        if (val !== 'analytics') {
                            this.destroyCharts();
                        } else {
                            setTimeout(() => this.drawCharts(this.analyticsData), 50);
                        }
                    });
                },

                updateAnalytics(data) {
                    if (!data || !data.trend) return;
                    
                    // Prevent redundant redraws if data hasn't changed
                    const newDataStr = JSON.stringify(data.trend);
                    const oldDataStr = this.analyticsData ? JSON.stringify(this.analyticsData.trend) : null;
                    if (newDataStr === oldDataStr) return;

                    this.analyticsData = data;
                    if (this.panel === 'analytics') {
                        this.drawCharts(data);
                    }
                },

                destroyCharts() {
                    if (this.trendChart) {
                        try { this.trendChart.destroy(); } catch (e) {}
                        this.trendChart = null;
                    }
                },

                drawCharts(data) {
                    if (typeof window.ApexCharts === 'undefined' || !data || !data.trend) return;

                    // In-place update to prevent flicker
                    if (this.trendChart) {
                        try {
                            this.trendChart.updateOptions({
                                xaxis: { categories: data.trend.dates || [] }
                            }, false, false);
                            this.trendChart.updateSeries([
                                { name: 'Fulfillment Value', data: data.trend.totals || [] },
                                { name: 'Orders Dispatched', data: data.trend.counts || [] }
                            ]);
                            return;
                        } catch (e) {
                            console.warn("Falling back to full chart redraw", e);
                            this.destroyCharts();
                        }
                    }

                    this.destroyCharts();

                    const trendEl = this.$refs.activityTrendChart;
                    if (trendEl) {
                        const trendOptions = {
                            series: [
                                {
                                    name: 'Fulfillment Value',
                                    data: data.trend.totals || []
                                },
                                {
                                    name: 'Orders Dispatched',
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
                                    animateGradually: { enabled: true, delay: 150 },
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
                                        text: 'Dispatched Value',
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
                                        text: 'Orders Fulfilled',
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
                                padding: { top: 0, right: 15, bottom: 0, left: 15 }
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
                                    { formatter: (val) => "₱ " + val.toLocaleString(undefined, { minimumFractionDigits: 2 }) },
                                    { formatter: (val) => val + " orders" }
                                ]
                            }
                        };
                        this.trendChart = new ApexCharts(trendEl, trendOptions);
                        this.trendChart.render();
                    }
                }
            }));
        });
    </script>
</div>
