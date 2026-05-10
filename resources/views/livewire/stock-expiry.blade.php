<div
    x-data="{ 
        statusFilter: @js($statusFilter), 
        search: @js($search)
    }"
    class="relative overflow-hidden">

    <div class="px-1" x-cloak>

        {{-- Header --}}
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">Expiry Tracking</h2>
                <p class="text-[12px] text-gray-500 font-medium tracking-tight">Monitor batch expiration dates and dispose of expired stock safely</p>
            </div>
            @if(!$this->isStaff())
                <div class="flex items-center gap-3">
                    {{-- Manual alert trigger --}}
                    <x-secondary-button wire:click="$dispatch('send-alerts-test')" class="hidden">
                        Test Email Alert
                    </x-secondary-button>
                </div>
            @endif
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            {{-- Expired --}}
            <div wire:click="$set('statusFilter', 'expired')"
                 class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group transition-all hover:shadow-md cursor-pointer {{ $statusFilter === 'expired' ? 'ring-2 ring-red-400 border-red-200' : '' }}">
                <div class="absolute top-0 right-0 p-1">
                    @if($stats['expired'] > 0)
                        <span class="flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                        </span>
                    @endif
                </div>
                <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center text-red-600 transition-transform group-hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Expired</span>
                    <span class="block text-[18px] font-black {{ $stats['expired'] > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $stats['expired'] }}</span>
                </div>
            </div>

            {{-- Expiring Soon --}}
            <div wire:click="$set('statusFilter', 'expiring')"
                 class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 relative overflow-hidden group transition-all hover:shadow-md cursor-pointer {{ $statusFilter === 'expiring' ? 'ring-2 ring-amber-400 border-amber-200' : '' }}">
                <div class="absolute top-0 right-0 p-1">
                    @if($stats['expiring'] > 0)
                        <span class="flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                        </span>
                    @endif
                </div>
                <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 transition-transform group-hover:scale-110">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Expiring Soon</span>
                    <span class="block text-[18px] font-black {{ $stats['expiring'] > 0 ? 'text-amber-600' : 'text-gray-900' }}">{{ $stats['expiring'] }}</span>
                </div>
            </div>

            {{-- Fresh --}}
            <div wire:click="$set('statusFilter', 'fresh')"
                 class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 transition-all hover:shadow-md cursor-pointer {{ $statusFilter === 'fresh' ? 'ring-2 ring-emerald-400 border-emerald-200' : '' }}">
                <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Fresh (7+ days)</span>
                    <span class="block text-[18px] font-black text-gray-900">{{ $stats['fresh'] }}</span>
                </div>
            </div>

            {{-- No Date --}}
            <div wire:click="$set('statusFilter', 'no_date')"
                 class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm flex items-center gap-4 transition-all hover:shadow-md cursor-pointer {{ $statusFilter === 'no_date' ? 'ring-2 ring-gray-400 border-gray-300' : '' }}">
                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">No Date Set</span>
                    <span class="block text-[18px] font-black text-gray-900">{{ $stats['no_date'] }}</span>
                </div>
            </div>
        </div>

        {{-- Filter Controls --}}
        <div class="flex flex-col md:flex-row md:items-center gap-3 mb-4 border-b border-gray-200 pb-4">
            {{-- Search --}}
            <div class="relative flex-1 max-w-xs">
                <div class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </div>
                <x-text-input wire:model.live.debounce.300ms="search" type="text" placeholder="Search ingredient..." class="pl-8 w-full" />
            </div>

            {{-- Branch Filter --}}
            @if($this->isSuperAdmin())
                <x-dropdown align="left" width="52" containerClasses="block">
                    <x-slot name="trigger">
                        <button type="button" class="flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-xl text-[12px] font-bold text-gray-700 hover:bg-gray-50 focus:outline-none shadow-sm transition-all min-h-[36px]">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            {{ $branchFilter ? $branches->firstWhere('id', $branchFilter)?->branch_name : 'All Branches' }}
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link wire:click="$set('branchFilter', '')" class="text-[12px] !py-2 {{ !$branchFilter ? 'font-bold text-indigo-600' : '' }}">All Branches</x-dropdown-link>
                        @foreach($branches as $b)
                            <x-dropdown-link wire:click="$set('branchFilter', {{ $b->id }})" class="text-[12px] !py-2 {{ $branchFilter == $b->id ? 'font-bold text-indigo-600' : '' }}">
                                {{ $b->branch_name }}
                            </x-dropdown-link>
                        @endforeach
                    </x-slot>
                </x-dropdown>
            @endif

            {{-- Status Filter Pills --}}
            <div class="flex items-center gap-1 ml-auto">
                @foreach(['all' => 'All', 'expired' => 'Expired', 'expiring' => 'Expiring', 'fresh' => 'Fresh', 'no_date' => 'No Date'] as $val => $label)
                    <button wire:click="$set('statusFilter', '{{ $val }}')"
                        class="px-3 py-1 text-[11px] font-bold rounded-full transition-all {{ $statusFilter === $val ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Batch Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-white z-10 shadow-sm border-b border-gray-200">
                        <tr>
                            <th class="py-3 px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Ingredient</th>
                            <th class="py-3 px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Branch</th>
                            <th class="py-3 px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Qty in Batch</th>
                            <th class="py-3 px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Expiry Date</th>
                            <th class="py-3 px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Status</th>
                            @if(!$this->isStaff())
                                <th class="py-3 px-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($batches as $batch)
                            @php
                                $expiry = $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->startOfDay() : null;
                                $today = \Carbon\Carbon::today();
                                $nextWeek = $today->copy()->addDays(7);
                                $isExpired  = $expiry && $expiry->lt($today);
                                $isExpiring = $expiry && !$isExpired && $expiry->lte($nextWeek);
                                $isFresh    = $expiry && $expiry->gt($nextWeek);
                                $daysLeft   = $expiry ? (int) $today->diffInDays($expiry, false) : null;
                            @endphp
                            <tr class="hover:bg-gray-50/50 transition-colors {{ $isExpired ? 'bg-red-50/30' : ($isExpiring ? 'bg-amber-50/30' : '') }}">
                                <td class="py-3 px-4">
                                    <div class="flex flex-col">
                                        <span class="text-[13px] font-bold text-gray-900">{{ $batch->ingredient->name ?? '—' }}</span>
                                        <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest">{{ strtoupper($batch->ingredient->unit ?? '') }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="text-[12px] font-semibold text-gray-600">{{ $batch->branch->branch_name ?? '—' }}</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="text-[13px] font-black {{ $isExpired ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ \App\Helpers\StockHelper::formatForDisplay($batch->current_quantity, $batch->ingredient->unit ?? 'pcs') }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($batch->expiry_date)
                                        <span class="text-[12px] font-bold {{ $isExpired ? 'text-red-600' : ($isExpiring ? 'text-amber-600' : 'text-gray-700') }}">
                                            {{ \Carbon\Carbon::parse($batch->expiry_date)->format('M d, Y') }}
                                        </span>
                                    @else
                                        <span class="text-[12px] text-gray-400 italic">Not set</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    @if($isExpired)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-black uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span> Expired
                                        </span>
                                    @elseif($isExpiring)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-[10px] font-black uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse inline-block"></span>
                                            {{ $daysLeft === 0 ? 'Today' : $daysLeft . 'd left' }}
                                        </span>
                                    @elseif($isFresh)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase tracking-wider">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span> Fresh
                                        </span>
                                    @else
                                        <span class="text-[11px] text-gray-400 italic">No date</span>
                                    @endif
                                </td>
                                @if(!$this->isStaff())
                                    <td class="py-3 px-4 text-center">
                                        @if($isExpired || $isExpiring)
                                            <button wire:click="wasteBatch({{ $batch->id }})"
                                                wire:confirm="Dispose this batch as waste? This action cannot be undone."
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[11px] font-bold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                Dispose
                                            </button>
                                        @else
                                            <span class="text-[11px] text-gray-300 italic">—</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $this->isStaff() ? 5 : 6 }}" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-3">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <span class="text-[13px] font-bold text-gray-400">No batches found</span>
                                        <span class="text-[11px] text-gray-300">Try adjusting your search or filter</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <x-pagination :paginator="$batches" />
        </div>

    </div>

</div>
