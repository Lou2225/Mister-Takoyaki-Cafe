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

<div
    x-data="kdsDisplay(@js($activeTab))"
    class="relative bg-[#F9FAFB] min-h-[calc(100vh-65px)] p-4"
    x-cloak>

    {{-- ════════════════ MASTER PAGE CONTAINER ════════════════ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col">
        
        {{-- Section 1: Title & Action (Matching Stock Header) --}}
        <div class="px-6 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Kitchen Operations Board</h2>
                    <p class="text-[12px] text-gray-500 font-medium leading-none mt-1">
                        Active Intelligence: <span class="{{ $primaryText }} font-bold">{{ $stats['active'] }} orders in queue</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <x-secondary-button wire:click="$refresh" class="h-10 text-[12px]">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Refresh Board
                    </x-secondary-button>
                </div>
            </div>
        </div>

        {{-- Section 2: Tab Navigation (Mirrored from BI) --}}
        <x-sliding-tabs model="activeTab" class="mb-6 px-1" ref="tabList" wire:ignore>
            @foreach([
                'active' => ['Processing Queue', 'M13 10V3L4 14h7v7l9-11h-7'],
                'ready' => ['Ready Board', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                'history' => ['Historical Record', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z']
            ] as $tab => $info)
                <x-sliding-tab model="activeTab" value="{{ $tab }}">
                    <x-slot name="icon">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info[1] }}"/></svg>
                    </x-slot>
                    {{ $info[0] }}
                    @if($tab === 'active' && $stats['active'] > 0)
                        <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full {{ $primaryBg }} text-white text-[9px] font-black shadow-sm ml-1">{{ $stats['active'] }}</span>
                    @endif
                </x-sliding-tab>
            @endforeach
        </x-sliding-tabs>

        {{-- Section 3: Metrics & Content Flow --}}
        <div class="flex flex-col bg-white rounded-b-2xl">
            
            {{-- ── KDS Health Dashboard Area ── --}}
            <div class="px-6 pb-6 shrink-0 border-b border-slate-100">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    {{-- Active Queue --}}
                    <div class="bg-gradient-to-br from-{{ $primaryColor }}-50 to-{{ $primaryColor }}-100 border border-{{ $primaryColor }}-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 transition-all group hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-white border border-{{ $primaryColor }}-100 flex items-center justify-center text-{{ $primaryColor }}-600 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black text-{{ $primaryColor }}-700/60 uppercase tracking-widest leading-none mb-1">Queue Size</span>
                            <span class="block text-[20px] font-black text-gray-900 leading-none">{{ $stats['active'] }}</span>
                        </div>
                    </div>
                    
                    {{-- Critical Delay --}}
                    <div class="bg-gradient-to-br {{ $stats['delayed'] > 0 ? 'from-rose-50 to-rose-100 border-rose-200' : 'from-slate-50 to-slate-100 border-slate-200' }} border rounded-2xl p-4 shadow-sm flex items-center gap-4 transition-all group {{ $stats['delayed'] > 0 ? 'hover:shadow-md' : 'hover:shadow-sm opacity-60' }}">
                        <div class="w-10 h-10 rounded-xl bg-white border {{ $stats['delayed'] > 0 ? 'border-rose-100 text-rose-600' : 'border-slate-100 text-slate-400' }} flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black {{ $stats['delayed'] > 0 ? 'text-rose-700/60' : 'text-slate-400' }} uppercase tracking-widest leading-none mb-1">Critical Delay</span>
                            <span class="block text-[20px] font-black {{ $stats['delayed'] > 0 ? 'text-rose-600' : 'text-slate-400' }} leading-none">{{ $stats['delayed'] }}</span>
                        </div>
                    </div>

                    {{-- Ready Board --}}
                    <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 border border-emerald-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 transition-all group hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black text-emerald-700/60 uppercase tracking-widest leading-none mb-1">Items Ready</span>
                            <span class="block text-[20px] font-black text-gray-900 leading-none">{{ $stats['ready'] }}</span>
                        </div>
                    </div>

                    {{-- Throughput --}}
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 transition-all group hover:shadow-md">
                        <div class="w-10 h-10 rounded-xl bg-white border border-blue-100 flex items-center justify-center text-blue-600 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black text-blue-700/60 uppercase tracking-widest leading-none mb-1">Served Today</span>
                            <span class="block text-[20px] font-black text-gray-900 leading-none">{{ $stats['completed'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Operational Content Area ── --}}
            <div class="flex flex-col bg-white rounded-b-2xl">
                <div x-cloak x-show="activeTab === 'history'" 
                     class="p-6 animate-fadeIn" wire:poll.2s>
                    
                    {{-- macOS Style Unified Toolbar --}}
                    <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
                        <div class="flex items-center px-2">
                            <h3 class="text-[14px] font-black text-slate-900 tracking-tight">Historical Record</h3>
                        </div>
                        <div class="flex flex-wrap items-center lg:justify-end gap-2">
                            <x-date-range-filter startModel="startDate" endModel="endDate" class="shadow-none border-slate-200" />
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

                            @forelse($orders as $order)
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
                        
                        @if($orders instanceof \Illuminate\Pagination\Paginator && $orders->hasPages())
                            <div class="mt-4">
                                <x-pagination :paginator="$orders" />
                            </div>
                        @endif
                    </div>
                </div>

                <div x-cloak x-show="activeTab === 'active' || activeTab === 'ready'" 
                    class="p-6 animate-fadeIn" wire:poll.2s>
                    <div class="flex gap-4 items-stretch flex-wrap pb-2">
                        @forelse($orders as $order)
                            <div class="w-[340px] shrink-0 bg-white border border-slate-200 rounded-2xl flex flex-col shadow-sm hover:shadow-md hover:border-{{ $primaryColor }}-200 transition-all group">
                                {{-- Ticket Title --}}
                                <div class="p-4 border-b border-slate-100 {{ $stats['delayed'] > 0 && $order->isPreparing() && $order->created_at->lt(now()->subMinutes(10)) ? 'bg-red-50' : 'bg-slate-50/50' }} rounded-t-2xl">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="px-2.5 py-1 rounded-lg {{ $primaryLight }} {{ $primaryText }} border {{ $primaryBorder }} text-[10px] font-black uppercase tracking-widest">{{ $order->order_type }}</span>
                                        <div class="ticket-timer-container flex items-center gap-1.5 px-3 py-1 rounded-full text-[12px] font-black tabular-nums transition-all bg-slate-100 text-slate-700 border border-slate-200 group-hover:bg-{{ $primaryColor }}-50 group-hover:text-{{ $primaryColor }}-700 group-hover:border-{{ $primaryColor }}-200">
                                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span data-created-at="{{ $order->created_at->toIso8601String() }}">0:00</span>
                                        </div>
                                    </div>
                                    <div class="flex items-baseline justify-between">
                                        <h2 class="text-[20px] font-black text-slate-900 tracking-tight">{{ $order->reference_no }}</h2>
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

                                {{-- Action --}}
                                <div class="p-4 bg-slate-50 mt-auto border-t border-slate-100 rounded-b-2xl">
                                    @if($activeTab === 'active')
                                        <x-primary-button wire:click="markAsReady({{ $order->id }})" class="w-full justify-center h-11 bg-emerald-600 hover:bg-emerald-700 text-[12px] font-bold">Mark as Ready</x-primary-button>
                                    @else
                                        <x-primary-button wire:click="markAsServed({{ $order->id }})" class="w-full justify-center h-11 {{ $primaryBg }} text-[12px] font-bold">
                                            {{ $order->order_type === \App\Models\Order::TYPE_DELIVERY ? 'Hand to Driver' : 'Mark as Served' }}
                                        </x-primary-button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="flex-1 h-full flex items-center justify-center p-12">
                                <x-empty-state title="Kitchen is Clear" description="Station is at standby." />
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════ RIDER SELECTION MODAL ════════════════ --}}
    <x-modal name="assign-rider-modal" :show="$showRiderModal" maxWidth="md">
        <div class="p-8">
            {{-- Header --}}
            <div class="mb-6">
                <h3 class="text-[20px] font-black text-slate-900 tracking-tight mb-2">Assign Rider to Delivery</h3>
                <p class="text-[13px] text-slate-500 font-medium">Select an available driver to handle this delivery order.</p>
            </div>

            {{-- Riders List --}}
            <div class="space-y-2 mb-6 max-h-96 overflow-y-auto custom-scrollbar">
                @if(count($availableRiders) > 0)
                    @foreach($availableRiders as $rider)
                    <button wire:click="assignRiderAndDeliver({{ $rider['id'] }})" 
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
                                <svg class="w-5 h-5 text-slate-300 group-hover:text-emerald-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </button>
                    @endforeach
                @else
                    <div class="p-6 text-center">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-[13px] font-bold text-slate-900">No Available Riders</p>
                        <p class="text-[12px] text-slate-500 mt-2">Please check back later or contact your manager.</p>
                    </div>
                @endif
            </div>

            {{-- Close Button --}}
            <button wire:click="closeRiderModal" class="w-full py-3 px-4 rounded-xl border-2 border-slate-200 hover:border-slate-300 text-slate-700 font-bold text-[13px] hover:bg-slate-50 transition-all">
                Cancel
            </button>
        </div>
    </x-modal>

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.05); border-radius: 10px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeIn { animation: fadeIn 0.4s ease-out forwards; }
        
        @keyframes growWidth { from { width: 0; } }
        .animate-growWidth { animation: growWidth 1s ease-out forwards; }
    </style>

    @once
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('kdsDisplay', (tab) => {
            const tabs = window.createTabComponent({ activeTab: tab }, 'activeTab');
            return {
                ...tabs,
                _int: null,
                init() {
                    tabs.init.call(this);
                    this.updateTimers();
                    this._int = setInterval(() => this.updateTimers(), 1000);
                },
                destroy() {
                    if (this._int) clearInterval(this._int);
                },
                updateTimers() {
                    const now = new Date();
                    document.querySelectorAll('[data-created-at]').forEach(el => {
                        const createdAt = el.dataset.createdAt;
                        if (!createdAt) return;
                        const diff = Math.floor((now - new Date(createdAt)) / 1000);
                        const m = Math.floor(diff / 60);
                        const s = diff % 60;
                        el.innerText = `${m}:${s.toString().padStart(2, '0')}`;

                        const container = el.closest('.ticket-timer-container');
                        if (container) {
                            container.className = `ticket-timer-container flex items-center gap-1.5 px-3 py-1 rounded-full text-[13px] font-black tabular-nums transition-colors ` +
                                (m >= 10 ? 'bg-red-50 text-red-600 border border-red-100 animate-pulse' :
                                    (m >= 5 ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-slate-50 text-slate-600 border border-slate-100'));
                        }
                    });
                }
            };
        });
    });
    </script>
    @endonce
</div>
