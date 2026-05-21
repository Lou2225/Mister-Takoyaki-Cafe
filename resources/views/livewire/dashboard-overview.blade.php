<div wire:poll.10s>
    <!-- ApexCharts Library -->


    {{-- ─── RBAC Welcome Banner + Filters ─────────────────────────── --}}
    @php
        $user         = auth()->user();
        $userName     = $user->name ?? 'User';
        $firstName    = explode(' ', $userName)[0];
        $hour         = (int) now('Asia/Manila')->format('H');
        $greeting     = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
        
        // Fetch centralized RBAC theme
        $roleTheme    = $user->getRoleTheme();
        $bannerConfig = $roleTheme;
    @endphp


    <div
        x-data="{ show: true }"
        wire:ignore.self
        wire:key="welcome-banner-wrapper"
    >
        {{-- Welcome Banner --}}
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 -translate-y-4 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 -translate-y-4 scale-[0.98]"
            class="mb-4 transition-all duration-500 overflow-hidden"
        >
            <div
                class="relative overflow-hidden rounded-2xl ring-1 {{ $bannerConfig['ring'] }} shadow-xl bg-gradient-to-br {{ $bannerConfig['gradient'] }}"
            >
                {{-- Decorative Background Glow --}}
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-white/5 blur-3xl"></div>
                    <div class="absolute -bottom-10 -left-10 w-48 h-48 rounded-full bg-white/5 blur-3xl"></div>
                    <div class="absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
                </div>

                {{-- Content --}}
                <div class="relative px-4 py-4 sm:px-6 sm:py-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        {{-- Role Icon --}}
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl {{ $bannerConfig['icon_bg'] }} flex items-center justify-center flex-shrink-0 shadow-inner border border-white/10">
                            <svg class="w-6 h-6 {{ $bannerConfig['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $bannerConfig['icon_path'] }}"/>
                            </svg>
                        </div>

                        <div>
                            {{-- Role Badge --}}
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-[0.18em] mb-1.5 {{ $bannerConfig['badge_bg'] }}">
                                {{ $bannerConfig['badge_text'] }}
                            </span>
                            {{-- Greeting --}}
                            <h2 class="text-[17px] sm:text-[20px] font-black text-white leading-tight {{ $bannerConfig['header_track'] ?? 'tracking-tight' }}">
                                {{ $greeting }}, <span class="text-white/90">{{ $firstName }}.</span>
                            </h2>
                            <p class="text-[12px] font-medium {{ $bannerConfig['sub_color'] }} mt-0.5 {{ $bannerConfig['header_track'] ?? 'tracking-tight' }}">
                                {{ $bannerConfig['tagline'] }}
                            </p>
                        </div>
                    </div>

                    {{-- Dismiss (Optional) --}}
                    <button @click="show = false" class="text-white/20 hover:text-white/60 transition-colors p-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ─── Unified Control Panel (Branch & Integrated 3-in-1 Date Dropdown) ─────────────── --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 p-4 sm:p-0 bg-gray-50/50 sm:bg-transparent rounded-2xl border border-gray-100 sm:border-0 select-none">
            
            {{-- Primary Branch Selection --}}
            <div class="w-full sm:w-auto">
                <div class="w-full sm:w-max sm:min-w-[250px] sm:max-w-md min-w-0">
                    @if($isSuperAdmin)
                        <x-dropdown align="left" width="96" wire:key="dashboard-branch-filter" containerClasses="w-full">
                            <x-slot name="trigger">
                                <x-secondary-button type="button" class="gap-2 w-full justify-between sm:justify-start min-h-[2.5rem] h-auto py-2">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        <span class="whitespace-normal text-left leading-snug text-[13px] sm:text-[14px]">{{ $branches->firstWhere('id', $selectedBranchId)?->branch_name ?? 'Enterprise Overview' }}</span>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                </x-secondary-button>
                            </x-slot>
                            <x-slot name="content">
                                <div class="max-h-[60vh] overflow-y-auto custom-scrollbar">
                                    <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', null)" class="whitespace-normal">
                                        <span class="font-bold text-indigo-600">All Locations (Global)</span>
                                    </x-dropdown-link>
                                    <div class="border-t border-gray-100 my-1"></div>
                                    @foreach($branches as $branch)
                                        <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', {{ $branch->id }})" class="whitespace-normal leading-snug py-2.5">
                                            {{ $branch->branch_name }}
                                        </x-dropdown-link>
                                    @endforeach
                                </div>
                            </x-slot>
                        </x-dropdown>
                    @else
                        @php $adminBranch = auth()->user()->branch; @endphp
                        @if($adminBranch)
                        <div class="inline-flex items-center gap-2 px-4 py-2 text-[12px] font-medium text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm min-h-[2.5rem] w-full sm:w-auto sm:min-w-[250px] sm:max-w-md">
                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            <span class="text-left leading-snug whitespace-normal text-[13px] sm:text-[14px]">{{ $adminBranch->branch_name }}</span>
                        </div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- 3-in-1 Datepicker & Report Dropdown --}}
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" refreshAction="refreshChart" />
                <x-report-dropdown module="Dashboard" />
            </div>

        </div>
    </div>


    {{-- Date Validation Error --}}
    @if($dateError)
        <div class="mb-4 flex items-start gap-3 p-3 bg-red-50 border border-red-200 rounded-lg">
            <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="text-[12px] font-bold text-red-800">{{ $dateError }}</p>
        </div>
    @endif

    <div class="space-y-6">

        {{-- ─── Top Dashboard Grid (KPIs 2/3 and Operations Index 1/3) ─────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6 sm:mb-8">
            
            {{-- Left 2/3: Financial Intelligence KPIs (Gross Revenue, Net Profit, and Row 2 Sub-metrics) --}}
            <div class="lg:col-span-8 flex flex-col justify-between space-y-6">
                
                {{-- Row 1: Gross Sales & Net Profit --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Card 1: Gross Sales (Hero Card) --}}
                    <div wire:click="openBreakdown('Revenue')" class="p-5 sm:p-6 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">Gross Revenue</span>
                            <span class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 transition-colors">View breakdown &rarr;</span>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex-grow">
                                <h3 class="text-3xl font-black text-slate-900 tracking-tight leading-tight">₱ {{ number_format($kpi['gross_sales'], 2) }}</h3>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <p class="text-[11px] sm:text-[12px] font-semibold text-slate-500">₱{{ number_format($kpi['total_discounts'], 2) }} Discounts Applied</p>
                                </div>
                            </div>
                            {{-- Sparkline chart --}}
                            <div class="w-[140px] sm:w-[160px] h-[55px] shrink-0" wire:ignore>
                                <div x-ref="sparklineSales" data-height="55"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Net Earnings / Profit & Loss (Cashflow Card) --}}
                    <div wire:click="openBreakdown('Profit')" class="p-5 sm:p-6 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                        <div class="flex flex-col justify-between h-full">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">Net Profit</span>
                                    <span class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 transition-colors">View &rarr;</span>
                                </div>
                                <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱ {{ number_format($kpi['gross_profit'], 2) }}</h3>
                                <p class="text-[11px] text-slate-500 font-semibold mt-0.5">Net profit margin of {{ $kpi['profit_margin_pct'] }}%</p>
                            </div>

                            {{-- Double Indicators (Inflow & Outflow) --}}
                            <div class="mt-4 pt-3 border-t border-slate-100 space-y-3">
                                {{-- Revenue Intake (Inflow) --}}
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-[11px] font-bold">
                                        <span class="text-emerald-600 uppercase tracking-wider flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Sales intake
                                        </span>
                                        <span class="text-slate-700">₱ {{ number_format($kpi['net_sales'], 2) }}</span>
                                    </div>
                                    <div class="h-1.5 w-full bg-emerald-500 rounded-full"></div>
                                </div>

                                {{-- Spoilage & Costs (Outflow) --}}
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-[11px] font-bold">
                                        <span class="text-rose-600 uppercase tracking-wider flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            COGS & Spoilage
                                        </span>
                                        <span class="text-slate-700">₱ {{ number_format($kpi['total_cogs'] + ($kpi['waste_cost'] ?? 0), 2) }}</span>
                                    </div>
                                    {{-- Premium Dash Segmented Progress Bar --}}
                                    <div class="flex gap-0.5 w-full">
                                        @for($i = 0; $i < 12; $i++)
                                            <span class="h-1.5 flex-1 bg-rose-500 rounded-sm opacity-90 animate-pulse" style="animation-delay: {{ $i * 100 }}ms"></span>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Sparkline cards grid (Net Sales, AOV, Resource Costs) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {{-- Card 3: Net Sales --}}
                    <div wire:click="openBreakdown('Revenue')" class="p-5 sm:p-6 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">Net Sales</span>
                                <span class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 transition-colors">View</span>
                            </div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱ {{ number_format($kpi['net_sales'], 2) }}</h3>
                            <p class="text-[11px] text-slate-500 font-semibold mt-0.5">Excl. ₱{{ number_format($kpi['delivery_fees'], 2) }} delivery</p>
                        </div>
                        {{-- Small Sparkline bottom --}}
                        <div class="w-full h-[35px] mt-4" wire:ignore>
                            <div x-ref="sparklineNetSales" data-height="35"></div>
                        </div>
                    </div>

                    {{-- Card 4: Avg. Order Value (AOV) --}}
                    <div wire:click="openBreakdown('AOV')" class="p-5 sm:p-6 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">Avg. Order Value</span>
                                <span class="text-[11px] font-bold text-indigo-600 hover:text-indigo-700 transition-colors">View</span>
                            </div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱ {{ number_format($kpi['aov'], 2) }}</h3>
                            <p class="text-[11px] text-slate-500 font-semibold mt-0.5">Sales intensity per ticket</p>
                        </div>
                        {{-- Small Sparkline bottom --}}
                        <div class="w-full h-[35px] mt-4" wire:ignore>
                            <div x-ref="sparklineAov" data-height="35"></div>
                        </div>
                    </div>

                    {{-- Card 5: Resource Costs (COGS) --}}
                    <div wire:click="openBreakdown('COGS')" class="p-5 sm:p-6 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex flex-col justify-between relative overflow-hidden group">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-[12px] font-bold text-slate-400 uppercase tracking-wider">Resource Costs</span>
                                <span class="text-[11px] font-bold text-rose-600 hover:text-rose-700 transition-colors">View</span>
                            </div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight">₱ {{ number_format($kpi['total_cogs'], 2) }}</h3>
                            <p class="text-[11px] text-slate-500 font-semibold mt-0.5">Raw material consumption</p>
                        </div>
                        {{-- Small Sparkline bottom --}}
                        <div class="w-full h-[35px] mt-4" wire:ignore>
                            <div x-ref="sparklineCogs" data-height="35"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right 1/3: Operations Index Card --}}
            <div class="lg:col-span-4 rounded-2xl bg-white border border-gray-200 shadow-sm p-6 flex flex-col justify-between h-full">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-[14px] font-bold text-gray-900 uppercase tracking-wider">Operations Index</h2>
                    <div class="w-8 h-8 rounded bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16m-7 6h7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-y-8 gap-x-6 flex-grow">
                    {{-- Master Catalog --}}
                    <div class="group">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Master Catalog</span>
                        <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ $kpi['active_products'] }}</h3>
                        <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">Active Items</p>
                    </div>

                    {{-- Materials --}}
                    <div class="group">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Materials</span>
                        <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ $kpi['total_ingredients'] }}</h3>
                        <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">Raw Resources</p>
                    </div>

                    {{-- System Alerts --}}
                    <div class="group">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">System Alerts</span>
                        <h3 @class(['text-3xl font-black tracking-tighter', ($kpi['low_stock_count'] > 0 || count($expirationAlerts) > 0) ? 'text-red-600' : 'text-gray-900'])>
                            {{ $kpi['low_stock_count'] + count($expirationAlerts) }}
                        </h3>
                        <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">Stock Threats</p>
                    </div>

                    {{-- Workforce --}}
                    <div class="group">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Workforce</span>
                        <div class="flex items-baseline gap-1">
                            <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ explode(' / ', $kpi['staff_active'])[0] }}</h3>
                            <span class="text-[12px] font-bold text-gray-300">/ {{ explode(' / ', $kpi['staff_active'])[1] }}</span>
                        </div>
                        <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">On-Duty Staff</p>
                    </div>
                </div>
            </div>
            
        </div>

        {{-- ─── Breakdown Side Panel ────────────────────────────────────────── --}}
        <x-side-panel wire:model.live="showBreakdown" name="kpi-breakdown" width="max-w-md">
            <div class="flex flex-col h-full bg-white">
                {{-- Premium Header --}}
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white shadow-lg shadow-slate-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div>
                            <h3 class="text-[15px] font-black text-slate-900 tracking-tight leading-none">Intelligence Report</h3>
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
                                            @if($selectedMetric === 'Revenue')
                                                Σ (Sales Price × Qty)
                                            @elseif($selectedMetric === 'COGS')
                                                Σ (Inventory Used × Unit Cost)
                                            @elseif($selectedMetric === 'AOV')
                                                Total Revenue ÷ Order Count
                                            @elseif($selectedMetric === 'Profit')
                                                Revenue - Total Costs (COGS)
                                            @elseif($selectedMetric === 'Margin')
                                                (Net Profit ÷ Revenue) × 100
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
                    <div class="flex items-center justify-between mb-8">
                        <h5 class="text-[11px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                            Performance Breakdown
                        </h5>
                        <span class="text-[10px] font-bold text-slate-400 uppercase bg-slate-50 px-2 py-1 rounded-md border border-slate-100">{{ count($breakdownData) }} Data Nodes</span>
                    </div>

                    @if($selectedMetric === 'Profit')
                        <div class="space-y-4">
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest block mb-1">Total Revenue inflow</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">₱ {{ number_format($kpi['revenue'], 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Gross</span>
                                </div>
                            </div>
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-rose-600 uppercase tracking-widest block mb-1">Resource Consumption</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">- ₱ {{ number_format($kpi['total_cogs'], 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Total Costs</span>
                                </div>
                            </div>
                            <div class="p-5 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <span class="text-[10px] font-black text-amber-600 uppercase tracking-widest block mb-1">Variance & Spoilage</span>
                                <div class="flex items-baseline gap-2">
                                    <p class="text-2xl font-black text-slate-900">- ₱ {{ number_format($kpi['waste_cost'] ?? 0, 2) }}</p>
                                    <span class="text-[10px] font-bold text-slate-400">Loss</span>
                                </div>
                            </div>
                            <div class="mt-6 pt-6 border-t border-dashed border-slate-200">
                                <div class="flex justify-between items-end bg-slate-900 p-6 rounded-2xl shadow-xl shadow-slate-200 overflow-hidden relative">
                                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-bl-full -mr-16 -mt-16"></div>
                                    <div class="relative z-10">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] block mb-1">Net Earnings Result</span>
                                        <p class="text-3xl font-black text-white">₱ {{ number_format($kpi['gross_profit'], 2) }}</p>
                                    </div>
                                    <div class="relative z-10 text-right">
                                        <span class="px-3 py-1.5 rounded-xl bg-white/10 text-white text-[14px] font-black backdrop-blur-md">{{ $kpi['profit_margin_pct'] }}%</span>
                                        <p class="text-[9px] font-black text-slate-500 uppercase mt-2">Margin</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="space-y-6 relative">
                            {{-- Vertical line --}}
                            <div class="absolute left-[7px] top-2 bottom-2 w-[1px] bg-slate-100"></div>

                            @forelse($breakdownData as $item)
                                <div class="relative pl-8 group">
                                    {{-- Dot --}}
                                    <div class="absolute left-0 top-[6px] w-[16px] h-[16px] rounded-full border-[3px] border-white bg-slate-100 group-hover:bg-slate-900 group-hover:scale-110 transition-all duration-300 z-10 shadow-sm"></div>
                                    
                                    <div class="flex items-center justify-between p-4 rounded-2xl border border-transparent hover:border-slate-100 hover:bg-slate-50/50 transition-all duration-300">
                                        <div>
                                            <h4 class="text-[14px] font-bold text-slate-900 group-hover:text-indigo-600 transition-colors">
                                                {{ $item['category'] ?? $item['name'] ?? $item['bucket'] }}
                                            </h4>
                                            @isset($item['count'])
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ $item['count'] }} Volume</p>
                                                </div>
                                            @endisset
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[14px] font-black text-slate-900">
                                                @if(isset($item['is_percentage']) && $item['is_percentage'])
                                                    {{ number_format($item['total'], 2) }}%
                                                @elseif($selectedMetric === 'Volume')
                                                    {{ number_format($item['total'], 0) }}
                                                @elseif(isset($item['total']))
                                                    ₱ {{ number_format($item['total'], 2) }}
                                                @else
                                                    {{ $item['count'] ?? '' }}
                                                @endif
                                            </p>
                                            <p class="text-[9px] font-black text-slate-400 uppercase mt-0.5 tracking-tighter">
                                                @if($selectedMetric === 'Margin') Efficiency @elseif($selectedMetric === 'AOV') Volume Share @else Contribution @endif
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

        {{-- ─── Main Analytical Layer ────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-6" x-data="dashboardCharts()" @update-sales-chart.window="handleChartUpdate($event.detail)" wire:ignore.self wire:key="dashboard-charts-container">
            {{-- 1: Financial Overview (Line Chart) --}}
            <div class="lg:col-span-8 rounded-2xl bg-white border border-gray-200/80 shadow-sm p-5 sm:p-6 overflow-hidden h-[400px] relative flex flex-col justify-between">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-4 sm:mb-6">
                    <div>
                        <h2 class="text-[16px] font-bold text-gray-900 tracking-tight">
                            Spend & Revenue Activity
                        </h2>
                        <p class="text-[11px] text-gray-400 font-medium">Financial performance and raw material spend trends</p>
                    </div>

                    <div class="flex items-center gap-3 self-end sm:self-auto overflow-x-auto max-w-full pb-1 sm:pb-0">
                        {{-- Metric selector --}}
                        <div class="flex items-center p-0.5 bg-gray-50 rounded-lg border border-gray-100">
                            @foreach(['Sales' => 'Sales', 'Volume' => 'Vol', 'Profit' => 'Profit'] as $m => $label)
                                <button @click="updateMetricLocal('{{ $m }}')"
                                        :class="selectedMetric === '{{ $m }}' ? 'bg-white text-gray-900 shadow-sm border border-gray-100 scale-[1.02]' : 'text-gray-400 hover:text-gray-600'"
                                        class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider transition-all duration-200">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div wire:ignore class="relative flex-1 flex flex-col justify-end">
                    <div x-ref="salesChart" class="w-full"></div>
                </div>
            </div>

            {{-- 2: Branch Pin Locations Map Card (1/3 width) --}}
            <div class="lg:col-span-4 rounded-2xl bg-white border border-gray-200/80 shadow-sm p-5 sm:p-6 flex flex-col justify-between h-[400px] overflow-hidden" 
                 x-data='window.branchMapWidget(@json($branchMapData))'>
                 
                <!-- Leaflet assets loaded globally in app layout -->
                
                <style>
                    .leaflet-tooltip-premium {
                        background: rgba(15, 23, 42, 0.95) !important;
                        border: 1px solid rgba(51, 65, 85, 0.5) !important;
                        border-radius: 12px !important;
                        padding: 8px 12px !important;
                        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.5), 0 8px 10px -6px rgb(0 0 0 / 0.5) !important;
                        color: white !important;
                    }
                    .leaflet-tooltip-premium::before {
                        border-top-color: rgba(15, 23, 42, 0.95) !important;
                    }
                    .custom-leaflet-icon {
                        background: transparent !important;
                        border: none !important;
                    }
                    /* Clean leaflet attribution & UI items */
                    .leaflet-container {
                        font-family: inherit;
                    }
                    /* Premium map view dropdown style */
                    .custom-scrollbar::-webkit-scrollbar {
                        width: 4px;
                    }
                    .custom-scrollbar::-webkit-scrollbar-track {
                        background: transparent;
                    }
                    .custom-scrollbar::-webkit-scrollbar-thumb {
                        background: #cbd5e1;
                        border-radius: 4px;
                    }
                </style>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <h2 class="text-[14px] font-bold text-gray-900 tracking-tight">Branch Live Map</h2>
                        
                        {{-- Dropdown Switcher --}}
                        <div class="relative" @click.away="mapModeOpen = false">
                            <button @click="mapModeOpen = !mapModeOpen" 
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-gray-50 hover:bg-gray-100 border border-gray-200 text-[10px] font-black text-gray-700 uppercase tracking-wider transition-all select-none">
                                <span x-text="mapMode === 'satellite' ? 'Satellite' : (mapMode === 'street' ? 'Street View' : 'Vector View')"></span>
                                <svg class="w-3 h-3 text-gray-500 transition-transform duration-200" :class="mapModeOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            {{-- Dropdown Panel --}}
                            <div x-show="mapModeOpen" 
                                 x-transition:enter="transition ease-out duration-100" 
                                 x-transition:enter-start="transform opacity-0 scale-95" 
                                 x-transition:enter-end="transform opacity-100 scale-100" 
                                 x-transition:leave="transition ease-in duration-75" 
                                 x-transition:leave-start="transform opacity-100 scale-100" 
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-0 mt-1.5 w-32 rounded-xl bg-white border border-gray-200 shadow-xl z-50 overflow-hidden py-1"
                                 style="display: none;">
                                
                                <button @click="mapMode = 'satellite'; mapModeOpen = false" 
                                        :class="mapMode === 'satellite' ? 'bg-slate-50 text-gray-900 font-extrabold' : 'text-gray-600 hover:bg-slate-50 font-semibold'" 
                                        class="w-full text-left px-3 py-1.5 text-[10px] uppercase tracking-wider transition-colors">
                                    Satellite
                                </button>
                                <button @click="mapMode = 'street'; mapModeOpen = false" 
                                        :class="mapMode === 'street' ? 'bg-slate-50 text-gray-900 font-extrabold' : 'text-gray-600 hover:bg-slate-50 font-semibold'" 
                                        class="w-full text-left px-3 py-1.5 text-[10px] uppercase tracking-wider transition-colors">
                                    Street View
                                </button>
                                <button @click="mapMode = 'vector'; mapModeOpen = false" 
                                        :class="mapMode === 'vector' ? 'bg-slate-50 text-gray-900 font-extrabold' : 'text-gray-600 hover:bg-slate-50 font-semibold'" 
                                        class="w-full text-left px-3 py-1.5 text-[10px] uppercase tracking-wider transition-colors">
                                    Vector View
                                </button>
                            </div>
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 font-medium mb-3">Laguna Branch network sales footprint</p>
                </div>

                {{-- Leaflet Real Map Canvas --}}
                <div class="relative w-full h-[180px] rounded-xl overflow-hidden shadow-inner border border-slate-200/80" wire:ignore>
                    <div id="branchMap" class="w-full h-full z-0"></div>
                </div>

                {{-- Branch Performance Leaderboard --}}
                <div class="space-y-2 mt-3 pt-3 border-t border-slate-100 flex-grow flex flex-col justify-between overflow-y-auto custom-scrollbar">
                    @forelse($branchMapData as $branch)
                        <div class="flex justify-between items-center text-[11px]">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full {{ $branch['is_highest'] ? 'bg-emerald-500 animate-pulse' : $branch['color'] }}"></span>
                                <span class="font-bold {{ $branch['is_highest'] ? 'text-gray-900' : 'text-gray-500' }}">
                                    {{ $branch['clean_name'] }}
                                    @if($branch['is_highest'])
                                        <span class="text-emerald-600 text-[10px] font-black ml-1">★</span>
                                    @endif
                                </span>
                            </div>
                            <span class="font-black text-gray-900">
                                ₱{{ number_format($branch['total_sales'], 2) }}
                                <span class="text-[9px] text-gray-400 font-medium ml-1">({{ $branch['sales_pct'] }}%)</span>
                            </span>
                        </div>
                    @empty
                        <div class="flex items-center justify-center h-full py-4 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wider">No dynamic branch sales</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ─── Secondary Analytical Grid ────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- 1: Revenue Channels (Dynamic from POS Config) --}}
            <div class="lg:col-span-4 rounded-xl bg-white border border-gray-200 shadow-sm p-6 flex flex-col h-[400px]">
                <h2 class="text-[14px] font-black text-gray-900 uppercase tracking-widest mb-6">Revenue Channels</h2>
                <div class="flex-1 flex flex-col justify-center space-y-6">
                    @php
                        $totalOrders = array_sum($chart['channels']['series']);
                        $channelLabels = $chart['channels']['labels'];
                        $channelSeries = $chart['channels']['series'];
                        $channelValues = array_map(function($val) use ($totalOrders) {
                            return $totalOrders > 0 ? round(($val / $totalOrders) * 100, 1) : 0;
                        }, $channelSeries);
                        
                        // Dynamic Color Mapping
                        $channelColors = [
                            'Dine-in' => 'bg-emerald-500',
                            'Take-out' => 'bg-blue-500',
                            'Delivery' => 'bg-amber-500',
                            'Pick-up' => 'bg-indigo-500',
                        ];
                    @endphp
                    @forelse($channelLabels as $idx => $label)
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-[12px] font-black text-gray-600 uppercase tracking-wider">{{ $label }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] font-bold text-gray-400">{{ $channelSeries[$idx] }} orders</span>
                                    <span class="text-[12px] font-black text-gray-900">{{ $channelValues[$idx] }}%</span>
                                </div>
                            </div>
                            <div class="h-3 w-full bg-gray-50 border border-gray-100 rounded-full overflow-hidden">
                                <div class="h-full {{ $channelColors[$label] ?? 'bg-slate-400' }} transition-all duration-1000" style="width: {{ $channelValues[$idx] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="flex-1 flex flex-col items-center justify-center -mt-6">
                            <div class="w-12 h-12 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-300 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">No Active Channels</p>
                        </div>
                    @endforelse
                    {{-- Payment Methods Breakdown --}}
                    <div class="pt-4 border-t border-gray-100">
                        @php
                            $paymentLabels = $chart['payment']['labels'] ?? [];
                            $paymentSeries = $chart['payment']['series'] ?? [];
                        @endphp
                        <h4 class="text-[12px] font-black text-gray-700 mb-3 uppercase tracking-wider">Payment Methods</h4>
                        <div class="space-y-3">
                            @foreach($paymentLabels as $i => $pm)
                                    @php
                                        $count = (int)($paymentSeries[$i] ?? 0);
                                        $amount = (float)($chart['payment']['totals'][$i] ?? 0);
                                        $currency = $financialConfig['currency_symbol'] ?? '₱';
                                    @endphp
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-600 font-bold text-xs">{{ substr($pm, 0, 2) }}</div>
                                            <div>
                                                <p class="text-[13px] font-bold text-gray-900">{{ $pm }}</p>
                                                <p class="text-[10px] text-gray-400">{{ $count }} orders</p>
                                            </div>
                                        </div>
                                        <div class="text-[12px] font-black text-gray-900">{{ $currency }} {{ number_format($amount, 2) }}</div>
                                    </div>
                                @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2: Usage Velocity (Linear Items) --}}
            <div class="lg:col-span-4 p-6 bg-white border border-gray-200 rounded-xl shadow-sm flex flex-col h-[400px]">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-[14px] font-black text-gray-900 uppercase tracking-widest">Usage Velocity</h2>
                </div>
                <div class="space-y-4 flex-1 pr-2">
                    @forelse($inventoryIntel['velocity'] as $v)
                        <div class="space-y-1.5 p-3 rounded-lg bg-gray-50 border border-gray-100 transition-all hover:border-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-300 text-left">
                            <div class="flex justify-between items-end">
                                <span class="text-[12px] font-black text-gray-800 tracking-tight">{{ $v['name'] }}</span>
                                <span class="text-[10px] font-black text-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-600 uppercase tracking-widest">{{ $v['daily'] }} / DAY</span>
                            </div>
                            <div class="h-1 w-full bg-white rounded-full overflow-hidden border border-gray-100">
                                <div class="h-full bg-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-500" style="width: {{ $v['progress'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="flex-1 flex flex-col items-center justify-center p-8 bg-gray-50/50 rounded-xl border border-dashed border-gray-100 py-12">
                            <div class="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-300 mb-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                            </div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">No Consumption Velocity</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- 3: Efficiency Audit (New Level Indicator Design) --}}
            <div class="lg:col-span-4 p-6 bg-white border border-gray-200 rounded-xl shadow-sm flex flex-col h-[400px]">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-[14px] font-black text-gray-900 uppercase tracking-widest">Health Index</h2>
                </div>
                <div class="flex-1 flex gap-10 items-center px-4">
                    {{-- Premium High-Tech Segmented Battery Indicator --}}
                    <div class="relative w-16 h-36 flex flex-col items-center shrink-0">
                        {{-- Battery Cap --}}
                        <div class="w-6 h-2 bg-slate-800 rounded-t-md shadow-sm z-10"></div>
                        
                        {{-- Battery Body --}}
                        <div class="w-full flex-1 bg-slate-50 border-[3.5px] border-slate-800 rounded-2xl p-1 relative overflow-hidden flex flex-col justify-end shadow-[inset_0_2px_4px_rgba(0,0,0,0.06)]">
                            
                            {{-- Segment lines overlay (creating battery bar segments) --}}
                            <div class="absolute inset-0 z-20 pointer-events-none" 
                                 style="background-image: repeating-linear-gradient(0deg, transparent, transparent 16px, #ffffff 16px, #ffffff 19px);">
                            </div>

                            {{-- Dynamic Battery Juice --}}
                            @php
                                $health = $inventoryIntel['health_score'];
                                $juiceColor = $health > 80 
                                    ? 'bg-gradient-to-t from-emerald-500 to-emerald-400 shadow-[0_0_15px_rgba(16,185,129,0.4)] border border-emerald-400/30' 
                                    : ($health > 50 
                                        ? 'bg-gradient-to-t from-amber-500 to-amber-400 shadow-[0_0_15px_rgba(245,158,11,0.4)] border border-amber-400/30' 
                                        : 'bg-gradient-to-t from-rose-600 to-red-500 shadow-[0_0_15px_rgba(239,68,68,0.4)] border border-rose-500/30');
                            @endphp
                            <div class="w-full transition-all duration-1000 ease-out rounded-xl {{ $juiceColor }}" 
                                 style="height: {{ $health }}%">
                            </div>

                            {{-- Glass Reflection shine effect --}}
                            <div class="absolute top-0 left-0 w-1/2 h-full bg-white/10 z-10 rounded-l-md pointer-events-none"></div>
                        </div>
                    </div>
                    <div class="flex-1 space-y-6">
                        <div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] block mb-1">Operational Rating</span>
                            <h3 class="text-4xl font-black text-gray-900 tracking-tighter">{{ $inventoryIntel['health_score'] }}%</h3>
                        </div>
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-[11px] font-bold mb-1 uppercase tracking-widest {{ $inventoryIntel['health_score'] > 80 ? 'text-emerald-600' : ($inventoryIntel['health_score'] > 50 ? 'text-amber-600' : 'text-red-600') }}">Variance Loss</p>
                            <p class="text-[16px] font-black text-gray-900">{{ $financialConfig['currency_symbol'] ?? '₱' }} {{ number_format($inventoryIntel['waste_value'], 2) }} <span class="text-[12px] font-bold text-gray-400">VALUE</span></p>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-1">{{ number_format($inventoryIntel['variance_pct'], 2) }}% of consumed stock value</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <div class="lg:col-span-5 rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden flex flex-col h-[400px]">
                <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between shrink-0">
                    <h2 class="text-[14px] font-black text-gray-900 uppercase tracking-widest">Top Products</h2>
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Volume Ranking</span>
                </div>
                <div class="flex-1 overflow-y-auto custom-scrollbar">
                    <table class="w-full text-left border-collapse">
                        <tbody class="divide-y divide-gray-50">
                            @forelse($bestSellers as $item)
                                <tr class="hover:bg-gray-50/80 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl bg-gray-50 text-gray-400 group-hover:bg-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-50 group-hover:text-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-600 flex items-center justify-center font-bold text-sm shrink-0 transition-all">
                                                {{ substr($item['name'], 0, 1) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-[13px] font-extrabold text-gray-900 group-hover:text-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-600 transition-colors">{{ $item['name'] }}</span>
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mt-0.5">{{ $item['category'] }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <p class="text-[13px] font-black text-gray-900">{{ $item['sold'] }}</p>
                                        <p class="text-[11px] font-bold text-emerald-600">{{ $item['revenue'] }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="py-20 text-center">
                                        <div class="flex flex-col items-center justify-center px-6">
                                            <div class="w-12 h-12 rounded-full bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-200 mb-4">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                            </div>
                                            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest">No Items Sold</p>
                                            <p class="text-[10px] font-bold text-gray-300 uppercase mt-1">Products ranking will appear here</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-gray-50 text-center">
                    <a href="{{ route('menu.index') }}" wire:navigate 
                       class="inline-block text-[11px] font-bold text-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-600 uppercase tracking-[0.2em] hover:text-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-800 transition-colors">
                        Load Extended Catalog
                    </a>
                </div>
            </div>

            {{-- Enhanced Stock Intelligence (col-span-4) --}}
            <div class="lg:col-span-4 rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden flex flex-col h-[400px]" x-data="slidingTabs(@entangle('stockTab').live, 'stockTab')" wire:ignore.self wire:key="stock-intelligence-tabs">
                <div class="px-6 py-5 flex items-center justify-between bg-white sticky top-0 z-20 shrink-0">
                    <h2 class="text-[14px] font-black text-gray-900 uppercase tracking-widest">Stock Intelligence</h2>
                </div>
                <div class="px-2 shrink-0">
                    <x-sliding-tabs model="stockTab" class="mb-4">
                        <x-sliding-tab model="stockTab" value="deficiency">
                            Shortage
                        </x-sliding-tab>
                        <x-sliding-tab model="stockTab" value="expiration">
                            <span class="flex items-center gap-1.5">
                                Expiry
                                @if(count($expirationAlerts) > 0)
                                    <div class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></div>
                                @endif
                            </span>
                        </x-sliding-tab>
                    </x-sliding-tabs>
                </div>

                <div class="flex-1 p-4 pt-0 space-y-3 overflow-y-auto custom-scrollbar">
                    {{-- Deficiency Tab --}}
                    <div x-show="stockTab === 'deficiency'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="space-y-0">
                        @forelse($lowStockAlerts as $alert)
                            <div class="p-3 rounded-2xl bg-red-50/30 border border-red-50 flex items-center justify-between group hover:border-red-200 transition-all mb-2">
                                <div>
                                    <h3 class="text-[13px] font-black text-gray-900">{{ $alert['ingredient'] }}</h3>
                                    <p class="text-[10px] font-bold text-gray-400 mt-1">{{ $alert['branch'] }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2.5 py-1 rounded-lg text-red-700 text-[12px] font-black bg-red-100/50">
                                        {{ explode(' ', $alert['current'])[0] }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500 mb-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">Stock Secured</p>
                                <p class="text-[9px] font-bold text-gray-400 uppercase mt-1 px-4">All ingredients meet the threshold</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Expiration Tab --}}
                    <div x-show="stockTab === 'expiration'"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="space-y-0">
                        @forelse($expirationAlerts as $exp)
                            <div class="p-3 rounded-2xl bg-amber-50/30 border border-amber-50 flex items-center justify-between group hover:border-amber-200 transition-all mb-2">
                                <div>
                                    <h3 class="text-[13px] font-black text-gray-900">{{ $exp['ingredient'] }}</h3>
                                    <p class="text-[10px] font-bold text-gray-400 mt-1">{{ $exp['expiry'] }}</p>
                                </div>
                                <div class="text-right">
                                    <span @class([
                                        'px-2.5 py-1 rounded-lg text-[12px] font-black',
                                        $exp['days_left'] < 0 ? 'bg-red-500 text-white' : 'bg-amber-100/50 text-amber-700'
                                    ])>
                                        {{ $exp['days_left'] < 0 ? 'EXPIRED' : $exp['days_left'] . 'd' }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-500 mb-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">No Expirations</p>
                                <p class="text-[9px] font-bold text-gray-400 uppercase mt-1 px-4 text-emerald-600/60">Materials life-cycle is healthy</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="p-4 border-t border-gray-50 text-center">
                    <a href="{{ route('stock.index') }}" wire:navigate 
                       class="inline-block text-[11px] font-bold text-gray-400 uppercase tracking-widest hover:text-gray-900 transition-colors">
                        Expand Full Ledger
                    </a>
                </div>
            </div>

            {{-- Live Terminal Feed (col-span-3) --}}
            <div class="lg:col-span-3 rounded-xl bg-gray-900 border border-gray-800 shadow-xl overflow-hidden flex flex-col relative text-white h-[400px]">
                <div class="px-5 py-4 border-b border-gray-800 flex items-center justify-between bg-gray-900 sticky top-0 z-20 shrink-0">
                    <h2 class="text-[11px] font-black text-gray-200 uppercase tracking-widest flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_12px_rgba(16,185,129,0.8)] animate-pulse"></div>
                        Remote POS
                    </h2>
                </div>
                
                <div class="flex-1 p-5 space-y-4 overflow-y-auto custom-scrollbar">
                    @forelse($liveOrders as $order)
                        <div class="relative pl-5 border-l-2 {{ $order['status'] === 'Preparing' ? 'border-amber-500' : ($order['status'] === 'Completed' ? 'border-gray-700' : 'border-emerald-500') }} group transition-all">
                            <div class="flex justify-between items-start mb-1 text-left">
                                <span class="text-[12px] font-black text-gray-100 tracking-tight">{{ $order['id'] }}</span>
                                <span class="text-[10px] font-bold text-gray-500">{{ $order['time'] }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">{{ $order['status'] }}</span>
                                <span class="text-[12px] font-black text-emerald-400 bg-emerald-400/10 px-1.5 py-0.5 rounded">{{ $order['amount'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-20 flex flex-col items-center justify-center text-center">
                            <div class="w-12 h-12 rounded-full border-2 border-gray-800 flex items-center justify-center text-gray-700 mb-4 bg-gray-900/50">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zM12 8V6m0 8v2m4-4H8m8 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <p class="text-[11px] font-black text-gray-500 uppercase tracking-[0.2em]">Silence on POS</p>
                            <p class="text-[10px] font-bold text-gray-700 uppercase mt-1">Listening for incoming orders...</p>
                        </div>
                    @endforelse
                </div>
                
                <div class="p-4 border-t border-gray-800 bg-gray-950/80">
                    <a href="{{ route('pos.index') }}" wire:navigate 
                       class="block w-full py-3 rounded-xl bg-gray-800 hover:bg-gray-700 text-gray-300 text-[11px] font-black tracking-[0.2em] uppercase text-center transition-all transform active:scale-[0.98]">
                        POS Console
                    </a>
                </div>
            </div>

        </div>

    </div>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 4px; }
    </style>

    @push('scripts')
<script>
        function dashboardCharts() {
            return {
                salesChart: null,
                sparklineSalesChart: null,
                sparklineNetSalesChart: null,
                sparklineAovChart: null,
                sparklineCogsChart: null,
                selectedMetric: '{{ $selectedChartMetric ?? "Sales" }}',
                metrics: @json($chart['metrics']['series'] ?? []),
                metricForecasts: @json($chart['metrics']['forecast'] ?? []),
                metricsCategories: @json($chart['categories'] ?? []),
                
                init() {
                    this.initSales();
                    this.initSparklines();
                    
                    if (this.$cleanup) {
                        this.$cleanup(() => {
                            if (this.salesChart) { try { this.salesChart.destroy(); } catch(e) {} }
                            if (this.sparklineSalesChart) { try { this.sparklineSalesChart.destroy(); } catch(e) {} }
                            if (this.sparklineNetSalesChart) { try { this.sparklineNetSalesChart.destroy(); } catch(e) {} }
                            if (this.sparklineAovChart) { try { this.sparklineAovChart.destroy(); } catch(e) {} }
                            if (this.sparklineCogsChart) { try { this.sparklineCogsChart.destroy(); } catch(e) {} }
                        });
                    }
                },

                initSparklines() {
                    if (typeof window.ApexCharts === 'undefined') return;

                    const createSparkline = (el, data, color) => {
                        if (!el) return null;
                        
                        let seriesData = data ?? [];
                        if (seriesData.length === 0) {
                            seriesData = [0, 0, 0, 0, 0, 0, 0];
                        }
                        
                        var options = {
                            series: [{ data: seriesData }],
                            chart: {
                                type: 'area',
                                height: el.dataset.height ? parseInt(el.dataset.height) : 35,
                                sparkline: { enabled: true },
                                animations: { enabled: true, speed: 600 }
                            },
                            stroke: { curve: 'smooth', width: 2 },
                            fill: {
                                type: 'gradient',
                                gradient: {
                                    shadeIntensity: 1,
                                    opacityFrom: 0.25,
                                    opacityTo: 0.0,
                                    stops: [0, 90, 100]
                                }
                            },
                            colors: [color],
                            tooltip: {
                                theme: 'dark',
                                fixed: { enabled: false },
                                x: { show: false },
                                y: {
                                    title: { formatter: (seriesName) => '' },
                                    formatter: (val) => '₱ ' + val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                                },
                                marker: { show: false }
                            }
                        };
                        const chart = new ApexCharts(el, options);
                        chart.render();
                        return chart;
                    };
 
                    setTimeout(() => {
                        this.sparklineSalesChart = createSparkline(this.$refs.sparklineSales, this.metrics['Sales'], '#10B981');
                        this.sparklineNetSalesChart = createSparkline(this.$refs.sparklineNetSales, this.metrics['Sales'], '#10B981');
                        this.sparklineAovChart = createSparkline(this.$refs.sparklineAov, this.metrics['AOV'] ?? this.metrics['Aov'], '#6366F1');
                        this.sparklineCogsChart = createSparkline(this.$refs.sparklineCogs, this.metrics['COGS'] ?? this.metrics['Cogs'], '#F43F5E');
                    }, 300);
                },

                handleChartUpdate(detail) {
                    const data = detail?.chart ?? detail?.[0] ?? detail;
                    if (!data) return;

                    try {
                        if (data.metrics) {
                            this.metrics = data.metrics.series ?? {};
                            this.metricForecasts = data.metrics.forecast ?? {};
                        }
                        this.metricsCategories = data.categories ?? [];

                        this.updateMetricLocal(this.selectedMetric);
                        
                        // Update sparklines dynamically
                        if (this.sparklineSalesChart) this.sparklineSalesChart.updateSeries([{ data: this.metrics['Sales'] ?? [] }]);
                        if (this.sparklineNetSalesChart) this.sparklineNetSalesChart.updateSeries([{ data: this.metrics['Sales'] ?? [] }]);
                        if (this.sparklineAovChart) this.sparklineAovChart.updateSeries([{ data: this.metrics['AOV'] ?? this.metrics['Aov'] ?? [] }]);
                        if (this.sparklineCogsChart) this.sparklineCogsChart.updateSeries([{ data: this.metrics['COGS'] ?? this.metrics['Cogs'] ?? [] }]);
                    } catch (e) {
                        console.warn("Chart update failed, re-initializing...", e);
                        this.initSales();
                        this.initSparklines();
                    }
                },

                updateMetricLocal(metric) {
                    if (!this.salesChart || !this.metrics) return;
                    try {
                        this.selectedMetric = metric;

                        // Silent background sync to Livewire without triggering redrawing lag
                        this.$wire.set('selectedChartMetric', metric, false);

                        let seriesData = [];
                        let colors = [];
                        let strokeWidths = [3, 2];

                        if (metric === 'Sales') {
                            seriesData = [
                                {
                                    name: "Gross Revenue",
                                    data: this.metrics['Sales'] ?? []
                                },
                                {
                                    name: "Resource Costs",
                                    data: this.metrics['COGS'] ?? this.metrics['Cogs'] ?? []
                                }
                            ];
                            colors = ['#10B981', '#F43F5E'];
                            strokeWidths = [3, 3];
                        } else if (metric === 'Volume') {
                            seriesData = [
                                {
                                    name: "Order Volume",
                                    data: this.metrics['Volume'] ?? []
                                },
                                {
                                    name: "Predictive Forecast",
                                    data: this.metricForecasts['Volume'] ?? []
                                }
                            ];
                            colors = ['#6366F1', '#A5B4FC'];
                        } else { // Profit
                            seriesData = [
                                {
                                    name: "Net Profit",
                                    data: this.metrics['Profit'] ?? []
                                },
                                {
                                    name: "Predictive Forecast",
                                    data: this.metricForecasts['Profit'] ?? []
                                }
                            ];
                            colors = ['#10B981', '#6EE7B7'];
                        }

                        this.salesChart.updateOptions({ 
                            xaxis: { 
                                categories: this.metricsCategories,
                                tickAmount: window.innerWidth < 640 ? 4 : 8
                            },
                            colors: colors,
                            stroke: { 
                                width: strokeWidths, 
                                curve: 'smooth', 
                                dashArray: metric === 'Sales' ? [0, 0] : [0, 8] 
                            },
                            series: seriesData
                        }, false, true);
                    } catch (e) {
                        console.warn('Local metric update failed', e);
                    }
                },

                initSales() {
                    if (typeof window.ApexCharts === 'undefined' || !this.$refs.salesChart) return;
                    
                    @php
                        $hexMap = [1 => '#6366F1', 2 => '#F43F5E', 3 => '#10B981'];
                        $hexMain = $hexMap[$user->role_id] ?? '#6366F1';
                        $hexLight = $user->role_id === 1 ? '#A5B4FC' : ($user->role_id === 2 ? '#FDA4AF' : '#6EE7B7');
                    @endphp

                    let seriesData = [];
                    let colors = [];
                    let strokeWidths = [3, 2];

                    if (this.selectedMetric === 'Sales') {
                        seriesData = [
                            {
                                name: "Gross Revenue",
                                data: this.metrics['Sales'] ?? []
                            },
                            {
                                name: "Resource Costs",
                                data: this.metrics['COGS'] ?? this.metrics['Cogs'] ?? []
                            }
                        ];
                        colors = ['#10B981', '#F43F5E'];
                        strokeWidths = [3, 3];
                    } else if (this.selectedMetric === 'Volume') {
                        seriesData = [
                            {
                                name: "Order Volume",
                                data: this.metrics['Volume'] ?? []
                            },
                            {
                                name: "Predictive Forecast",
                                data: this.metricForecasts['Volume'] ?? []
                            }
                        ];
                        colors = ['#6366F1', '#A5B4FC'];
                    } else { // Profit
                        seriesData = [
                            {
                                name: "Net Profit",
                                data: this.metrics['Profit'] ?? []
                            },
                            {
                                name: "Predictive Forecast",
                                data: this.metricForecasts['Profit'] ?? []
                            }
                        ];
                        colors = ['#10B981', '#6EE7B7'];
                    }

                    var options = {
                        series: seriesData,
                        chart: { 
                            height: 330, 
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
                        colors: colors,
                        dataLabels: { enabled: false },
                        stroke: { 
                            width: strokeWidths, 
                            curve: 'smooth', 
                            dashArray: this.selectedMetric === 'Sales' ? [0, 0] : [0, 8] 
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
                            categories: this.metricsCategories,
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
                        yaxis: {
                            labels: {
                                style: { colors: '#9CA3AF', fontSize: '11px', fontWeight: 600 },
                                formatter: (val) => { 
                                    if (this.selectedMetric === 'Volume') {
                                        return val >= 1000 ? (val/1000).toFixed(1) + "k" : val;
                                    }
                                    return val >= 1000 ? "₱" + (val/1000).toFixed(1) + "k" : "₱" + val; 
                                }
                            }
                        },
                        grid: { 
                            borderColor: '#f8fafc', 
                            strokeDashArray: 4, 
                            padding: { 
                                top: 0, 
                                right: 15, 
                                bottom: 25, 
                                left: 15 
                            } 
                        },
                        legend: { 
                            position: 'top', 
                            horizontalAlign: 'right', 
                            fontSize: '11px', 
                            fontWeight: 700,
                            markers: { radius: 12, width: 8, height: 8 } 
                        },
                        tooltip: {
                            theme: 'dark',
                            x: { show: true },
                            y: { 
                                formatter: (val) => { 
                                    if (this.selectedMetric === 'Volume') return val.toLocaleString() + " units";
                                    return "₱ " + val.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); 
                                }
                            },
                            style: { fontSize: '12px', fontFamily: 'Outfit' },
                            onDatasetHover: { highlightDataSeries: true },
                            marker: { show: true },
                            items: { display: 'flex' },
                            fixed: { enabled: false }
                        }
                    };
                    this.salesChart = new ApexCharts(this.$refs.salesChart, options);
                    this.salesChart.render();
                }
            }
        }
    </script>

    <script>
        window.branchMapWidget = function(branchMapData) {
            return {
                mapMode: 'satellite',
                map: null,
                markers: [],
                mapModeOpen: false,
                branchMapData: branchMapData,
                
                init() {
                    this.initMap();
                },
                
                initMap() {
                    if (typeof L === 'undefined') {
                        // Wait and retry if Leaflet script CDN is still loading
                        setTimeout(() => this.initMap(), 100);
                        return;
                    }

                    // Global vaccine for Leaflet Tooltip/Popup zoom animation & positioning crashes (orphaned layers)
                    [L.Tooltip, L.Popup].forEach(protoClass => {
                        if (protoClass) {
                            ['_animateZoom', '_updatePosition'].forEach(method => {
                                if (protoClass.prototype[method] && !protoClass.prototype[method + '_patched']) {
                                    const originalMethod = protoClass.prototype[method];
                                    protoClass.prototype[method] = function(...args) {
                                        if (this._map) return originalMethod.apply(this, args);
                                    };
                                    protoClass.prototype[method + '_patched'] = true;
                                }
                            });
                        }
                    });
                    
                    const container = document.getElementById('branchMap');
                    if (!container) {
                        // Wait and retry if container is not in DOM yet
                        setTimeout(() => this.initMap(), 50);
                        return;
                    }

                    // Safely remove any existing map instance associated with this container
                    if (window.branchMapInstance) {
                        try {
                            // Close tooltips and unbind layers to prevent orphaned event handlers during zoom animations
                            if (window.branchMapInstance.eachLayer) {
                                window.branchMapInstance.eachLayer(layer => {
                                    try {
                                        if (layer.closeTooltip) layer.closeTooltip();
                                        if (layer.unbindTooltip) layer.unbindTooltip();
                                        window.branchMapInstance.removeLayer(layer);
                                    } catch (e) {}
                                });
                            }
                            window.branchMapInstance.remove();
                        } catch (e) {
                            console.error('Map cleanup failed:', e);
                        }
                        window.branchMapInstance = null;
                    } else if (container.leafletMap) {
                        try {
                            if (container.leafletMap.eachLayer) {
                                container.leafletMap.eachLayer(layer => {
                                    try {
                                        if (layer.closeTooltip) layer.closeTooltip();
                                        if (layer.unbindTooltip) layer.unbindTooltip();
                                        container.leafletMap.removeLayer(layer);
                                    } catch (e) {}
                                });
                            }
                            container.leafletMap.remove();
                        } catch (e) {
                            console.error('Map cleanup from element failed:', e);
                        }
                        container.leafletMap = null;
                    }
                    
                    // Force clean the Leaflet state properties and DOM content
                    if (container._leaflet_id) {
                        container._leaflet_id = null;
                    }
                    container.innerHTML = '';
                    
                    // Initialize map centered on southern Laguna lake branches, bounded to Laguna province
                    this.map = L.map('branchMap', {
                        center: [14.2189, 121.1672],
                        zoom: 11,
                        minZoom: 10,
                        maxBounds: [[13.85, 120.85], [14.65, 121.95]],
                        maxBoundsViscosity: 1.0,
                        zoomControl: false,
                        attributionControl: false
                    });

                    // Store map reference globally and on the element to ensure safe cleanup on subsequent Alpine re-evaluations
                    window.branchMapInstance = this.map;
                    container.leafletMap = this.map;

                    // Configure satellite, street view, and vector dark basemaps
                    this.tileLayers = {
                        satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            maxZoom: 19
                        }),
                        street: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
                            maxZoom: 19
                        }),
                        vector: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{y}/{x}{r}.png', {
                            maxZoom: 20
                        })
                    };

                    // Add starting tile layer
                    this.tileLayers[this.mapMode].addTo(this.map);

                    // Draw coordinates & custom animated DOM markers
                    this.renderMarkers();

                    // Reactive layer toggling watcher
                    this.$watch('mapMode', (mode) => {
                        Object.keys(this.tileLayers).forEach(k => {
                            if (this.map.hasLayer(this.tileLayers[k])) {
                                this.map.removeLayer(this.tileLayers[k]);
                            }
                        });
                        this.tileLayers[mode].addTo(this.map);
                    });
                },

                renderMarkers() {
                    this.markers.forEach(m => {
                        if (m) {
                            try {
                                m.closeTooltip();
                                m.unbindTooltip();
                                this.map.removeLayer(m);
                            } catch (e) {
                                console.error('Marker cleanup failed:', e);
                            }
                        }
                    });
                    this.markers = [];

                    // Real GPS coordinates mapping for Laguna branches
                    const coordinates = {
                        'calauan': [14.1500, 121.3167],
                        'bay': [14.1818, 121.2858],
                        'pila': [14.2333, 121.3667],
                        'calamba': [14.2136, 121.1649]
                     };

                    this.branchMapData.forEach(branch => {
                        const cleanNameLower = (branch.clean_name || '').toLowerCase();
                        let coords = null;
                        
                        for (const [key, value] of Object.entries(coordinates)) {
                            if (cleanNameLower.includes(key)) {
                                coords = value;
                                break;
                            }
                        }
                        
                        if (!coords) {
                            coords = [14.19 + (Math.random() - 0.5) * 0.05, 121.28 + (Math.random() - 0.5) * 0.05];
                        }
                        
                        let iconHtml = '';
                        if (branch.is_highest) {
                            iconHtml = `
                                 <div class='relative w-5 h-5'>
                                     <div class='absolute inset-0 flex items-center justify-center pointer-events-none'>
                                         <span class='w-8 h-8 rounded-full bg-emerald-500/35 animate-ping' style='animation-duration: 2s;'></span>
                                     </div>
                                     <div class='absolute inset-0 flex items-center justify-center pointer-events-none'>
                                         <span class='w-6 h-6 rounded-full bg-emerald-500/20 animate-pulse'></span>
                                     </div>
                                     <div class='absolute inset-0 flex items-center justify-center pointer-events-none'>
                                         <div class='w-3 h-3 bg-emerald-500 rounded-full border-2 border-white shadow-xl shadow-emerald-500/50 flex items-center justify-center'>
                                             <span class='w-0.5 h-0.5 bg-white rounded-full'></span>
                                         </div>
                                     </div>
                                 </div>
                            `;
                        } else {
                            iconHtml = `
                                 <div class='relative w-5 h-5'>
                                     <div class='absolute inset-0 flex items-center justify-center pointer-events-none'>
                                         <span class='w-5 h-5 rounded-full bg-blue-500/10 animate-ping opacity-60' style='animation-duration: 3s;'></span>
                                     </div>
                                     <div class='absolute inset-0 flex items-center justify-center pointer-events-none'>
                                         <div class='w-2.5 h-2.5 bg-blue-500 rounded-full border-2 border-white shadow shadow-blue-500/50'></div>
                                     </div>
                                 </div>
                            `;
                        }

                        const customIcon = L.divIcon({
                            html: iconHtml,
                            className: 'custom-leaflet-icon',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        });

                        const tooltipContent = `
                             <div class='flex flex-col text-[10px] text-slate-100 font-sans leading-relaxed'>
                                 <div class='flex items-center gap-1 font-black text-[11px] text-white'>
                                     <span class='w-1.5 h-1.5 rounded-full ${branch.is_highest ? 'bg-emerald-400' : 'bg-blue-400'}'></span>
                                     ${branch.name}
                                 </div>
                                 <div class='text-slate-300 font-bold mt-0.5'>
                                     Sales: <span class='text-white font-black'>₱${Number(branch.total_sales).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                 </div>
                                 <div class='text-[8px] text-slate-400 uppercase tracking-widest mt-1'>
                                     Share: ${branch.sales_pct}%
                                 </div>
                             </div>
                        `;

                        const marker = L.marker(coords, { icon: customIcon })
                            .addTo(this.map)
                            .bindTooltip(tooltipContent, {
                                permanent: false,
                                direction: 'top',
                                className: 'leaflet-tooltip-premium',
                                offset: [0, -5]
                            });
                         
                        this.markers.push(marker);
                    });

                    if (this.markers.length > 0) {
                        const group = new L.featureGroup(this.markers);
                        this.map.fitBounds(group.getBounds().pad(0.2));
                    }
                }
            };
        };
    </script>
@endpush
</div>
