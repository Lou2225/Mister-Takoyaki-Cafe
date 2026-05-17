@php
    $user = auth()->user();
    $roleTheme = $user->getRoleTheme();
    $primaryColor = $roleTheme['primary'] ?? 'indigo';
    $primaryText = $roleTheme['text'] ?? 'text-indigo-600';
@endphp
<div
    x-data="slidingTabs(@js($activeTab), 'activeTab')"
    class="relative overflow-hidden">

    <div class="px-1" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0">
        {{-- Page title + actions --}}
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Business Reports</h2>
                <p class="text-[12px] text-gray-500 font-medium whitespace-nowrap overflow-hidden text-ellipsis">Consolidated analytics and <span class="{{ $primaryText }} font-bold">predictive forecasting</span></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="w-full sm:w-auto">
                    <x-quick-date-filter :activeFilter="$activeFilter" class="w-full justify-between sm:justify-start h-10" />
                </div>
                <x-date-range-filter startModel="startDate" endModel="endDate" :startValue="$startDate" />
                <x-report-dropdown module="BI Report" />

                {{-- Branch Scope (Super Admin) --}}
                @if(auth()->user()->role_id === 1)
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center gap-2 px-4 py-1.5 text-[12px] font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none shadow-sm transition-colors h-10">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <span>{{ $branches->firstWhere('id', $selectedBranchId)?->branch_name ?? 'Select Branch' }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">

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
        <x-sliding-tabs model="activeTab" class="mb-6 px-1" ref="tabList" wire:ignore>
            @foreach([
                'performance' => ['Performance', 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
                'forecasting' => ['Forecasting', 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
                'products' => ['Products', 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                'operations' => ['Operations', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                'sales' => ['Sales Report', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
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

    <div x-cloak x-show="activeTab === 'performance'" class="space-y-6 animate-fadeIn">
        <div class="space-y-6">
            {{-- Primary Revenue Metrics --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 px-1">
                {{-- Gross Revenue --}}
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-2xl p-5 shadow-sm flex flex-col group hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-10 h-10 rounded-xl bg-white border border-gray-100 flex items-center justify-center text-gray-600 shadow-sm transition-transform group-hover:scale-110">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Gross Revenue</span>
                    </div>
                    <span class="block text-[24px] font-black text-gray-900 leading-none">₱{{ number_format($performance['gross_sales'], 2) }}</span>
                </div>

                {{-- Net Sales --}}
                <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-2xl p-5 shadow-sm flex flex-col group hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-10 h-10 rounded-xl bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm transition-transform group-hover:scale-110">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <span class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Net Sales</span>
                    </div>
                    <span class="block text-[24px] font-black text-emerald-700 leading-none">₱{{ number_format($performance['net_sales'], 2) }}</span>
                </div>

                {{-- Gross Profit --}}
                <div class="bg-gradient-to-br from-{{ $primaryColor }}-50 to-{{ $primaryColor }}-100 border border-{{ $primaryColor }}-200 rounded-2xl p-5 shadow-sm flex flex-col group hover:shadow-md transition-all">
                    <div class="flex items-center justify-between mb-2">
                        <div class="w-10 h-10 rounded-xl bg-white border border-{{ $primaryColor }}-100 flex items-center justify-center text-{{ $primaryColor }}-600 shadow-sm transition-transform group-hover:scale-110">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <span class="text-[10px] font-black text-{{ $primaryColor }}-700/60 uppercase tracking-widest">Gross Profit</span>
                    </div>
                    <span class="block text-[24px] font-black text-{{ $primaryColor }}-600 leading-none">₱{{ number_format($performance['gross_profit'], 2) }}</span>
                </div>
            </div>

            {{-- Operational Insights & Deductions --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 px-1">
                {{-- Discounts --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:border-rose-200 transition-all">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Discounts</span>
                        <span class="block text-[15px] font-black text-rose-600 leading-none">-₱{{ number_format($performance['total_discounts'], 2) }}</span>
                    </div>
                </div>

                {{-- Delivery Fees --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:border-amber-200 transition-all">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div>
                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Delivery</span>
                        <span class="block text-[15px] font-black text-amber-600 leading-none">₱{{ number_format($performance['delivery_fees'], 2) }}</span>
                    </div>
                </div>

                {{-- Refunds --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:border-rose-200 transition-all">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3"/></svg>
                    </div>
                    <div>
                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Refunds</span>
                        <span class="block text-[15px] font-black text-rose-600 leading-none">-₱{{ number_format($performance['refunds'], 2) }}</span>
                    </div>
                </div>

                {{-- Waste / Spoilage --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:border-rose-200 transition-all">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Spoilage</span>
                        <span class="block text-[15px] font-black text-rose-600 leading-none">-₱{{ number_format($performance['waste_cost'] ?? 0, 2) }}</span>
                    </div>
                </div>

                {{-- Average Ticket --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 group hover:border-blue-200 transition-all">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    </div>
                    <div>
                        <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest leading-none mb-1">Avg Ticket</span>
                        <span class="block text-[15px] font-black text-gray-900 leading-none">₱{{ number_format($performance['avg_order_value'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales Ledger Table --}}
        <div class="mx-1 overflow-x-auto">
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
                    <td class="py-3 px-4 border-r border-gray-100 text-right font-black text-[13px] text-gray-900 font-mono whitespace-nowrap">₱{{ number_format($order->total_amount, 2) }}</td>
                    <td class="py-3 px-4 border-r border-gray-100 text-center whitespace-nowrap">
                        @php
                            $statusColor = match($order->status) {
                                'Completed' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                'Refunded' => 'bg-rose-50 text-rose-700 border-rose-100',
                                'Partially Refunded' => 'bg-amber-50 text-amber-700 border-amber-100',
                                default => 'bg-gray-50 text-gray-700 border-gray-100'
                            };
                        @endphp
                        <span class="inline-flex px-2 py-0.5 rounded-lg border text-[9px] font-black uppercase tracking-widest {{ $statusColor }}">
                            {{ $order->status }}
                        </span>
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

    <div x-cloak x-show="activeTab === 'forecasting'" class="space-y-6 animate-fadeIn">

        {{-- Combined Dual-Line Forecast Chart (ApexCharts) --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm mx-1">
            @php
                $st = $forecasting['short_term'];
                $lt = $forecasting['long_term'];

                // Build combined X-axis labels: 7 day labels + 6 month labels
                $stLabels = collect($st['forecast'])->map(fn($p) => ($p['day'] ?? '') . ' ' . ($p['date'] ?? ''))->values()->toArray();
                $ltLabels = collect($lt['forecast'])->map(fn($p) => $p['date'] ?? '')->values()->toArray();
                $allLabels = array_merge($stLabels, $ltLabels);
                $totalCount = count($allLabels);

                // Short-term series: values for first 7 slots, null for remaining 6
                $stSeries = array_merge(
                    collect($st['forecast'])->map(fn($p) => round($p['predicted'], 2))->toArray(),
                    array_fill(0, count($ltLabels), null)
                );

                // Long-term series: null for first 7 slots, values for remaining 6
                $ltSeries = array_merge(
                    array_fill(0, count($stLabels), null),
                    collect($lt['forecast'])->map(fn($p) => round($p['predicted'], 2))->toArray()
                );
            @endphp

            {{-- Header --}}
            <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                <div>
                    <h3 class="text-[15px] font-bold text-gray-900 tracking-tight">Revenue Forecast</h3>
                    <p class="text-[12px] text-gray-500 font-medium italic">Linear regression model — 7-day demand + 6-month growth trajectory</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="w-6 border-t-2 border-indigo-500 inline-block"></span>
                        <span class="text-[11px] font-bold text-gray-600">Short-Term (7 Days)</span>
                        <span class="px-2 py-0.5 rounded-lg bg-indigo-50 border border-indigo-100 text-indigo-600 text-[9px] font-black uppercase tracking-widest">{{ $st['confidence'] }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-6 border-t-2 border-dashed border-emerald-500 inline-block"></span>
                        <span class="text-[11px] font-bold text-gray-600">Long-Term (6 Months)</span>
                        <span class="px-2 py-0.5 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-600 text-[9px] font-black uppercase tracking-widest">{{ $lt['confidence'] }}</span>
                    </div>
                </div>
            </div>

            @if(count($st['forecast']) > 0 || count($lt['forecast']) > 0)
                <div wire:ignore>
                    <div id="bi-forecast-chart"></div>
                </div>
            @else
                <x-empty-state title="Insufficient Data" description="Not enough sales history to generate a forecast model." />
            @endif
        </div>


        {{-- 3. Insights Row --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 px-1">
            {{-- Predicted Restock Insights --}}
            <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-8 opacity-5 group-hover:opacity-10 transition-opacity text-emerald-500">
                    <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-14L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                
                <div class="flex items-center justify-between mb-6 relative z-10">
                    <h4 class="text-[13px] font-black uppercase tracking-widest text-gray-900">Restock Insights (7d)</h4>
                    <span class="text-[9px] font-bold text-gray-500 italic">Demand Prediction</span>
                </div>

                <div class="space-y-3 relative z-10">
                    @forelse($forecasting['restock_insights'] as $ri)
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100 {{ $isActionable ? 'hover:bg-emerald-50 hover:border-emerald-200 cursor-pointer' : 'cursor-not-allowed opacity-60' }} transition-all"
                         @if($isActionable) wire:click="redirectToOrdering({{ $ri['id'] }})" @endif
                         @if(!$isActionable) title="Action not available when viewing 'All Branches'" @endif>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-white border border-gray-200 flex items-center justify-center text-emerald-600 shadow-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            </div>
                            <div>
                                <p class="text-[12px] font-bold text-gray-900">{{ $ri['name'] }}</p>
                                <p class="text-[10px] text-gray-500 font-medium">Need: <span class="text-gray-900 font-bold">{{ number_format($ri['amount'], 1) }} {{ $ri['unit'] }}</span></p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 rounded-md bg-{{ $ri['priority'] === 'High' ? 'rose' : 'emerald' }}-50 text-{{ $ri['priority'] === 'High' ? 'rose' : 'emerald' }}-600 border border-{{ $ri['priority'] === 'High' ? 'rose' : 'emerald' }}-200 text-[9px] font-black uppercase tracking-widest shadow-sm">
                            {{ $ri['priority'] }}
                        </span>
                    </div>
                    @empty
                    <div class="p-6 text-center bg-gray-50 rounded-2xl border border-gray-100">
                        <p class="text-[12px] font-bold text-gray-500">No Restock Data</p>
                        <p class="text-[10px] text-gray-400 mt-1">Not enough data to predict restocking needs.</p>
                    </div>
                    @endforelse
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 relative z-10">
                    <p class="text-[10px] text-gray-400 leading-relaxed italic">
                        * Estimates based on predicted sales of Top 5 products and recipe quantities.
                    </p>
                </div>
            </div>

            {{-- Growth Momentum --}}
            <div class="bg-white border border-gray-200 rounded-3xl p-6 shadow-sm flex flex-col relative overflow-hidden group">
                <div class="absolute -right-10 -bottom-10 opacity-5 group-hover:opacity-10 transition-opacity text-{{ $primaryColor }}-500">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
                
                <div class="flex items-center justify-between mb-6 relative z-10">
                    <h4 class="text-[13px] font-black uppercase tracking-widest text-gray-900">Performance Velocity</h4>
                    <div class="px-3 py-1 rounded-full bg-{{ $forecasting['short_term']['trend'] === 'Upward' ? 'emerald' : 'rose' }}-50 text-{{ $forecasting['short_term']['trend'] === 'Upward' ? 'emerald' : 'rose' }}-600 border border-current text-[9px] font-black uppercase tracking-widest">
                        {{ $forecasting['short_term']['trend'] }} Momentum
                    </div>
                </div>
                
                <div class="flex-1 flex flex-col justify-center relative z-10">
                    <span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Average Daily Change</span>
                    <div class="flex items-baseline gap-2 mb-8">
                        <span class="text-[40px] font-black text-gray-900 tracking-tighter leading-none">₱{{ number_format(abs($forecasting['short_term']['growth_rate']), 2) }}</span>
                        <span class="text-[12px] font-black text-gray-400 uppercase tracking-widest">/ day</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-auto">
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 hover:border-gray-200 transition-colors flex flex-col justify-center">
                            <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Baseline Avg</span>
                            <span class="text-[14px] font-black text-gray-900">₱{{ number_format($forecasting['short_term']['baseline_avg'] ?? 0, 0) }}</span>
                            <span class="text-[9px] text-gray-500 font-bold block mt-0.5">Last 7 active days</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 hover:border-gray-200 transition-colors flex flex-col justify-center">
                            <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Peak Prediction</span>
                            <span class="text-[14px] font-black text-emerald-600">{{ $forecasting['short_term']['peak_day'] ?? 'N/A' }}</span>
                            <span class="text-[9px] text-gray-500 font-bold block mt-0.5">Next highest day</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 hover:border-gray-200 transition-colors flex flex-col justify-center">
                            <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Monthly Outlook</span>
                            <span class="text-[14px] font-black text-gray-900">₱{{ number_format($forecasting['long_term']['growth_rate'], 0) }}</span>
                            <span class="text-[9px] text-gray-500 font-bold block mt-0.5">Avg change/mo</span>
                        </div>
                        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 hover:border-gray-200 transition-colors flex flex-col justify-center">
                            <span class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Strategic Score</span>
                            <span class="text-[14px] font-black text-gray-900">{{ $forecasting['long_term']['confidence'] }}</span>
                            <span class="text-[9px] text-gray-500 font-bold block mt-0.5">Model reliability</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Product Insights Tab ── --}}
    <div x-cloak x-show="activeTab === 'products'" class="space-y-6 animate-fadeIn">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 px-1">
            {{-- Star Products --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden flex flex-col">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-[14px] font-bold text-gray-900 tracking-tight">Star Products</h4>
                        <p class="text-[11px] text-gray-400 font-medium">Top performing items by volume</p>
                    </div>
                    <svg class="w-4 h-4 text-{{ $primaryColor }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.54 1.118l-3.976-2.888a1 1 0 00-1.175 0l-3.976 2.888c-.784.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.382-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </div>
                <div class="flex-1">
                    @forelse($productInsights['top_products'] as $product)
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
                            <span class="block text-[13px] font-black text-gray-900 leading-none mb-1">₱{{ number_format($product->revenue, 2) }}</span>
                            <span class="block text-[10px] font-black uppercase text-{{ $primaryColor }}-500 tracking-widest">Revenue</span>
                        </div>
                    </div>
                    @empty
                    <x-empty-state title="No Product Data" description="No sales recorded for the selected period." />
                    @endforelse
                </div>
            </div>

            {{-- Revenue Mix --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col translate-all">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h4 class="text-[14px] font-bold text-gray-900 tracking-tight">Revenue Mix</h4>
                    <p class="text-[11px] text-gray-400 font-medium">Distribution by category</p>
                </div>
                <div class="p-6 flex-1 space-y-6">
                    @php 
                        $totalRev = $productInsights['category_sales']->sum('revenue') ?: 1;
                    @endphp
                    @forelse($productInsights['category_sales'] as $cat)
                    <div class="space-y-2 group">
                        <div class="flex items-center justify-between text-[11px] font-black uppercase tracking-widest leading-none">
                            <span class="text-gray-500 group-hover:text-gray-900 transition-colors">{{ $cat->name }}</span>
                            <span class="text-gray-900">₱{{ number_format($cat->revenue, 2) }}</span>
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

        {{-- Product Seasonality (Weekly / Monthly) — unified chart --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mx-1 mt-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/30">
                <div>
                    <h4 class="text-[14px] font-bold text-gray-900 tracking-tight">Product Seasonality</h4>
                    <p class="text-[11px] text-gray-400 font-medium italic">
                        {{ $seasonalityMode === 'weekly' ? 'Units sold per day of week — top 3 products' : 'Units sold per month — top 3 products' }}
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
                        <div class="relative flex items-end border-l border-b border-gray-200 overflow-x-auto" style="height:200px">
                            {{-- Horizontal grid lines --}}
                            <div class="absolute inset-0 flex flex-col justify-between pointer-events-none">
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
    <div x-cloak x-show="activeTab === 'operations'" class="space-y-6 animate-fadeIn">
        {{-- Hourly Intensity Heatmap --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm mx-1">
            <h4 class="text-[14px] font-bold text-gray-900 tracking-tight mb-8">Sales Intensity (24h Heatmap)</h4>
            <div class="flex gap-3">
                {{-- Y-Axis Labels --}}
                @php 
                    $maxCount = $operations['hourly_sales']->max('count') ?: 1;
                    $yStepsH = 4;
                    $stepSizeH = ceil($maxCount / $yStepsH) ?: 1;
                    $yMaxH = $stepSizeH * $yStepsH;
                @endphp
                <div class="flex flex-col justify-between items-end h-48 pb-6 text-[9px] font-bold text-gray-400 select-none" style="min-width:34px">
                    @for($s = $yStepsH; $s >= 0; $s--)
                        <span>{{ number_format($stepSizeH * $s) }}</span>
                    @endfor
                </div>

                <div class="flex-1 flex flex-col">
                    <div class="relative h-48 w-full flex items-end gap-1.5 px-2 border-b border-l border-gray-100 pb-1">
                        {{-- Grid lines --}}
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none opacity-5 pr-4">
                            @for($s = $yStepsH; $s >= 1; $s--)
                                <div class="border-t border-gray-900 w-full"></div>
                            @endfor
                            <div></div>
                        </div>

                        @for($i=0; $i<24; $i++)
                            @php 
                                $hourData = $operations['hourly_sales']->firstWhere('hour', $i);
                                $count = $hourData->count ?? 0;
                                $hPct = $yMaxH > 0 ? ($count / $yMaxH) * 100 : 0;
                                $opacity = $count > 0 ? max(0.2, ($count / $maxCount)) : 0.05;
                            @endphp
                            <div class="flex-1 flex flex-col items-center group relative z-10 h-full justify-end">
                                <div class="w-full bg-{{ $primaryColor }}-500 rounded-t-sm transition-all group-hover:bg-{{ $primaryColor }}-600" 
                                     style="height: {{ $hPct }}%; opacity: {{ $opacity }}">
                                    <div class="opacity-0 group-hover:opacity-100 absolute -top-10 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-950 text-white text-[10px] font-black rounded-lg transition-all z-20 pointer-events-none whitespace-nowrap shadow-xl">
                                        {{ $i }}:00 — {{ $count }} Orders
                                    </div>
                                </div>
                                <span class="absolute -bottom-6 text-[8px] font-black text-gray-400 uppercase tracking-tighter whitespace-nowrap">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</span>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="h-6"></div>
        </div>

        {{-- Branch Comparison (Admin Only) --}}
        @if(auth()->user()->role_id === 1)
        <div class="mx-1 overflow-x-auto">
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
                    <td class="py-3 px-6 border-r border-gray-100 text-right font-black text-[13px] text-gray-900 font-mono whitespace-nowrap">₱{{ number_format($branch->revenue, 2) }}</td>
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
    <div x-cloak x-show="activeTab === 'sales'" class="space-y-6 animate-fadeIn">
        {{-- Sales Performance Trend --}}
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm mx-1">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h4 class="text-[14px] font-bold text-gray-900 tracking-tight">Sales Performance Trend</h4>
                    <p class="text-[11px] text-gray-400 font-medium">Daily revenue over the selected period</p>
                </div>
            </div>

            <div class="flex gap-3">
                @php
                    $trend = $salesData['trend'] ?? [];
                    $maxT = collect($trend)->max('value') ?: 1;
                    $yStepsT = 5;
                    $stepSizeT = ceil($maxT / $yStepsT) ?: 1;
                    $yMaxT = $stepSizeT * $yStepsT;
                @endphp
                {{-- Y-Axis --}}
                <div class="flex flex-col justify-between items-end pb-6 text-[9px] font-bold text-gray-400 select-none" style="min-width:44px; height:192px">
                    @for($s = $yStepsT; $s >= 0; $s--)
                        <span>₱{{ number_format($stepSizeT * $s) }}</span>
                    @endfor
                </div>

                {{-- Chart Area --}}
                <div class="flex-1 flex flex-col min-w-0">
                    <div class="relative h-48 flex items-end border-l border-b border-gray-200 overflow-hidden pb-0">
                        {{-- Grid lines --}}
                        <div class="absolute inset-0 flex flex-col justify-between pointer-events-none opacity-5 pr-4">
                            @for($s = $yStepsT; $s >= 1; $s--)
                                <div class="border-t border-gray-900 w-full"></div>
                            @endfor
                            <div></div>
                        </div>

                        {{-- Bars — flex-1 so each takes equal share of full width --}}
                        <div class="absolute inset-0 flex items-end px-1 gap-px">
                            @foreach($trend as $day)
                                @php $tPct = $yMaxT > 0 ? ($day['value'] / $yMaxT) * 100 : 0; @endphp
                                <div class="flex-1 group relative flex flex-col items-center justify-end h-full z-10">
                                    <div class="w-full bg-gradient-to-t from-emerald-600 to-emerald-400 rounded-t-sm transition-all group-hover:brightness-110 shadow-sm"
                                         style="height: {{ $tPct }}%">
                                        <div class="opacity-0 group-hover:opacity-100 absolute -top-10 left-1/2 -translate-x-1/2 px-2 py-1.5 bg-gray-900 text-white text-[10px] font-black rounded-lg transition-all z-20 pointer-events-none shadow-xl whitespace-nowrap">
                                            {{ $day['label'] }}: ₱{{ number_format($day['value'], 0) }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- X-Axis Labels (sampled to avoid overlap) --}}
                    <div class="flex px-1 gap-px mt-2">
                        @php $skip = count($trend) > 14 ? ceil(count($trend) / 14) : 1; @endphp
                        @foreach($trend as $idx => $day)
                            <div class="flex-1 text-center">
                                @if($idx % $skip == 0)
                                    <span class="text-[8px] font-black text-gray-500 uppercase tracking-tight leading-none whitespace-nowrap">
                                        {{ $day['label'] }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-1">

            {{-- Payment Methods + Order Sources --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h4 class="text-[13px] font-bold text-gray-900 mb-4 uppercase tracking-wide">Payment Methods</h4>
                    <div class="space-y-3">
                        @forelse($salesData['payment_methods'] as $pm)
                            <div class="flex items-center justify-between p-3 rounded-xl hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-{{ $primaryColor }}-50 flex items-center justify-center text-{{ $primaryColor }}-600 font-bold text-xs">{{ substr($pm->payment_method ?? '?', 0, 2) }}</div>
                                    <div>
                                        <p class="text-[13px] font-bold text-gray-900">{{ $pm->payment_method ?? 'Unknown' }}</p>
                                        <p class="text-[11px] text-gray-400 font-medium">{{ $pm->count }} transactions</p>
                                    </div>
                                </div>
                                <p class="text-[14px] font-black text-gray-900">₱{{ number_format($pm->total, 2) }}</p>
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
                                <h5 class="text-lg font-black text-gray-900 mt-1">₱{{ number_format($src->total, 2) }}</h5>
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
                            <span class="text-[13px] font-bold text-gray-900">₱{{ number_format($salesData['avg_order_value'], 2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h4 class="text-[13px] font-bold text-gray-900 mb-4 uppercase tracking-wide">Top Selling Items</h4>
                    <div class="space-y-4">
                        @forelse($salesData['top_items'] as $item)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $item->product->image_url ?? '' }}" alt="" class="w-9 h-9 rounded-xl object-cover bg-gray-100 border border-gray-200">
                                    <div>
                                        <p class="text-[12px] font-bold text-gray-900 line-clamp-1">{{ $item->product->name ?? 'Unknown' }}</p>
                                        <p class="text-[11px] text-gray-400 font-medium">{{ $item->total_quantity }} sold</p>
                                    </div>
                                </div>
                                <p class="text-[13px] font-bold text-gray-900">₱{{ number_format($item->total_sales, 2) }}</p>
                            </div>
                        @empty
                            <x-empty-state title="No items" description="" />
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var stSeries = @json($stSeries ?? []);
        var ltSeries = @json($ltSeries ?? []);
        var labels   = @json($allLabels ?? []);
        var stCount  = {{ count($stLabels ?? []) }};

        function initBiForecastChart() {
            var el = document.getElementById('bi-forecast-chart');
            if (!el || typeof window.ApexCharts === 'undefined') return;
            if (el._apexChart) { try { el._apexChart.destroy(); } catch(e) {} }

            var options = {
                series: [
                    { name: 'Demand (7 Days)',    data: stSeries },
                    { name: 'Growth (6 Months)', data: ltSeries },
                ],
                chart: {
                    height: 340,
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
                    },
                    dropShadow: {
                        enabled: true,
                        top: 10, left: 0, blur: 10,
                        color: '#6366f1',
                        opacity: 0.12
                    }
                },
                colors: ['#6366f1', '#10b981'],
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
                        opacityFrom: 0.40,
                        opacityTo: 0.01,
                        stops: [0, 90, 100]
                    }
                },
                markers: {
                    size: [4, 4],
                    strokeWidth: 2,
                    strokeColors: '#ffffff',
                    hover: { size: 6 }
                },
                xaxis: {
                    categories: labels,
                    tooltip: { enabled: false },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        rotate: -35,
                        rotateAlways: false,
                        hideOverlappingLabels: true,
                        style: { colors: '#9CA3AF', fontSize: '10px', fontWeight: 600 }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Revenue (₱)',
                        style: { fontSize: '11px', fontWeight: 600, color: '#6B7280' }
                    },
                    labels: {
                        style: { colors: '#9CA3AF', fontSize: '11px', fontWeight: 600 },
                        formatter: function(val) {
                            if (val === null || val === undefined) return '';
                            return val >= 1000 ? '₱' + (val/1000).toFixed(1) + 'k' : '₱' + Math.round(val);
                        }
                    }
                },
                grid: {
                    borderColor: '#f1f5f9',
                    strokeDashArray: 4,
                    padding: { top: 0, right: 15, bottom: 20, left: 15 }
                },
                annotations: {
                    xaxis: stCount > 0 ? [{
                        x: labels[stCount - 1],
                        borderColor: '#d1d5db',
                        borderWidth: 1.5,
                        strokeDashArray: 5,
                        label: {
                            text: '7D / 6M',
                            style: { color: '#9ca3af', fontSize: '10px', fontWeight: 700, background: 'transparent' },
                            position: 'top',
                            orientation: 'horizontal'
                        }
                    }] : []
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '12px',
                    fontWeight: 700,
                    markers: { radius: 12, width: 10, height: 10 }
                },
                tooltip: {
                    theme: 'dark',
                    x: { show: true },
                    y: {
                        formatter: function(val) {
                            if (val === null || val === undefined) return 'N/A';
                            return '₱ ' + val.toLocaleString();
                        }
                    },
                    style: { fontSize: '12px', fontFamily: 'Outfit' },
                    onDatasetHover: { highlightDataSeries: true },
                }
            };

            el._apexChart = new ApexCharts(el, options);
            el._apexChart.render();
        }

        // Init on load and re-init on Livewire navigation
        document.addEventListener('DOMContentLoaded', initBiForecastChart);
        document.addEventListener('livewire:navigated', initBiForecastChart);
        setTimeout(initBiForecastChart, 300); // fallback
    })();
    </script>
</div>
