@php
    $user = auth()->user();
    // Primary palette
    $primaryColor = $user->role_id === 1 ? 'indigo' : ($user->role_id === 2 ? 'rose' : 'emerald');
    $primaryText = "text-{$primaryColor}-600";
@endphp

{{-- Table Container --}}
<div id="order-table-{{ $tabName ?? 'all' }}" class="mt-2 relative"
     wire:loading.class="opacity-50 pointer-events-none"
     wire:target="statusFilter, applyQuickDateFilter, startDate, endDate, previousPage, nextPage, gotoPage, setPage">
    
    <x-data-table>
        <x-slot name="header">
            <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest">Order Info</th>
            <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-right">Amount</th>
            <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-center">Status</th>
            <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-center hidden sm:table-cell">Type</th>
            <th class="py-3 px-4 text-[11px] font-bold text-slate-500 uppercase tracking-widest text-center hidden md:table-cell">Date & Time</th>
            <th class="py-3 px-4 text-right text-[11px] font-bold text-slate-500 uppercase tracking-widest">Actions</th>
        </x-slot>
 
        @forelse($orders as $order)
            <tr wire:key="order-row-{{ $order->id }}" 
                class="hover:bg-slate-50/50 transition-colors cursor-pointer" 
                @click="selectedOrderId = {{ $order->id }}; $dispatch('open-modal', 'view-order-detail')">
                <td class="py-3 px-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-{{ $primaryColor }}-50 flex items-center justify-center {{ $primaryText }} font-bold text-[13px] flex-shrink-0 shadow-sm">
                            {{ $order->source === 'App' ? 'APP' : 'POS' }}
                        </div>
                        <div>
                            <div class="font-bold text-[13px] text-slate-900">{{ $order->reference_no }}</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1">
                                {{ $order->branch->branch_name }}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="py-3 px-4 text-right text-[13px] font-black text-slate-900 font-mono whitespace-nowrap">
                    ₱{{ number_format($order->total_amount, 2) }}
                    <div class="text-[9px] text-{{ $primaryColor }}-500 font-bold uppercase tracking-wider mt-0.5">{{ $order->payment_method }}</div>
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap">
                    @php
                        $isFullyDone = ($order->status === 'Completed' || $order->status === 'Delivered') && $order->payment_status === 'Paid';
                        $displayStatus = $isFullyDone ? $order->status : "{$order->payment_status} / {$order->status}";
                        $dotColors = [
                            'Pending' => 'bg-amber-500 animate-pulse',
                            'Preparing' => 'bg-blue-500',
                            'Ready' => 'bg-emerald-500',
                            'Handed to Rider' => 'bg-indigo-500',
                            'Out for Delivery' => 'bg-indigo-500',
                            'Delivered' => 'bg-emerald-500',
                            'Completed' => 'bg-emerald-500',
                            'Cancelled' => 'bg-rose-500',
                            'Drafted' => 'bg-slate-400',
                            'Void' => 'bg-rose-500',
                            'Refunded' => 'bg-orange-500',
                            'Partially Refunded' => 'bg-orange-500',
                        ];
                        $dotColor = $dotColors[$order->status] ?? 'bg-slate-400';
                        $textColors = [
                            'Pending' => 'text-amber-600',
                            'Preparing' => 'text-blue-600',
                            'Ready' => 'text-emerald-600',
                            'Handed to Rider' => 'text-indigo-600',
                            'Out for Delivery' => 'text-indigo-600',
                            'Delivered' => 'text-emerald-600',
                            'Completed' => 'text-emerald-600',
                            'Cancelled' => 'text-rose-600',
                            'Drafted' => 'text-slate-600',
                            'Void' => 'text-rose-600',
                            'Refunded' => 'text-orange-600',
                            'Partially Refunded' => 'text-orange-600',
                        ];
                        $textColor = $textColors[$order->status] ?? 'text-slate-600';
                    @endphp
                    <div class="flex items-center justify-center gap-2">
                        <span class="h-1.5 w-1.5 rounded-full {{ $dotColor }}"></span>
                        <span class="text-[11px] font-bold {{ $textColor }} uppercase">{{ $displayStatus }}</span>
                    </div>
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap hidden sm:table-cell">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-50 text-slate-600 border border-slate-100">{{ $order->order_type }}</span>
                </td>
                <td class="py-3 px-4 text-center whitespace-nowrap hidden md:table-cell">
                    <div class="text-[12px]">
                        <div class="font-bold text-slate-900">{{ $order->created_at->format('M d, Y') }}</div>
                        <div class="text-slate-400 font-medium text-[11px]">{{ $order->created_at->format('h:i A') }}</div>
                    </div>
                </td>
                <td class="py-3 px-4 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1" @click.stop="">
                        @if($order->status === 'Pending')
                            <x-secondary-button type="button" wire:click="acceptOrder({{ $order->id }})" class="h-9 px-3 border-emerald-500/50 text-emerald-600 hover:bg-emerald-50">
                                Accept
                            </x-secondary-button>
                        @endif
                        <x-secondary-button type="button" @click="selectedOrderId = {{ $order->id }}; $dispatch('open-modal', 'view-order-detail')" class="h-9 px-3 whitespace-nowrap">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            View Details
                        </x-secondary-button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="py-12">
                    <x-empty-state 
                        title="No Orders Found"
                        description="Adjust your filters or search terms to find what you're looking for."
                        icon="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                </td>
            </tr>
        @endforelse
 
    </x-data-table>

    <div class="mt-4">
        <x-pagination :paginator="$orders" />
    </div>
</div>

