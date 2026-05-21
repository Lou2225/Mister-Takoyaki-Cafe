<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $order->reference_no }}</title>
    <!--
        Receipt Design System
        ========================
        This receipt template is connected to System Settings.
        
        FINANCIAL SETTINGS:
        - Currency: {{ $settings['currency'] }} {{ $settings['currency_symbol'] }}
        
        RECEIPT DESIGN SETTINGS:
        - Show Logo: {{ $settings['receipt_logo_enabled'] ? 'Yes' : 'No' }}
        - Footer Message: "{{ $settings['receipt_footer_message'] }}"
        - QR Code for Reviews: {{ !empty($settings['receipt_qr_url']) ? 'Enabled' : 'Disabled' }}
    -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
                width: 80mm;
                font-family: 'Courier New', monospace;
            }
            .receipt-container {
                width: 80mm;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
        
        body {
            margin: 0;
            padding: 0;
        }
        
        .receipt-paper {
            width: 80mm;
            font-family: 'Courier New', monospace;
            font-size: 10px;
            line-height: 1.2;
            color: #1f2937;
            background: white;
        }
        
        .receipt-container {
            padding: 16px;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 12px;
        }
        
        .receipt-logo {
            width: 32px;
            height: 32px;
            margin: 0 auto 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .receipt-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .receipt-logo-placeholder {
            width: 32px;
            height: 32px;
            background: #111827;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
        }
        
        .receipt-business-name {
            font-weight: bold;
            font-size: 11px;
            margin: 0;
        }
        
        .receipt-meta {
            border-top: 1px dashed #d1d5db;
            border-bottom: 1px dashed #d1d5db;
            padding: 6px 0;
            margin: 12px 0;
            font-size: 9px;
            display: flex;
            justify-content: space-between;
        }
        
        .receipt-items {
            margin: 12px 0;
            font-size: 10px;
        }
        
        .receipt-item {
            margin-bottom: 4px;
        }
        
        .receipt-item-main {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        
        .receipt-item-name {
            flex: 1;
            word-break: break-word;
        }
        
        .receipt-item-price {
            text-align: right;
            margin-left: 8px;
            font-weight: bold;
        }
        
        .receipt-item-details {
            font-size: 8px;
            color: #6b7280;
            margin-left: 4px;
            margin-top: 2px;
        }
        
        .receipt-totals {
            border-top: 1px dashed #d1d5db;
            padding-top: 6px;
            margin-top: 12px;
            font-size: 10px;
        }
        
        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }
        
        .receipt-total-label {
            flex: 1;
        }
        
        .receipt-total-value {
            text-align: right;
            font-weight: bold;
            width: 60px;
        }
        
        .receipt-grand-total {
            border-top: 1px solid #1f2937;
            border-bottom: 1px solid #1f2937;
            padding: 4px 0;
            margin-top: 4px;
            font-weight: bold;
            font-size: 11px;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 12px;
            font-size: 8px;
        }
        
        .receipt-footer-text {
            font-weight: bold;
            font-style: italic;
            color: #4b5563;
            margin: 4px 0;
        }
        
        .receipt-qr {
            text-align: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #d1d5db;
        }
        
        .receipt-qr-label {
            font-size: 7px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .receipt-qr img {
            width: 48px;
            height: 48px;
            border: 1px solid #d1d5db;
        }
        
        .print-button-container {
            display: flex;
            gap: 12px;
            padding: 16px;
            background: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 16px;
        }
        
        .print-button-container button {
            flex: 1;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-print {
            background: #4f46e5;
            color: white;
        }
        
        .btn-print:hover {
            background: #4338ca;
        }
        
        .btn-close {
            background: white;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        
        .btn-close:hover {
            background: #f9fafb;
        }
        
        .receipt-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="no-print print-button-container">
        <button type="button" class="btn-print" onclick="window.print()">
            🖨️ Print Receipt
        </button>
        <button type="button" class="btn-close" onclick="window.history.back()">
            Close
        </button>
    </div>

    <div class="receipt-wrapper">
        <div class="receipt-paper">
            <div class="receipt-container">
                {{-- Header --}}
                @if($settings['receipt_logo_enabled'])
                    <div class="receipt-header">
                        @if($businessLogo)
                            <div class="receipt-logo">
                                <img src="{{ asset('storage/' . $businessLogo) }}" alt="Business Logo">
                            </div>
                        @else
                            <div class="receipt-logo-placeholder" style="background: black; color: white; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; margin: 0 auto; border-radius: 4px; font-weight: bold; font-size: 10px;">
                                MTC
                            </div>
                        @endif
                    </div>
                @endif
                
                <p class="receipt-business-name">{{ $settings['business_name'] ?? 'Your Business Name' }}</p>
                
                @if(!empty($settings['business_phone']))
                    <div style="font-size: 8px; color: #4b5563; margin-top: 2px;">+63 {{ ltrim(trim($settings['business_phone']), '+63') }}</div>
                @endif
                @if(!empty($settings['business_email']))
                    <div style="font-size: 8px; color: #4b5563; margin-top: 2px;">{{ $settings['business_email'] }}</div>
                @endif
                <div style="text-align: center; margin-top: 4px; margin-bottom: 8px;">
                    <span style="font-size: 14px; font-weight: 900; text-transform: uppercase; border: 2px solid black; padding: 2px 8px; border-radius: 4px; letter-spacing: 1px;">
                        {{ $order->order_type }}
                    </span>
                </div>

                {{-- Order Meta --}}
                <div class="receipt-meta">
                    <span>#{{ $order->reference_no }}</span>
                    <span>{{ $order->created_at->format('d/m/y H:i') }}</span>
                </div>

                {{-- Customer Info / Delivery Block --}}
                @if($order->order_type === 'Delivery')
                    <div style="font-size: 9px; margin-bottom: 12px; border: 1.5px solid #111; border-radius: 4px; padding: 6px 8px;">
                        <div style="font-weight: 900; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; border-bottom: 1px dashed #d1d5db; padding-bottom: 4px;">📦 Delivery Info</div>
                        @if($order->customer_name)
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span style="color: #6b7280;">CUSTOMER:</span>
                                <span style="font-weight: bold;">{{ $order->customer_name }}</span>
                            </div>
                        @endif
                        @if($order->customer_phone)
                            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                                <span style="color: #6b7280;">CONTACT:</span>
                                <span style="font-weight: bold;">{{ $order->customer_phone }}</span>
                            </div>
                        @endif
                        @if($order->delivery_address)
                            <div style="margin-top: 4px;">
                                <div style="color: #6b7280; font-size: 8px; margin-bottom: 2px;">DELIVER TO:</div>
                                <div style="font-weight: bold; line-height: 1.4;">{{ $order->delivery_address }}</div>
                            </div>
                        @endif
                        @if($order->delivery_notes)
                            <div style="margin-top: 4px; font-style: italic; color: #4b5563; border-top: 1px dashed #e5e7eb; padding-top: 4px;">
                                <span style="font-size: 8px; font-weight: bold;">NOTES:</span> {{ $order->delivery_notes }}
                            </div>
                        @endif
                    </div>
                @elseif($order->customer_name || $order->customer_phone)
                    <div style="font-size: 9px; margin-bottom: 12px; border-bottom: 1px dashed #d1d5db; padding-bottom: 8px;">
                        @if($order->customer_name)
                            <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                                <span style="color: #6b7280;">CUSTOMER:</span>
                                <span style="font-weight: bold;">{{ $order->customer_name }}</span>
                            </div>
                        @endif
                        @if($order->customer_phone)
                            <div style="display: flex; justify-content: space-between; margin-bottom: 2px;">
                                <span style="color: #6b7280;">CONTACT:</span>
                                <span style="font-weight: bold;">{{ $order->customer_phone }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Items --}}
                <div class="receipt-items">
                    @foreach($order->items as $item)
                        <div class="receipt-item">
                            <div class="receipt-item-main">
                                <span class="receipt-item-name">{{ $item->quantity }}x {{ $item->product->name }}</span>
                                <span class="receipt-item-price">{{ $settings['currency_symbol'] }}{{ number_format($item->subtotal, 2) }}</span>
                            </div>
                            
                            {{-- Options --}}
                            @if($item->options->count() > 0)
                                <div class="receipt-item-details">
                                    @foreach($item->options as $opt)
                                        <div>+ {{ $opt->option->name }} ({{ $settings['currency_symbol'] }}{{ number_format($opt->price, 2) }})</div>
                                    @endforeach
                                </div>
                            @endif
                            
                            {{-- Modifiers --}}
                            @if($item->modifiers->count() > 0)
                                <div class="receipt-item-details">
                                    @foreach($item->modifiers as $mod)
                                        <div>+ {{ $mod->modifier->name }} ({{ $settings['currency_symbol'] }}{{ number_format($mod->unit_price, 2) }})</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="receipt-totals">
                    @php
                        $subtotal = $order->total_amount + $order->discount_amount;
                    @endphp
                    <div class="receipt-total-row">
                        <span class="receipt-total-label">Subtotal</span>
                        <span class="receipt-total-value">{{ $settings['currency_symbol'] }}{{ number_format($subtotal, 2) }}</span>
                    </div>



                    @if($order->discount_amount > 0)
                        <div class="receipt-total-row">
                            <span class="receipt-total-label">Discount</span>
                            <span class="receipt-total-value">-{{ $settings['currency_symbol'] }}{{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                    @endif

                    @if($order->service_charge > 0)
                        <div class="receipt-total-row">
                            <span class="receipt-total-label">Service Charge</span>
                            <span class="receipt-total-value">{{ $settings['currency_symbol'] }}{{ number_format($order->service_charge, 2) }}</span>
                        </div>
                    @endif

                    <div class="receipt-total-row receipt-grand-total">
                        <span class="receipt-total-label">TOTAL</span>
                        <span class="receipt-total-value">{{ $settings['currency_symbol'] }}{{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>

                {{-- Payment Info --}}
                <div class="receipt-totals" style="border-top: none; padding-top: 0; margin-top: 8px;">
                    <div class="receipt-total-row">
                        <span class="receipt-total-label">Payment</span>
                        <span class="receipt-total-value" style="width: auto;">{{ $order->payment_method }}</span>
                    </div>
                    <div class="receipt-total-row">
                        <span class="receipt-total-label">Order Type</span>
                        <span class="receipt-total-value" style="width: auto;">{{ $order->order_type }}</span>
                    </div>
                    @if($order->table_number)
                        <div class="receipt-total-row">
                            <span class="receipt-total-label">Table</span>
                            <span class="receipt-total-value" style="width: auto;">{{ $order->table_number }}</span>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="receipt-footer">
                    <p class="receipt-footer-text">{{ $settings['receipt_footer_message'] ?? 'Thank you for your visit!' }}</p>

                    @if(!empty($settings['receipt_return_policy']))
                        <div style="font-size: 7px; color: #6b7280; margin-top: 6px; padding-top: 4px; border-top: 1px dashed #e5e7eb;">
                            {{ $settings['receipt_return_policy'] }}
                        </div>
                    @endif

                    @if(!empty($settings['qr_code']))
                        <div class="receipt-qr">
                            <div class="receipt-qr-label">Scan to Review:</div>
                            <img src="{{ $settings['qr_code'] }}" alt="Review QR Code">
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>

