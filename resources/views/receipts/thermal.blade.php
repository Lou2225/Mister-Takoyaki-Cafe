<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $order->reference_no }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            margin: 0;
            padding: 30px 10px;
            background-color: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #000;
        }
        .receipt-container {
            width: 80mm; /* Force 80mm width for both screen and print */
            background-color: #fff;
            padding: 25px 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            position: relative;
            box-sizing: border-box;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        
        .header { margin-bottom: 15px; }
        .logo-img {
            width: 56px;
            height: 56px;
            object-fit: contain;
            margin: 0 auto 8px;
            display: block;
        }
        .logo-placeholder {
            width: 44px;
            height: 44px;
            background: #000;
            color: #fff;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            font-weight: bold;
            font-size: 16px;
        }
        .business-name { 
            font-size: 13px; 
            font-weight: bold; 
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header-info { font-size: 9px; color: #333; margin-bottom: 1px; }
        
        .divider {
            border-bottom: 1px dashed #999;
            margin: 12px 0;
        }
        
        .info-section { 
            font-size: 9px; 
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .row { display: flex; justify-content: space-between; margin-bottom: 2px; }
        
        .items-section { margin-bottom: 10px; }
        .item-row { margin-bottom: 6px; }
        .item-main { display: flex; justify-content: space-between; font-weight: bold; }
        .item-detail { font-size: 9px; padding-left: 12px; font-style: italic; color: #444; }
        
        .totals-section {
            border-top: 1px dashed #999;
            padding-top: 6px;
        }
        .grand-total {
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px solid #000;
            font-weight: bold;
            font-size: 11px;
        }
        
        .footer { 
            margin-top: 25px; 
            text-align: center;
        }
        .footer-msg { font-weight: bold; font-style: italic; font-size: 9px; margin-bottom: 12px; }
        .qr-img {
            width: 64px;
            height: 64px;
            margin: 6px auto 0;
            display: block;
            border: 1px solid #ddd;
            padding: 3px;
            background: #fff;
        }
        .qr-label { font-size: 8px; color: #666; margin-top: 10px; }
        
        .no-print-actions {
            margin-top: 25px;
            display: flex;
            gap: 12px;
        }
        .btn {
            background-color: #1e293b;
            color: #fff;
            padding: 9px 18px;
            border: none;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            border-radius: 6px;
            transition: background 0.2s;
        }
        .btn:hover { background-color: #0f172a; }

        @media print {
            body { 
                background-color: #fff; 
                padding: 0; 
                margin: 0;
                width: 80mm;
            }
            .receipt-container { 
                width: 80mm; 
                box-shadow: none; 
                border: none; 
                padding: 10mm 5mm; 
            }
            .no-print-actions { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    @php
        $kitchenItems = $order->items->filter(fn($item) => ($item->product->category->production_station ?? 'kitchen') === 'kitchen');
        $baristaItems = $order->items->filter(fn($item) => ($item->product->category->production_station ?? '') === 'barista');
        
        $receipts = [];
        
        // 1. Kitchen Slip
        if ($kitchenItems->count() > 0) {
            $receipts[] = ['type' => 'kitchen', 'title' => 'KITCHEN SLIP', 'items' => $kitchenItems];
        }
        
        // 2. Barista Slip
        if ($baristaItems->count() > 0) {
            $receipts[] = ['type' => 'barista', 'title' => 'BARISTA SLIP', 'items' => $baristaItems];
        }
        
        // 3. Customer Receipt
        $receipts[] = ['type' => 'customer', 'title' => $settings['business_name'], 'items' => $order->items];
    @endphp

    @foreach($receipts as $index => $receipt)
    <div class="receipt-container {{ $index < count($receipts) - 1 ? 'page-break' : '' }}">
        <div class="text-center header">
            @php
                $logo = \App\Models\SystemSetting::get('business_logo');
            @endphp
            
            @if($receipt['type'] === 'customer')
                @if($settings['receipt_logo_enabled'])
                    @if($logo)
                        <img src="{{ Storage::url($logo) }}" class="logo-img">
                    @else
                        <div class="logo-placeholder">MTC</div>
                    @endif
                @endif
                <div class="business-name">{{ $receipt['title'] }}</div>
                <div class="header-info">{{ $settings['business_address'] }}</div>
                <div class="header-info">Tel: {{ $settings['business_phone'] }}</div>
            @else
                <div class="business-name" style="font-size: 18px; border: 2px solid #000; padding: 5px; display: inline-block;">{{ $receipt['title'] }}</div>
                <div class="header-info" style="margin-top: 5px; font-weight: bold;">Order #{{ $order->reference_no }}</div>
            @endif
        </div>

        <div class="divider"></div>

        <div class="info-section">
            <div class="row"><span>#{{ $order->reference_no }}</span><span>{{ $order->created_at->format('d/m/y H:i') }}</span></div>
            <div class="row"><span>TYPE: {{ strtoupper($order->order_type) }}</span><span>STAFF: {{ strtoupper($order->user->first_name ?? 'APP') }}</span></div>
        </div>

        <div class="divider"></div>

        <div class="items-section">
            @foreach($receipt['items'] as $item)
                <div class="item-row">
                    <div class="item-main">
                        <span style="{{ $receipt['type'] !== 'customer' ? 'font-size: 14px;' : '' }}">{{ $item->quantity }}x {{ $item->product->name }}</span>
                        @if($receipt['type'] === 'customer')
                            <span>{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format($item->subtotal, 2) }}</span>
                        @endif
                    </div>
                    @foreach($item->options as $opt)
                        <div class="item-detail" style="{{ $receipt['type'] !== 'customer' ? 'font-size: 11px; font-weight: bold;' : '' }}">+ {{ $opt->option->name ?? 'Option' }}</div>
                    @endforeach
                    @foreach($item->modifiers as $mod)
                        <div class="item-detail" style="{{ $receipt['type'] !== 'customer' ? 'font-size: 11px; font-weight: bold;' : '' }}">+ {{ $mod->modifier->name ?? 'Modifier' }}</div>
                    @endforeach
                </div>
            @endforeach
        </div>

        @if($receipt['type'] === 'customer')
            <div class="totals-section">
                <div class="row">
                    <span>Subtotal</span>
                    <span>{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format($order->total_amount + $order->discount_amount, 2) }}</span>
                </div>

                @if($order->discount_amount > 0)
                    <div class="row">
                        <span>Discount</span>
                        <span>-{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif

                <div class="row grand-total">
                    <span class="bold">TOTAL</span>
                    <span class="bold">{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format($order->total_amount, 2) }}</span>
                </div>

                <div class="row" style="margin-top: 6px;">
                    <span>{{ $order->payment_method }}</span>
                    <span>{{ $settings['currency_symbol'] ?? '₱' }}{{ number_format($order->total_amount, 2) }}</span>
                </div>
            </div>
        @endif

        @if($order->notes || $order->delivery_notes)
            <div class="divider"></div>
            <div style="font-size: {{ $receipt['type'] !== 'customer' ? '12px' : '9px' }};">
                <div class="bold uppercase">Remarks:</div>
                <div style="margin-top: 2px; {{ $receipt['type'] !== 'customer' ? 'font-weight: bold;' : '' }}">{{ $order->notes }} {{ $order->delivery_notes }}</div>
            </div>
        @endif

        @if($receipt['type'] === 'customer')
            <div class="footer">
                <p class="footer-msg">{{ $settings['receipt_footer_message'] }}</p>
                
                @if($settings['qr_code'])
                    <div class="divider"></div>
                    <div class="qr-label">Scan to Review</div>
                    <img src="{{ $settings['qr_code'] }}" class="qr-img">
                @endif

                <p style="margin-top: 15px; font-weight: bold; letter-spacing: 2px;">*** THANK YOU ***</p>
            </div>
        @else
            <div class="footer">
                <div class="divider"></div>
                <p style="font-weight: bold; font-size: 10px;">PREPARATION SLIP</p>
            </div>
        @endif
    </div>
    @endforeach

    <div class="no-print-actions">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <button class="btn" style="background-color: #4b5563;" onclick="window.close()">Close Window</button>
    </div>

    <script>
        // Auto-close window after printing (improves 'automatic' feel)
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
