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
    >
        {{-- Welcome Banner --}}
        <div
            class="mb-6 transition-all duration-500 overflow-hidden"
        >
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-500"
                x-transition:enter-start="opacity-0 -translate-y-4 scale-[0.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                class="relative overflow-hidden rounded-2xl ring-1 {{ $bannerConfig['ring'] }} shadow-xl bg-gradient-to-br {{ $bannerConfig['gradient'] }}"
            >
                {{-- Decorative Background Glow --}}
                <div class="absolute inset-0 pointer-events-none overflow-hidden">
                    <div class="absolute -top-20 -right-20 w-64 h-64 rounded-full bg-white/5 blur-3xl"></div>
                    <div class="absolute -bottom-10 -left-10 w-48 h-48 rounded-full bg-white/5 blur-3xl"></div>
                    <div class="absolute inset-0 opacity-[0.04]" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 22px 22px;"></div>
                </div>

                {{-- Content --}}
                <div class="relative px-6 py-5 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        {{-- Role Icon --}}
                        <div class="w-12 h-12 rounded-2xl {{ $bannerConfig['icon_bg'] }} flex items-center justify-center flex-shrink-0 shadow-inner border border-white/10">
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
                            <h2 class="text-[20px] font-black text-white leading-tight {{ $bannerConfig['header_track'] ?? 'tracking-tight' }}">
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

        {{-- ─── Dashboard Filters (Compact Inline Style) ─────────────── --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            {{-- Branch Selection & Date Filters --}}
            <div class="flex flex-wrap items-center gap-2">
                {{-- Branch Dropdown --}}
                @if($isSuperAdmin)
                    <x-dropdown align="left" width="56">
                        <x-slot name="trigger">
                            <x-secondary-button type="button" class="gap-2">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                <span>{{ $branches->firstWhere('id', $selectedBranchId)?->branch_name ?? 'Enterprise Overview' }}</span>
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </x-secondary-button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', null)">
                                <span class="font-bold text-indigo-600">All Locations (Global)</span>
                            </x-dropdown-link>
                            <div class="border-t border-gray-100 my-1"></div>
                            @foreach($branches as $branch)
                                <x-dropdown-link href="#" wire:click.prevent="$set('selectedBranchId', {{ $branch->id }})">
                                    {{ $branch->branch_name }}
                                </x-dropdown-link>
                            @endforeach
                        </x-slot>
                    </x-dropdown>
                @else
                    @php $adminBranch = auth()->user()->branch; @endphp
                    @if($adminBranch)
                    <div class="inline-flex items-center gap-2 px-4 py-1.5 text-[12px] font-medium text-gray-700 bg-white border border-gray-200 rounded-xl shadow-sm h-10">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        <span>{{ $adminBranch->branch_name }}</span>
                    </div>
                    @endif
                @endif

                {{-- Date Range Filter --}}
                <x-date-range-filter startModel="startDate" endModel="endDate" :error="$dateError" :startValue="$startDate" />
            </div>

            {{-- Quick Select Dropdown --}}
            <div class="flex items-center gap-2">
                <x-quick-date-filter :activeFilter="$activeFilter" />
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

        {{-- ─── Financial Intelligence Grid ────────────────────────────────── --}}
        <div class="grid grid-cols-2 gap-6 lg:grid-cols-4 mb-8">
            {{-- Revenue --}}
            <div wire:click="openBreakdown('Revenue')" class="p-6 bg-gradient-to-br from-emerald-500 to-teal-700 rounded-3xl shadow-lg shadow-emerald-200/40 group hover:shadow-xl hover:shadow-emerald-300/40 hover:-translate-y-1 transition-all duration-500 relative overflow-hidden cursor-pointer border border-white/10">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-bl-[80px] -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white backdrop-blur-md transition-all duration-500 shadow-sm border border-white/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2-2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <span class="text-[11px] font-black text-white/60 uppercase tracking-[0.2em]">Gross Revenue</span>
                    </div>
                    <h3 class="text-3xl font-black text-white tracking-tight">₱ {{ number_format($kpi['revenue'], 2) }}</h3>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="flex h-2 w-2 rounded-full bg-emerald-300 animate-pulse"></span>
                        <p class="text-[12px] font-bold text-white/80">{{ $kpi['order_count'] }} Completed Sales</p>
                    </div>
                </div>
            </div>

            {{-- AOV --}}
            <div wire:click="openBreakdown('AOV')" class="p-6 bg-gradient-to-br from-indigo-500 to-violet-700 rounded-3xl shadow-lg shadow-indigo-200/40 group hover:shadow-xl hover:shadow-indigo-300/40 hover:-translate-y-1 transition-all duration-500 relative overflow-hidden cursor-pointer border border-white/10">
                <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-bl-[80px] -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                <div class="relative z-10 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white backdrop-blur-md transition-all duration-500 shadow-sm border border-white/20">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <span class="text-[11px] font-black text-white/60 uppercase tracking-[0.2em]">Avg. Order Value</span>
                    </div>
                    <h3 class="text-3xl font-black text-white tracking-tight">₱ {{ number_format($kpi['aov'], 2) }}</h3>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span class="flex h-2 w-2 rounded-full bg-indigo-300 animate-pulse"></span>
                                            <p class="text-[12px] font-bold text-white/80">Profit/Sales Intensity</p>
                                        </div>
                                    </div>
                                </div>
                
                                {{-- COGS --}}
                                <div wire:click="openBreakdown('COGS')" class="p-6 bg-gradient-to-br from-slate-600 to-slate-800 rounded-3xl shadow-lg shadow-slate-200/40 group hover:shadow-xl hover:shadow-slate-300/40 hover:-translate-y-1 transition-all duration-500 relative overflow-hidden cursor-pointer border border-white/10">
                                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-bl-[80px] -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                                    <div class="relative z-10 text-white">
                                        <div class="flex items-center justify-between mb-4">
                                            <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white backdrop-blur-md transition-all duration-500 shadow-sm border border-white/20">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            </div>
                                            <span class="text-[11px] font-black text-white/60 uppercase tracking-[0.2em]">Resource Costs</span>
                                        </div>
                                        <h3 class="text-3xl font-black text-white tracking-tight">₱ {{ number_format($kpi['total_cogs'], 2) }}</h3>
                                        <div class="flex items-center gap-2 mt-2">
                                            <span class="flex h-2 w-2 rounded-full bg-slate-300 animate-pulse"></span>
                                            <p class="text-[12px] font-bold text-white/80">Inventory Consumption</p>
                                        </div>
                                    </div>
                                </div>
                
                                {{-- Profit Insight --}}
                                <div class="relative group h-full">
                                    <div wire:click="openBreakdown('Profit')" class="p-6 bg-gradient-to-br from-amber-500 to-orange-700 rounded-3xl shadow-lg shadow-amber-200/40 group-hover:shadow-xl hover:shadow-amber-300/40 hover:-translate-y-1 transition-all duration-500 relative overflow-hidden cursor-pointer border border-white/10">
                                        <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-bl-[80px] -mr-8 -mt-8 transition-transform group-hover:scale-110"></div>
                                        <div class="relative z-10 text-white">
                                            <div class="flex items-center justify-between mb-4">
                                                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center text-white backdrop-blur-md transition-all duration-500 shadow-sm border border-white/20">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                                </div>
                                                <span class="text-[11px] font-black text-white/60 uppercase tracking-[0.2em]">Net Profit</span>
                                            </div>
                                            <h3 class="text-3xl font-black text-white tracking-tight">₱ {{ number_format($kpi['gross_profit'], 2) }}</h3>
                                            <div class="flex items-center gap-2 mt-2">
                                                <span class="flex h-2 w-2 rounded-full bg-amber-300 animate-pulse"></span>
                                                <p class="text-[12px] font-bold text-white/80">{{ $kpi['profit_margin_pct'] }}% Profit Margin</p>
                                            </div>
                                        </div>
                                    </div>
                
                                    {{-- Mini Margin Trigger --}}
                                    <button wire:click="openBreakdown('Margin')" class="absolute bottom-4 right-4 z-20 w-8 h-8 rounded-full bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white hover:bg-white/30 transition-all shadow-sm" title="View Margin Breakdown">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </button>
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
                    <button @click="show = false" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all">
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
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6" x-data="dashboardCharts()">
            {{-- 1: Financial Pulse (Line Chart) --}}
            <div class="lg:col-span-8 rounded-xl bg-white border border-gray-200 shadow-sm p-6 overflow-hidden h-[400px]">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-[14px] font-black text-gray-900 uppercase tracking-widest flex items-center gap-2">
                        Financial Pulse
                    </h2>

                    <div class="flex items-center p-1 bg-gray-50 rounded-xl border border-gray-100">
                        @foreach(['Sales', 'Volume', 'Profit'] as $m)
                            <button wire:click="setChartMetric('{{ $m }}')"
                                    @class([
                                        'px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all duration-300',
                                        $selectedChartMetric === $m 
                                            ? 'bg-white text-gray-900 shadow-sm border border-gray-100 scale-105' 
                                            : 'text-gray-400 hover:text-gray-600'
                                    ])>
                                {{ $m }}
                            </button>
                        @endforeach
                    </div>
                </div>
                
                <div wire:ignore class="relative">
                    <div x-ref="salesChart"></div>
                </div>
            </div>

            {{-- 2: Operations Index (2x2 Grid) --}}
            <div class="lg:col-span-4 p-6 bg-white border border-gray-200 rounded-xl shadow-sm flex flex-col h-[400px]">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-[14px] font-black text-gray-900 uppercase tracking-widest">Operations Index</h2>
                    <div class="w-8 h-8 rounded bg-gray-50 flex items-center justify-center text-gray-400 border border-gray-100">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16m-7 6h7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-y-12 gap-x-6 flex-1">
                    {{-- KPI Cards with Sharper Design --}}
                    <div class="group">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] block mb-2">Master Catalog</span>
                        <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ $kpi['active_products'] }}</h3>
                        <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">Active Items</p>
                    </div>

                    <div class="group">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] block mb-2">Materials</span>
                        <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ $kpi['total_ingredients'] }}</h3>
                        <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">Raw Resources</p>
                    </div>

                    <div class="group">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] block mb-2">System Alerts</span>
                        <h3 @class(['text-3xl font-black tracking-tighter', ($kpi['low_stock_count'] > 0 || count($expirationAlerts) > 0) ? 'text-red-600' : 'text-gray-900'])>
                            {{ $kpi['low_stock_count'] + count($expirationAlerts) }}
                        </h3>
                        <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">Stock Threats</p>
                    </div>

                    <div class="group">
                        <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] block mb-2">Workforce</span>
                        <div class="flex items-baseline gap-1">
                            <h3 class="text-3xl font-black text-gray-900 tracking-tighter">{{ explode(' / ', $kpi['staff_active'])[0] }}</h3>
                            <span class="text-[12px] font-black text-gray-300">/ {{ explode(' / ', $kpi['staff_active'])[1] }}</span>
                        </div>
                        <p class="text-[11px] font-bold text-gray-500 mt-1 uppercase tracking-wider">On-Duty Staff</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ─── Secondary Analytical Grid ────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            {{-- 1: Revenue Channels (Dynamic from POS Config) --}}
            <div class="lg:col-span-4 rounded-xl bg-white border border-gray-200 shadow-sm p-6 flex flex-col h-[320px]">
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
                </div>
            </div>

            {{-- 2: Usage Velocity (Linear Items) --}}
            <div class="lg:col-span-4 p-6 bg-white border border-gray-200 rounded-xl shadow-sm flex flex-col">
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
            <div class="lg:col-span-4 p-6 bg-white border border-gray-200 rounded-xl shadow-sm flex flex-col h-[320px]">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-[14px] font-black text-gray-900 uppercase tracking-widest">Health Index</h2>
                </div>
                <div class="flex-1 flex gap-10 items-center px-4">
                    {{-- Vertical Level Indicator (No Circle) --}}
                    <div class="h-40 w-12 bg-gray-50 border-2 border-gray-100 rounded-xl relative overflow-hidden flex flex-col justify-end">
                        <div class="absolute inset-0 opacity-10" style="background-image: repeating-linear-gradient(0deg, #9ca3af, #9ca3af 1px, transparent 1px, transparent 10px);"></div>
                        <div class="w-full transition-all duration-1000 shadow-[0_0_15px_rgba(16,185,129,0.3)] {{ $inventoryIntel['variance_pct'] > 5 ? 'bg-red-500' : 'bg-emerald-500' }}" style="height: {{ $inventoryIntel['health_score'] }}%"></div>
                    </div>
                    <div class="flex-1 space-y-6">
                        <div>
                            <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] block mb-1">Operational Rating</span>
                            <h3 class="text-4xl font-black text-gray-900 tracking-tighter">{{ $inventoryIntel['health_score'] }}%</h3>
                        </div>
                        <div class="pt-4 border-t border-gray-100">
                            <p class="text-[11px] font-bold text-gray-400 mb-1 uppercase tracking-widest text-red-600">Variance Loss</p>
                            <p class="text-[16px] font-black text-gray-900">{{ $inventoryIntel['waste_qty'] }} <span class="text-[12px] font-bold text-gray-400">UNITS</span></p>
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
                    <a href="{{ route('menu.index') }}" 
                       class="inline-block text-[11px] font-bold text-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-600 uppercase tracking-[0.2em] hover:text-{{ $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald') }}-800 transition-colors">
                        Load Extended Catalog
                    </a>
                </div>
            </div>

            {{-- Enhanced Stock Intelligence (col-span-4) --}}
            <div class="lg:col-span-4 rounded-xl bg-white border border-gray-200 shadow-sm overflow-hidden flex flex-col h-[400px]" x-data="slidingTabs(@entangle('stockTab').live, 'stockTab')">
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

                <div class="flex-1 p-5 pt-0 space-y-3 overflow-y-auto custom-scrollbar">
                    {{-- Deficiency Tab --}}
                    <div x-show="stockTab === 'deficiency'" class="space-y-2">
                        @forelse($lowStockAlerts as $alert)
                            <div class="p-3 rounded-2xl bg-red-50/30 border border-red-50 flex items-center justify-between group hover:border-red-200 transition-all">
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
                    <div x-show="stockTab === 'expiration'" class="space-y-2" x-cloak>
                        @forelse($expirationAlerts as $exp)
                            <div class="p-3 rounded-2xl bg-amber-50/30 border border-amber-50 flex items-center justify-between group hover:border-amber-200 transition-all">
                                <div>
                                    <h3 class="text-[13px] font-black text-gray-900">{{ $exp['ingredient'] }}</h3>
                                    <p class="text-[10px] font-bold text-amber-600 mt-1 uppercase">{{ $exp['expiry'] }}</p>
                                </div>
                                <div class="text-right">
                                    <span @class([
                                        'px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider',
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
                    <a href="{{ route('stock.index') }}" 
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
                    <a href="{{ route('pos.index') }}" 
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
                paymentChart: null,
                
                init() {
                    this.initSales();
                    
                    window.addEventListener('updateSalesChart', (event) => {
                        const data = event.detail;
                        if (this.salesChart) {
                            this.salesChart.updateOptions({
                                xaxis: {
                                    categories: data.categories
                                }
                            });
                            this.salesChart.updateSeries([{
                                name: data.metric === 'Volume' ? "Order Volume" : (data.metric === 'Profit' ? "Net Profit" : "Gross Revenue"),
                                data: data.history
                            }, {
                                name: "Predictive Forecast",
                                data: data.forecast
                            }]);
                        }
                    });
                    
                    if (this.$cleanup) {
                        this.$cleanup(() => {
                            if (this.salesChart) this.salesChart.destroy();
                            if (this.paymentChart) this.paymentChart.destroy();
                        });
                    }
                },

                initSales() {
                    if (typeof window.ApexCharts === 'undefined' || !this.$refs.salesChart) return;
                    
                    @php
                        $hexMap = [1 => '#6366F1', 2 => '#F43F5E', 3 => '#10B981'];
                        $hexMain = $hexMap[$user->role_id] ?? '#6366F1';
                        $hexLight = $user->role_id === 1 ? '#A5B4FC' : ($user->role_id === 2 ? '#FDA4AF' : '#6EE7B7');
                    @endphp

                    var options = {
                        series: [
                            { name: "Gross Revenue", data: @json($chart['history']) },
                            { name: "Predictive Forecast", data: @json($chart['forecast']) }
                        ],
                        chart: { 
                            height: 320, 
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
                                top: 10,
                                left: 0,
                                blur: 10,
                                color: '#10B981',
                                opacity: 0.15
                            }
                        },
                        colors: ['#10B981', '#6366F1'],
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
                                opacityFrom: 0.45,
                                opacityTo: 0.02,
                                stops: [0, 90, 100]
                            }
                        },
                        xaxis: {
                            categories: @json($chart['categories']),
                            tooltip: { enabled: false },
                            axisBorder: { show: false }, 
                            axisTicks: { show: false },
                            labels: { 
                                style: { colors: '#9CA3AF', fontSize: '11px', fontWeight: 600 } 
                            }
                        },
                        yaxis: {
                            labels: {
                                style: { colors: '#9CA3AF', fontSize: '11px', fontWeight: 600 },
                                formatter: function (val) { return val >= 1000 ? "₱" + (val/1000).toFixed(1) + "k" : "₱" + val; }
                            }
                        },
                        grid: { 
                            borderColor: '#f1f5f9', 
                            strokeDashArray: 4, 
                            padding: { top: 0, right: 20, bottom: 0, left: 10 } 
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
                                formatter: function(val, { series, seriesIndex, dataPointIndex, w }) { 
                                    const metric = @json($selectedChartMetric);
                                    if (metric === 'Volume') return val.toLocaleString() + " units";
                                    return "₱ " + val.toLocaleString(); 
                                } 
                            },
                            style: { fontSize: '12px', fontFamily: 'Outfit' },
                            onDatasetHover: { highlightDataSeries: true },
                            marker: { show: true },
                            items: { display: 'flex' },
                            fixed: { enabled: false, position: 'topRight' }
                        }
                    };
                    this.salesChart = new ApexCharts(this.$refs.salesChart, options);
                    this.salesChart.render();
                }
            }
        }
    </script>
@endpush

</div>
