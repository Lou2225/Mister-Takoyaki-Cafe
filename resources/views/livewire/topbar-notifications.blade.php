<div class="relative" x-data="{ notificationsOpen: false }" wire:ignore.self @close-notifications.window="notificationsOpen = false" wire:poll.30s.keep-alive>
    <button @click="notificationsOpen = !notificationsOpen" @click.outside="notificationsOpen = false" 
        class="p-3 text-gray-400 hover:text-gray-500 rounded-full hover:bg-gray-100 transition-colors focus:outline-none relative group">
        <span class="sr-only">View notifications</span>
        <svg class="h-6 w-6 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        @if($unreadCount > 0)
            <div class="absolute top-2 right-2 min-w-[18px] h-5 px-1 bg-red-500 text-white text-[10px] font-black rounded-full flex items-center justify-center ring-2 ring-white shadow-sm">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </div>
        @endif
    </button>

    <div x-show="typeof notificationsOpen !== 'undefined' && notificationsOpen" x-transition x-cloak
        class="absolute right-0 mt-2 w-80 bg-white border border-gray-100 rounded-2xl shadow-[0_20px_50px_rgba(0,0,0,0.2)] z-[100] overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-50 flex items-center justify-between bg-gray-50/30">
            <h3 class="text-[12px] font-black text-gray-900 uppercase tracking-widest">System Alerts</h3>
            @if($unreadCount > 0)
                <button wire:click.stop="markAllAsRead" class="text-[10px] font-bold text-indigo-600 hover:text-indigo-800 transition-colors">Mark All as Read</button>
            @endif
        </div>
        <div style="max-height: 320px !important; overflow-y: auto !important;">
            @forelse($notifications as $n)
                <div class="relative group/n">
                    <button wire:click.stop="traceNotification({{ $n['id'] }})"
                        class="w-full text-left px-4 py-2.5 hover:bg-gray-50 transition-colors border-b border-gray-50/50 last:border-0 flex gap-3 {{ !$n['is_read'] ? 'bg-indigo-50/10' : '' }}">
                        <div class="w-8 h-8 rounded-lg bg-{{ $n['color'] }}-50 flex items-center justify-center shrink-0">
                            @if($n['type'] === 'stock' || $n['type'] === 'stock_order')
                                <svg class="w-4 h-4 text-{{ $n['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            @elseif($n['type'] === 'expiry')
                                <svg class="w-4 h-4 text-{{ $n['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @elseif($n['type'] === 'order')
                                <svg class="w-4 h-4 text-{{ $n['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            @else
                                <svg class="w-4 h-4 text-{{ $n['color'] }}-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.921-.755 1.688-1.54 1.118l-3.976-2.888a1 1 0 00-1.175 0l-3.976 2.888c-.784.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.383-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0 pr-6">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-[11px] font-bold text-gray-900 truncate">{{ $n['title'] }}</p>
                                <span class="text-[9px] font-medium text-gray-400 whitespace-nowrap uppercase tracking-tighter">{{ $n['time'] }}</span>
                            </div>
                            <p class="text-[11px] text-gray-500 leading-normal mt-0.5 line-clamp-2">{{ $n['message'] }}</p>
                        </div>
                    </button>
                    @if(!$n['is_read'])
                        <button wire:click.stop="markAsRead({{ $n['id'] }})"
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1.5 text-gray-300 hover:text-indigo-600 transition-all opacity-0 group-hover/n:opacity-100 bg-white rounded-lg shadow-sm border border-gray-100"
                            title="Mark as read">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    @endif
                </div>
            @empty
                <div class="px-6 py-10 text-center">
                    <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v2m4.667 3.333L10.667 15l3.333 3.333m-1.334-1.333h6.667"/></svg>
                    </div>
                    <p class="text-[12px] font-bold text-gray-400">All clear!</p>
                    <p class="text-[10px] text-gray-400 mt-1">You have no new notifications.</p>
                </div>
            @endforelse
        </div>
        <a href="{{ route('notifications.index') }}" wire:navigate
            class="block w-full px-4 py-2 bg-gray-50 text-center text-[10px] font-black text-gray-500 hover:bg-gray-100 hover:text-gray-700 uppercase tracking-[0.2em] transition-all">
            Notification History
        </a>
    </div>
</div>