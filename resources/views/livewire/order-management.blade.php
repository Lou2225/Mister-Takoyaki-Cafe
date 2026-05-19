@php
    $user = auth()->user();
    // Primary palette
    $primaryColor = $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald');
    $primaryText = "text-{$primaryColor}-600";
    $primaryBg = "bg-{$primaryColor}-600";
@endphp

<div 
    x-data="{
        ...slidingTabs(@entangle('sourceFilter').live, 'sourceFilter'),
        selectedOrderId: null
    }"
    class="relative overflow-hidden">

    <div class="relative min-h-[600px]">

        {{-- ════ Shared header & tabs ════ --}}
        <div class="px-1">
            {{-- Page title + actions --}}
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Order Management</h2>
                    <p class="text-[12px] text-gray-500 font-medium whitespace-nowrap overflow-hidden text-ellipsis flex items-center gap-1">
                        Managing 
                        <span x-show="sourceFilter === 'App'" class="{{ $primaryText }} font-bold">{{ $appOrders->total() }} {{ Str::plural('order', $appOrders->total()) }}</span>
                        <span x-show="sourceFilter === 'POS'" x-cloak class="{{ $primaryText }} font-bold">{{ $posOrders->total() }} {{ Str::plural('order', $posOrders->total()) }}</span>
                        <span x-show="sourceFilter === 'History'" x-cloak class="{{ $primaryText }} font-bold">{{ $historyOrders->total() }} {{ Str::plural('order', $historyOrders->total()) }}</span>
                    </p>
                </div>
            </div>

            {{-- Tab Navigation --}}
            <x-sliding-tabs model="sourceFilter" class="mb-5 px-1" wire:ignore>
                @foreach([
                    'App' => ['Delivery Orders', 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9-4v4m4-4v4', 'text-blue-600'],
                    'POS' => ['POS Orders', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text-amber-600'],
                    'History' => ['Order History', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'text-emerald-600'],
                ] as $val => $info)
                    <x-sliding-tab model="sourceFilter" value="{{ $val }}" @click="$wire.set('statusFilter', '')">
                        <x-slot name="icon">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $info[1] }}"/></svg>
                        </x-slot>
                        {{ $info[0] }}
                        @if($val === 'App')
                            @php
                                $deliveryCount = \App\Models\Order::where('source', 'App')
                                    ->where('branch_id', auth()->user()->branch_id)
                                    ->whereIn('status', ['Pending', 'Preparing'])
                                    ->count();
                            @endphp
                            @if($deliveryCount > 0)
                                <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-blue-500 text-white text-[9px] font-black ml-1">{{ $deliveryCount }}</span>
                            @endif
                        @elseif($val === 'POS')
                            @php
                                $posCount = \App\Models\Order::where('source', 'POS')
                                    ->where('branch_id', auth()->user()->branch_id)
                                    ->whereIn('status', ['Drafted', 'Void'])
                                    ->count();
                            @endphp
                            @if($posCount > 0)
                                <span class="inline-flex items-center justify-center min-w-[16px] h-4 px-1 rounded-full bg-amber-500 text-white text-[9px] font-black ml-1">{{ $posCount }}</span>
                            @endif
                        @endif
                    </x-sliding-tab>
                @endforeach
            </x-sliding-tabs>
        </div>

        <div class="px-1 mt-4">

            {{-- ── Order Health Overview ── --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6">
                {{-- Total Orders --}}
                <div class="p-3 sm:p-4 bg-gradient-to-br from-{{ $primaryColor }}-500/10 via-{{ $primaryColor }}-500/5 to-white border border-{{ $primaryColor }}-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Orders</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-{{ $primaryColor }}-100 flex items-center justify-center text-{{ $primaryColor }}-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    </div>
                    <span x-show="sourceFilter === 'App'">
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $appOrders->total() }}</h3>
                    </span>
                    <span x-show="sourceFilter === 'POS'" x-cloak>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $posOrders->total() }}</h3>
                    </span>
                    <span x-show="sourceFilter === 'History'" x-cloak>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $historyOrders->total() }}</h3>
                    </span>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Placed orders count</p>
                </div>
                
                {{-- Pending Orders --}}
                @php
                    $pendingCount = \App\Models\Order::where('branch_id', auth()->user()->branch_id)
                        ->whereIn('status', ['Pending', 'Preparing'])
                        ->count();
                @endphp
                <div class="p-3 sm:p-4 bg-gradient-to-br {{ $pendingCount > 0 ? 'from-amber-500/10 via-amber-500/5 to-white border-amber-500/10' : 'from-emerald-500/10 via-emerald-500/5 to-white border-emerald-500/10' }} border rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold {{ $pendingCount > 0 ? 'text-amber-600/90' : 'text-emerald-600/90' }} uppercase tracking-wider">Pending</span>
                        <div class="w-7 h-7 rounded-lg bg-white border {{ $pendingCount > 0 ? 'border-amber-100 text-amber-600' : 'border-emerald-100 text-emerald-600' }} flex items-center justify-center shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black {{ $pendingCount > 0 ? 'text-amber-600' : 'text-emerald-600' }} tracking-tight leading-none">{{ number_format($pendingCount) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Awaiting kitchen service</p>
                </div>

                {{-- Completed Orders Today --}}
                @php
                    $completedToday = \App\Models\Order::where('branch_id', auth()->user()->branch_id)
                        ->where('status', 'Completed')
                        ->whereDate('created_at', today())
                        ->count();
                @endphp
                <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Completed Today</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-emerald-600 tracking-tight leading-none">{{ number_format($completedToday) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Served and cleared today</p>
                </div>

                {{-- Today's Revenue --}}
                @php
                    $todayRevenue = \App\Models\Order::where('branch_id', auth()->user()->branch_id)
                        ->where('payment_status', 'Paid')
                        ->whereDate('created_at', today())
                        ->sum('total_amount');
                @endphp
                <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Today's Revenue</span>
                        <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                    </div>
                    <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">₱{{ number_format($todayRevenue, 0) }}</h3>
                    <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Net generated income today</p>
                </div>
            </div>

            {{-- macOS Style Unified Toolbar --}}
            <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm">
                
                {{-- Left: Search Bar --}}
                <div class="w-full lg:w-auto lg:flex-1">
                    <x-search-bar wire:model.live.debounce.300ms="search" placeholder="Search order #..." width="w-full lg:w-80" />
                </div>

                {{-- Right: Filters --}}
                <div class="w-full lg:w-auto">
                    <div class="flex items-center gap-2 w-full lg:w-auto justify-between lg:justify-end">
                        {{-- 3-in-1 Date Filter Component --}}
                        <div class="w-full lg:w-auto">
                            <x-date-filter startModel="startDate" endModel="endDate" activeModel="activeFilter" />
                        </div>

                        {{-- Status Filter Dropdown --}}
                        <div class="w-[140px] sm:w-[160px]">
                            <x-dropdown align="right" width="full" containerClasses="w-full">
                                <x-slot name="trigger">
                                    <x-secondary-button type="button" class="w-full gap-1.5 h-10 !px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none justify-between truncate">
                                        <div class="flex items-center gap-1.5 truncate">
                                            <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                                            <span class="text-[12px] truncate">{{ $statusFilter ?: 'All Status' }}</span>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </x-secondary-button>
                                </x-slot>
                                <x-slot name="content">
                                    <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', '')">All Status</x-dropdown-link>
                                    <hr class="my-1 border-slate-100">
                                    @foreach($statuses as $status => $label)
                                        <x-dropdown-link href="#" wire:click.prevent="$set('statusFilter', '{{ $status }}')">{{ $label }}</x-dropdown-link>
                                    @endforeach
                                </x-slot>
                            </x-dropdown>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full animate-fadeIn relative">
                {{-- App Orders Tab Content --}}
                <div x-show="sourceFilter === 'App'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="w-full">
                    @include('livewire.order-list-table', ['orders' => $appOrders])
                </div>

                {{-- POS Orders Tab Content --}}
                <div x-show="sourceFilter === 'POS'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="w-full" x-cloak>
                    @include('livewire.order-list-table', ['orders' => $posOrders])
                </div>

                {{-- History Orders Tab Content --}}
                <div x-show="sourceFilter === 'History'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="w-full" x-cloak>
                    @include('livewire.order-list-table', ['orders' => $historyOrders])
                </div>
            </div>

        </div>
        <x-side-panel name="view-order-detail" width="max-w-md">
            @foreach($allLoadedOrders as $order)
                <div x-show="selectedOrderId === {{ $order->id }}" x-cloak class="flex flex-col h-full bg-white relative"
                    x-data="{ 
                        ...slidingTabs('summary', 'activeTab'), 
                        activeTab: 'summary' 
                    }">
                    {{-- Premium Header --}}
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/40">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-900 flex items-center justify-center text-white shadow-lg shadow-slate-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-[15px] font-black text-slate-900 tracking-tight leading-none">Order Details</h3>
                                <span class="text-[11px] text-indigo-600 font-bold uppercase tracking-wider mt-1 block">Ref: #{{ $order->reference_no }}</span>
                            </div>
                        </div>
                        <button @click="$dispatch('close-modal', 'view-order-detail')" class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:bg-white hover:text-slate-600 hover:shadow-sm transition-all border border-transparent hover:border-slate-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Tabs Navigation --}}
                    <div class="px-6 bg-white">
                        <x-sliding-tabs model="activeTab">
                            <x-sliding-tab value="summary" model="activeTab">Summary</x-sliding-tab>
                            <x-sliding-tab value="activity" model="activeTab">Order History</x-sliding-tab>
                        </x-sliding-tabs>
                    </div>

                    {{-- Content Area --}}
                    <div class="flex-1 overflow-y-auto custom-scrollbar relative">
                        <div x-show="activeTab === 'summary'">
                            {{-- Transaction Context Section --}}
                            <div class="p-6 bg-gradient-to-b from-slate-50/80 to-white border-b border-slate-50">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Source & Branch</span>
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm">
                                                @if($order->source === 'POS')
                                                    <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                @else
                                                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                                @endif
                                            </div>
                                            <span class="text-[12px] font-bold text-slate-700">{{ $order->branch->branch_name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Status</span>
                                        @php
                                            $statusColors = [
                                                'Pending' => 'amber',
                                                'Preparing' => 'blue',
                                                'Ready' => 'emerald',
                                                'Handed to Rider' => 'indigo',
                                                'Out for Delivery' => 'indigo',
                                                'Delivered' => 'emerald',
                                                'Completed' => 'emerald',
                                                'Cancelled' => 'red',
                                                'Drafted' => 'slate',
                                                'Void' => 'rose',
                                                'Refunded' => 'orange',
                                                'Partially Refunded' => 'orange',
                                            ];
                                            $statusColor = $statusColors[$order->status] ?? 'slate';
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-{{ $statusColor }}-50 text-{{ $statusColor }}-600 border border-{{ $statusColor }}-100">
                                            {{ $order->status }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Processed By</span>
                                        <span class="text-[12px] font-bold text-slate-700">{{ $order->user->first_name ?? 'System' }} {{ $order->user->last_name ?? '' }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Date & Time</span>
                                        <span class="text-[12px] font-bold text-slate-700">{{ $order->created_at->format('M d, h:i A') }}</span>
                                    </div>
                                </div>

                                @if($order->source === 'App')
                                <div class="mt-4 pt-4 border-t border-slate-100">
                                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1.5">Customer details</span>
                                    <div class="text-[12px] text-slate-700 font-medium">
                                        <div class="font-bold">{{ $order->customer_name ?? 'N/A' }} <span class="text-slate-400 font-normal">({{ $order->customer_phone ?? 'No Phone' }})</span></div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 leading-tight">{{ $order->delivery_address ?? 'No Address' }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- Itemized Breakdown Section --}}
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h5 class="text-[11px] font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-900"></span>
                                        Order Summary
                                    </h5>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $order->items->count() }} Items</span>
                                </div>

                                <div class="space-y-6 relative">
                                    {{-- Vertical line --}}
                                    <div class="absolute left-[7px] top-2 bottom-2 w-[2px] bg-slate-50 rounded-full"></div>

                                    @forelse($order->items as $item)
                                        <div class="relative pl-7 group">
                                            {{-- Dot --}}
                                            <div class="absolute left-0 top-[6px] w-[16px] h-[16px] rounded-full border-4 border-white bg-slate-100 group-hover:bg-slate-900 transition-colors z-10"></div>
                                            
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <p class="text-[13px] font-bold text-slate-900 leading-tight">{{ $item->product->name ?? 'Unknown Item' }}</p>
                                                    @if($item->options && $item->options->isNotEmpty())
                                                        <div class="flex flex-wrap gap-x-2 gap-y-0.5 mt-1">
                                                            @foreach($item->options as $opt)
                                                                <span class="text-[11px] text-slate-400 italic">{{ $opt->option->name ?? 'N/A' }}</span>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                    <p class="text-[11px] font-bold text-slate-400 mt-1">₱{{ number_format($item->unit_price, 2) }} × {{ $item->quantity }}</p>
                                                </div>
                                                <div class="text-right">
                                                    <span class="text-[13px] font-black text-slate-900">₱{{ number_format($item->quantity * $item->unit_price, 2) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-[12px] text-slate-500 italic pl-7">No items in this order</p>
                                    @endforelse
                                </div>
                            </div>

                            {{-- Financial Totals Section --}}
                            <div class="p-6 bg-slate-50 border-t border-slate-100">
                                <div class="space-y-2.5">
                                    <div class="flex justify-between text-[12px] font-medium text-slate-500">
                                        <span>Subtotal</span>
                                        <span class="font-bold text-slate-700">₱{{ number_format($order->total_amount - $order->delivery_fee + $order->discount_amount, 2) }}</span>
                                    </div>
                                    @if($order->discount_amount > 0)
                                        <div class="flex justify-between text-[12px] font-bold text-rose-500">
                                            <span>Applied Discount</span>
                                            <span>-₱{{ number_format($order->discount_amount, 2) }}</span>
                                        </div>
                                    @endif
                                    @if($order->delivery_fee > 0)
                                        <div class="flex justify-between text-[12px] font-medium text-slate-500">
                                            <span>Delivery Fee</span>
                                            <span class="font-bold text-slate-700">₱{{ number_format($order->delivery_fee, 2) }}</span>
                                        </div>
                                    @endif
                                    <div class="pt-3 mt-3 border-t border-slate-200 flex justify-between items-baseline">
                                        <span class="text-[13px] font-black text-slate-900 uppercase tracking-tight">Grand Total</span>
                                        <span class="text-[24px] font-black text-slate-900 tracking-tighter">₱{{ number_format($order->total_amount, 2) }}</span>
                                    </div>
                                    
                                    @if($order->refunded_amount > 0)
                                        <div class="mt-3 pt-3 border-t border-red-100 flex justify-between text-[12px] font-bold text-red-600">
                                            <span>Refunded Amount</span>
                                            <span>-₱{{ number_format($order->refunded_amount, 2) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div x-show="activeTab === 'activity'" style="display: none;">
                            <div class="p-6">
                                <h5 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-6">Activity Timeline</h5>
                                <div class="space-y-6 relative">
                                    {{-- Vertical line --}}
                                    <div class="absolute left-[15px] top-2 bottom-2 w-[2px] bg-slate-100 rounded-full"></div>

                                    @php
                                        $timeline = collect([
                                            ['label' => 'Order Placed', 'time' => $order->created_at, 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'slate'],
                                        ]);

                                        if ($order->accepted_at) {
                                            $timeline->push(['label' => 'Order Accepted', 'time' => $order->accepted_at, 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'blue']);
                                        }

                                        if ($order->prepared_at) {
                                            $timeline->push(['label' => 'Order Prepared', 'time' => $order->prepared_at, 'icon' => 'M5 13l4 4L19 7', 'color' => 'indigo']);
                                        }

                                        if ($order->dispatched_at) {
                                            $timeline->push(['label' => 'Dispatched to Rider', 'time' => $order->dispatched_at, 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'amber']);
                                        }

                                        if ($order->delivered_at) {
                                            $timeline->push(['label' => 'Order Delivered/Completed', 'time' => $order->delivered_at, 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald']);
                                        } elseif ($order->status === 'Completed') {
                                            $timeline->push(['label' => 'Order Completed', 'time' => $order->updated_at, 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'emerald']);
                                        }

                                        if ($order->refunded_at) {
                                            $timeline->push(['label' => 'Order Refunded/Voided', 'time' => $order->refunded_at, 'icon' => 'M6 18L18 6M6 6l12 12', 'color' => 'red', 'desc' => $order->refund_reason]);
                                        } else if (in_array($order->status, ['Cancelled', 'Void'])) {
                                            $timeline->push(['label' => 'Order ' . $order->status, 'time' => $order->updated_at, 'icon' => 'M6 18L18 6M6 6l12 12', 'color' => 'red', 'desc' => $order->notes]);
                                        }

                                        $timeline = $timeline->sortByDesc('time')->values();
                                    @endphp

                                    @foreach($timeline as $index => $event)
                                        <div class="relative pl-10 group">
                                            {{-- Icon Dot --}}
                                            <div class="absolute left-0 top-[2px] w-8 h-8 rounded-full bg-{{ $event['color'] }}-50 flex items-center justify-center border-4 border-white z-10 shadow-sm">
                                                <svg class="w-3.5 h-3.5 text-{{ $event['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $event['icon'] }}"/></svg>
                                            </div>
                                            
                                            <div class="flex justify-between items-start pt-1">
                                                <div>
                                                    <p class="text-[13px] font-bold text-slate-900 leading-tight">{{ $event['label'] }}</p>
                                                    @if(isset($event['desc']) && $event['desc'])
                                                        <p class="text-[11px] text-slate-500 mt-0.5 max-w-[200px]">{{ $event['desc'] }}</p>
                                                    @endif
                                                </div>
                                                <div class="text-right">
                                                    <span class="block text-[11px] font-bold text-slate-700">{{ $event['time']->format('M d, Y') }}</span>
                                                    <span class="block text-[10px] text-slate-400">{{ $event['time']->format('h:i A') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Action Footer --}}
                    <div class="p-5 border-t border-slate-100 bg-white grid grid-cols-2 gap-2 shrink-0" x-show="activeTab === 'summary'">
                        {{-- 1. App Specific Progress Actions --}}
                        @if($order->source === 'App')
                            @if($order->status === 'Pending')
                                <x-primary-button wire:click="acceptOrder({{ $order->id }})" class="col-span-1 h-10 justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Accept
                                </x-primary-button>
                                @if($order->created_at->diffInHours(now()) <= 24)
                                    <x-secondary-button wire:click="openRejectModal({{ $order->id }})" class="col-span-1 h-10 justify-center text-red-600 border-red-200">
                                        Reject
                                    </x-secondary-button>
                                @else
                                    <div class="col-span-1"></div>
                                @endif
                            @elseif($order->status === 'Preparing')
                                <x-primary-button wire:click="openHandToRiderModal({{ $order->id }})" class="col-span-2 h-10 justify-center bg-indigo-600 hover:bg-indigo-700">
                                    Hand to Rider
                                </x-primary-button>
                                <x-secondary-button wire:click="markAsOutForDelivery({{ $order->id }})" class="col-span-2 h-10 justify-center">
                                    Out for Delivery
                                </x-secondary-button>
                            @elseif(in_array($order->status, ['Handed to Rider', 'Out for Delivery']))
                                <x-primary-button wire:click="markAsDelivered({{ $order->id }})" class="col-span-2 h-10 justify-center bg-emerald-600 hover:bg-emerald-700">
                                    Mark as Delivered
                                </x-primary-button>
                            @endif
                        @endif

                        {{-- 2. POS Draft Actions --}}
                        @if($order->source === 'POS' && $order->status === 'Drafted')
                            <x-primary-button wire:click="restoreDraft({{ $order->id }})" class="col-span-2 h-10 justify-center">
                                Restore to Cart
                            </x-primary-button>
                            <x-secondary-button wire:click="deleteDraft({{ $order->id }})" class="col-span-2 h-10 justify-center text-red-600 border-red-200">
                                Delete Draft
                            </x-secondary-button>
                        @endif

                        {{-- 3. Consolidated Refund Action (Respects 1-hour window via model) --}}
                        @if($order->canBeRefunded())
                            <x-primary-button wire:click="openRefundModal({{ $order->id }})" class="col-span-2 h-10 justify-center">
                                Refund Order
                            </x-primary-button>
                        @endif

                        {{-- 4. Consolidated Void Action --}}
                        @if($order->canBeVoided())
                            <x-danger-button wire:click="openVoidModal({{ $order->id }})" class="{{ $order->status !== 'Drafted' ? 'col-span-1' : 'col-span-2' }} h-10 justify-center text-red-600 bg-red-50 hover:bg-red-100 border-red-200">
                                Void Order
                            </x-danger-button>
                        @endif

                        {{-- 5. Standard Global Actions --}}
                        @if($order->status !== 'Drafted')
                            <x-secondary-button @click="$dispatch('open-modal', 'receipt-modal')" class="{{ $order->canBeVoided() ? 'col-span-1' : 'col-span-2' }} h-10 justify-center">
                                View Receipt
                            </x-secondary-button>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-side-panel>

    </div>{{-- end relative wrapper --}}


{{-- ════════════════ MODALS ════════════════ --}}

{{-- Refund Modal --}}
<x-modal name="refund-modal" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-blue-400 to-indigo-500 rounded-t-lg"></div>
    <div class="p-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h10m4 0a1 1 0 11-2 0 1 1 0 012 0z"/></svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900">Process Refund</h3>
                <p class="mt-1 text-[12px] text-gray-500">Enter refund details below</p>
            </div>
        </div>

        @if($selectedOrder)
            <div class="space-y-4">
                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-3">
                    <p class="text-[12px] text-blue-900 font-medium">
                        <strong>Order #{{ $selectedOrder->reference_no }}</strong> • ₱{{ number_format($selectedOrder->total_amount, 2) }}<br>
                        <span class="text-blue-700 font-bold">Refundable: ₱{{ number_format($selectedOrder->refundable_amount, 2) }}</span>
                    </p>
                </div>

                <div>
                    <x-input-label for="refund_amount" value="Refund Amount (₱) *" />
                    <div class="relative mt-1.5">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 text-sm">₱</span>
                        </div>
                        <x-text-input id="refund_amount" wire:model.live="refundAmount" type="text" class="block w-full h-11 pl-8" placeholder="0.00" inputFilter="price" :hasError="$errors->has('refundAmount')" />
                    </div>
                    <x-input-error :messages="$errors->get('refundAmount')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="refund_reason" value="Reason for Refund *" />
                    <textarea id="refund_reason" wire:model.live="refundReason" rows="2" placeholder="e.g., Customer request, product damage..."
                        class="mt-1.5 block w-full border border-gray-200 rounded-xl px-3 py-2 text-[13px] focus:border-blue-300 focus:ring-0 resize-none transition-all"></textarea>
                    <x-input-error :messages="$errors->get('refundReason')" class="mt-1" />
                </div>
            </div>
        @endif

        <div class="flex items-center justify-end gap-2 px-6 py-4 bg-gray-50 text-right -mx-6 -mb-6 mt-6 rounded-b-lg border-t border-gray-100">
            <x-secondary-button @click="$dispatch('close-modal', 'refund-modal')" wire:click="$set('refundAmount', 0); $set('refundReason', '')" class="h-11">
                Cancel
            </x-secondary-button>
            <x-primary-button wire:click="submitRefund" class="h-11">
                Process Refund
            </x-primary-button>
        </div>
    </div>
</x-modal>

{{-- Reject Modal --}}
<x-modal name="reject-modal" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-red-400 to-rose-500 rounded-t-lg"></div>
    <div class="p-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-50 border border-red-100 flex items-center justify-center text-red-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900">Reject Order</h3>
                <p class="mt-1 text-[12px] text-gray-500">Provide a reason for rejection</p>
            </div>
        </div>

        @if($selectedOrder)
            <div class="space-y-4">
                <div class="bg-red-50 border border-red-100 rounded-xl p-3">
                    <p class="text-[12px] text-red-900 font-medium">
                        Are you sure you want to reject <strong>Order #{{ $selectedOrder->reference_no }}</strong>? The customer will be notified.
                    </p>
                </div>

                <div>
                    <x-input-label for="reject_reason" value="Reason for Rejection (Optional)" />
                    <textarea id="reject_reason" wire:model="rejectReason" rows="2" placeholder="e.g., Out of stock, branch closing..."
                        class="mt-1.5 block w-full border border-gray-200 rounded-xl px-3 py-2 text-[13px] focus:border-red-300 focus:ring-0 resize-none transition-all"></textarea>
                </div>
            </div>
        @endif

        <div class="flex items-center justify-end gap-2 px-6 py-4 bg-gray-50 text-right -mx-6 -mb-6 mt-6 rounded-b-lg border-t border-gray-100">
            <x-secondary-button @click="$dispatch('close-modal', 'reject-modal')" wire:click="$set('rejectReason', '')" class="h-11">
                Cancel
            </x-secondary-button>
            <x-danger-button wire:click="rejectOrder" class="h-11">
                Reject Order
            </x-danger-button>
        </div>
    </div>
</x-modal>

{{-- Void Modal --}}
<x-modal name="confirm-void-order" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-rose-400 to-red-500 rounded-t-lg"></div>
    <div class="p-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900">Confirm Void</h3>
                <p class="mt-1 text-[12px] text-gray-500">This action cannot be undone</p>
            </div>
        </div>

        @if($selectedOrder)
            <p class="text-[13px] text-gray-700 mb-4 leading-relaxed">
                Are you sure you want to void <strong>Order #{{ $selectedOrder->reference_no }}</strong>?
            </p>
            <div class="bg-rose-50 border border-rose-100 rounded-xl p-3 mb-4">
                <p class="text-[12px] text-rose-900 font-medium">
                    The order will be marked as <strong>Void</strong> permanently.
                </p>
            </div>
        @endif

        <div class="flex items-center justify-end gap-2 px-6 py-4 bg-gray-50 text-right -mx-6 -mb-6 mt-6 rounded-b-lg border-t border-gray-100">
            <x-secondary-button @click="$dispatch('close-modal', 'confirm-void-order')" wire:click="cancelVoid" class="h-11">
                Cancel
            </x-secondary-button>
            <x-danger-button wire:click="confirmVoid" class="h-11">
                Confirm Void
            </x-danger-button>
        </div>
    </div>
</x-modal>

{{-- Receipt Modal --}}
<x-modal name="receipt-modal" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-emerald-400 to-green-500 rounded-t-lg"></div>
    <div class="p-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900">Order Receipt</h3>
                <p class="mt-1 text-[12px] text-gray-500">View and print order details</p>
            </div>
        </div>

        @foreach($allLoadedOrders as $order)
            <div x-show="selectedOrderId === {{ $order->id }}" x-cloak class="flex justify-center bg-gray-50 rounded-xl p-6 border border-gray-100 shadow-inner overflow-hidden mb-4">
                <div id="receipt_paper_{{ $order->id }}" class="w-full max-w-[260px] bg-white shadow-md p-4 pt-6 pb-8 font-mono text-[10px] text-gray-800 relative receipt-paper border border-gray-100">
                    @php
                        $address = \App\Models\SystemSetting::get('business_address', '');
                        if (is_array($address)) {
                            $address = $address['formatted'] ?? '';
                        }
                        $logoUrl = \App\Models\SystemSetting::get('business_logo') ? Storage::url(\App\Models\SystemSetting::get('business_logo')) : null;
                        $businessName = \App\Models\SystemSetting::get('business_name', 'Your Business Name');
                        $businessEmail = \App\Models\SystemSetting::get('business_email', '');
                        $businessPhone = \App\Models\SystemSetting::get('business_phone', '');
                        $businessAddress = $address;
                        $logoEnabled = (bool)\App\Models\SystemSetting::get('receipt_logo_enabled', true);
                        $showVat = (bool)\App\Models\SystemSetting::get('receipt_show_vat', true);
                        $footer = \App\Models\SystemSetting::get('receipt_footer_message', 'Thank you for your visit!');
                        $returnPolicy = \App\Models\SystemSetting::get('receipt_return_policy', 'No return, no exchange.');
                        $qrUrl = \App\Models\SystemSetting::get('receipt_qr_url', '');
                        $currencySymbol = '₱';

                        if (empty($qrUrl)) {
                            $qrUrl = route('customer.review', ['branch' => $order->branch_id]);
                        } else {
                            $qrUrl = str_replace('{order_id}', $order->id, $qrUrl);
                            // Append branch context to custom URLs so reviews are attributed correctly
                            $separator = str_contains($qrUrl, '?') ? '&' : '?';
                            $qrUrl .= $separator . 'branch=' . $order->branch_id;
                        }

                        // Rewrite localhost to local LAN IP so phones can scan it
                        if (str_contains($qrUrl, 'localhost') || str_contains($qrUrl, '127.0.0.1')) {
                            $localIp = gethostbyname(gethostname());
                            $qrUrl = str_replace(['localhost', '127.0.0.1'], $localIp, $qrUrl);
                        }

                        // Generate dynamic QR code
                        $qrCodeSvg = null;
                        try {
                            $qrCodeSvg = \App\Helpers\QrCodeHelper::generateReviewQrCode($qrUrl);
                        } catch (\Exception $e) {
                            $qrCodeSvg = null;
                        }
                    @endphp

                    <div class="text-center mb-4">
                        @if($logoEnabled)
                            <div class="mb-2 flex justify-center">
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" class="w-10 h-10 object-contain">
                                @else
                                    <div class="w-8 h-8 bg-black rounded flex items-center justify-center text-white font-bold text-sm">
                                        <span>MTC</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                        <p class="font-bold text-[11px]">{{ $businessName }}</p>
                        @if(!empty($businessAddress))
                            <p class="text-[7px] text-gray-500 mt-0.5">{{ $businessAddress }}</p>
                        @endif
                        @if(!empty($businessPhone))
                            <p class="text-[7px] text-gray-500">Tel: +63 {{ ltrim(trim($businessPhone), '+63') }}</p>
                        @endif
                        @if(!empty($businessEmail))
                            <p class="text-[7px] text-gray-500">{{ $businessEmail }}</p>
                        @endif
                    </div>

                    <div class="border-y border-dashed border-gray-200 py-1.5 mb-3 space-y-0.5 text-[9px]">
                        <div class="flex justify-between"><span>#{{ $order->reference_no }}</span><span>{{ $order->created_at->format('d/m/y H:i') }}</span></div>
                        <div class="flex justify-between font-bold" style="font-size:9px; margin-top:4px;">
                            <span style="text-transform:uppercase; letter-spacing:0.05em;">⬛ {{ strtoupper($order->order_type ?? 'DINE-IN') }}</span>
                            <span>STAFF: {{ strtoupper($order->user->first_name ?? 'APP') }}</span>
                        </div>
                        
                        @if($order->source === 'App' || ($order->order_type ?? '') === 'Delivery')
                            <div class="mt-2 pt-2 border-t border-gray-100 flex flex-col gap-0.5">
                                <span class="font-bold">Customer: {{ $order->customer_name ?? 'N/A' }}</span>
                                <span>Phone: {{ $order->customer_phone ?? 'N/A' }}</span>
                                <span class="whitespace-normal leading-tight">Address: {{ $order->delivery_address ?? 'N/A' }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-1 mb-3">
                        @forelse($order->items as $item)
                            <div class="flex justify-between items-start">
                                <span class="flex-1 pr-2 leading-tight">{{ $item->quantity }}x {{ $item->product->name ?? 'Item' }}</span>
                                <span class="whitespace-nowrap">₱{{ number_format($item->subtotal ?? ($item->quantity * $item->unit_price), 2) }}</span>
                            </div>
                            @if($item->options && $item->options->isNotEmpty())
                                @foreach($item->options as $opt)
                                    <div class="flex justify-between text-[8px] text-gray-500 pl-3 leading-tight">
                                        <span>+ {{ $opt->option->name ?? 'N/A' }}</span>
                                        <span>₱{{ number_format($opt->price, 2) }}</span>
                                    </div>
                                @endforeach
                            @endif
                        @empty
                            <div class="text-center italic">No items</div>
                        @endforelse
                    </div>

                    <div class="border-t border-dashed border-gray-200 pt-1.5 mb-3 space-y-0.5">
                        <div class="flex justify-between">
                            <span>Subtotal</span>
                            <span>₱{{ number_format($order->total_amount + $order->discount_amount, 2) }}</span>
                        </div>

                        @if($order->discount_amount > 0)
                            <div class="flex justify-between text-[9px] text-gray-500">
                                <span>Discount</span>
                                <span>-₱{{ number_format($order->discount_amount, 2) }}</span>
                            </div>
                        @endif

                        @if($order->delivery_fee > 0)
                            <div class="flex justify-between text-[9px] text-gray-500">
                                <span>Delivery Fee</span>
                                <span>₱{{ number_format($order->delivery_fee, 2) }}</span>
                            </div>
                        @endif

                        <div class="flex justify-between font-bold text-[10px] pt-1 mt-1 border-t border-gray-100">
                            <span>TOTAL</span>
                            <span>₱{{ number_format($order->total_amount, 2) }}</span>
                        </div>
                    </div>

                    @if($order->refunded_amount > 0)
                        <div class="text-center font-bold text-[9px] mb-3 mt-1 py-1 border-y border-dashed border-gray-200 uppercase text-gray-900">
                            Refunded: ₱{{ number_format($order->refunded_amount, 2) }}
                        </div>
                    @endif

                    <div class="text-center mt-4">
                        <p class="font-bold italic text-gray-700 text-[8px] mb-2">{{ $footer }}</p>
                        
                        @if(!empty($returnPolicy))
                            <p class="text-[7px] text-gray-500 border-t border-dashed border-gray-200 pt-2 mb-2 leading-tight">{{ $returnPolicy }}</p>
                        @endif

                        @if($qrCodeSvg)
                            <div class="mt-2 pt-2 border-t border-gray-200">
                                <p class="text-[7px] text-gray-600 mb-1">Scan to Review:</p>
                                <div class="bg-white p-1 inline-block border border-gray-300 mx-auto">
                                    <img src="{{ $qrCodeSvg }}" alt="Review QR Code" class="w-16 h-16 block">
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="flex items-center justify-end gap-2 px-6 py-4 bg-gray-50 text-right -mx-6 -mb-6 mt-6 rounded-b-lg border-t border-gray-100">
            <x-secondary-button @click="$dispatch('close-modal', 'receipt-modal')" class="h-11">
                Close
            </x-secondary-button>
            <x-primary-button @click="window.printOrderReceipt(selectedOrderId)" class="h-11 font-black uppercase tracking-widest text-[11px]">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Receipt
            </x-primary-button>
        </div>
    </div>
</x-modal>

{{-- Hand to Rider Modal --}}
<x-modal name="hand-to-rider-modal" maxWidth="sm" focusable>
    <div class="h-1 w-full bg-gradient-to-r from-indigo-400 to-blue-500 rounded-t-lg"></div>
    <div class="p-6">
        <div class="flex items-start gap-4 mb-4">
            <div class="flex-shrink-0 w-10 h-10 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-gray-900">Hand to Rider</h3>
                <p class="mt-1 text-[12px] text-gray-500">Assign a rider to start delivery</p>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <x-input-label for="rider_select" value="Select Available Rider *" />
                <select id="rider_select" wire:model.live="selectedRiderId" 
                    class="mt-1.5 block w-full border border-gray-200 rounded-xl h-11 px-3 text-[13px] focus:border-indigo-300 focus:ring-0 transition-all">
                    <option value="">-- Choose a Rider --</option>
                    @foreach($riders as $rider)
                        <option value="{{ $rider->id }}">{{ $rider->first_name }} {{ $rider->last_name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('selectedRiderId')" class="mt-1" />
            </div>

            @if($selectedOrder)
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3">
                    <p class="text-[11px] text-indigo-900 font-medium">
                        Handing over <strong>Order #{{ $selectedOrder->reference_no }}</strong> to the selected rider. They will be notified immediately via the Flutter app.
                    </p>
                </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-2 px-6 py-4 bg-gray-50 text-right -mx-6 -mb-6 mt-6 rounded-b-lg border-t border-gray-100">
            <x-secondary-button @click="$dispatch('close-modal', 'hand-to-rider-modal')" class="h-11">
                Cancel
            </x-secondary-button>
            <x-primary-button wire:click="handToRider" class="h-11 bg-indigo-600 hover:bg-indigo-700">
                Confirm Handover
            </x-primary-button>
        </div>
    </div>
</x-modal>

</div>{{-- end root wrapper --}}

<script>
window.printOrderReceipt = function(orderId) {
    const element = document.getElementById('receipt_paper_' + orderId);
    if (!element) return;

    let iframe = document.getElementById('receipt_print_iframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'receipt_print_iframe';
        iframe.style.position = 'fixed';
        iframe.style.bottom = '0';
        iframe.style.right = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = 'none';
        document.body.appendChild(iframe);
    }

    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write("<html><head><title>Print Receipt</title><style>@page { size: 80mm auto; margin: 0; } body { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; margin: 0; padding: 10px; background: #fff; width: 72mm; } .text-center { text-align: center; } .mb-2 { margin-bottom: 8px; } .mb-4 { margin-bottom: 16px; } .mt-2 { margin-top: 8px; } .mt-4 { margin-top: 16px; } .py-1.5 { padding-top: 6px; padding-bottom: 6px; } .pt-1 { padding-top: 4px; } .pt-2 { padding-top: 8px; } .pl-3 { padding-left: 12px; } .font-bold { font-weight: bold; } .italic { font-style: italic; } .border-y { border-top: 1px dashed #000; border-bottom: 1px dashed #000; } .border-t { border-top: 1px dashed #000; } .space-y-0.5 > * + * { margin-top: 2px; } .space-y-1 > * + * { margin-top: 4px; } .flex { display: flex; } .justify-between { justify-content: space-between; } .items-start { align-items: flex-start; } .flex-col { flex-direction: column; } .gap-0.5 { gap: 2px; } .gap-2 { gap: 8px; } .flex-1 { flex: 1; } .pr-2 { padding-right: 8px; } .whitespace-nowrap { white-space: nowrap; } .whitespace-normal { white-space: normal; } .leading-tight { line-height: 1.25; } .uppercase { text-transform: uppercase; } .text-[7px] { font-size: 8px; } .text-[8px] { font-size: 9px; } .text-[9px] { font-size: 10px; } .text-[10px] { font-size: 11px; } .text-[11px] { font-size: 12px; } img { max-width: 40px; height: auto; }</style></head><body>" + element.innerHTML + "<script>window.onload = function() { window.focus(); window.print(); };<\/script></body></html>");
    doc.close();
}
</script>

