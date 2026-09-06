@php
    $user = auth()->user();
    $roleTheme = $user->getRoleTheme();
    $primaryColor = $roleTheme['primary'];
    $primaryText = "text-{$primaryColor}-600";
    $primaryBg = "bg-{$primaryColor}-600";
@endphp

<div class="relative overflow-hidden">
    <div class="relative min-h-[600px] px-1">
        
        {{-- Header Section --}}
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h2 class="text-[17px] font-bold text-gray-900 tracking-tight">System Notifications</h2>
                <p class="text-[12px] text-gray-500 font-medium">Archive of all system alerts and activity logs.</p>
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="markAllAsRead" 
                    class="px-4 py-2 bg-{{ $primaryColor }}-50 text-{{ $primaryColor }}-600 text-[11px] font-bold rounded-xl hover:bg-{{ $primaryColor }}-100 transition-all border border-{{ $primaryColor }}-100">
                    Mark All as Read
                </button>
                <button @click="$dispatch('open-modal', 'confirm-clear-history')"
                    class="px-4 py-2 bg-rose-50 text-rose-600 text-[11px] font-bold rounded-xl hover:bg-rose-100 transition-all border border-rose-100">
                    Clear All History
                </button>
            </div>
        </div>

        {{-- KPI Cards Section --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-8">
            {{-- Total Notifications --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-{{ $primaryColor }}-500/10 via-{{ $primaryColor }}-500/5 to-white border border-{{ $primaryColor }}-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Alerts</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-{{ $primaryColor }}-100 flex items-center justify-center {{ $primaryText }} shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $stats['total'] }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Lifetime notifications logged</p>
            </div>

            {{-- Unread Alerts --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-indigo-500/10 via-indigo-500/5 to-white border border-indigo-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Unread Alerts</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-indigo-100 flex items-center justify-center text-indigo-500 shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $stats['unread'] }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">New items requiring attention</p>
            </div>

            {{-- Critical Alerts --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-rose-500/10 via-rose-500/5 to-white border border-rose-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-rose-600/90 uppercase tracking-wider">Critical (Unread)</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-rose-100 flex items-center justify-center text-rose-600 shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-rose-600 tracking-tight leading-none">{{ $stats['critical'] }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Unresolved high-priority alerts</p>
            </div>

            {{-- Recent Activity --}}
            <div class="p-3 sm:p-4 bg-gradient-to-br from-emerald-500/10 via-emerald-500/5 to-white border border-emerald-500/10 rounded-2xl shadow-sm hover:shadow-md hover:scale-[1.02] cursor-pointer transition-all duration-300 relative overflow-hidden group">
                <div class="flex items-center justify-between mb-1 sm:mb-2">
                    <span class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase tracking-wider">Recent (7 Days)</span>
                    <div class="w-7 h-7 rounded-lg bg-white border border-emerald-100 flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight leading-none">{{ $stats['recent'] }}</h3>
                <p class="text-[9px] sm:text-[10px] text-slate-400 font-semibold mt-1 sm:mt-1.5 leading-none">Alerts logged in the last week</p>
            </div>
        </div>

        {{-- macOS Style Unified Toolbar --}}
        <div class="relative z-20 flex flex-col lg:flex-row lg:items-center justify-between mb-6 gap-4 bg-white p-2.5 rounded-xl border border-slate-200/60 shadow-sm mx-1">
            
            {{-- Left: Search Bar --}}
            <div class="flex flex-1 w-full lg:w-auto">
                <x-search-bar wireModel="search" placeholder="Search alerts..." width="w-full lg:w-72" />
            </div>

            {{-- Right: Filters --}}
            <div class="flex flex-wrap items-center lg:justify-end gap-2">
                @php
                    $typeLabels = [
                        ''        => 'All Types',
                        'stock'   => 'Inventory Alerts',
                        'expiry'  => 'Expiry Warnings',
                        'order'   => 'New Orders',
                        'review'  => 'Customer Reviews',
                    ];
                @endphp
                <x-dropdown align="right" width="48" wire:key="filter-type">
                    <x-slot name="trigger">
                        <x-secondary-button type="button" class="gap-0 sm:gap-1.5 h-10 !px-2.5 sm:!px-3 bg-white hover:bg-slate-50 border-slate-200 text-slate-600 shadow-none">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            <span class="hidden sm:inline text-[12px] whitespace-nowrap">{{ $typeLabels[$type] ?? 'All Types' }}</span>
                            <svg class="hidden sm:block w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </x-secondary-button>
                    </x-slot>
                    <x-slot name="content">
                        @foreach($typeLabels as $value => $label)
                            <x-dropdown-link href="#" wire:click.prevent="$set('type', '{{ $value }}')">{{ $label }}</x-dropdown-link>
                        @endforeach
                    </x-slot>
                </x-dropdown>
            </div>
        </div>

        {{-- Table View --}}
        <div class="mx-1">
            <x-data-table>
                <x-slot name="header">
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest w-16">Icon</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Alert Details</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Type</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Status</th>
                    <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Date</th>
                </x-slot>

                @forelse($notifications as $n)
                    <tr wire:click="trace({{ $n->id }})" class="cursor-pointer hover:bg-slate-50/50 transition-colors {{ !$n->is_read ? 'bg-indigo-50/30' : '' }}">
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="w-9 h-10 rounded-xl flex items-center justify-center {{ $n->is_read ? 'bg-gray-100 text-gray-400' : 'bg-white shadow-sm ring-1 ring-indigo-500/20 text-indigo-500' }}">
                                @if($n->type === 'stock')
                                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                @elseif($n->type === 'expiry')
                                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                @elseif($n->type === 'order')
                                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                @else
                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.921-.755 1.688-1.54 1.118l-3.976-2.888a1 1 0 00-1.175 0l-3.976 2.888c-.784.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.383-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                @endif
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-[13px] {{ !$n->is_read ? 'text-indigo-900' : 'text-slate-800' }}">{{ $n->title }}</div>
                            <div class="text-[12px] text-slate-500 mt-0.5 line-clamp-1">{{ $n->message }}</div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-black bg-slate-50 text-slate-500 border border-slate-100">
                                {{ strtoupper($n->type ?: 'General') }}
                            </div>
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            @if(!$n->is_read)
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-indigo-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                                    Unread
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-400">
                                    Read
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 whitespace-nowrap">
                            <div class="text-[13px] font-bold text-slate-900">{{ $n->created_at->format('M d, Y') }}</div>
                            <div class="text-[11px] text-slate-400 font-medium italic">{{ $n->created_at->diffForHumans() }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-0">
                            <x-empty-state title="Archive is empty" description="No past alerts found matching your filters." icon="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </td>
                    </tr>
                @endforelse
            </x-data-table>
            <x-pagination :paginator="$notifications" />
        </div>
    </div>

    {{-- Confirmation Modal for Clear All --}}
    <x-modal name="confirm-clear-history" maxWidth="md">
        <div class="p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-4 text-rose-600 shadow-sm border border-rose-200">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h2 class="text-[20px] font-black text-gray-900 mb-2 tracking-tight">Clear Notification History</h2>
            <p class="text-[13px] text-gray-500 font-medium leading-relaxed mb-8">Are you absolutely sure you want to clear all your notification history? This action is permanent and cannot be undone.</p>
            
            <div class="flex gap-3">
                <button @click="$dispatch('close-modal', 'confirm-clear-history')" class="flex-1 py-3 text-[13px] font-bold text-gray-600 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                    Cancel
                </button>
                <button wire:click="clearAll" @click="$dispatch('close-modal', 'confirm-clear-history')" class="flex-1 py-3 text-[13px] font-bold text-white bg-rose-600 rounded-xl hover:bg-rose-700 transition-all shadow-lg shadow-rose-200">
                    Yes, Clear All
                </button>
            </div>
        </div>
    </x-modal>
</div>
