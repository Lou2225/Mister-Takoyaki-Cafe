@php
    $user = auth()->user();
    $roleTheme = $user->getRoleTheme();
    $primaryColor = $roleTheme['primary'] ?? 'indigo';
    $primaryText = $roleTheme['text'] ?? 'text-indigo-600';
@endphp
<div
    x-data="slidingTabs($wire.entangle('activeTab').live, 'activeTab')"
    class="relative overflow-hidden">

    <div class="px-1" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0">
        {{-- Page title + actions --}}
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Business Reports</h2>
                <p class="text-[12px] text-gray-500 font-medium whitespace-nowrap overflow-hidden text-ellipsis">Consolidated analytics and <span class="{{ $primaryText }} font-bold">forecasting insights</span></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" refreshAction="applyQuickDateFilter" />
                <x-report-dropdown module="BI Report" />

                {{-- Branch Scope (Super Admin) --}}
                @if(auth()->user()->role_id === 1)
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button type="button" class="inline-flex items-center gap-2 px-4 py-1.5 text-[12px] font-medium text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-gray-300 focus:outline-none shadow-sm transition-colors h-10">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <span>{{ $selectedBranchId === 'all' ? 'All Branches' : ($branches->firstWhere('id', $selectedBranchId)?->branch_name ?? 'Select Branch') }}</span>
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
                <div wire:click="openBreakdown('Gross Revenue')" class="p-5 sm:p-6 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">Gross Revenue</span>
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 border border-indigo-100 group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱{{ number_format($performance['gross_sales'], 2) }}</h3>
                        <p class="text-[11px] text-slate-500 font-semibold mt-1">Consolidated overall gross inflow</p>
                    </div>
                </div>

                {{-- Net Sales --}}
                <div wire:click="openBreakdown('Net Sales')" class="p-5 sm:p-6 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">Net Sales</span>
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 border border-emerald-100 group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱{{ number_format($performance['net_sales'], 2) }}</h3>
                        <p class="text-[11px] text-slate-500 font-semibold mt-1">Excl. ₱{{ number_format($performance['delivery_fees'], 2) }} delivery</p>
                    </div>
                </div>

                {{-- Gross Profit --}}
                <div wire:click="openBreakdown('Gross Profit')" class="p-5 sm:p-6 bg-gradient-to-br from-violet-500/10 via-violet-500/5 to-white border border-violet-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">Gross Profit</span>
                            <div class="w-8 h-8 rounded-lg bg-violet-50 flex items-center justify-center text-violet-600 border border-violet-100 group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱{{ number_format($performance['gross_profit'], 2) }}</h3>
                        <p class="text-[11px] text-slate-500 font-semibold mt-1">Earnings margin after raw ingredient costs</p>
                    </div>
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

    <div x-cloak x-show="activeTab === 'forecasting'" class="space-y-6 animate-fadeIn">
        @php
            $fc = $forecasting ?? ['short_term' => ['forecast' => []], 'long_term' => ['forecast' => []], 'restock_insights' => []];
            $restock = $fc['restock_insights'] ?? [];
        @endphp

        <div class="grid grid-cols-1 xl:grid-cols-[1.75fr_1fr] gap-6">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4">
                    <div class="flex-1">
                        <h4 class="text-[15px] font-bold text-gray-900 tracking-tight">Forecasting Analytics</h4>
                        <p class="text-[11px] text-gray-400 font-medium">Short-term sales forecast for the selected period and branch.</p>
                    </div>

                    <div class="hidden sm:flex items-center gap-2">
                        <button x-on:click.prevent="$dispatch('set-forecast-metric', { metric: 'Volume' })" class="px-3 py-1 rounded-lg text-[11px] font-black uppercase tracking-widest bg-white shadow-sm">Volume</button>
                        <button x-on:click.prevent="$dispatch('set-forecast-metric', { metric: 'Profit' })" class="px-3 py-1 rounded-lg text-[11px] font-black uppercase tracking-widest bg-white shadow-sm">Profit</button>
                    </div>
                </div>

                {{-- Forecast summary: trend / confidence / baseline vs predicted --}}
                @php
                    $summary = $fc['short_term'] ?? ['trend'=>'N/A','growth_rate'=>0,'confidence'=>'Low','baseline_avg'=>0];
                    $predicted_sum = collect($fc['short_term']['forecast'] ?? [])->sum('predicted');
                    $baseline_week = ($summary['baseline_avg'] ?? 0) * 7;
                @endphp
                <div class="mb-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="bg-gray-50/50 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center border border-gray-100">
                            <svg class="w-5 h-5 text-{{ $primaryColor }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18"/></svg>
                        </div>
                        <div>
                            <div class="text-[11px] text-gray-500">Trend</div>
                            <div class="text-[14px] font-black text-gray-900">{{ $summary['trend'] ?? 'N/A' }} <span class="text-[12px] font-bold text-gray-400">({{ $summary['confidence'] ?? 'Low' }})</span></div>
                        </div>
                    </div>

                    <div class="bg-gray-50/50 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center border border-gray-100">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m4-4H8"/></svg>
                        </div>
                        <div>
                            <div class="text-[11px] text-gray-500">Baseline vs Predicted (7d)</div>
                            <div class="text-[14px] font-black text-gray-900">₱{{ number_format($baseline_week,0) }} → ₱{{ number_format($predicted_sum,0) }}</div>
                        </div>
                    </div>

                    <div class="bg-gray-50/50 rounded-xl p-3 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center border border-gray-100">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <div class="text-[11px] text-gray-500">Growth Rate</div>
                            <div class="text-[14px] font-black text-gray-900">{{ round($summary['growth_rate'] ?? 0,2) }}</div>
                        </div>
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

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div>
                        <h4 class="text-[15px] font-bold text-gray-900 tracking-tight">Restock Insight</h4>
                        <p class="text-[11px] text-gray-400 font-medium">Ingredient demand recommendations based on the forecasted sales trend.</p>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-{{ $primaryColor }}-50 text-{{ $primaryColor }}-700 text-[10px] font-black uppercase tracking-widest px-2.5 py-1">
                        Forecast-driven
                    </span>
                </div>

                @if(count($restock) > 0)
                    <div class="space-y-3">
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
                                <div class="text-right">
                                    <p class="text-[16px] font-black text-gray-900">{{ number_format($item['amount'], 0) }}</p>
                                    <p class="text-[10px] uppercase text-gray-400 tracking-widest">Next 14 days</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6">
                        <x-empty-state title="No restock insight" description="Not enough historical order data to generate ingredient demand recommendations." />
                    </div>
                @endif
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
                        <div x-ref="hoursChartTop" class="w-full h-[320px]"></div>
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

    {{-- ─── Breakdown Side Panel ────────────────────────────────────────── --}}
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
                                            Σ (Sales Price × Qty)
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
                    @if(!empty($gpData))
                        <div class="space-y-4">
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-indigo-600 uppercase tracking-widest block mb-1">Net Sales inflow</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">₱ {{ number_format($gpData['net_sales'] ?? 0, 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Total Net</span>
                                </div>
                            </div>
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-rose-600 uppercase tracking-widest block mb-1">Ingredient Cost (COGS)</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">- ₱ {{ number_format($gpData['total_cogs'] ?? 0, 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Raw Expense</span>
                                </div>
                            </div>
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-amber-600 uppercase tracking-widest block mb-1">Waste & Spoilage Cost</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">- ₱ {{ number_format($gpData['waste_cost'] ?? 0, 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Spoiled Raw</span>
                                </div>
                            </div>
                            <div class="mt-6 pt-6 border-t border-dashed border-slate-200">
                                <div class="flex justify-between items-end bg-slate-900 p-6 rounded-2xl shadow-xl shadow-slate-200 overflow-hidden relative">
                                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-bl-full -mr-16 -mt-16"></div>
                                    <div class="relative z-10">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block mb-1">Gross Margin Result</span>
                                        <p class="text-3xl font-black text-white">₱ {{ number_format($gpData['gross_profit'] ?? 0, 2) }}</p>
                                    </div>
                                    <div class="relative z-10 text-right">
                                        <span class="px-3 py-1.5 rounded-xl bg-white/10 text-white text-[14px] font-black backdrop-blur-md">{{ number_format($gpData['profit_margin_pct'] ?? 0, 1) }}%</span>
                                        <p class="text-[9px] font-black text-slate-500 uppercase mt-2">Margin</p>
                                    </div>
                                </div>
                            </div>
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
                                            ₱ {{ number_format($item['total'], 2) }}
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
                                enabled: true,
                                easing: 'easeinout',
                                speed: 800,
                                animateGradually: { enabled: false },
                                dynamicAnimation: { enabled: false }
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
                                        return '₱' + (val / 1000).toFixed(1) + 'k';
                                    }
                                    return '₱' + val.toFixed(0);
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
                                formatter: (val) => '₱ ' + Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
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

                    const el = this.$refs.hoursChartTop ?? this.$refs.hoursChart;
                    if (!el) return;

                    const options = {
                        series: [{ name: 'Orders', data: this.chartData.counts }],
                        chart: {
                            type: 'area',
                            height: 320,
                            toolbar: { show: false },
                            zoom: { enabled: false }
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

                    // small heatmap strip to show color intensity per hour
                    try {
                        const heatEl = this.$refs.hoursHeatmapTop;
                        if (heatEl && Array.isArray(this.chartData.counts)) {
                            const counts = this.chartData.counts.map(v => Number(v) || 0);
                            const cats = this.chartData.categories || [];
                            const max = counts.length ? Math.max(...counts) : 0;

                            const heatSeries = [{ name: 'Intensity', data: cats.map((c, i) => ({ x: c, y: counts[i] ?? 0 })) }];

                            const ranges = [];
                            if (max > 0) {
                                const q1 = Math.ceil(max * 0.25);
                                const q2 = Math.ceil(max * 0.5);
                                const q3 = Math.ceil(max * 0.75);
                                ranges.push({ from: 0, to: q1, color: '#d1fae5' });
                                ranges.push({ from: q1+1, to: q2, color: '#86efac' });
                                ranges.push({ from: q2+1, to: q3, color: '#16a34a' });
                                ranges.push({ from: q3+1, to: max, color: '#065f46' });
                            } else {
                                ranges.push({ from: 0, to: 1, color: '#d1fae5' });
                            }

                            const heatOpts = {
                                series: heatSeries,
                                chart: { type: 'heatmap', height: 48, toolbar: { show: false } },
                                plotOptions: { heatmap: { radius: 4, enableShades: false, useFillColorAsStroke: false } },
                                dataLabels: { enabled: false },
                                legend: { show: false },
                                tooltip: { y: { formatter: val => `${val} orders` } },
                                grid: { padding: { top: 0, bottom: 0, left: 0, right: 0 } },
                                xaxis: { labels: { show: false }, axisTicks: { show: false }, axisBorder: { show: false } },
                                yaxis: { show: false },
                                fill: { opacity: 1 },
                                states: { hover: { filter: { type: 'none' } } },
                                colorScale: { ranges }
                            };

                            const heatChart = new ApexCharts(heatEl, heatOpts);
                            heatChart.render();
                        }
                    } catch (e) {
                        console.error('Heatmap render error', e);
                    }
                }
            };
        }

        function forecastingChart(initialData) {
            return {
                chartRef: null,
                chartData: initialData || { categories: [], predicted: [] },

                init() {
                    if (typeof window.ApexCharts === 'undefined' || !this.$refs.forecastChart) return;

                    const options = {
                        series: [{ name: 'Forecast', data: this.chartData.predicted }],
                        chart: {
                            type: 'area',
                            height: 320,
                            toolbar: { show: false },
                            zoom: { enabled: false }
                        },
                        dataLabels: { enabled: false },
                        stroke: { curve: 'smooth', width: 3 },
                        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.03, stops: [0,80,100] } },
                        xaxis: { categories: this.chartData.categories, labels: { rotate: -45, style: { fontSize: '11px', colors: '#9CA3AF' } } },
                        yaxis: { labels: { formatter: (val) => Math.round(val) } },
                        colors: ['#6366F1'],
                        tooltip: { y: { formatter: (val) => '₱ ' + Number(val).toLocaleString() } },
                        grid: { borderColor: '#f8fafc' }
                    };

                    this.chartRef = new ApexCharts(this.$refs.forecastChart, options);
                    this.chartRef.render();
                }
            };
        }
    </script>
    @endpush
</div>
