@php
    $user = auth()->user();
    $roleTheme = $user->getRoleTheme();
    $primaryColor = $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald');
    $primaryText = "text-{$primaryColor}-600";
    $primaryBg = "bg-{$primaryColor}-600";
    $primaryBorder = "border-{$primaryColor}-200";
    $primaryLight = "bg-{$primaryColor}-50";
    $stats = $this->kdsStats;
@endphp

{{-- ═══════════════════════════════════════════════════════════════════
     OUTER wrapper: Alpine scope for $wire — NO wire:ignore here.
     wire:ignore.self is moved to the INNER board card only, so that
     Livewire still processes wire:click inside the modal.
     ═══════════════════════════════════════════════════════════════════ --}}
<div
    x-data="{
        activeTab: $wire.entangle('activeTab').live,
        ...(typeof window.slidingTabs === 'function' ? window.slidingTabs(@entangle('activeTab').live, 'activeTab') : {}),
                _timerInterval: null,
        delayThreshold: @js($delayThresholdMinutes),
        updateTimers() {
            const now = new Date();
            const warnThreshold = Math.max(1, Math.floor(this.delayThreshold / 2));
            document.querySelectorAll('[data-created-at]').forEach(el => {
                const createdAt = el.dataset.createdAt;
                if (!createdAt) return;
                const diff = Math.floor((now - new Date(createdAt)) / 1000);
                const m = Math.floor(diff / 60);
                const s = diff % 60;
                el.innerText = `${m}:${s.toString().padStart(2, '0')}`;

                const container = el.closest('.ticket-timer-container');
                if (container) {
                    container.className =
                        'ticket-timer-container flex items-center gap-1.5 px-3 py-1 rounded-full text-[12px] font-black tabular-nums transition-all ' +
                        (m >= this.delayThreshold
                            ? 'bg-red-50 text-red-600 border border-red-100 animate-pulse'
                            : m >= warnThreshold
                                ? 'bg-amber-50 text-amber-600 border border-amber-100'
                                : 'bg-slate-100 text-slate-700 border border-slate-200');
                }
            });
        },
    }"
    x-init="
        updateTimers();
        _timerInterval = setInterval(() => updateTimers(), 1000);
        $el.addEventListener('alpine:destroy', () => clearInterval(_timerInterval));
    "
    wire:key="kds-master-container"
    wire:poll.5s="refreshStats"
    class="relative bg-[#F9FAFB] p-2 md:p-4"
    x-cloak>

    {{-- ════════════ BOARD CARD — plain wrapper, no wire:ignore.self ════════════ --}}
    <div
        wire:key="kds-board-card"
        class="bg-white rounded-2xl shadow-sm border border-gray-200">

        {{-- Section 1: Title --}}
        <div class="px-4 py-4 md:px-6 md:py-6 shrink-0">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-[16px] md:text-[17px] font-bold text-gray-900 tracking-tight">Kitchen Operations Board</h2>
                    <p class="text-[11px] md:text-[12px] text-gray-500 font-medium leading-none mt-1">
                        Active Intelligence: <span class="{{ $primaryText }} font-bold">{{ $stats['active'] }} orders in queue</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Section 2: Tab Navigation --}}
        <div class="shrink-0 px-4 md:px-6 mb-4">
            <x-sliding-tabs model="activeTab" class="mb-2" wire:ignore x-ref="activeTabList">
                @foreach([
                    'active' => ['Processing Queue', 'M13 10V3L4 14h7v7l9-11h-7', 'text-blue-600'],
                    'ready' => ['Ready Board', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text-emerald-600'],
                    'history' => ['Historical Record', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'text-amber-600'],
                ] as $val => $info)
                    <x-sliding-tab model="activeTab" value="{{ $val }}">
                        <x-slot name="icon">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info[1] }}"/></svg>
                        </x-slot>
                        {{ $info[0] }}
                        @if($val === 'active' && $stats['active'] > 0)
                            <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-blue-500 text-white text-[9px] font-black ml-1">{{ $stats['active'] }}</span>
                        @elseif($val === 'ready' && $stats['ready'] > 0)
                            <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-emerald-500 text-white text-[9px] font-black ml-1">{{ $stats['ready'] }}</span>
                        @endif
                    </x-sliding-tab>
                @endforeach
            </x-sliding-tabs>
        </div>

        {{-- Section 3: KDS Health Metrics --}}
        <div class="px-4 pb-4 md:px-6 md:pb-6 shrink-0 border-b border-slate-100">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">

                {{-- Active Queue --}}
                <div class="p-3 md:p-4 bg-gradient-to-br from-{{ $primaryColor }}-500/10 via-{{ $primaryColor }}-500/5 to-white border border-{{ $primaryColor }}-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 md:mb-2">
                        <span class="text-[10px] md:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Queue</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-{{ $primaryColor }}-100 flex items-center justify-center text-{{ $primaryColor }}-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $stats['active'] }}</h3>
                    <p class="text-[9px] md:text-[10px] text-slate-400 font-semibold mt-1 md:mt-1.5 leading-none">Active orders cooking</p>
                </div>

                {{-- Critical Delay --}}
                <div class="p-3 md:p-4 bg-gradient-to-br {{ $stats['delayed'] > 0 ? 'from-rose-500/10 via-rose-500/5 to-white border-rose-500/10' : 'from-slate-500/10 via-slate-500/5 to-white border-slate-500/10' }} border rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group {{ $stats['delayed'] > 0 ? '' : 'opacity-65' }}">
                    <div class="flex items-center justify-between mb-1 md:mb-2">
                        <span class="text-[10px] md:text-[11px] font-bold {{ $stats['delayed'] > 0 ? 'text-rose-600/90' : 'text-slate-400' }} uppercase tracking-wider">Delayed</span>
                        <div class="w-7 h-7 rounded-lg bg-white border {{ $stats['delayed'] > 0 ? 'border-rose-100 text-rose-600' : 'border-slate-100 text-slate-400' }} flex items-center justify-center shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl md:text-2xl font-black {{ $stats['delayed'] > 0 ? 'text-rose-600' : 'text-slate-500' }} tracking-tight leading-none">{{ $stats['delayed'] }}</h3>
                    <p class="text-[9px] md:text-[10px] text-slate-400 font-semibold mt-1 md:mt-1.5 leading-none">Pending past target limit</p>
                </div>

                {{-- Ready Board --}}
                <div class="p-3 md:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 md:mb-2">
                        <span class="text-[10px] md:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ready</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl md:text-2xl font-black text-emerald-600 tracking-tight leading-none">{{ $stats['ready'] }}</h3>
                    <p class="text-[9px] md:text-[10px] text-slate-400 font-semibold mt-1 md:mt-1.5 leading-none">Awaiting pickup/delivery</p>
                </div>

                {{-- Throughput --}}
                <div class="p-3 md:p-4 bg-gradient-to-br from-blue-500/10 via-blue-500/5 to-white border border-blue-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 md:mb-2">
                        <span class="text-[10px] md:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Served</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-blue-100 flex items-center justify-center text-blue-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $stats['completed'] }}</h3>
                    <p class="text-[9px] md:text-[10px] text-slate-400 font-semibold mt-1 md:mt-1.5 leading-none">Completed today</p>
                </div>

            </div>
        </div>

        {{-- ── Operational Content Area ── --}}
<div class="bg-white rounded-b-2xl">

{{-- HISTORY TAB --}}
            <div x-cloak x-show="$wire.activeTab === 'history'"
                 class="p-6 animate-fadeIn"
                 wire:key="kds-history-container">

                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
                    <div class="flex items-center gap-2">
                        <h3 class="text-[14px] font-black text-slate-900 tracking-tight px-2">Historical Record</h3>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                        <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" />
                    </div>
                </div>

                <div class="relative w-full border-t border-transparent mt-2">
                    <x-data-table>
                        <x-slot name="header">
                            <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Order Identity</th>
                            <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Items Detail</th>
                            <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Time Logs</th>
                            <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Final Status</th>
                        </x-slot>

                        @forelse($historyOrders as $order)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-bold text-slate-900 tracking-tight">#{{ $order->reference_no }}</span>
                                        <span class="text-[10px] font-bold uppercase text-slate-400 tracking-widest mt-0.5">{{ $order->order_type }} • {{ $order->source }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($order->items as $item)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-[11px] font-bold border border-slate-200">
                                                {{ $item->quantity }}x {{ $item->product->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="py-3 px-4 tabular-nums whitespace-nowrap">
                                    <div class="text-[12px] text-slate-600 font-medium">Placed: {{ $order->created_at->format('h:i A') }}</div>
                                    <div class="text-[11px] text-emerald-600 font-bold mt-0.5">Served: {{ $order->delivered_at?->format('h:i A') }}</div>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <span class="inline-flex px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter bg-emerald-50 text-emerald-700 border border-emerald-100">
                                        {{ $order->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12">
                                    <x-empty-state title="No History Found" description="Select a different date or check active orders." />
                                </td>
                            </tr>
                        @endforelse
                    </x-data-table>

                    @if($historyOrders instanceof \Illuminate\Pagination\Paginator && $historyOrders->hasPages())
                        <div class="mt-4">
                            <x-pagination :paginator="$historyOrders" />
                        </div>
                    @endif
                </div>
            </div>

            {{-- ACTIVE TAB --}}
            <div x-cloak x-show="$wire.activeTab === 'active'"
                 class="p-4 md:p-6 animate-fadeIn"
                 wire:key="kds-active-container">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pb-2">
                    @forelse($activeOrders as $order)
                        <div class="w-full bg-white border border-slate-200 rounded-2xl flex flex-col shadow-sm hover:shadow-md hover:border-{{ $primaryColor }}-200 transition-all group">

                            {{-- Ticket Header --}}
                            <div class="p-4 border-b border-slate-100 {{ $order->created_at->lt(now()->subMinutes(10)) ? 'bg-red-50' : 'bg-slate-50/50' }} rounded-t-2xl">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-2.5 py-1 rounded-lg {{ $primaryLight }} {{ $primaryText }} border {{ $primaryBorder }} text-[10px] font-black uppercase tracking-widest">{{ $order->order_type }}</span>
                                    <div class="ticket-timer-container flex items-center gap-1.5 px-3 py-1 rounded-full text-[12px] font-black tabular-nums transition-all bg-slate-100 text-slate-700 border border-slate-200">
                                        <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span data-created-at="{{ $order->created_at->toIso8601String() }}">0:00</span>
                                    </div>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <h2 class="text-[20px] font-black text-slate-900 tracking-tight">#{{ $order->reference_no }}</h2>
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest tabular-nums">{{ $order->created_at->format('h:i A') }}</span>
                                </div>
                                @if($order->table_number)
                                    <div class="mt-3 text-[12px] font-black {{ $primaryText }} uppercase tracking-widest flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4 4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        Table {{ $order->table_number }}
                                    </div>
                                @endif
                            </div>

                            {{-- Ticket Items --}}
                            <div class="p-4 space-y-4">
                                @foreach($order->items as $item)
                                    <div class="flex gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-{{ $primaryColor }}-50 flex items-center justify-center shrink-0 text-[13px] font-black border border-{{ $primaryColor }}-100 {{ $primaryText }}">
                                            {{ $item->quantity }}x
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-[15px] font-extrabold text-slate-900 uppercase tracking-tight leading-tight mb-1.5">{{ $item->product->name }}</h3>
                                            @if($item->options->count() > 0 || $item->modifiers->count() > 0)
                                                <div class="space-y-0.5 mt-2 p-2 bg-slate-50 rounded-lg border border-slate-100">
                                                    @foreach($item->options as $opt)
                                                        <div class="text-[10px] font-bold text-amber-600 uppercase flex items-center gap-1.5 leading-tight">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>{{ $opt->option->name }}
                                                        </div>
                                                    @endforeach
                                                    @foreach($item->modifiers as $mod)
                                                        <div class="text-[10px] font-bold text-emerald-600 uppercase flex items-center gap-1.5 leading-tight">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>{{ $mod->modifier->name }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Ticket Action --}}
                            <div class="p-4 bg-slate-50 mt-auto border-t border-slate-100 rounded-b-2xl">
                                <x-primary-button
                                    wire:click="markAsReady({{ $order->id }})"
                                    class="w-full justify-center h-11 bg-emerald-600 hover:bg-emerald-700 text-[12px] font-bold">
                                    Mark as Ready
                                </x-primary-button>
                            </div>

                        </div>
                    @empty
                        <div class="col-span-full flex items-center justify-center p-12">
                            <x-empty-state title="Kitchen is Clear" description="No orders currently preparing. Check back soon!" />
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- READY TAB --}}
            <div x-cloak x-show="$wire.activeTab === 'ready'"
                 class="p-4 md:p-6 animate-fadeIn"
                 wire:key="kds-ready-container">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pb-2">
                    @forelse($readyOrders as $order)
                        <div class="w-full bg-white border border-emerald-200 rounded-2xl flex flex-col shadow-sm hover:shadow-md hover:border-emerald-300 transition-all group bg-gradient-to-b from-emerald-50/50 to-white">

                            {{-- Ticket Header --}}
                            <div class="p-4 border-b border-emerald-100 bg-emerald-50/80 rounded-t-2xl">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-100 text-emerald-700 border border-emerald-200 text-[10px] font-black uppercase tracking-widest">{{ $order->order_type }}</span>
                                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black text-emerald-700 bg-emerald-100 border border-emerald-200">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        Ready
                                    </div>
                                </div>
                                <div class="flex items-baseline justify-between">
                                    <h2 class="text-[20px] font-black text-slate-900 tracking-tight">#{{ $order->reference_no }}</h2>
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest tabular-nums">{{ $order->updated_at->format('h:i A') }}</span>
                                </div>
                                @if($order->table_number)
                                    <div class="mt-3 text-[12px] font-black text-emerald-700 uppercase tracking-widest flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4 4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                        Table {{ $order->table_number }}
                                    </div>
                                @endif
                            </div>

                            {{-- Ticket Items --}}
                            <div class="p-4 space-y-4">
                                @foreach($order->items as $item)
                                    <div class="flex gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0 text-[13px] font-black border border-emerald-200 text-emerald-700">
                                            {{ $item->quantity }}x
                                        </div>
                                        <div class="flex-1">
                                            <h3 class="text-[15px] font-extrabold text-slate-900 uppercase tracking-tight leading-tight mb-1.5">{{ $item->product->name }}</h3>
                                            @if($item->options->count() > 0 || $item->modifiers->count() > 0)
                                                <div class="space-y-0.5 mt-2 p-2 bg-slate-50 rounded-lg border border-slate-100">
                                                    @foreach($item->options as $opt)
                                                        <div class="text-[10px] font-bold text-amber-600 uppercase flex items-center gap-1.5 leading-tight">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>{{ $opt->option->name }}
                                                        </div>
                                                    @endforeach
                                                    @foreach($item->modifiers as $mod)
                                                        <div class="text-[10px] font-bold text-emerald-600 uppercase flex items-center gap-1.5 leading-tight">
                                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>{{ $mod->modifier->name }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Ticket Action --}}
                            <div class="p-4 bg-emerald-50/80 mt-auto border-t border-emerald-100 rounded-b-2xl">
                                <x-primary-button
                                    wire:click="markAsServed({{ $order->id }})"
                                    class="w-full justify-center h-11 bg-emerald-600 hover:bg-emerald-700 text-[12px] font-bold">
                                    {{ $order->order_type === \App\Models\Order::TYPE_DELIVERY ? 'Hand to Driver' : 'Mark as Served' }}
                                </x-primary-button>
                            </div>

                        </div>
                    @empty
                        <div class="col-span-full flex items-center justify-center p-12">
                            <x-empty-state title="No Orders Ready" description="Orders will appear here once they're ready for pickup." />
                        </div>
                    @endforelse
                </div>
            </div>

        </div>{{-- end Operational Content Area --}}
    </div>{{-- end MAIN BOARD CARD (wire:ignore.self) --}}


    {{-- ════════════════════════════════════════════════════════════════════
         RIDER SELECTION MODAL
         KEY FIX: This is a SIBLING of the wire:ignore.self div, NOT a
         child. It lives inside the outer Alpine x-data wrapper so $wire
         still resolves, but outside wire:ignore.self so that Livewire
         fully processes all wire:click events inside it.
         ════════════════════════════════════════════════════════════════════ --}}
    <div
        x-show="$wire.showRiderModal"
        x-cloak
        class="fixed inset-0 z-[9999] overflow-y-auto px-4 py-6 sm:px-0">

        {{-- Backdrop --}}
        <div
            class="fixed inset-0 bg-gray-900/40"
            style="backdrop-filter:blur(4px);"
            wire:click="closeRiderModal">
        </div>

        {{-- Modal Panel --}}
        <div
            x-show="$wire.showRiderModal"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            class="mb-6 bg-white rounded-xl overflow-hidden shadow-2xl sm:w-full sm:max-w-md sm:mx-auto relative z-10">

            <div class="p-8">
                <div class="mb-6">
                    <h3 class="text-[20px] font-black text-slate-900 tracking-tight mb-2">Assign Rider to Delivery</h3>
                    <p class="text-[13px] text-slate-500 font-medium">Select an available driver to handle this delivery order.</p>
                </div>

                <div class="space-y-2 mb-6 max-h-96 overflow-y-auto">
                    @if(count($availableRiders) > 0)
                        @foreach($availableRiders as $rider)
                            <button
                                type="button"
                                wire:click="assignRiderAndDeliver({{ $rider['id'] }})"
                                class="w-full text-left p-4 rounded-2xl border-2 border-slate-200 hover:border-emerald-400 hover:bg-emerald-50 transition-all duration-200 group">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-[14px] font-bold text-slate-900 group-hover:text-emerald-700">{{ $rider['name'] }}</h4>
                                        <p class="text-[12px] text-slate-500 mt-1">📞 {{ $rider['phone'] }}</p>
                                        <div class="mt-2 inline-flex items-center gap-2 px-2.5 py-1 rounded-lg bg-slate-100 group-hover:bg-emerald-100">
                                            <span class="w-2 h-2 rounded-full {{ $rider['active_orders'] > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                                            <span class="text-[11px] font-bold text-slate-700 group-hover:text-emerald-700">
                                                {{ $rider['active_orders'] }} active {{ $rider['active_orders'] === 1 ? 'order' : 'orders' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ml-2">
                                        <svg class="w-5 h-5 text-slate-300 group-hover:text-emerald-500 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    @else
                        <div class="p-6 text-center">
                            <p class="text-[13px] font-bold text-slate-900">No Available Riders</p>
                            <p class="text-[12px] text-slate-500 mt-2">Please check back later or contact your manager.</p>
                        </div>
                    @endif
                </div>

                <button
                    wire:click="closeRiderModal"
                    class="w-full py-3 px-4 rounded-xl border-2 border-slate-200 hover:border-slate-300 text-slate-700 font-bold text-[13px] hover:bg-slate-50 transition-all">
                    Cancel
                </button>
            </div>
        </div>{{-- end Modal Panel --}}

    </div>{{-- end Rider Modal --}}


    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.05); border-radius: 10px; }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }
        .animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }

        @keyframes growWidth { from { width: 0; } }
        .animate-growWidth { animation: growWidth 1s ease-out forwards; }
    </style>

   </div>{{-- end root wrapper --}}