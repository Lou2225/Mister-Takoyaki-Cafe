<div class="px-2 py-2 space-y-6" x-data="slidingTabs({ panel: @entangle('panel').live }, ['panel'])">
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

    <!-- KPI Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <x-metric-card title="New Requests" value="{{ number_format($kpis['pending']) }}" color="amber">
            <x-slot name="icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg></x-slot>
        </x-metric-card>
        <x-metric-card title="Active Transfers" value="{{ number_format($kpis['active']) }}" color="indigo">
            <x-slot name="icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></x-slot>
        </x-metric-card>
        <x-metric-card title="Delivered (Mo)" value="{{ number_format($kpis['delivered_month']) }}" color="emerald">
            <x-slot name="icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot>
        </x-metric-card>
        <x-metric-card title="Rejected (Mo)" value="{{ number_format($kpis['rejected_month']) }}" color="rose">
            <x-slot name="icon"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></x-slot>
        </x-metric-card>
    </div>

    <!-- Toolbar -->
    <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-8 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm" x-show="panel !== 'logistics'">
        <x-search-bar wireModel="search" placeholder="Find records..." width="w-full lg:w-72" />
        <x-dropdown align="right" width="48" x-show="panel !== 'analytics'">
            <x-slot name="trigger">
                <x-secondary-button class="gap-1.5 h-9 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span class="text-[12px]">Filter Options</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </x-secondary-button>
            </x-slot>
            <x-slot name="content">
                <div class="px-4 py-2 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Branch Filter</div>
                <x-dropdown-link href="#" wire:click.prevent="$set('branchFilter', '')">All Branches</x-dropdown-link>
                @foreach($branches as $branch) <x-dropdown-link href="#" wire:click.prevent="$set('branchFilter', '{{ $branch->id }}')">{{ $branch->branch_name }}</x-dropdown-link> @endforeach
            </x-slot>
        </x-dropdown>
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
                            <button wire:click="viewOrder({{ $order->id }})" class="inline-flex items-center px-2 py-1 border border-slate-200 text-[12px] font-semibold rounded-lg text-slate-500 bg-white hover:text-gray-700 hover:border-slate-300 transition-colors focus:outline-none shadow-sm">
                                View Details
                            </button>
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
                            <div class="flex items-center gap-2"><span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }} {{ $order->status === 'in_transit' ? 'animate-pulse' : '' }}"></span><span class="text-[12px] font-bold text-{{ $s['color'] }}-600">{{ $s['label'] }}</span></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col"><span class="text-[11px] font-black text-slate-700 uppercase">By {{ $order->approver->name ?? 'Admin' }}</span><span class="text-[10px] text-slate-400 font-bold tabular-nums">{{ $order->approved_at?->format('M d, H:i') ?? '—' }}</span></div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="viewOrder({{ $order->id }})" class="inline-flex items-center px-2 py-1 border border-slate-200 text-[12px] font-semibold rounded-lg text-slate-500 bg-white hover:text-gray-700 hover:border-slate-300 transition-colors focus:outline-none shadow-sm">
                                View Details
                            </button>
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
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black bg-{{ $s['color'] }}-50 text-{{ $s['color'] }}-600 uppercase tracking-widest border border-{{ $s['color'] }}-100">{{ $s['label'] }}</span>
                        </td>
                        <td class="px-6 py-4 text-[11px] text-slate-500 tabular-nums font-bold">{{ $order->delivered_at?->format('M d, Y') ?? $order->updated_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="viewOrder({{ $order->id }})" class="inline-flex items-center px-2 py-1 border border-slate-200 text-[12px] font-semibold rounded-lg text-slate-500 bg-white hover:text-gray-700 hover:border-slate-300 transition-colors focus:outline-none shadow-sm">
                                View Details
                            </button>
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
                                        $displayAddr = ($data['STREET'] ?? '') . ', ' . ($data['BARANGAY'] ?? '') . ', ' . ($data['CITY'] ?? '') . ', ' . ($data['PROVINCE'] ?? '');
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
                            <div class="space-y-8">
                                <div>
                                    <label class="block text-[11px] font-black text-indigo-300 uppercase tracking-[0.2em] mb-3">Global Logistics Rate</label>
                                    <div class="relative">
                                        <span class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 font-black text-lg">₱</span>
                                        <input wire:model.live="globalRate" type="number" class="w-full bg-white/5 border-white/10 rounded-2xl h-14 pl-10 pr-6 text-[18px] font-black text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all placeholder-white/10">
                                    </div>
                                    <p class="text-[10px] text-slate-500 font-medium italic mt-3 leading-relaxed">System applies this multiplier (₱/km) to calculate suggested delivery fees for all incoming branch requests.</p>
                                </div>
                                <div class="pt-4">
                                    <button wire:click="updateLogistics" class="w-full h-14 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-black text-[13px] uppercase tracking-[0.25em] shadow-xl shadow-indigo-900/50 transition-all flex items-center justify-center gap-3 active:scale-[0.98]">
                                        Apply Logistics
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
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
        <div x-show="panel === 'analytics'" class="space-y-8 animate-fadeIn px-1">
            @if($panel === 'analytics')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8">
                        <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-tight mb-6">Top Requested <span class="text-indigo-500">(Month)</span></h3>
                        <div class="space-y-5">
                            @foreach($analytics['topIngredients'] as $item)
                                <div class="space-y-1.5">
                                    <div class="flex justify-between text-[11px] font-black uppercase tracking-tight"><span class="text-slate-600">{{ $item->ingredient->name }}</span><span class="text-indigo-600">{{ number_format($item->total_requested, 2) }} {{ $item->ingredient->unit }}</span></div>
                                    <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                        @php $max = $analytics['topIngredients']->first()->total_requested; $pct = ($item->total_requested / max(1, $max)) * 100; @endphp
                                        <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 p-8">
                        <h3 class="text-[14px] font-black text-slate-900 uppercase tracking-tight mb-6">Fulfillment Volume</h3>
                        <div class="space-y-3">
                            @foreach($analytics['branchVolume'] as $idx => $branch)
                                <div class="flex items-center p-3 bg-slate-50 rounded-2xl border border-slate-100 hover:border-indigo-200 transition-all group">
                                    <div class="w-8 h-8 bg-white text-indigo-600 rounded-xl shadow-sm text-[11px] font-black flex items-center justify-center mr-3 border border-slate-100 group-hover:bg-indigo-600 group-hover:text-white transition-colors">#{{ $idx + 1 }}</div>
                                    <div class="flex-1"><span class="text-[12px] font-black text-slate-900 uppercase tracking-tight">{{ $branch->requestingBranch->branch_name }}</span><p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">{{ $branch->total }} Orders</p></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
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
                            <x-textarea wire:model.live="adminRemarks" rows="2" class="w-full text-xs font-medium" placeholder="Add fulfillment remarks..."></x-textarea>
                            <div class="flex gap-3"><x-secondary-button wire:click="confirmReject({{ $selectedOrder->id }})" class="flex-1 justify-center h-11 text-red-600 border-red-100">Reject</x-secondary-button><x-primary-button wire:click="confirmApprove({{ $selectedOrder->id }})" class="flex-[2] justify-center h-11">Approve Request</x-primary-button></div>
                        </div>
                    @elseif($selectedOrder->isApproved())
                        <x-primary-button 
                            wire:click="markPreparing({{ $selectedOrder->id }})" 
                            wire:loading.attr="disabled"
                            wire:target="markPreparing({{ $selectedOrder->id }})"
                            class="w-full h-12 justify-center"
                        >
                            <span wire:loading.remove wire:target="markPreparing({{ $selectedOrder->id }})">Mark as Preparing</span>
                            <span wire:loading wire:target="markPreparing({{ $selectedOrder->id }})" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Updating...
                            </span>
                        </x-primary-button>
                    @elseif($selectedOrder->status === 'preparing')
                        <x-primary-button wire:click="confirmDispatch({{ $selectedOrder->id }})" class="w-full h-12 justify-center">Ship Transfer</x-primary-button>
                    @endif
                </div>
            </div>
        @endif
    </x-side-panel>

    <x-modal name="confirm-approve-order" maxWidth="sm">
        <div class="p-6 text-center" x-data="{ processing: false }">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h3 class="text-lg font-black text-slate-900 uppercase">Approve Request?</h3>
            <p class="mt-2 text-xs text-slate-500 font-medium leading-relaxed">This will authorize the transfer for <span class="font-bold text-slate-900">{{ $approveTargetRef }}</span>. Stock will be reserved for fulfillment.</p>
            <div class="flex items-center gap-3 mt-8">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-approve-order')" class="flex-1 justify-center h-11">Cancel</x-secondary-button>
                <x-primary-button 
                    @click="processing = true; $wire.approveOrder({{ $approveTargetId ?? 0 }})" 
                    x-bind:disabled="processing"
                    class="flex-1 justify-center h-11 shadow-indigo-100"
                >
                    <div x-show="!processing">Approve Now</div>
                    <div x-show="processing" class="flex items-center gap-2" x-cloak>
                        <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Approving...</span>
                    </div>
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirm-reject-order" maxWidth="sm">
        <div class="p-6 text-center" x-data="{ processing: false }">
            <div class="w-16 h-16 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
            <h3 class="text-lg font-black text-red-600 uppercase">Reject Request?</h3>
            <div class="mt-6"><x-textarea wire:model.live="rejectionReason" rows="3" class="w-full text-xs" placeholder="Reason..."></x-textarea></div>
            <div class="flex items-center gap-3 mt-8">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-reject-order')" class="flex-1 justify-center h-11">Cancel</x-secondary-button>
                <x-primary-button 
                    @click="processing = true; $wire.rejectOrder()" 
                    x-bind:disabled="processing"
                    class="flex-1 justify-center h-11 !bg-red-600 shadow-red-100"
                >
                    <div x-show="!processing">Reject Now</div>
                    <div x-show="processing" class="flex items-center gap-2" x-cloak>
                        <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Rejecting...</span>
                    </div>
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <x-modal name="confirm-dispatch-order" maxWidth="sm">
        <div class="p-6 text-center" x-data="{ processing: false }">
            <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-4"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>
            <h3 class="text-lg font-black text-slate-900 uppercase">Ship Transfer?</h3>
            <p class="mt-2 text-xs text-slate-500 font-medium leading-relaxed">This will officially dispatch <span class="font-bold text-slate-900">{{ $dispatchTargetRef }}</span> and update branch inventory levels. This action is irreversible.</p>
            <div class="flex items-center gap-3 mt-8">
                <x-secondary-button @click="$dispatch('close-modal', 'confirm-dispatch-order')" class="flex-1 justify-center h-11">Back</x-secondary-button>
                <x-primary-button 
                    @click="processing = true; $wire.dispatchOrder()" 
                    x-bind:disabled="processing"
                    class="flex-1 justify-center h-11 shadow-indigo-100"
                >
                    <div x-show="!processing">Confirm & Ship</div>
                    <div x-show="processing" class="flex items-center gap-2" x-cloak>
                        <svg class="animate-spin h-3 w-3 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Shipping...</span>
                    </div>
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    <style> .custom-scrollbar::-webkit-scrollbar { width: 5px; } .custom-scrollbar::-webkit-scrollbar-track { background: transparent; } .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; } </style>
    <div x-on:close-dispatch-modals.window="$dispatch('close-modal', 'fulfillment-review'); $dispatch('close-modal', 'confirm-dispatch-order');"></div>
</div>
