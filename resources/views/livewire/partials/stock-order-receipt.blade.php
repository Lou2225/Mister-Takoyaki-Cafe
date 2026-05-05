<div class="p-8 bg-white text-slate-900 font-sans print:p-0" id="printable-receipt">
    <div class="text-center mb-8 border-b-2 border-slate-900 pb-6">
        <h1 class="text-2xl font-black uppercase tracking-tighter">{{ config('app.name') }}</h1>
        <p class="text-sm font-bold uppercase tracking-widest mt-1">Stock Transfer Receipt</p>
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8 text-sm">
        <div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Source Branch (From)</p>
            <p class="font-black uppercase tracking-tight">{{ $order->sourceBranch->branch_name ?? 'Main Branch' }}</p>
            <p class="text-slate-500">{{ $order->sourceBranch->address ?? '' }}</p>
        </div>
        <div class="text-right">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Destination Branch (To)</p>
            <p class="font-black uppercase tracking-tight">{{ $order->requestingBranch->branch_name }}</p>
            <p class="text-slate-500">{{ $order->requestingBranch->address ?? '' }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-8 mb-8 text-sm bg-slate-50 p-4 rounded-xl print:bg-transparent print:border print:border-slate-200">
        <div>
            <p class="mb-1"><strong>Order Ref:</strong> {{ $order->reference_no }}</p>
            <p class="mb-1"><strong>Requested By:</strong> {{ $order->requester->name }}</p>
            <p><strong>Approved By:</strong> {{ $order->approver->name ?? '—' }}</p>
        </div>
        <div class="text-right">
            <p class="mb-1"><strong>Requested At:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
            <p class="mb-1"><strong>Approved At:</strong> {{ $order->approved_at ? $order->approved_at->format('M d, Y H:i') : '—' }}</p>
            <p><strong>Dispatched At:</strong> {{ $order->dispatched_at ? $order->dispatched_at->format('M d, Y H:i') : '—' }}</p>
        </div>
    </div>

    <table class="w-full text-left border-collapse mb-8">
        <thead>
            <tr class="border-b-2 border-slate-900">
                <th class="py-3 text-[10px] font-black uppercase tracking-widest">#</th>
                <th class="py-3 text-[10px] font-black uppercase tracking-widest">Ingredient</th>
                <th class="py-3 text-[10px] font-black uppercase tracking-widest text-right">Requested</th>
                <th class="py-3 text-[10px] font-black uppercase tracking-widest text-right">Approved</th>
                <th class="py-3 text-[10px] font-black uppercase tracking-widest">Unit</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @foreach($order->items as $item)
                <tr>
                    <td class="py-4 text-xs font-bold tabular-nums">{{ $loop->iteration }}</td>
                    <td class="py-4">
                        <p class="text-sm font-black uppercase tracking-tight">{{ $item->ingredient->name }}</p>
                        @if($item->notes)
                            <p class="text-[10px] text-slate-500 italic font-medium">"{{ $item->notes }}"</p>
                        @endif
                    </td>
                    <td class="py-4 text-sm font-black text-right tabular-nums">{{ number_format($item->requested_quantity, 2) }}</td>
                    <td class="py-4 text-sm font-black text-right tabular-nums">{{ number_format($item->approved_quantity ?? $item->requested_quantity, 2) }}</td>
                    <td class="py-4 text-xs font-bold uppercase tracking-widest">{{ $item->unit }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if($order->admin_remarks)
        <div class="mb-12 p-4 border-l-4 border-slate-900 bg-slate-50 print:bg-transparent">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Admin Remarks</p>
            <p class="text-sm font-medium italic">"{{ $order->admin_remarks }}"</p>
        </div>
    @endif

    <div class="grid grid-cols-2 gap-20 pt-12">
        <div class="text-center">
            <div class="border-b border-slate-900 h-12 mb-2"></div>
            <p class="text-[10px] font-black uppercase tracking-widest">Released By (HQ)</p>
        </div>
        <div class="text-center">
            <div class="border-b border-slate-900 h-12 mb-2"></div>
            <p class="text-[10px] font-black uppercase tracking-widest">Received By (Branch)</p>
        </div>
    </div>

    <div class="mt-20 text-center text-[10px] text-slate-400 font-bold uppercase tracking-[0.3em]">
        System Generated Receipt — {{ now()->format('Y-m-d H:i:s') }}
    </div>
</div>
