<div class="p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-[20px] font-black text-gray-900 tracking-tight">Notification History</h2>
            <p class="text-[12px] text-gray-500 font-medium">Archive of all system alerts and activity logs.</p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="markAllAsRead" 
                class="px-4 py-2 bg-indigo-50 text-indigo-600 text-[11px] font-bold rounded-xl hover:bg-indigo-100 transition-all">
                Mark All as Read
            </button>
            <button wire:click="clearAll" wire:confirm="Are you sure you want to clear all notification history?" 
                class="px-4 py-2 bg-rose-50 text-rose-600 text-[11px] font-bold rounded-xl hover:bg-rose-100 transition-all">
                Clear All History
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex items-center gap-3 mb-6 bg-gray-50/50 p-3 rounded-2xl border border-gray-100">
        <div class="relative flex-1">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search alerts..."
                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-2 text-[12px] focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all">
        </div>
        <select wire:model="type" class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-[12px] focus:ring-2 focus:ring-indigo-500/20 transition-all min-w-[140px]">
            <option value="">All Types</option>
            <option value="stock">Inventory Alerts</option>
            <option value="expiry">Expiry Warnings</option>
            <option value="order">New Orders</option>
            <option value="review">Customer Reviews</option>
        </select>
    </div>

    <div class="space-y-3">
        @forelse($notifications as $n)
            <div class="group relative bg-white border border-gray-100 rounded-2xl p-4 transition-all hover:border-indigo-200 hover:shadow-[0_8px_30px_rgba(0,0,0,0.04)] {{ !$n->is_read ? 'ring-1 ring-indigo-500/10' : '' }}">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        @if($n->type === 'stock')
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        @elseif($n->type === 'expiry')
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($n->type === 'order')
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        @else
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.921-.755 1.688-1.54 1.118l-3.976-2.888a1 1 0 00-1.175 0l-3.976 2.888c-.784.57-1.838-.197-1.539-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.383-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h4 class="text-[13px] font-bold text-gray-900">{{ $n->title }}</h4>
                            <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wider">{{ $n->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-[12px] text-gray-500 mt-1 leading-relaxed">{{ $n->message }}</p>
                        
                        <div class="flex items-center gap-4 mt-3">
                            @if($n->link)
                                <button wire:click="trace({{ $n->id }})" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 flex items-center gap-1.5 transition-colors">
                                    <span>Investigate issue</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                </button>
                            @endif
                            @if(!$n->is_read)
                                <button wire:click="markAsRead({{ $n->id }})" class="text-[11px] font-bold text-gray-400 hover:text-gray-600 transition-colors">Mark as read</button>
                            @endif
                            <button wire:click="deleteNotification({{ $n->id }})" class="text-[11px] font-bold text-gray-300 hover:text-rose-500 transition-colors ml-auto opacity-0 group-hover:opacity-100">Remove</button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-20 text-center bg-gray-50/50 rounded-3xl border border-dashed border-gray-200">
                <div class="w-16 h-16 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <h3 class="text-[14px] font-bold text-gray-900">Archive is empty</h3>
                <p class="text-[12px] text-gray-400 mt-1">No past alerts found matching your filters.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
