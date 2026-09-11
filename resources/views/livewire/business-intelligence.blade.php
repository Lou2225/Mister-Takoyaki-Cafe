@php
    $user = auth()->user();
    $roleTheme = $user->getRoleTheme();
    $primaryColor = $roleTheme['primary'] ?? 'indigo';
    $primaryText = $roleTheme['text'] ?? 'text-indigo-600';
@endphp
<div
    x-data="slidingTabs($wire.entangle('activeTab').live, 'activeTab')"
    wire:ignore.self
    class="relative overflow-hidden">

    <div class="px-1">
        {{-- Page title + actions --}}
        <div class="mb-4 flex items-center justify-between gap-3">
            <div class="min-w-0 shrink">
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Business Reports</h2>
                <p class="hidden sm:block text-[12px] text-gray-500 font-medium whitespace-nowrap overflow-hidden text-ellipsis">Four-layer business intelligence: what happened, why, what is next, and what to do</p>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-1.5 sm:gap-2 shrink-0">
                <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" refreshAction="applyQuickDateFilter" />
                <x-report-dropdown module="BI Report" />

                {{-- Branch Scope (Super Admin) --}}
                @if(auth()->user()->role_id === 1)
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center gap-0 sm:gap-1.5 lg:gap-2 px-2.5 sm:px-3 lg:px-4 text-[11px] lg:text-[12px] font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none shadow-sm transition-colors h-10 shrink-0">
                                <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <span class="hidden sm:inline whitespace-nowrap max-w-[90px] lg:max-w-none truncate">{{ $selectedBranchId === 'all' ? 'All Branches' : ($branches->firstWhere('id', $selectedBranchId)?->branch_name ?? 'Select Branch') }}</span>
                                <svg class="hidden sm:block w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', 'all')">
                                All Branches
                            </x-dropdown-link>
                            
                            @foreach($branches as $branch)
                                <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', {{ $branch->id }})">
                                    {{ $branch->branch_name }}
                                </x-dropdown-link>
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                @endif
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="mb-6 -mx-1 px-1 overflow-x-auto no-scrollbar scroll-smooth">
            <x-sliding-tabs model="activeTab" class="w-max" ref="tabList" wire:ignore>
                @foreach([
                    'descriptive' => ['Sales Performance', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                    'diagnostic' => ['Products & Operations', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    'predictive' => ['Forecasting', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                    'prescriptive' => ['Restock Actions', 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 12c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                ] as $tab => $info)
                    <x-sliding-tab model="activeTab" value="{{ $tab }}">
                        <x-slot name="icon">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info[1] }}"/></svg>
                        </x-slot>
                        {{ $info[0] }}
                    </x-sliding-tab>
                @endforeach
            </x-sliding-tabs>
        </div>
    </div>

    <div x-cloak x-show="activeTab === 'descriptive'" class="space-y-6">

            {{-- Primary Revenue Metrics --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 px-1">
                    {{-- Gross Revenue --}}
                <div wire:click="openBreakdown('Gross Revenue')" class="p-3 sm:p-4 md:p-6 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                    <div>
                        <div class="flex items-center justify-between mb-1 sm:mb-2">
    <span class="text-[10px] sm:text-[11px] md:text-[12px] font-bold text-slate-400 uppercase tracking-wider">Gross Revenue</span>
    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 group-hover:scale-110 transition-transform shrink-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
</div>
<h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">&#8369;{{ number_format($performance['gross_sales'], 2) }}</h3>
<p class="text-[9px] sm:text-[10px] md:text-[11px] text-slate-500 font-semibold mt-1 sm:mt-1.5 leading-none">Consolidated overall gross inflow</p>
                    </div>
                </div>

                {{-- Net Sales --}}
                <div wire:click="openBreakdown('Net Sales')" class="p-3 sm:p-4 md:p-6 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                    <div>
                        <div class="flex items-center justify-between mb-1 sm:mb-2">
    <span class="text-[10px] sm:text-[11px] md:text-[12px] font-bold text-slate-400 uppercase tracking-wider">Net Sales</span>
    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-100 group-hover:scale-110 transition-transform shrink-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
</div>
<h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">&#8369;{{ number_format($performance['net_sales'], 2) }}</h3>
<p class="text-[9px] sm:text-[10px] md:text-[11px] text-slate-500 font-semibold mt-1 sm:mt-1.5 leading-none">Excl. &#8369;{{ number_format($performance['delivery_fees'], 2) }} delivery</p>
                    </div>
                </div>

                {{-- Gross Profit --}}
                <div wire:click="openBreakdown('Gross Profit')" class="p-3 sm:p-4 md:p-6 bg-gradient-to-br from-violet-500/10 via-violet-500/5 to-white border border-violet-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group col-span-2 lg:col-span-1">
                    <div>
                        <div class="flex items-center justify-between mb-1 sm:mb-2">
    <span class="text-[10px] sm:text-[11px] md:text-[12px] font-bold text-slate-400 uppercase tracking-wider">Gross Profit</span>
    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-violet-50 flex items-center justify-center text-violet-600 border border-violet-100 group-hover:scale-110 transition-transform shrink-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
    </div>
</div>
<h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">&#8369;{{ number_format($performance['gross_profit'], 2) }}</h3>
<p class="text-[9px] sm:text-[10px] md:text-[11px] text-slate-500 font-semibold mt-1 sm:mt-1.5 leading-none">Earnings margin after raw ingredient costs</p>
                    </div>
                </div>
            </div>

            {{-- Operational Insights & Deductions --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:flex lg:flex-nowrap gap-3 sm:gap-4 px-1">
                {{-- Discounts --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-3 sm:gap-4 group hover:border-rose-200 transition-all lg:flex-1 lg:min-w-0">
    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
    <div class="min-w-0">
        <span class="block text-[8px] sm:text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Discounts</span>
        <span class="block text-[13px] sm:text-[15px] font-black text-rose-600 leading-none truncate">-&#8369;{{ number_format($performance['total_discounts'], 2) }}</span>
    </div>
</div>

                {{-- Delivery Fees --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-3 sm:gap-4 group hover:border-amber-200 transition-all lg:flex-1 lg:min-w-0">
    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
    </div>
    <div class="min-w-0">
        <span class="block text-[8px] sm:text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Delivery</span>
        <span class="block text-[13px] sm:text-[15px] font-black text-amber-600 leading-none truncate">&#8369;{{ number_format($performance['delivery_fees'], 2) }}</span>
    </div>
</div>

                {{-- Refunds --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-3 sm:gap-4 group hover:border-rose-200 transition-all lg:flex-1 lg:min-w-0">
    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3"/></svg>
    </div>
    <div class="min-w-0">
        <span class="block text-[8px] sm:text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Refunds</span>
        <span class="block text-[13px] sm:text-[15px] font-black text-rose-600 leading-none truncate">-&#8369;{{ number_format($performance['refunds'], 2) }}</span>
    </div>
</div>

                {{-- Waste / Spoilage --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-3 sm:gap-4 group hover:border-rose-200 transition-all lg:flex-1 lg:min-w-0">
    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </div>
    <div class="min-w-0">
        <span class="block text-[8px] sm:text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Spoilage</span>
        <span class="block text-[13px] sm:text-[15px] font-black text-rose-600 leading-none truncate">-&#8369;{{ number_format($performance['waste_cost'] ?? 0, 2) }}</span>
    </div>
</div>

                {{-- Average Ticket --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-3 sm:p-4 shadow-sm flex items-center gap-3 sm:gap-4 group hover:border-blue-200 transition-all col-span-2 sm:col-span-1 lg:flex-1 lg:min-w-0">
    <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
    </div>
    <div class="min-w-0">
        <span class="block text-[8px] sm:text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Avg Ticket</span>
        <span class="block text-[13px] sm:text-[15px] font-black text-gray-900 leading-none truncate">&#8369;{{ number_format($performance['avg_order_value'], 2) }}</span>
    </div>
</div>
        </div>

        {{-- Sales Ledger Table --}}
        <div class="mx-1">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-4 border-b border-gray-200 pb-4 gap-4">
                <div class="flex items-center gap-1.5">
                    <x-search-bar wireModel="search" placeholder="Search reference..." />
                </div>
            </div>

            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-4 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide uppercase">Reference</th>
                    <th class="py-3 px-4 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide uppercase">Branch</th>
                    <th class="py-3 px-4 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide uppercase">Customer</th>
                    <th class="py-3 px-4 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide uppercase text-right">Total</th>
                    <th class="py-3 px-4 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide uppercase text-center">Status</th>
                    <th class="py-3 px-4 text-[12px] font-medium text-gray-500 tracking-wide uppercase text-center">Date</th>
                </x-slot>
                @forelse($recentOrders as $order)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="py-3 px-4 border-r border-gray-100 font-bold text-[13px] text-gray-900 whitespace-nowrap">{{ $order->reference_no }}</td>
                    <td class="py-3 px-4 border-r border-gray-100 text-[12px] font-semibold text-gray-600 whitespace-nowrap">{{ $order->branch->branch_name }}</td>
                    <td class="py-3 px-4 border-r border-gray-100 text-[12px] font-bold text-gray-700 whitespace-nowrap">{{ $order->customer_name ?: 'Walk-in' }}</td>
                    <td class="py-3 px-4 border-r border-gray-100 text-right font-black text-[13px] text-gray-900 font-mono whitespace-nowrap">&#8369;{{ number_format($order->total_amount, 2) }}</td>
                    <td class="py-3 px-4 border-r border-gray-100 text-center whitespace-nowrap">
                        @php
                            $s = match($order->status) {
                                'Completed' => ['dot' => 'bg-emerald-500', 'color' => 'emerald', 'label' => 'Completed'],
                                'Refunded' => ['dot' => 'bg-rose-500', 'color' => 'rose', 'label' => 'Refunded'],
                                'Partially Refunded' => ['dot' => 'bg-amber-500', 'color' => 'amber', 'label' => 'Partially Refunded'],
                                default => ['dot' => 'bg-slate-400', 'color' => 'slate', 'label' => $order->status]
                            };
                        @endphp
                        <div class="flex items-center justify-center gap-2">
                            <span class="h-1.5 w-1.5 rounded-full {{ $s['dot'] }}"></span>
                            <span class="text-[11px] font-bold text-{{ $s['color'] }}-600">{{ $s['label'] }}</span>
                        </div>
                    </td>
                    <td class="py-3 px-4 text-center text-[11px] font-bold text-gray-400 tabular-nums uppercase whitespace-nowrap">{{ $order->created_at->format('M d, H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-0">
                        <x-empty-state title="No orders found" description="Try adjusting your filters or search term." />
                    </td>
                </tr>
                @endforelse
            </x-data-table>
                        <div class="mt-4">
                <x-pagination :paginator="$recentOrders" keyPrefix="bi-orders" />
            </div>
        </div>
    </div>

    <div x-cloak x-show="activeTab === 'predictive'" class="space-y-6">

        {{-- Reference Guide --}}
        <div x-data="{ guideOpen: false }" class="mx-0">
            <button @click="guideOpen = !guideOpen"
                class="w-full flex items-center justify-between px-5 py-3 bg-indigo-50 border border-indigo-100 rounded-2xl text-left hover:bg-indigo-100/60 transition-all group">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-[13px] font-black text-indigo-900 leading-none">Forecasting Reference</p>
                        <p class="text-[11px] text-indigo-600/70 font-medium mt-0.5">Confidence levels and model quality metrics</p>
                    </div>
                </div>
                <svg class="w-4 h-4 text-indigo-400 transition-transform duration-300" :class="guideOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>

            <div x-show="guideOpen" x-collapse class="mt-2">
                <div class="bg-white border border-indigo-100 rounded-2xl p-6 space-y-5">
                    {{-- Confidence explainer --}}
                    <div>
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Confidence Level Guide</p>
                        <div class="flex flex-wrap gap-3">
                            <div class="flex items-center gap-2 px-3 py-2 bg-emerald-50 rounded-lg border border-emerald-100">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <div>
                                    <p class="text-[11px] font-black text-emerald-700">High</p>
                                    <p class="text-[10px] text-emerald-600/70">60+ days of data, R2 >= 0.70</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-2 bg-amber-50 rounded-lg border border-amber-100">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                <div>
                                    <p class="text-[11px] font-black text-amber-700">Medium</p>
                                    <p class="text-[10px] text-amber-600/70">30+ days of data, R2 >= 0.40</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-2 bg-rose-50 rounded-lg border border-rose-100">
                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                <div>
                                    <p class="text-[11px] font-black text-rose-700">Low</p>
                                    <p class="text-[10px] text-rose-600/70">14+ days, pattern not yet clear</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-lg border border-slate-200">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                <div>
                                    <p class="text-[11px] font-black text-slate-600">Insufficient</p>
                                    <p class="text-[10px] text-slate-500/70">Less than 14 days of sales data</p>
                                </div>
                            </div>
                        </div>
                        <p class="mt-3 text-[10px] leading-relaxed text-slate-500">Confidence is assigned separately to the 7-day and 6-month forecasts. It combines the amount of usable history with how closely the weighted trend fits that history (R2). It does not mean the forecast is guaranteed.</p>
                    </div>

                    {{-- Acronym guide --}}
                    <div class="pt-4 border-t border-slate-100">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Acronym guide</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            <div class="rounded-lg bg-indigo-50 px-3 py-2"><span class="font-black text-indigo-800">MAE</span><span class="text-slate-600"> - Mean Absolute Error</span></div>
                            <div class="rounded-lg bg-indigo-50 px-3 py-2"><span class="font-black text-indigo-800">RMSE</span><span class="text-slate-600"> - Root Mean Squared Error</span></div>
                            <div class="rounded-lg bg-indigo-50 px-3 py-2"><span class="font-black text-indigo-800">MAPE</span><span class="text-slate-600"> - Mean Absolute Percentage Error</span></div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2"><span class="font-black text-slate-800">R2</span><span class="text-slate-600"> - R-squared model fit score</span></div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2"><span class="font-black text-slate-800">WLR</span><span class="text-slate-600"> - Weighted Linear Regression</span></div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2"><span class="font-black text-slate-800">IQR</span><span class="text-slate-600"> - Interquartile Range for outlier filtering</span></div>
                        </div>
                    </div>

                    {{-- Forecast method --}}
                    <div class="pt-4 border-t border-slate-100">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">How the forecast is calculated</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                                <p class="text-[11px] font-black text-indigo-800">7-Day Forecast</p>
                                <p class="mt-1 text-[10px] leading-relaxed text-indigo-900/70">Uses up to 60 completed daily periods. Recent days have more weight, unusual values are filtered with the IQR method, and day-of-week patterns are applied when at least 14 days are available.</p>
                            </div>
                            <div class="rounded-xl border border-violet-100 bg-violet-50/60 p-4">
                                <p class="text-[11px] font-black text-violet-800">6-Month Forecast</p>
                                <p class="mt-1 text-[10px] leading-relaxed text-violet-900/70">Uses up to 12 completed monthly periods. The trend is damped over time, so a short recent spike does not grow indefinitely across six months.</p>
                            </div>
                        </div>
                        <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Model flow</p>
                            <p class="mt-2 text-[10px] leading-relaxed text-slate-600">Historical sales -> remove extreme outliers -> fit a recent-weighted trend -> apply seasonality -> damp the distant trend -> prevent unrealistic near-zero results.</p>
                        </div>
                    </div>

                    {{-- Accuracy metrics --}}
                    <div class="pt-4 border-t border-slate-100">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Accuracy metrics explained</p>
                        <div class="space-y-2">
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[11px] font-black text-slate-800">MAE - Mean Absolute Error</p>
                                    <span class="text-[10px] font-black text-indigo-600">Pesos</span>
                                </div>
                                <p class="mt-1 text-[10px] font-mono text-indigo-700">MAE = average(|actual - predicted|)</p>
                                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">The average absolute difference between predicted and actual daily revenue. Example: MAE of P500 means the forecast is off by about P500 per day on average.</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[11px] font-black text-slate-800">RMSE - Root Mean Squared Error</p>
                                    <span class="text-[10px] font-black text-indigo-600">Pesos</span>
                                </div>
                                <p class="mt-1 text-[10px] font-mono text-indigo-700">RMSE = sqrt(average((actual - predicted)^2))</p>
                                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">Similar to MAE, but large misses count more heavily. A much higher RMSE than MAE usually means a few days had unusually large errors.</p>
                            </div>
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-[11px] font-black text-slate-800">MAPE - Mean Absolute Percentage Error</p>
                                    <span class="text-[10px] font-black text-indigo-600">Percent</span>
                                </div>
                                <p class="mt-1 text-[10px] font-mono text-indigo-700">MAPE = average(|actual - predicted| / actual) x 100</p>
                                <p class="mt-1 text-[10px] leading-relaxed text-slate-500">The average error relative to actual revenue. A MAPE of 12% means the forecast differs from actual sales by about 12% on average. Days with zero sales are excluded from this percentage.</p>
                            </div>
                        </div>
                        <p class="mt-3 text-[10px] leading-relaxed text-slate-500">These metrics are calculated by hiding the most recent 7 complete days, forecasting them from earlier data, and comparing the predictions with what actually happened. Lower MAE, RMSE, and MAPE are better.</p>
                    </div>

                    {{-- Where metrics apply --}}
                    <div class="pt-4 border-t border-slate-100">
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-3">Where each signal is used</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[10px] leading-relaxed text-slate-600">
                            <div class="rounded-lg bg-indigo-50 px-3 py-2"><span class="font-black text-indigo-800">Confidence:</span> shown on both forecast cards to indicate how much trust to place in the trend.</div>
                            <div class="rounded-lg bg-slate-50 px-3 py-2"><span class="font-black text-slate-800">MAE / RMSE / MAPE:</span> shown in Benchmarking to evaluate forecast quality against the baseline.</div>
                            <div class="rounded-lg bg-amber-50 px-3 py-2"><span class="font-black text-amber-800">RMSE range:</span> used around the 7-day chart as an approximate error range, not a guaranteed interval.</div>
                            <div class="rounded-lg bg-emerald-50 px-3 py-2"><span class="font-black text-emerald-800">Restock actions:</span> use the 7-day forecast to estimate 14-day ingredient demand, then adjust for buffer, waste, and current stock.</div>
                        </div>
                    </div>

                    {{-- R2 display if available --}}
                    @if(isset($forecasting['short_term']['r_squared']))
                    <div class="pt-4 border-t border-slate-100">
                        <div class="bg-indigo-50 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Model Quality (R2 Score)</p>
                                <p class="text-[18px] font-black text-indigo-900">{{ number_format($forecasting['short_term']['r_squared'] * 100, 1) }}%</p>
                            </div>
                            <p class="text-[10px] text-slate-600 leading-relaxed">R2 measures how well the regression model fits your historical sales data on a scale of 0-100%. Higher scores indicate more reliable forecasts. Typically, 70%+ = Strong, 40-70% = Moderate, Below 40% = Weak.</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

                @php
            $fc = $forecasting ?? ['short_term' => ['forecast' => []], 'long_term' => ['forecast' => []], 'restock_insights' => []];
            $restock = $fc['restock_insights'] ?? [];
            $accuracy = $forecasting['accuracy'] ?? [
                'mae' => 0,
                'rmse' => 0,
                'mape' => 0,
                'benchmark_mape' => 0,
                'model_improvement_pct' => 0,
            ];
        @endphp

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-gray-400">Benchmarking</p>
                    <h4 class="mt-1 text-[15px] font-bold text-gray-900 tracking-tight">Forecast accuracy vs. baseline</h4>
                </div>
                <div class="rounded-full border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-700">
                    {{ number_format($accuracy['model_improvement_pct'] ?? 0, 1) }}% better
                </div>
            </div>

            <div class="grid grid-cols-2 xl:grid-cols-4 gap-3">
                <div class="rounded-2xl border border-gray-100 bg-indigo-50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-500">MAE</p>
                    <p class="mt-2 text-[24px] font-black text-indigo-900">&#8369;{{ number_format($accuracy['mae'] ?? 0, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-slate-50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-500">RMSE</p>
                    <p class="mt-2 text-[24px] font-black text-slate-900">&#8369;{{ number_format($accuracy['rmse'] ?? 0, 2) }}</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-amber-50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-600">Model MAPE</p>
                    <p class="mt-2 text-[24px] font-black text-amber-900">{{ number_format($accuracy['mape'] ?? 0, 2) }}%</p>
                </div>
                <div class="rounded-2xl border border-gray-100 bg-emerald-50 p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-emerald-600">Improvement</p>
                    <p class="mt-2 text-[24px] font-black text-emerald-900">{{ number_format($accuracy['model_improvement_pct'] ?? 0, 1) }}%</p>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-4">
                <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-[0.22em] text-slate-500">
                    <span>Benchmark</span>
                    <span>Model</span>
                </div>
                <div class="mt-3 flex items-center gap-3">
                    <div class="flex-1">
                        <div class="h-2.5 rounded-full bg-slate-200 overflow-hidden">
                            <div class="h-full rounded-full bg-slate-400" style="width: {{ min(100, max(10, (float)($accuracy['benchmark_mape'] ?? 0) * 3.5)) }}%"></div>
                        </div>
                    </div>
                    <div class="w-20 text-right text-[12px] font-black text-slate-700">
                        {{ number_format($accuracy['benchmark_mape'] ?? 0, 1) }}%
                    </div>
                    <div class="flex-1">
                        <div class="h-2.5 rounded-full bg-indigo-100 overflow-hidden">
                            <div class="h-full rounded-full bg-indigo-500" style="width: {{ min(100, max(10, (float)($accuracy['mape'] ?? 0) * 3.5)) }}%"></div>
                        </div>
                    </div>
                    <div class="w-16 text-right text-[12px] font-black text-indigo-700">
                        {{ number_format($accuracy['mape'] ?? 0, 1) }}%
                    </div>
                </div>

                @php
                    $short = $fc['short_term']['forecast'] ?? [];
                    $categories = collect($short)->pluck('date')->all();
                    $predicted = collect($short)->pluck('predicted')->map(fn($v) => round($v, 2))->all();
                @endphp

                @if(count($predicted) > 0)
                    <script type="application/json" id="bi-forecast-short-data">
                        {!! json_encode(['categories' => $categories, 'predicted' => $predicted], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
                    </script>

                    <div x-data="forecastingChart(JSON.parse(document.getElementById('bi-forecast-short-data').textContent))"
                         wire:ignore
                         wire:key="bi-forecast-short-{{ $selectedBranchId }}-{{ $startDate }}-{{ $endDate }}-{{ str_replace(' ', '-', $activeFilter) }}"
                         x-init="init()"
                         class="w-full">
                        <div x-ref="forecastChart" class="w-full h-[320px]"></div>
                    </div>
                @else
                    <div class="p-6">
                        <x-empty-state title="No Forecast Data" description="Not enough historical data to compute a forecast for this selection." />
                    </div>
                @endif
            </div>

            <p class="mt-4 text-[11px] leading-relaxed text-gray-500">
                Benchmark uses the previous-period average as the simple baseline. The model is evaluated using MAE, RMSE, and MAPE, then compared against that baseline to show whether the forecasting logic adds practical value.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-stretch">
        <div x-data="{ flipped: false }" class="flex flex-col">
            {{-- Flip Toggle --}}
            <div class="flex items-center justify-between mb-3">
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">
                    <span x-show="!flipped">Showing 7-Day Forecast</span>
                    <span x-show="flipped" x-cloak>Showing 6-Month Forecast</span>
                </p>
                <button type="button" @click="flipped = !flipped"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-[11px] font-black uppercase tracking-widest text-white shadow-sm hover:shadow-md transition-all active:scale-95"
                    :class="flipped ? 'bg-violet-600 hover:bg-violet-700' : 'bg-indigo-600 hover:bg-indigo-700'">
                    <svg class="w-3.5 h-3.5 transition-transform duration-500" :class="flipped ? '-scale-x-100' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="flipped ? 'View 7-Day' : 'View 6-Month'"></span>
                </button>
            </div>

            {{-- Flip Card --}}
            <div style="perspective: 2000px;">
                <div class="relative w-full"
                     :style="'transform-style: preserve-3d; transition: transform 0.7s cubic-bezier(0.4,0,0.2,1); transform: rotateY(' + (flipped ? '180deg' : '0deg') + ');'">

                    {{-- FRONT FACE: Short-Term Forecast --}}
                    <div class="relative bg-white rounded-2xl border border-gray-100 shadow-sm p-6 overflow-hidden" style="backface-visibility:hidden;">
                        <div class="flex flex-col gap-3 mb-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-[15px] font-bold text-gray-900 tracking-tight">7-Day Forecast</h4>
                                    <p class="text-[11px] text-gray-400 font-medium">Short-term revenue projection for the next week.</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-200 text-[9px] font-black text-indigo-700 uppercase tracking-widest whitespace-nowrap">
                                    <span class="w-1 h-1 rounded-full bg-indigo-500"></span>
                                    WLR w/ Seasonality
                                </span>
                            </div>
                        </div>

                        {{-- Short-Term Metrics --}}
                        @php
                            $summary = $fc['short_term'] ?? ['trend'=>'N/A','growth_rate'=>0,'confidence'=>'Low','baseline_avg'=>0];
                            $predicted_sum = collect($fc['short_term']['forecast'] ?? [])->sum('predicted');
                            $baseline_week = ($summary['baseline_avg'] ?? 0) * 7;
                        @endphp
                        <div class="mb-4 grid grid-cols-2 gap-3">
                            <div class="bg-gray-50/50 rounded-xl p-3 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center border border-gray-100">
                                    <svg class="w-5 h-5 text-{{ $primaryColor }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[10px] text-gray-500 font-medium">Trend (Slope)</div>
                                    <div class="text-[13px] font-black text-gray-900 truncate">{{ $summary['trend'] ?? 'N/A' }}</div>
                                    <div class="text-[9px] text-gray-400">{{ $summary['confidence'] ?? 'Low' }} confidence</div>
                                </div>
                            </div>

                            <div class="bg-gray-50/50 rounded-xl p-3 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center border border-gray-100">
                                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[10px] text-gray-500 font-medium">7-Day Forecast</div>
                                    <div class="text-[13px] font-black text-gray-900 truncate">&#8369;{{ number_format($predicted_sum, 0) }}</div>
                                    <div class="text-[9px] text-gray-400">vs &#8369;{{ number_format($baseline_week, 0) }} baseline</div>
                                </div>
                            </div>
                        </div>

                        <div class="text-[10px] text-gray-500 space-y-1 bg-blue-50/50 border border-blue-100 rounded-lg p-3 mb-4">
                            <p><span class="font-bold text-gray-700">Trend:</span> Direction of daily sales change (60-day history analyzed)</p>
                            <p><span class="font-bold text-gray-700">Baseline:</span> Average of last 7 days x 7 (reference for comparison)</p>
                            <p><span class="font-bold text-gray-700">Method:</span> Weighted Linear Regression with exponential recent-data emphasis + day-of-week seasonal adjustment</p>
                        </div>

                          @php
                            $short = $fc['short_term']['forecast'] ?? [];
                            $shortCategories = collect($short)->pluck('date')->all();
                            $shortPredicted = collect($short)->pluck('predicted')->map(fn($v) => round($v, 2))->all();
                            $hasBandData = collect($short)->every(fn($p) => isset($p['lower'], $p['upper']));
                            $shortLower = $hasBandData ? collect($short)->pluck('lower')->map(fn($v) => round($v, 2))->all() : [];
                            $shortUpper = $hasBandData ? collect($short)->pluck('upper')->map(fn($v) => round($v, 2))->all() : [];
                        @endphp

                        {{-- Short-Term Chart --}}
                        @if(count($shortPredicted) > 0)
                            <script type="application/json" id="bi-forecast-short-data-card">
                                {!! json_encode(['categories' => $shortCategories, 'predicted' => $shortPredicted, 'lower' => $shortLower, 'upper' => $shortUpper], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
                            </script>
                            <div x-data="forecastingChart(JSON.parse(document.getElementById('bi-forecast-short-data-card').textContent), '#6366F1')"
                                 wire:ignore
                                 wire:key="bi-forecast-short-card-{{ $selectedBranchId }}-{{ $startDate }}-{{ $endDate }}-{{ str_replace(' ', '-', $activeFilter) }}"
                                 x-intersect.once="init()"
                                 class="w-full">
                                <div x-ref="forecastChart" class="w-full h-[300px]"></div>
                            </div>
                        @else
                            <div class="p-6">
                                <x-empty-state title="No Short-Term Forecast" description="Not enough historical data to compute a forecast." />
                            </div>
                        @endif
                    </div>

                    {{-- BACK FACE: Long-Term Forecast --}}
                    <div class="absolute inset-0 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 overflow-hidden" style="backface-visibility:hidden; transform: rotateY(180deg);">
                        <div class="flex flex-col gap-3 mb-4">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="text-[15px] font-bold text-gray-900 tracking-tight">6-Month Forecast</h4>
                                    <p class="text-[11px] text-gray-400 font-medium">Long-term revenue projection for the next 6 months.</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-violet-50 border border-violet-200 text-[9px] font-black text-violet-700 uppercase tracking-widest whitespace-nowrap">
                                    <span class="w-1 h-1 rounded-full bg-violet-500"></span>
                                    WLR w/ Seasonality
                                </span>
                            </div>
                        </div>

                        {{-- Long-Term Metrics --}}
                        @php
                            $longSummary = $fc['long_term'] ?? ['trend'=>'N/A','growth_rate'=>0,'confidence'=>'Low','baseline_avg'=>0];
                            $long_total = collect($fc['long_term']['forecast'] ?? [])->sum('predicted');
                        @endphp
                        <div class="mb-4 grid grid-cols-2 gap-3">
                            <div class="bg-gray-50/50 rounded-xl p-3 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center border border-gray-100">
                                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[10px] text-gray-500 font-medium">Trend (Monthly)</div>
                                    <div class="text-[13px] font-black text-gray-900 truncate">{{ $longSummary['trend'] ?? 'N/A' }}</div>
                                    <div class="text-[9px] text-gray-400">{{ $longSummary['confidence'] ?? 'Low' }} confidence</div>
                                </div>
                            </div>

                            <div class="bg-gray-50/50 rounded-xl p-3 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center border border-gray-100">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-[10px] text-gray-500 font-medium">6-Month Total</div>
                                    <div class="text-[13px] font-black text-gray-900 truncate">&#8369;{{ number_format($long_total, 0) }}</div>
                                    <div class="text-[9px] text-gray-400">Projected revenue</div>
                                </div>
                            </div>
                        </div>

                        <div class="text-[10px] text-gray-500 space-y-1 bg-violet-50/50 border border-violet-100 rounded-lg p-3 mb-4">
                            <p><span class="font-bold text-gray-700">Trend:</span> Direction of monthly sales change (12-month history analyzed)</p>
                            <p><span class="font-bold text-gray-700">Total:</span> Sum of all 6 monthly forecasts with seasonal adjustments applied</p>
                            <p><span class="font-bold text-gray-700">Method:</span> Weighted Linear Regression using monthly aggregates + day-of-week patterns maintained across months</p>
                        </div>

                        @php
                            $long = $fc['long_term']['forecast'] ?? [];
                            $longCategories = collect($long)->pluck('date')->all();
                            $longPredicted = collect($long)->pluck('predicted')->map(fn($v) => round($v, 2))->all();
                        @endphp

                        {{-- Long-Term Chart --}}
                        @if(count($longPredicted) > 0)
                            <script type="application/json" id="bi-forecast-long-data">
                                {!! json_encode(['categories' => $longCategories, 'predicted' => $longPredicted], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
                            </script>
                            <div x-data="forecastingChart(JSON.parse(document.getElementById('bi-forecast-long-data').textContent), '#8b5cf6')"
                                 wire:ignore
                                 wire:key="bi-forecast-long-{{ $selectedBranchId }}-{{ $startDate }}-{{ $endDate }}-{{ str_replace(' ', '-', $activeFilter) }}"
                                 x-intersect.once="init()"
                                 class="w-full">
                                <div x-ref="forecastChart" class="w-full h-[300px]"></div>
                            </div>
                        @else
                            <div class="p-6">
                                <x-empty-state title="No Long-Term Forecast" description="At least 2 months of sales data required for long-term projection." />
                            </div>
                        @endif
                    </div>
               </div>
            </div>
        </div>

        {{-- Restock Insight - sits to the right of the flip card, filling the space instead of leaving it empty --}}
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col h-full overflow-hidden">
                <div class="flex items-center justify-between gap-3 mb-4 shrink-0">
                    <div>
                        <h4 class="text-[15px] font-bold text-gray-900 tracking-tight">Restock Insight</h4>
                        <p class="text-[11px] text-gray-400 font-medium">Ingredient demand recommendations based on the forecasted sales trend.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-{{ $primaryColor }}-50 text-{{ $primaryColor }}-700 text-[10px] font-black uppercase tracking-widest px-2.5 py-1 shrink-0">
                        Forecast-driven
                    </span>
                </div>

                <div class="relative flex-1 min-h-0">
                    @if(count($restock) > 0)
                        <div class="absolute inset-0 space-y-3 overflow-y-auto custom-scrollbar pr-2">
                            @foreach($restock as $item)
                                <div class="rounded-3xl border border-gray-100 p-4 flex items-center justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-gray-900 truncate">{{ $item['name'] }} <span class="text-[10px] uppercase tracking-widest font-black text-gray-400">({{ $item['unit'] }})</span></p>
                                        @php
                                            $p = strtolower($item['priority'] ?? 'medium');
                                            $badge = match($p) {
                                                'high' => 'bg-red-50 text-red-700',
                                                'medium' => 'bg-amber-50 text-amber-700',
                                                default => 'bg-gray-50 text-gray-600'
                                            };
                                        @endphp
                                        <p class="text-[11px] text-gray-500 mt-1">Priority: <span class="font-black px-2 py-0.5 rounded-full text-[11px] {{ $badge }}">{{ $item['priority'] }}</span></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[16px] font-black text-gray-900">{{ number_format($item['amount'], 0) }}</p>
                                        <p class="text-[10px] uppercase text-gray-400 tracking-widest">Next 14 days</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="absolute inset-0 flex items-center justify-center">
                            <x-empty-state title="No restock insight" description="Not enough historical order data to generate ingredient demand recommendations." />
                        </div>
                    @endif
                </div>
            </div>
        </div>
        </div>

    {{-- Prescriptive Analytics --}}
    <div x-cloak x-show="activeTab === 'prescriptive'" class="space-y-6">
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/70 p-5 sm:p-6">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 12c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.031 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-700">Recommended actions</p>
                        <h3 class="mt-1 text-[18px] font-black text-emerald-950">Turn the forecast into a decision</h3>
                        <p class="mt-1 text-[12px] leading-relaxed text-emerald-800">Recommendations combine projected 14-day demand, a spoilage-adjusted safety buffer, and current usable stock. Select a branch before acting.</p>
                    </div>
                </div>
                @if($selectedBranchId !== 'all')
                    <button type="button" wire:click="exportPrescriptiveCsv" class="shrink-0 inline-flex items-center gap-1.5 rounded-xl bg-white border border-emerald-200 px-3 py-2 text-[10px] font-black uppercase tracking-widest text-emerald-700 hover:bg-emerald-50 transition-colors">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H8a2 2 0 01-2-2V5a2 2 0 012-2h6l6 6v11a2 2 0 01-2 2z"/></svg>
                        Export list
                    </button>
                @endif
            </div>
        </div>

        @if($selectedBranchId === 'all')
            @php
                $formatFriendlyUnit = function($amount, $unit) {
                    $unitLower = strtolower(trim($unit ?? ''));
                    if (in_array($unitLower, ['g', 'gram', 'grams'])) {
                        if ($amount >= 1000) {
                            return [
                                'primary' => number_format($amount / 1000, 1) . ' kg',
                                'secondary' => number_format($amount) . ' g'
                            ];
                        }
                        return ['primary' => number_format($amount) . ' g', 'secondary' => null];
                    }
                    if (in_array($unitLower, ['ml', 'milliliter', 'milliliters'])) {
                        if ($amount >= 1000) {
                            return [
                                'primary' => number_format($amount / 1000, 1) . ' L',
                                'secondary' => number_format($amount) . ' ml'
                            ];
                        }
                        return ['primary' => number_format($amount) . ' ml', 'secondary' => null];
                    }
                    return ['primary' => number_format($amount) . ' ' . $unit, 'secondary' => null];
                };

                $maxAmount = !empty($networkRestockSummary) ? max(array_column($networkRestockSummary, 'amount') ?: [1]) : 1;
            @endphp

            <div class="space-y-4">
                {{-- The Top 15 Aggregated Network Summary Card --}}
                @if(auth()->user()->role_id === 1 || auth()->user()->isSuperAdmin())
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden animate-fadeIn" wire:key="network-restock-panel-expanded">
                        {{-- Top Header with controls --}}
                        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 bg-gray-50/50">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="flex h-2.5 w-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <h4 class="text-[13px] font-black text-gray-900 tracking-tight">Top 15 Ingredients by Combined Network Demand</h4>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-0.5">Estimated 14-day commissary consumption across all branches (15% rush buffer included)</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="loadNetworkRestockSummary" 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 text-[11px] font-bold transition-all shadow-2xs">
                                    <svg wire:loading.class="animate-spin" wire:target="loadNetworkRestockSummary" class="h-3.5 w-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    <span>Refresh</span>
                                </button>
                            </div>
                        </div>

                        {{-- Modern Responsive Grid Layout (2 cols on md, 3 cols on xl) --}}
                        <div class="p-4 sm:p-6">
                            @if(count($networkRestockSummary) > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5">
                                    @foreach($networkRestockSummary as $index => $item)
                                        @php
                                            $formatted = $formatFriendlyUnit($item['amount'], $item['unit']);
                                            $percentage = $maxAmount > 0 ? min(100, round(($item['amount'] / $maxAmount) * 100)) : 0;
                                            $rank = $index + 1;
                                            $rankStyle = match($rank) {
                                                1 => 'bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-sm shadow-amber-500/25 ring-2 ring-amber-200/70',
                                                2 => 'bg-gradient-to-r from-slate-700 to-slate-800 text-white shadow-xs ring-1 ring-slate-200',
                                                3 => 'bg-gradient-to-r from-amber-700 to-amber-800 text-white shadow-xs',
                                                default => 'bg-gray-100 text-gray-700 border border-gray-200'
                                            };
                                        @endphp
                                        <div class="rounded-2xl border border-gray-100 bg-white p-4 hover:border-gray-200 hover:shadow-md transition-all flex flex-col justify-between group">
                                            <div>
                                                <div class="flex items-start justify-between gap-2 mb-2">
                                                    <div class="flex items-center gap-2 min-w-0">
                                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-[11px] font-black {{ $rankStyle }}">
                                                            {{ $rank }}
                                                        </span>
                                                        <p class="text-[13px] font-bold text-gray-900 truncate group-hover:text-amber-700 transition-colors" title="{{ $item['name'] }}">
                                                            {{ $item['name'] }}
                                                        </p>
                                                    </div>
                                                    <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-50 text-gray-600 border border-gray-100">
                                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                                                        </svg>
                                                        <span>{{ $item['branch_count'] }} {{ Str::plural('branch', $item['branch_count']) }}</span>
                                                    </span>
                                                </div>

                                                {{-- Human-friendly quantity display --}}
                                                <div class="mt-3 flex items-baseline justify-between">
                                                    <span class="text-[18px] font-black text-gray-900 tracking-tight">
                                                        {{ $formatted['primary'] }}
                                                    </span>
                                                    @if($formatted['secondary'])
                                                        <span class="text-[11px] font-semibold text-gray-600 tabular-nums">
                                                            ({{ $formatted['secondary'] }})
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- Relative Demand Progress Bar --}}
                                            <div class="mt-3.5 pt-2.5 border-t border-gray-50">
                                                <div class="flex items-center justify-between text-[10px] text-gray-500 font-semibold mb-1">
                                                    <span>Relative volume</span>
                                                    <span class="tabular-nums">{{ $percentage }}%</span>
                                                </div>
                                                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                                    <div class="h-1.5 rounded-full {{ $rank === 1 ? 'bg-amber-500' : ($rank <= 3 ? 'bg-slate-700' : 'bg-emerald-500/80') }}" style="width: {{ $percentage }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-10">
                                    <p class="text-[12px] font-medium text-gray-500">No completed orders found in this date range to project demand.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="max-h-[38rem] overflow-y-auto overflow-x-auto custom-scrollbar">
                    <x-data-table>
                        <x-slot name="header">
                            <th class="py-3 px-4 border-r border-gray-100 text-[11px] font-medium text-gray-500 tracking-wide uppercase">Ingredient</th>
                            <th class="py-3 px-4 border-r border-gray-100 text-[11px] font-medium text-gray-500 tracking-wide uppercase text-center">Priority</th>
                            <th class="py-3 px-4 border-r border-gray-100 text-[11px] font-medium text-gray-500 tracking-wide uppercase text-right">On Hand</th>
                            <th class="py-3 px-4 border-r border-gray-100 text-[11px] font-medium text-gray-500 tracking-wide uppercase text-right">14-Day Demand</th>
                            <th class="py-3 px-4 border-r border-gray-100 text-[11px] font-medium text-gray-500 tracking-wide uppercase text-right">Safety Stock</th>
                            <th class="py-3 px-4 border-r border-gray-100 text-[11px] font-medium text-gray-500 tracking-wide uppercase text-center">Coverage</th>
                            <th class="py-3 px-4 border-r border-gray-100 text-[11px] font-medium text-gray-500 tracking-wide uppercase text-right">Recommended</th>
                            <th class="py-3 px-4 text-[11px] font-medium text-gray-500 tracking-wide uppercase text-center">Action</th>
                        </x-slot>
                        @forelse($prescriptiveRecommendations as $recommendation)
                            @php
                                $priority = strtolower($recommendation['priority'] ?? 'medium');
                                $priorityClass = match($priority) {
                                    'critical' => 'bg-red-50 text-red-700 border-red-100',
                                    'high' => 'bg-orange-50 text-orange-700 border-orange-100',
                                    'medium' => 'bg-amber-50 text-amber-700 border-amber-100',
                                    default => 'bg-slate-50 text-slate-600 border-slate-100',
                                };
                                $isActionable = ($recommendation['actionable'] ?? false) && isset($recommendation['recommended_quantity']);
                            @endphp
                            <tr wire:key="prescriptive-row-{{ $recommendation['ingredient_id'] ?? $loop->index }}" class="hover:bg-gray-50/50 transition-colors align-top">
                                <td class="py-3 px-4 border-r border-gray-100">
                                    <p class="text-[13px] font-bold text-gray-900">{{ $recommendation['title'] }}</p>
                                    <p class="mt-1 text-[11px] leading-relaxed text-gray-500 max-w-sm">{{ $recommendation['reason'] }}</p>
                                    <div class="mt-1.5 flex flex-wrap gap-1">
                                        @if($recommendation['stockout_suppressed'] ?? false)
                                            <span class="inline-flex items-center rounded-full bg-red-50 text-red-600 border border-red-100 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-widest">Out of stock now</span>
                                        @endif
                                        @if(($recommendation['waste_rate_pct'] ?? 0) > 10)
                                            <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-600 border border-amber-100 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-widest">{{ number_format($recommendation['waste_rate_pct'], 0) }}% waste rate</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3 px-4 border-r border-gray-100 text-center whitespace-nowrap">
                                    <span class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-widest {{ $priorityClass }}">{{ $recommendation['priority'] ?? '—' }}</span>
                                </td>
                                @if($isActionable)
                                    <td class="py-3 px-4 border-r border-gray-100 text-right text-[12px] font-bold text-gray-900 whitespace-nowrap">{{ number_format($recommendation['current_stock_display']['value'], 2) }} {{ $recommendation['current_stock_display']['unit'] }}</td>
                                    <td class="py-3 px-4 border-r border-gray-100 text-right text-[12px] font-bold text-indigo-900 whitespace-nowrap">{{ number_format($recommendation['projected_demand_display']['value'], 2) }} {{ $recommendation['projected_demand_display']['unit'] }}</td>
                                    <td class="py-3 px-4 border-r border-gray-100 text-right text-[12px] font-bold text-amber-900 whitespace-nowrap">{{ number_format($recommendation['safety_stock_display']['value'], 2) }} {{ $recommendation['safety_stock_display']['unit'] }}</td>
                                    <td class="py-3 px-4 border-r border-gray-100 text-center text-[12px] font-bold text-emerald-900 whitespace-nowrap">{{ number_format($recommendation['coverage_days'], 1) }} days</td>
                                    <td class="py-3 px-4 border-r border-gray-100 text-right whitespace-nowrap">
                                        <span class="text-[14px] font-black text-gray-900">{{ number_format($recommendation['recommended_quantity_display']['value'], 2) }}</span>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase ml-1">{{ $recommendation['recommended_quantity_display']['unit'] }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center whitespace-nowrap">
                                        <button type="button" wire:click="openStockReview({{ $recommendation['ingredient_id'] }})" wire:loading.attr="disabled" wire:loading.class="cursor-wait opacity-70" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-1.5 text-[10px] font-black uppercase tracking-widest text-white transition hover:bg-gray-700 disabled:cursor-wait disabled:opacity-70">
                                            Review
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                        </button>
                                    </td>
                                @else
                                    <td class="py-3 px-4 border-r border-gray-100 text-center text-[11px] text-gray-400" colspan="5">—</td>
                                    <td class="py-3 px-4 text-center text-[11px] text-gray-400">—</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-0">
                                    <x-empty-state title="No recommendations yet" description="More completed sales and stock data are needed to produce an actionable recommendation." />
                                </td>
                            </tr>
                        @endforelse
                    </x-data-table>
                </div>
                <div class="p-4 border-t border-gray-100">
                    <x-pagination :paginator="$prescriptiveRecommendations" keyPrefix="bi-prescriptive" perPageModel="prescriptivePerPage" />
                </div>
            </div>
        @endif

        <x-modal name="prescriptive-stock-review" maxWidth="2xl" focusable>
            @if(empty($stockReview))
                <div class="animate-pulse p-6" aria-label="Loading inventory details">
                    <div class="flex items-start justify-between gap-4">
                        <div class="w-full space-y-2">
                            <div class="h-2.5 w-28 rounded bg-emerald-100"></div>
                            <div class="h-5 w-44 rounded bg-gray-200"></div>
                            <div class="h-3 w-56 rounded bg-gray-100"></div>
                        </div>
                        <div class="h-8 w-8 rounded-lg bg-gray-100"></div>
                    </div>

                    <div class="mt-6 space-y-2">
                        <div class="h-3 w-28 rounded bg-gray-200"></div>
                        <div class="rounded-xl border border-gray-100 p-4">
                            <div class="grid grid-cols-3 gap-4">
                                <div class="space-y-2"><div class="h-2 w-12 rounded bg-gray-100"></div><div class="h-3 w-28 rounded bg-gray-200"></div></div>
                                <div class="space-y-2"><div class="h-2 w-16 rounded bg-gray-100"></div><div class="h-3 w-20 rounded bg-gray-200"></div></div>
                                <div class="space-y-2"><div class="h-2 w-12 rounded bg-gray-100"></div><div class="h-3 w-24 rounded bg-gray-200"></div></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-2">
                        <div class="h-3 w-40 rounded bg-gray-200"></div>
                        <div class="overflow-hidden rounded-xl border border-gray-100">
                            @for($skeletonRow = 0; $skeletonRow < 4; $skeletonRow++)
                                <div class="flex items-center justify-between border-b border-gray-100 px-3 py-3 last:border-0">
                                    <div class="space-y-2"><div class="h-3 w-16 rounded bg-gray-200"></div><div class="h-2 w-28 rounded bg-gray-100"></div></div>
                                    <div class="h-3 w-16 rounded bg-gray-200"></div>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
            @else
                <div class="max-h-[calc(100vh-7rem)] overflow-y-auto custom-scrollbar p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-600">Inventory review</p>
                            <h3 class="mt-1 text-[19px] font-black text-gray-900">{{ $stockReview['ingredient_name'] }}</h3>
                            <p class="mt-1 text-[11px] text-gray-500">Current balance: <span class="font-black text-gray-900">{{ number_format($stockReview['current_stock_display']['value'], 2) }} {{ $stockReview['current_stock_display']['unit'] }}</span></p>
                        </div>
                        <button type="button" @click="$dispatch('close-modal', 'prescriptive-stock-review')" class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="Close stock details">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="mt-5">
                        <div class="mb-2 flex items-center justify-between">
                            <h4 class="text-[12px] font-black uppercase tracking-widest text-gray-700">Active batches</h4>
                            <span class="text-[10px] font-bold text-gray-400">FEFO order</span>
                        </div>
                        <div class="max-h-56 overflow-y-auto rounded-xl border border-gray-200">
                            @forelse($stockReview['batches'] as $batch)
                                @php
                                    $state = $batch['expiry_state'];
                                    $stateClass = $state === 'Expired' ? 'text-red-700 bg-red-50' : ($state === 'Expiring soon' ? 'text-amber-700 bg-amber-50' : 'text-emerald-700 bg-emerald-50');
                                @endphp
                                <div class="grid grid-cols-3 gap-3 border-b border-gray-100 px-3 py-3 last:border-0">
                                    <div><p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Batch</p><p class="mt-1 text-[12px] font-bold text-gray-900">{{ $batch['batch_number'] }}</p></div>
                                    <div><p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Quantity</p><p class="mt-1 text-[12px] font-bold text-gray-900">{{ number_format($batch['quantity_display']['value'], 2) }} {{ $batch['quantity_display']['unit'] }}</p></div>
                                    <div><p class="text-[9px] font-black uppercase tracking-widest text-gray-400">Expiry</p><p class="mt-1 text-[11px] font-bold text-gray-700">{{ $batch['expiry_date'] ?: 'No expiry' }}</p><span class="mt-1 inline-flex rounded-full px-1.5 py-0.5 text-[9px] font-black {{ $stateClass }}">{{ $state }}</span></div>
                                </div>
                            @empty
                                <p class="px-3 py-4 text-[11px] text-gray-500">No active batches found for this ingredient and branch.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="mt-5">
                        <h4 class="mb-2 text-[12px] font-black uppercase tracking-widest text-gray-700">Recent inventory activity</h4>
                                <div class="h-48 max-h-48 overflow-y-auto overscroll-contain rounded-xl border border-gray-200">
                            @forelse($this->stockReviewMovements as $movement)
                                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-3 py-2.5 last:border-0">
                                    <div><p class="text-[11px] font-bold text-gray-900">{{ $movement['type'] }}</p><p class="text-[10px] text-gray-400">{{ $movement['reference'] }} · {{ $movement['date'] }}</p></div>
                                    <span class="text-[12px] font-black {{ $movement['quantity'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $movement['quantity'] > 0 ? '+' : '' }}{{ number_format($movement['quantity_display']['value'], 2) }} {{ $movement['quantity_display']['unit'] }}</span>
                                </div>
                            @empty
                                <p class="px-3 py-4 text-[11px] text-gray-500">No recent inventory movements found.</p>
                            @endforelse
                        </div>
                        <x-pagination :paginator="$this->stockReviewMovements" keyPrefix="bi-stock-review" perPageModel="stockReviewPerPage" compact />
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <x-secondary-button type="button" @click="$dispatch('close-modal', 'prescriptive-stock-review')">Close</x-secondary-button>
                        <a href="{{ $stockReview['adjustment_url'] }}" wire:navigate @click="$dispatch('close-modal', 'prescriptive-stock-review')" class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-[11px] font-black uppercase tracking-widest text-white hover:bg-gray-700">Open Adjustment</a>
                    </div>
                </div>
            @endif
        </x-modal>
    </div>

    {{-- Product Insights --}}
    <div x-cloak x-show="activeTab === 'diagnostic'" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 px-1 items-stretch">
{{-- Star Products --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-[14px] font-bold text-gray-900 tracking-tight">Star Products</h4>
                        <p class="text-[11px] text-gray-400 font-medium">Top 5 performing items by volume</p>
                    </div>
                    <svg class="w-4 h-4 text-{{ $primaryColor }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.54 1.118l-3.976-2.888a1 1 0 00-1.175 0l-3.976 2.888c-.784.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.382-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
                <div class="pb-6">
                    @forelse($productInsights['top_products']->take(5) as $product)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center border border-gray-100 font-black text-[14px] text-gray-400">
                                {{ $loop->iteration }}
                            </div>
                            <div>
                                <span class="block text-[13px] font-bold text-gray-900 leading-none mb-1">{{ $product->product->name ?? 'Unknown Product' }}</span>
                                <span class="block text-[11px] text-gray-400 font-medium italic">{{ $product->units_sold }} units sold</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="block text-[13px] font-black text-gray-900 leading-none mb-1">&#8369;{{ number_format($product->revenue, 2) }}</span>
                            <span class="block text-[10px] font-black uppercase text-{{ $primaryColor }}-500 tracking-widest">Revenue</span>
                        </div>
                    </div>
                    @empty
                    <x-empty-state title="No Product Data" description="No sales recorded for the selected period." />
                    @endforelse
                </div>
            </div>

            {{-- Revenue Mix --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col h-full overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 shrink-0">
                    <h4 class="text-[14px] font-bold text-gray-900 tracking-tight">Revenue Mix</h4>
                    <p class="text-[11px] text-gray-400 font-medium">Distribution by category</p>
                </div>
                <div class="relative flex-1 min-h-0">
                    <div class="absolute inset-0 p-6 overflow-y-auto custom-scrollbar space-y-6">
                        @php 
                            $totalRev = $productInsights['category_sales']->sum('revenue') ?: 1;
                        @endphp
                        @forelse($productInsights['category_sales'] as $cat)
                        <div class="space-y-2 group">
                            <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-widest leading-none">
                                <span class="text-gray-500 group-hover:text-gray-900 transition-colors">{{ $cat->name }}</span>
                                <span class="text-gray-900">&#8369;{{ number_format($cat->revenue, 2) }}</span>
                            </div>
                            <div class="w-full h-2 bg-gray-50 rounded-full overflow-hidden">
                                <div class="h-full bg-{{ $primaryColor }}-500 rounded-full transition-all duration-1000 group-hover:opacity-80 shadow-sm" style="width: {{ ($cat->revenue / $totalRev) * 100 }}%"></div>
                            </div>
                            <div class="flex justify-end">
                                <span class="text-[9px] font-bold text-gray-400 italic">{{ round(($cat->revenue / $totalRev) * 100, 1) }}% of total</span>
                            </div>
                        </div>
                        @empty
                        <x-empty-state title="No Category Data" description="Categories have no sales recorded in this period." />
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Product Seasonality (Weekly / Monthly) - unified chart --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mx-1 mt-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
                <div>
                    <h4 class="text-[14px] font-bold text-gray-900 tracking-tight">Product Seasonality</h4>
                    <p class="text-[11px] text-gray-400 font-medium italic">
                        {{ $seasonalityMode === 'weekly' ? 'Units sold per day of week - top 3 products' : 'Units sold per month - top 3 products' }}
                    </p>
                </div>
                {{-- Weekly / Monthly Toggle --}}
                <div class="flex items-center gap-1 bg-gray-100 rounded-xl p-1">
                    <button wire:click="$set('seasonalityMode', 'weekly')"
                        class="px-3 py-1.5 rounded-lg text-[11px] font-black uppercase tracking-widest transition-all
                               {{ $seasonalityMode === 'weekly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
                        Weekly
                    </button>
                    <button wire:click="$set('seasonalityMode', 'monthly')"
                        class="px-3 py-1.5 rounded-lg text-[11px] font-black uppercase tracking-widest transition-all
                               {{ $seasonalityMode === 'monthly' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-400 hover:text-gray-600' }}">
                        Monthly
                    </button>
                </div>
            </div>

            @php
                $seasonality = $productInsights['seasonality'];
                $productColors = [
                    ['bg' => '#4f46e5', 'light' => '#818cf8'], // Indigo
                    ['bg' => '#10b981', 'light' => '#34d399'], // Emerald
                    ['bg' => '#f59e0b', 'light' => '#fbbf24'], // Amber
                ];

                // Shared Y-axis max across all products
                $globalMax = 1;
                foreach ($seasonality as $s) {
                    if ($s['max'] > $globalMax) $globalMax = $s['max'];
                }
                $ySteps   = 5;
                $stepSize = $globalMax > 0 ? ceil($globalMax / $ySteps) : 1;
                $yMax     = $stepSize * $ySteps ?: 1;

                // X-axis labels from first product
                $labels = !empty($seasonality) ? collect($seasonality[0]['data'])->pluck('label')->all() : [];
            @endphp

            @if(count($seasonality) > 0)
            <div class="p-6">

                {{-- Legend --}}
                <div class="flex flex-wrap items-center gap-4 mb-5">
                    @foreach($seasonality as $si => $item)
                    @php $color = $productColors[$si] ?? ['bg' => '#9ca3af', 'light' => '#d1d5db']; @endphp
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-sm inline-block" style="background-color: {{ $color['bg'] }}"></span>
                        <span class="text-[11px] font-bold text-gray-700 truncate max-w-[140px]">{{ $item['name'] }}</span>
                    </div>
                    @endforeach
                    <span class="ml-auto text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        {{ $seasonalityMode === 'weekly' ? 'Units / Day' : 'Units / Month' }}
                    </span>
                </div>

                {{-- Chart --}}
                <div class="flex gap-3">
                    {{-- Y-Axis --}}
                    <div class="flex flex-col justify-between items-end pb-6 select-none" style="min-width:34px">
                        @for($s = $ySteps; $s >= 0; $s--)
                            <span class="text-[9px] font-bold text-gray-400 leading-none">{{ number_format($stepSize * $s) }}</span>
                        @endfor
                    </div>

                    {{-- Bars + X-axis wrapper --}}
                    <div class="flex-1 flex flex-col min-w-0">
                        {{-- Bar plot area --}}
                        <div class="relative flex items-end border-l border-b border-gray-200 overflow-x-auto" style="height:200px; padding-top:3rem;">
                            {{-- Horizontal grid lines --}}
                            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none" style="top:3rem;">
                                @for($s = $ySteps; $s >= 1; $s--)
                                    <div class="w-full border-t border-dashed border-gray-100"></div>
                                @endfor
                                <div></div>
                            </div>

                            {{-- Groups of bars, one group per X label --}}
                            <div class="flex items-end w-full h-full px-2 gap-3 z-10">
                                @foreach($labels as $li => $label)
                                <div class="flex-1 flex items-end justify-center gap-0.5 h-full min-w-0">
                                    @foreach($seasonality as $si => $item)
                                    @php
                                        $color = $productColors[$si] ?? ['bg' => '#9ca3af', 'light' => '#d1d5db'];
                                        $val = $item['data'][$li]['value'] ?? 0;
                                        $pct = $yMax > 0 ? min(100, ($val / $yMax) * 100) : 0;
                                    @endphp
                                    <div class="group relative flex flex-col items-center justify-end h-full" style="flex:1; max-width:28px">
                                        <div class="w-full rounded-t-sm transition-all duration-500 group-hover:brightness-110 shadow-sm"
                                             style="height:{{ $pct }}%; background: linear-gradient(to top, {{ $color['bg'] }}, {{ $color['light'] }})">
                                            {{-- Tooltip --}}
                                            <div class="opacity-0 group-hover:opacity-100 absolute -top-10 left-1/2 -translate-x-1/2 px-2 py-1.5 bg-gray-900 text-white text-[9px] font-black rounded-lg z-30 pointer-events-none shadow-xl whitespace-nowrap flex flex-col items-center gap-0.5 transition-opacity">
                                                <span style="color: {{ $color['light'] }}">{{ $item['name'] }}</span>
                                                <span>{{ number_format($val, 0) }} units</span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- X-Axis Labels --}}
                        <div class="flex gap-3 px-2 mt-1.5">
                            @foreach($labels as $label)
                            <div class="flex-1 text-center">
                                <span class="text-[9px] font-black text-gray-500 uppercase tracking-tight leading-none whitespace-nowrap">
                                    {{ $seasonalityMode === 'weekly' ? $label : \Illuminate\Support\Str::before($label, ' ') }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
            @else
            <div class="p-6">
                <x-empty-state title="No Seasonality Data" description="Cumulative sales data is required to map patterns." />
            </div>
            @endif
        </div>
    </div>{{-- end products tab --}}

    {{-- ── Operations Tab ── --}}
    <div x-cloak x-show="activeTab === 'diagnostic'" class="space-y-6">
        {{-- Hours Intensity Chart (above Network Performance) --}}
        <div class="mx-1">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <h4 class="text-[15px] font-bold text-gray-900 tracking-tight">Hours Intensity</h4>
                        <p class="text-[11px] text-gray-400 font-medium">Order intensity by hour of day for the selected period.</p>
                    </div>
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-50 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500 border border-slate-100">
                        Hourly Trend
                    </span>
                </div>

                @php
                    $hours = $operations['hours_intensity'] ?? ['categories' => [], 'counts' => [], 'has_data' => false];
                @endphp

                @if($hours['has_data'])
                    <script type="application/json" id="hours-intensity-data-top">
                    {!! json_encode($hours, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
                    </script>

                    <div x-data="hoursIntensityChart(JSON.parse(document.getElementById('hours-intensity-data-top').textContent))"
                         wire:ignore
                         wire:key="hours-intensity-top-{{ $selectedBranchId }}-{{ $startDate }}-{{ $endDate }}-{{ str_replace(' ', '-', $activeFilter) }}"
                         x-init="init()"
                         class="w-full">
                        <div x-ref="hoursChartTop" class="w-full h-[380px]"></div>
                        <div x-ref="hoursHeatmapTop" class="w-full h-[48px] mt-2"></div>
                    </div>
                @else
                    <div class="p-6">
                        <x-empty-state title="No hourly data" description="No completed orders in this period." />
                    </div>
                @endif
            </div>
        </div>

        {{-- Branch Comparison (Admin Only) --}}
        @if(auth()->user()->role_id === 1)
        <div class="mx-1 max-h-[26rem] overflow-y-auto custom-scrollbar pr-2">
            <div class="flex items-center justify-between mb-3 px-1">
                <div class="flex items-center gap-2">
                    <h4 class="text-[14px] font-bold text-gray-900 tracking-tight">Network Performance</h4>
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-emerald-50 text-[9px] font-black text-emerald-600 uppercase tracking-widest border border-emerald-100 italic">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Live Data Sync
                    </span>
                </div>
                <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest leading-none">By Branch Matrix</span>
            </div>

            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-6 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide uppercase">Branch Entity</th>
                    <th class="py-3 px-6 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide text-right uppercase">Revenue</th>
                    <th class="py-3 px-6 border-r border-gray-100 text-[12px] font-medium text-gray-500 tracking-wide text-center uppercase">Orders</th>
                    <th class="py-3 px-6 text-[12px] font-medium text-gray-500 tracking-wide text-right uppercase">Share</th>
                </x-slot>
                @php 
                    $networkTotal = $operations['global_network_total'];
                @endphp
                @forelse($operations['branch_performance'] as $branch)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="py-3 px-6 border-r border-gray-100 whitespace-nowrap">
                        <span class="block text-[13px] font-bold text-gray-900 leading-none">{{ $branch->branch->branch_name }}</span>
                        <span class="block text-[10px] text-gray-400 font-medium italic mt-1">{{ $branch->branch->branch_code }}</span>
                    </td>
                    <td class="py-3 px-6 border-r border-gray-100 text-right font-black text-[13px] text-gray-900 font-mono whitespace-nowrap">&#8369;{{ number_format($branch->revenue, 2) }}</td>
                    <td class="py-3 px-6 border-r border-gray-100 text-center text-[12px] font-bold text-gray-600 whitespace-nowrap">{{ $branch->count }}</td>
                    <td class="py-3 px-6 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-3">
                            <span class="text-[11px] font-black text-{{ $primaryColor }}-600 tabular-nums">{{ number_format(($branch->revenue / $networkTotal) * 100, 1) }}%</span>
                            <div class="w-16 h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-{{ $primaryColor }}-500 rounded-full" style="width: {{ ($branch->revenue / $networkTotal) * 100 }}%"></div>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="py-0">
                        <x-empty-state title="No Branch Data" description="No multi-branch sales data available for this range." />
                    </td>
                </tr>
                @endforelse
            </x-data-table>

            <div class="mt-4">
                <x-pagination :paginator="$operations['branch_performance']" keyPrefix="bi-branches" />
            </div>
        </div>
        @endif
    
    </div>



    {{-- ── Sales Report Tab ── --}}
    <div x-cloak x-show="activeTab === 'descriptive'" class="space-y-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h4 class="text-[15px] font-bold text-gray-900 tracking-tight">Sales Performance Trend</h4>
                    <p class="text-[11px] text-gray-400 font-medium">Revenue movement for the selected reporting period.</p>
                </div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-50 text-[10px] font-black uppercase tracking-[0.24em] text-slate-500 border border-slate-100">
                    Daily Trend
                </span>
            </div>
            @php
            $trendData = $salesData['sales_trend'] ?? ['categories' => [], 'gross' => [], 'net_sales' => [], 'has_data' => false];
            @endphp

            @if($trendData['has_data'])
                <script type="application/json" id="sales-trend-data">
                {!! json_encode($trendData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
                </script>
                <div x-data="salesReportTrend(JSON.parse(document.getElementById('sales-trend-data').textContent))"
                     wire:ignore
                     wire:key="sales-trend-{{ $selectedBranchId }}-{{ $startDate }}-{{ $endDate }}-{{ str_replace(' ', '-', $activeFilter) }}"
                     @refresh-bi-charts.window="updateChart($event.detail)"
                     x-init="init()"
                     class="w-full">
                    <div x-ref="trendChart" class="w-full h-[320px]"></div>
                </div>
            @else
                <div class="p-10">
                    <x-empty-state
                        title="No Sales Trend Data"
                        description="No completed sales were recorded for the selected period. Adjust the date range or branch filter to see trend movement."
                    />
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-1">

            {{-- Payment Methods + Order Sources --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h4 class="text-[13px] font-bold text-gray-900 mb-4 uppercase tracking-wide">Payment Methods</h4>
                    <div class="space-y-3 max-h-[24rem] overflow-y-auto custom-scrollbar pr-2">
                        @forelse($salesData['payment_methods'] as $pm)
                            <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-{{ $primaryColor }}-50 flex items-center justify-center text-{{ $primaryColor }}-600 font-bold text-xs">{{ substr($pm->payment_method ?? '?', 0, 2) }}</div>
                                    <div>
                                        <p class="text-[13px] font-bold text-gray-900">{{ $pm->payment_method ?? 'Unknown' }}</p>
                                        <p class="text-[11px] text-gray-400 font-medium">{{ $pm->count }} transactions</p>
                                    </div>
                                </div>
                                <p class="text-[14px] font-black text-gray-900">&#8369;{{ number_format($pm->total, 2) }}</p>
                            </div>
                        @empty
                            <x-empty-state title="No payment data" description="No completed orders in this period." />
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h4 class="text-[13px] font-bold text-gray-900 mb-4 uppercase tracking-wide">Order Sources</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        @forelse($salesData['order_sources'] as $src)
                            <div class="p-4 rounded-xl bg-gray-50 border border-gray-100">
                                <p class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">{{ $src->source }}</p>
                                <h5 class="text-lg font-black text-gray-900 mt-1">&#8369;{{ number_format($src->total, 2) }}</h5>
                                <p class="text-[11px] text-gray-400 mt-1 font-medium">{{ $src->count }} orders</p>
                            </div>
                        @empty
                            <div class="col-span-3"><x-empty-state title="No source data" description="" /></div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Right: Key Metrics + Top Items --}}
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h4 class="text-[13px] font-bold text-gray-900 mb-4 uppercase tracking-wide">Key Metrics</h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <span class="text-[12px] text-gray-600 font-medium">Total Orders</span>
                            <span class="text-[13px] font-bold text-gray-900">{{ $salesData['order_count'] }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-gray-100">
                            <span class="text-[12px] text-gray-600 font-medium">Avg. Order Value</span>
                            <span class="text-[13px] font-bold text-gray-900">&#8369;{{ number_format($salesData['avg_order_value'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h4 class="text-[13px] font-bold text-gray-900 mb-4 uppercase tracking-wide">Top Selling Items</h4>
                    <div class="space-y-4 max-h-[24rem] overflow-y-auto custom-scrollbar pr-2">
                        @forelse($salesData['top_items'] as $item)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item->product->image_url ?? '' }}" alt="" class="w-9 h-9 rounded-xl object-cover bg-gray-100 border border-gray-200">
                                    <div>
                                        <p class="text-[12px] font-bold text-gray-900 line-clamp-1">{{ $item->product->name ?? 'Unknown' }}</p>
                                        <p class="text-[11px] text-gray-400 font-medium">{{ $item->total_quantity }} sold</p>
                                    </div>
                                </div>
                                <p class="text-[13px] font-bold text-gray-900">&#8369;{{ number_format($item->total_sales, 2) }}</p>
                            </div>
                        @empty
                            <x-empty-state title="No items" description="" />
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- â”€â”€â”€ Breakdown Side Panel â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ --}}
    <x-side-panel name="kpi-breakdown" width="max-w-md">
        <div class="flex flex-col h-full bg-white">
            {{-- Premium Header --}}
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white shadow-lg shadow-slate-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div>
                        <h3 class="text-[15px] font-black text-slate-900 tracking-tight leading-none">BI Intelligence Report</h3>
                        <span class="text-[11px] text-indigo-600 font-bold uppercase tracking-wider mt-1 block">{{ $selectedMetric }} Analysis</span>
                    </div>
                </div>
                <button @click="isPanelOpen = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Identity Section --}}
            <div class="p-6 bg-gradient-to-b from-slate-50/80 to-white border-b border-slate-50">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-2xl bg-white border border-slate-200 shadow-sm flex items-center justify-center text-slate-900 text-lg font-black italic">
                        {{ substr($selectedMetric, 0, 1) }}
                    </div>
                    <div class="flex-1">
                        <h4 class="text-[16px] font-black text-slate-900 leading-tight">Analytical Insights</h4>
                        <div class="flex flex-col gap-1 mt-2">
                            <div class="flex items-center gap-2 text-[12px] text-slate-500 font-medium">
                                <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <div class="flex items-center gap-1.5">
                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Formula:</span>
                                    <code class="text-[11px] font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">
                                        @if($selectedMetric === 'Gross Revenue')
                                            Sum (Sales Price x Qty)
                                        @elseif($selectedMetric === 'Net Sales')
                                            Gross Revenue - Deductions
                                        @elseif($selectedMetric === 'Gross Profit')
                                            Net Sales - Total Ingredient Cost (COGS)
                                        @endif
                                    </code>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 text-[12px] text-slate-500 font-medium">
                                <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="font-bold text-slate-700 uppercase tracking-tight">{{ $activeFilter }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-6 flex-1 overflow-y-auto custom-scrollbar">
                @if($selectedMetric === 'Gross Profit')
                    @php
                        $gpData = $breakdownData;
                    @endphp
                    @if(!empty($gpData) && isset($gpData['gross_profit']))
    <div class="space-y-4">
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Net Sales inflow</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">&#8369; {{ number_format($gpData['net_sales'] ?? 0, 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Total Net</span>
                                </div>
                            </div>
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-rose-600 uppercase tracking-widest block mb-1">Ingredient Cost (COGS)</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">- &#8369; {{ number_format($gpData['total_cogs'] ?? 0, 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Raw Expense</span>
                                </div>
                            </div>
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-amber-600 uppercase tracking-widest block mb-1">Waste & Spoilage Cost</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">- &#8369; {{ number_format($gpData['waste_cost'] ?? 0, 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Spoiled Raw</span>
                                </div>
                            </div>
                            <div class="mt-6 pt-6 border-t border-dashed border-slate-200">
                                <div class="flex justify-between items-end bg-slate-900 p-6 rounded-2xl shadow-xl shadow-slate-200 overflow-hidden relative">
                                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-bl-full -mr-16 -mt-16"></div>
                                    <div class="relative z-10">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block mb-1">Gross Margin Result</span>
                                        <p class="text-3xl font-black text-white">&#8369; {{ number_format($gpData['gross_profit'] ?? 0, 2) }}</p>
                                    </div>
                                    <div class="relative z-10 text-right">
                                        <span class="px-3 py-1.5 rounded-xl bg-white/10 text-white text-[14px] font-black backdrop-blur-md">{{ number_format($gpData['profit_margin_pct'] ?? 0, 1) }}%</span>
                                        <p class="text-[9px] font-black text-slate-500 uppercase mt-2">Margin</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="py-20 text-center">
                            <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </div>
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">No analytical data available</p>
                        </div>
                    @endif
                @else
                    <div class="flex items-center justify-between mb-8">
                        <h5 class="text-[11px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                            Performance Breakdown
                        </h5>
                        <span class="text-[10px] font-bold text-slate-400 uppercase bg-slate-50 px-2 py-1 rounded-md border border-slate-100">{{ count($breakdownData) }} Nodes</span>
                    </div>

                    <div class="space-y-6 relative">
                        <div class="absolute left-[7px] top-2 bottom-2 w-[1px] bg-slate-100"></div>

                        @forelse($breakdownData as $item)
                            <div class="relative pl-8 group">
                                <div class="absolute left-0 top-[6px] w-[16px] h-[16px] rounded-full border-[3px] border-white bg-slate-100 group-hover:bg-slate-900 group-hover:scale-110 transition-all duration-300 z-10 shadow-sm"></div>
                                
                                <div class="flex items-center justify-between p-4 rounded-2xl border border-transparent hover:border-slate-100 hover:bg-slate-50/50 transition-all duration-300">
                                    <div>
                                        <h4 class="text-[14px] font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                            {{ $item['category'] }}
                                        </h4>
                                        @isset($item['count'])
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $item['count'] }} Orders</p>
                                            </div>
                                        @endisset
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[14px] font-black text-slate-900">
                                            &#8369; {{ number_format($item['total'], 2) }}
                                        </p>
                                        <p class="text-[9px] font-black text-slate-400 uppercase mt-0.5 tracking-tighter">
                                            Contribution
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="py-20 text-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <svg class="w-8 h-8 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">No analytical data available</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </div>
    </x-side-panel>

    {{-- Store chart data in a hidden DOM element so Livewire updates can refresh it reliably --}}
    @php
        $biChartDataState = json_encode([
            'branch' => $selectedBranchId,
        ], JSON_UNESCAPED_UNICODE);
    @endphp

    <div id="bi-chart-data" style="display:none;" data-state='{{ $biChartDataState }}'></div>

</div>{{-- This closes x-data="slidingTabs(...)" --}}

@push('scripts')
    <script>
        function salesReportTrend(initialData) {
            return {
                trendChart: null,
                chartData: initialData || { categories: [], gross: [], net_sales: [] },

                init() {
                    if (typeof window.ApexCharts === 'undefined' || !this.$refs.trendChart) {
                        return;
                    }

                    const options = {
                        series: [
                            { name: 'Gross Revenue', data: this.chartData.gross },
                            { name: 'Net Sales', data: this.chartData.net_sales }
                        ],
                        chart: {
                            type: 'area',
                            height: 320,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            animations: {
                                enabled: false
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: {
                            curve: 'smooth',
                            width: [3, 2],
                            dashArray: [0, 6]
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.28,
                                opacityTo: 0.03,
                                stops: [0, 80, 100]
                            }
                        },
                        colors: ['#10B981', '#6366F1'],
                        xaxis: {
                            categories: this.chartData.categories,
                            tickAmount: window.innerWidth < 640 ? 4 : 8,
                            labels: { style: { colors: '#9CA3AF', fontSize: '10px' } },
                            axisBorder: { show: false },
                            axisTicks: { show: false }
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#9CA3AF', fontSize: '11px' },
                                formatter: (val) => {
                                    if (val >= 1000) {
                                        return '\u20B1' + (val / 1000).toFixed(1) + 'k';
                                    }
                                    return '\u20B1' + val.toFixed(0);
                                }
                            }
                        },
                        grid: {
                            borderColor: '#f8fafc',
                            strokeDashArray: 4,
                            padding: { top: 0, right: 15, bottom: 10, left: 10 }
                        },
                        legend: {
                            position: 'top',
                            horizontalAlign: 'right',
                            fontSize: '11px',
                            fontWeight: 700,
                            markers: { radius: 8, width: 8, height: 8 }
                        },
                        tooltip: {
                            theme: 'dark',
                            y: {
                                formatter: (val) => '\u20B1 ' + Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                            }
                        }
                    };

                    this.trendChart = new ApexCharts(this.$refs.trendChart, options);
                    this.trendChart.render();
                },

                updateChart(detail) {
                    if (detail && detail.categories) {
                        this.chartData = {
                            categories: detail.categories ?? [],
                            gross: detail.gross ?? [],
                            net_sales: detail.net_sales ?? [],
                        };
                    }

                    this.$nextTick(() => {
                        if (!this.$refs.trendChart) {
                            return;
                        }

                        if (!this.trendChart) {
                            this.init();
                            return;
                        }

                        this.trendChart.updateOptions({
                            xaxis: { categories: this.chartData.categories },
                            series: [
                                { name: 'Gross Revenue', data: this.chartData.gross },
                                { name: 'Net Sales', data: this.chartData.net_sales }
                            ]
                        }, false, false, false);
                    });
                }
            };
        }
    </script>
    <script>
        function hoursIntensityChart(initialData) {
            return {
                hoursChart: null,
                chartData: initialData || { categories: [], counts: [] },

                init() {
                    if (typeof window.ApexCharts === 'undefined') return;

                    // Watch activeTab so chart renders cleanly once tab is visible
                    if (this.$watch) {
                        this.$watch('activeTab', (tab) => {
                            if (tab === 'diagnostic') {
                                this.$nextTick(() => {
                                    setTimeout(() => this.render(), 60);
                                });
                            }
                        });
                    }

                    // Render immediately if already on diagnostic tab
                    if (this.activeTab === 'diagnostic') {
                        this.$nextTick(() => {
                            setTimeout(() => this.render(), 60);
                        });
                    }

                    if (this.$cleanup) {
                        this.$cleanup(() => this.destroy());
                    }
                },

                destroy() {
                    if (this.hoursChart) {
                        try { this.hoursChart.destroy(); } catch (e) {}
                        this.hoursChart = null;
                    }
                },

                render(retryCount = 0) {
                    if (typeof window.ApexCharts === 'undefined') return;

                    const el = this.$refs.hoursChartTop ?? this.$refs.hoursChart;
                    if (!el) return;
                    if (el.offsetWidth === 0) {
                        if (retryCount < 5) {
                            setTimeout(() => this.render(retryCount + 1), 60);
                        }
                        return;
                    }

                    if (this.hoursChart) {
                        try {
                            this.hoursChart.updateOptions({
                                xaxis: { categories: this.chartData.categories }
                            }, false, false);
                            this.hoursChart.updateSeries([{ name: 'Orders', data: this.chartData.counts }], false);
                        } catch (e) {
                            this.destroy();
                            this.createChart(el);
                        }
                    } else {
                        this.createChart(el);
                    }

                    this.renderHeatmapStrip();
                },

                createChart(el) {
                    const options = {
                        series: [{ name: 'Orders', data: this.chartData.counts }],
                        chart: {
                            type: 'area',
                            height: 320,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            animations: {
                                enabled: false
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 3 },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.03, stops: [0,80,100] } },
                        xaxis: { categories: this.chartData.categories, labels: { rotate: -45, style: { fontSize: '11px', colors: '#9CA3AF' } } },
                        yaxis: { labels: { formatter: (val) => Math.round(val) } },
                        colors: ['#059669'],
                        tooltip: { y: { formatter: (val) => val + ' orders' } },
                        grid: { borderColor: '#f8fafc' }
                    };

                    this.hoursChart = new ApexCharts(el, options);
                    this.hoursChart.render();

                    this.renderHeatmapStrip();
                },

                renderHeatmapStrip() {
                    const heatEl = this.$refs.hoursHeatmapTop;
                    if (!heatEl || !Array.isArray(this.chartData.counts) || !this.chartData.counts.length) return;

                    const counts = this.chartData.counts.map(v => Number(v) || 0);
                    const cats = this.chartData.categories || [];
                    const max = Math.max(...counts, 0);

                    const getColor = (val) => {
                        if (max <= 0 || val <= 0) return '#f1f5f9';
                        const ratio = val / max;
                        if (ratio <= 0.25) return '#d1fae5';
                        if (ratio <= 0.5) return '#86efac';
                        if (ratio <= 0.75) return '#22c55e';
                        return '#065f46';
                    };

                    let html = '<div class="flex items-center gap-1 w-full h-8 pt-1">';
                    cats.forEach((cat, idx) => {
                        const cnt = counts[idx] ?? 0;
                        const bg = getColor(cnt);
                        html += `
                            <div class="group/bar relative flex-1 h-6 rounded-md transition-transform hover:scale-y-110 cursor-pointer"
                                 style="background-color: ${bg};"
                                 title="${cat}: ${cnt} order(s)">
                                <div class="opacity-0 group-hover/bar:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-[10px] font-bold rounded shadow-lg pointer-events-none whitespace-nowrap z-30 transition-opacity">
                                    ${cat}: ${cnt} order${cnt === 1 ? '' : 's'}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';

                    html += `
                        <div class="flex items-center justify-between text-[10px] font-bold text-gray-400 mt-1 px-0.5">
                            <span>12 AM</span>
                            <div class="flex items-center gap-1">
                                <span class="text-[9px] uppercase tracking-wider text-gray-400 mr-1">Intensity:</span>
                                <span class="w-2.5 h-2.5 rounded-sm bg-slate-100 border border-slate-200" title="0 orders"></span>
                                <span class="w-2.5 h-2.5 rounded-sm bg-emerald-100" title="Low"></span>
                                <span class="w-2.5 h-2.5 rounded-sm bg-emerald-300" title="Moderate"></span>
                                <span class="w-2.5 h-2.5 rounded-sm bg-emerald-500" title="High"></span>
                                <span class="w-2.5 h-2.5 rounded-sm bg-emerald-800" title="Peak"></span>
                            </div>
                            <span>11 PM</span>
                        </div>
                    `;

                    heatEl.innerHTML = html;
                }
            };
        }

        function forecastingChart(initialData, color) {
            return {
                chartRef: null,
                chartData: initialData || { categories: [], predicted: [] },
                chartColor: color || '#6366F1',

                init() {
                    if (typeof window.ApexCharts === 'undefined' || !this.$refs.forecastChart) return;

                    const self = this;
                    const toFiniteArray = (arr) => Array.isArray(arr) ? arr.map(v => Number(v)) : [];
                    const isAllFinite = (arr) => arr.length > 0 && arr.every(v => Number.isFinite(v));

                    const predicted = toFiniteArray(self.chartData.predicted);
                    const lower = toFiniteArray(self.chartData.lower);
                    const upper = toFiniteArray(self.chartData.upper);

                    const hasBand = isAllFinite(lower)
                        && isAllFinite(upper)
                        && lower.length === predicted.length
                        && upper.length === predicted.length
                        && !(lower.every((v, i) => v === upper[i])); // reject a zero-width band

                    if (!isAllFinite(predicted)) return; // don't render a chart with non-numeric data

                    const series = [{ name: 'Forecast', data: predicted }];
                    if (hasBand) {
                        series.push({ name: 'Upper bound', data: upper });
                        series.push({ name: 'Lower bound', data: lower });
                    }

                    const options = {
                        series,
                        chart: {
                            type: 'area',
                            height: 320,
                            toolbar: { show: false },
                            zoom: { enabled: false },
                            animations: {
                                enabled: false
                            }
                        },
                        dataLabels: { enabled: false },
                        stroke: {
                            curve: 'smooth',
                            width: hasBand ? [3, 1, 1] : 3,
                            dashArray: hasBand ? [0, 4, 4] : 0,
                        },
                        fill: hasBand ? {
                            type: ['gradient', 'solid', 'solid'],
                            gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 85, 100] },
                            opacity: [1, 0, 0],
                        } : {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.35,
                                opacityTo: 0.02,
                                stops: [0, 85, 100]
                            }
                        },
                        xaxis: {
                            categories: self.chartData.categories,
                            labels: {
                                rotate: -45,
                                style: { fontSize: '11px', colors: '#9CA3AF' }
                            },
                            axisBorder: { show: false },
                            axisTicks: { show: false }
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#9CA3AF', fontSize: '11px' },
                                formatter: (val) => {
                                    if (val >= 1000) return '\u20B1' + (val / 1000).toFixed(1) + 'k';
                                    return '\u20B1' + Math.round(val);
                                }
                            }
                        },
                        colors: hasBand ? [self.chartColor, '#94a3b8', '#94a3b8'] : [self.chartColor],
                        tooltip: {
                            theme: 'dark',
                            y: {
                                formatter: (val) => '\u20B1 ' + Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                            }
                        },
                        grid: {
                            borderColor: '#f8fafc',
                            strokeDashArray: 4,
                            padding: { top: 0, right: 15, bottom: 10, left: 10 }
                        },
                        legend: { show: hasBand },
                        markers: {
                            size: hasBand ? [4, 0, 0] : 4,
                            colors: [self.chartColor],
                            strokeColors: '#fff',
                            strokeWidth: 2,
                            hover: { size: hasBand ? [6, 0, 0] : 6 }
                        }
                    };

                    self.chartRef = new ApexCharts(self.$refs.forecastChart, options);
                    self.chartRef.render();
                }
            };
        }
    </script>
    @endpush
