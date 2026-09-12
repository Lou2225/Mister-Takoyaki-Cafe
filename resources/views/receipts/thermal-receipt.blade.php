<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $order->reference_no }}</title>
    <style>
        /* ──── Reset & Base ──── */
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --font-size-base: 11px;
            --font-size-sm: 9.5px;
            --font-size-xs: 8.5px;
            --font-size-lg: 13px;
            --font-size-xl: 15px;
        }

        body {
            font-family: Consolas, 'Courier New', 'Lucida Console', Monaco, 'DejaVu Sans Mono', monospace;
            font-size: var(--font-size-base);
            line-height: 1.25;
            color: #000;
            background: #f3f4f6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        /* ──── Page & Print Media ──── */
        @page {
            size: auto;
            margin: 0mm;
        }

        @media print {
            body {
                background: #fff !important;
                color: #000 !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .no-print {
                display: none !important;
            }

            .receipt-wrapper {
                padding: 0 !important;
                margin: 0 !important;
                background: none !important;
                min-height: auto !important;
                display: block !important;
            }

            .receipt-section {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-after: always !important;
                break-after: page !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .receipt-section:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            .receipt-paper {
                box-shadow: none !important;
                border: none !important;
                margin: 0 auto !important;
                padding: 0 !important;
                background: #fff !important;
            }

            .paper-58mm {
                width: 58mm !important;
                max-width: 58mm !important;
            }

            .paper-80mm {
                width: 80mm !important;
                max-width: 80mm !important;
            }

            .paper-auto {
                width: 100% !important;
                max-width: 100% !important;
            }
        }

        /* ──── Screen UI Toolbar ──── */
        .no-print-toolbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: #111827;
            color: #f9fafb;
            padding: 10px 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 13px;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .toolbar-title {
            font-weight: 700;
            font-size: 14px;
            color: #60a5fa;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .toolbar-badge {
            background: #374151;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            color: #9ca3af;
        }

        .toolbar-checkbox {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            user-select: none;
            background: #1f2937;
            padding: 4px 10px;
            border-radius: 6px;
            border: 1px solid #374151;
            font-size: 12px;
            transition: background 0.15s;
        }
        .toolbar-checkbox:hover {
            background: #374151;
        }
        .toolbar-checkbox input {
            cursor: pointer;
            accent-color: #3b82f6;
        }

        .size-select-wrap {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
        }
        .size-btn-group {
            display: inline-flex;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #374151;
        }
        .size-btn {
            background: #1f2937;
            color: #d1d5db;
            border: none;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .size-btn.active {
            background: #3b82f6;
            color: #fff;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-action-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-action-primary:hover {
            background: #1d4ed8;
        }
        .btn-action-secondary {
            background: #374151;
            color: #d1d5db;
        }
        .btn-action-secondary:hover {
            background: #4b5563;
        }

        /* ──── Receipt Container Layout ──── */
        .receipt-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 12px 60px;
            min-height: 100vh;
        }

        .receipt-paper {
            background: #fff;
            padding: 0;
            margin-bottom: 24px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
            transition: width 0.2s ease;
            box-sizing: border-box;
        }

        .paper-58mm {
            width: 58mm;
            max-width: 58mm;
        }

        .paper-80mm {
            width: 80mm;
            max-width: 80mm;
        }

        .paper-auto {
            width: 58mm;
            max-width: 100%;
        }

        .receipt-container {
            width: 100%;
            /* Safe 3mm margin on both sides to prevent physical printer head clipping */
            padding: 8px 3mm;
            box-sizing: border-box;
        }

        /* ──── Typography & Headers ──── */
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .font-black { font-weight: 900; }
        .uppercase { text-transform: uppercase; }
        .tabular-nums { font-variant-numeric: tabular-nums; }

        /* ──── Header & Branding ──── */
        .business-header {
            text-align: center;
            margin-bottom: 6px;
        }

        .receipt-logo-container {
            text-align: center;
            margin: 0 auto 6px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .receipt-logo-img {
            max-height: 44px;
            max-width: 130px;
            width: auto;
            height: auto;
            display: block;
            margin: 0 auto;
            object-fit: contain;
            filter: grayscale(100%) contrast(140%);
            image-rendering: -webkit-optimize-contrast;
            image-rendering: auto;
            background: #fff;
        }

        .business-name {
            font-weight: 900;
            font-size: var(--font-size-lg);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
            margin-bottom: 2px;
            word-break: break-word;
        }

        .business-info {
            font-size: var(--font-size-xs);
            line-height: 1.25;
            word-break: break-word;
            margin-top: 1px;
        }

        /* ──── Slip Headers (Kitchen / Barista) ──── */
        .slip-header {
            text-align: center;
            border-top: 1.5px dashed #000;
            border-bottom: 1.5px dashed #000;
            padding: 4px 0;
            margin-bottom: 6px;
        }

        .slip-title {
            font-weight: 900;
            font-size: var(--font-size-lg);
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .slip-subtitle {
            font-size: var(--font-size-xs);
            text-transform: uppercase;
            margin-top: 1px;
        }

        /* ──── Dividers ──── */
        .divider-dashed {
            border-top: 1px dashed #000;
            margin: 5px 0;
            width: 100%;
        }

        .divider-double {
            border-top: 3px double #000;
            margin: 5px 0;
            width: 100%;
        }

        .divider-solid {
            border-top: 1.5px solid #000;
            margin: 5px 0;
            width: 100%;
        }

        /* ──── Order Metadata ──── */
        .receipt-meta {
            font-size: var(--font-size-sm);
            margin: 5px 0;
            line-height: 1.3;
        }

        .receipt-meta-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: 4px;
        }

        .receipt-meta-row span {
            word-break: break-word;
        }

        .order-badge-container {
            margin-top: 3px;
            text-align: center;
        }

        .order-badge {
            display: inline-block;
            border: 1.5px solid #000;
            padding: 1px 6px;
            font-weight: 900;
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ──── Items Section ──── */
        .items-heading {
            font-weight: 900;
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .receipt-items {
            font-size: var(--font-size-sm);
        }

        .receipt-item {
            margin-bottom: 5px;
        }

        .receipt-item:last-child {
            margin-bottom: 2px;
        }

        .receipt-item-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 4px;
            line-height: 1.25;
        }

        .receipt-item-name {
            flex: 1 1 auto;
            min-width: 0;
            word-break: break-word;
            font-weight: 700;
            text-align: left;
        }

        .receipt-item-price {
            flex: 0 0 auto;
            white-space: nowrap;
            text-align: right;
            font-weight: 900;
            font-variant-numeric: tabular-nums;
        }

        .receipt-item-sub {
            font-size: var(--font-size-xs);
            color: #1f2937;
            padding-left: 8px;
            margin-top: 1px;
            line-height: 1.2;
            word-break: break-word;
        }

        .receipt-item-note {
            font-size: var(--font-size-xs);
            font-weight: 700;
            padding-left: 8px;
            margin-top: 2px;
            line-height: 1.2;
            word-break: break-word;
        }

        /* ──── Totals Section ──── */
        .receipt-totals {
            margin-top: 4px;
            font-size: var(--font-size-sm);
        }

        .receipt-total-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 2px;
            font-variant-numeric: tabular-nums;
        }

        .receipt-total-label {
            flex: 1 1 auto;
        }

        .receipt-total-val {
            flex: 0 0 auto;
            white-space: nowrap;
            text-align: right;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .receipt-grand-total {
            border-top: 1.5px dashed #000;
            border-bottom: 1.5px dashed #000;
            padding: 4px 0;
            margin: 4px 0;
            font-weight: 900;
            font-size: var(--font-size-lg);
        }

        .receipt-grand-total .receipt-total-label {
            font-weight: 900;
            font-size: var(--font-size-lg);
        }

        .receipt-grand-total .receipt-total-val {
            font-weight: 900;
            font-size: var(--font-size-lg);
        }

        /* ──── Footer & QR Code ──── */
        .receipt-footer {
            text-align: center;
            margin-top: 6px;
            font-size: var(--font-size-xs);
            line-height: 1.3;
        }

        .footer-message {
            font-weight: 700;
            margin-bottom: 2px;
        }

        .return-policy {
            font-size: 7.5px;
            margin-top: 2px;
        }

        .receipt-qr-wrap {
            text-align: center;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #000;
            width: 100%;
            box-sizing: border-box;
        }

        .receipt-qr-title {
            font-size: var(--font-size-xs);
            font-weight: 900;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            text-align: center;
            width: 100%;
        }

        .qr-canvas-container {
            display: block;
            text-align: center;
            margin: 4px auto 6px;
            width: 100%;
        }

        .qr-code-canvas {
            display: inline-block;
            margin: 0 auto !important;
            image-rendering: pixelated;
            image-rendering: -moz-crisp-edges;
            image-rendering: crisp-edges;
            shape-rendering: crispEdges;
            width: 110px;
            height: 110px;
        }

        .receipt-qr-url {
            font-size: 7.5px;
            word-break: break-all;
            line-height: 1.15;
            margin-top: 3px;
            text-align: center;
            width: 100%;
        }

        .cut-line {
            text-align: center;
            font-size: 8px;
            margin: 8px 0;
            border-top: 1px dashed #000;
            padding-top: 3px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

    @php
        $kitchenItems = $order->items->filter(fn ($item) => (optional($item->product->category)->production_station ?? 'kitchen') === 'kitchen');
        $baristaItems = $order->items->filter(fn ($item) => (optional($item->product->category)->production_station ?? '') === 'barista');
        $reviewUrl = $qrUrl ?? \App\Services\ReceiptService::buildReviewQrUrl($order);

        // Format Titles cleanly without non-ASCII junk that breaks printers
        $kitchenTitle = trim(preg_replace('/[^\x20-\x7E]/', '', $settings['kitchen_slip_title'] ?? 'KITCHEN SLIP')) ?: 'KITCHEN SLIP';
        $kitchenSubtitle = trim(preg_replace('/[^\x20-\x7E]/', '', $settings['kitchen_slip_subtitle'] ?? 'Food Preparation Order'));
        
        $baristaTitle = trim(preg_replace('/[^\x20-\x7E]/', '', $settings['barista_slip_title'] ?? 'BARISTA SLIP')) ?: 'BARISTA SLIP';
        $baristaSubtitle = trim(preg_replace('/[^\x20-\x7E]/', '', $settings['barista_slip_subtitle'] ?? 'Beverage Preparation Order'));
        
        $businessName = trim(preg_replace('/[^\x20-\x7E]/', '', $settings['business_name'] ?? 'Mister Takoyaki Cafe')) ?: 'Mister Takoyaki Cafe';
        $customerReceiptTitle = trim(preg_replace('/[^\x20-\x7E]/', '', $settings['customer_receipt_title'] ?? 'Customer Receipt & Invoice'));

        // Clean currency symbol - robust for Unicode & POS monospace
        $currency = $settings['currency_symbol'] ?? '₱';
    @endphp

    {{-- ──── Screen Control Toolbar ──── --}}
    <div class="no-print no-print-toolbar">
        <div class="toolbar-group">
            <span class="toolbar-title">🖨️ POS Receipt</span>
            <span class="toolbar-badge">#{{ $order->reference_no }}</span>
        </div>

        <div class="toolbar-group">
            {{-- Slip Selection --}}
            @if($kitchenItems->isNotEmpty())
            <label class="toolbar-checkbox">
                <input type="checkbox" id="chkKitchen" checked onchange="updateSlipVisibility()">
                <span>Kitchen</span>
            </label>
            @endif

            @if($baristaItems->isNotEmpty())
            <label class="toolbar-checkbox">
                <input type="checkbox" id="chkBarista" checked onchange="updateSlipVisibility()">
                <span>Barista</span>
            </label>
            @endif

            <label class="toolbar-checkbox">
                <input type="checkbox" id="chkCustomer" checked onchange="updateSlipVisibility()">
                <span>Customer</span>
            </label>

            {{-- Paper Width Switcher --}}
            <div class="size-select-wrap">
                <span>Width:</span>
                <div class="size-btn-group">
                    <button type="button" class="size-btn active" id="btnWidth58" onclick="setPaperWidth('58mm')">58mm (2")</button>
                    <button type="button" class="size-btn" id="btnWidth80" onclick="setPaperWidth('80mm')">80mm (3")</button>
                    <button type="button" class="size-btn" id="btnWidthAuto" onclick="setPaperWidth('auto')">Auto</button>
                </div>
            </div>
        </div>

        <div class="toolbar-group">
            <button type="button" class="btn-action btn-action-primary" onclick="printReceipts()">
                🖨️ Print Receipt
            </button>
            <button type="button" class="btn-action btn-action-secondary" onclick="window.close(); window.history.back();">
                Close
            </button>
        </div>
    </div>

    {{-- ──── Receipts Container ──── --}}
    <div class="receipt-wrapper">

        {{-- ══════════════════════════════════════════════
             1. KITCHEN SLIP
        ══════════════════════════════════════════════ --}}
        @if($kitchenItems->isNotEmpty())
        <div id="kitchenReceipt" class="receipt-section">
            <div class="receipt-paper paper-58mm">
                <div class="receipt-container">
                    <div class="slip-header">
                        <div class="slip-title">{{ $kitchenTitle }}</div>
                        @if($kitchenSubtitle)
                            <div class="slip-subtitle">{{ $kitchenSubtitle }}</div>
                        @endif
                    </div>

                    <div class="receipt-meta">
                        <div class="receipt-meta-row">
                            <span class="font-bold">Order #: {{ $order->reference_no }}</span>
                        </div>
                        <div class="receipt-meta-row">
                            <span>Date: {{ $order->created_at->format('d/m/y H:i') }}</span>
                            <span class="font-bold uppercase">{{ $order->order_type }}</span>
                        </div>
                        @if($order->table_number)
                            <div class="order-badge-container">
                                <span class="order-badge">TABLE #{{ $order->table_number }}</span>
                            </div>
                        @endif
                        @if($order->customer_phone)
                            <div class="receipt-meta-row">
                                <span>Phone: {{ $order->customer_phone }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="divider-dashed"></div>
                    <div class="items-heading">KITCHEN ITEMS:</div>
                    <div class="divider-dashed"></div>

                    <div class="receipt-items">
                        @foreach($kitchenItems as $item)
                            <div class="receipt-item">
                                <div class="receipt-item-row">
                                    <span class="receipt-item-name">{{ $item->quantity }}x {{ $item->product->name }}</span>
                                </div>
                                @if($item->options->count() > 0)
                                    @foreach($item->options as $opt)
                                        <div class="receipt-item-sub">+ {{ $opt->option->name }}</div>
                                    @endforeach
                                @endif
                                @if($item->modifiers && $item->modifiers->count() > 0)
                                    @foreach($item->modifiers as $mod)
                                        <div class="receipt-item-sub">+ {{ $mod->modifier->name }}</div>
                                    @endforeach
                                @endif
                                @if($item->special_instructions)
                                    <div class="receipt-item-note">*** NOTE: {{ $item->special_instructions }} ***</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($order->customer_name)
                        <div class="divider-dashed"></div>
                        <div style="font-size: var(--font-size-xs);">
                            Customer: <strong>{{ $order->customer_name }}</strong>
                        </div>
                    @endif

                    @if($order->delivery_notes)
                        <div class="divider-dashed"></div>
                        <div style="font-size: var(--font-size-xs);">
                            <strong>NOTES:</strong> {{ $order->delivery_notes }}
                        </div>
                    @endif

                    <div class="divider-solid"></div>
                    <div class="cut-line">-- TEAR HERE --</div>
                </div>
            </div>
        </div>
        @endif

        {{-- ══════════════════════════════════════════════
             2. BARISTA SLIP
        ══════════════════════════════════════════════ --}}
        @if($baristaItems->isNotEmpty())
        <div id="baristaReceipt" class="receipt-section">
            <div class="receipt-paper paper-58mm">
                <div class="receipt-container">
                    <div class="slip-header">
                        <div class="slip-title">{{ $baristaTitle }}</div>
                        @if($baristaSubtitle)
                            <div class="slip-subtitle">{{ $baristaSubtitle }}</div>
                        @endif
                    </div>

                    <div class="receipt-meta">
                        <div class="receipt-meta-row">
                            <span class="font-bold">Order #: {{ $order->reference_no }}</span>
                        </div>
                        <div class="receipt-meta-row">
                            <span>Date: {{ $order->created_at->format('d/m/y H:i') }}</span>
                            <span class="font-bold uppercase">{{ $order->order_type }}</span>
                        </div>
                        @if($order->table_number)
                            <div class="order-badge-container">
                                <span class="order-badge">TABLE #{{ $order->table_number }}</span>
                            </div>
                        @endif
                        @if($order->customer_phone)
                            <div class="receipt-meta-row">
                                <span>Phone: {{ $order->customer_phone }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="divider-dashed"></div>
                    <div class="items-heading">BEVERAGES:</div>
                    <div class="divider-dashed"></div>

                    <div class="receipt-items">
                        @foreach($baristaItems as $item)
                            <div class="receipt-item">
                                <div class="receipt-item-row">
                                    <span class="receipt-item-name">{{ $item->quantity }}x {{ $item->product->name }}</span>
                                </div>
                                @if($item->options->count() > 0)
                                    @foreach($item->options as $opt)
                                        <div class="receipt-item-sub">+ {{ $opt->option->name }}</div>
                                    @endforeach
                                @endif
                                @if($item->modifiers && $item->modifiers->count() > 0)
                                    @foreach($item->modifiers as $mod)
                                        <div class="receipt-item-sub">+ {{ $mod->modifier->name }}</div>
                                    @endforeach
                                @endif
                                @if($item->special_instructions)
                                    <div class="receipt-item-note">*** NOTE: {{ $item->special_instructions }} ***</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    @if($order->customer_name)
                        <div class="divider-dashed"></div>
                        <div style="font-size: var(--font-size-xs);">
                            Customer: <strong>{{ $order->customer_name }}</strong>
                        </div>
                    @endif

                    <div class="divider-solid"></div>
                    <div class="cut-line">-- TEAR HERE --</div>
                </div>
            </div>
        </div>
        @endif

        {{-- ══════════════════════════════════════════════
             3. CUSTOMER RECEIPT
             3. CUSTOMER RECEIPT (printed receipt_copies times)
        ══════════════════════════════════════════════ --}}
        <div id="customerReceipt" class="receipt-section">
        @for ($i = 0; $i < max(1, (int) ($settings['receipt_copies'] ?? 1)); $i++)
        <div class="customerReceipt receipt-section">
            <div class="receipt-paper paper-58mm">
                <div class="receipt-container">

                    {{-- Store Header & High-Contrast Logo --}}
                    <div class="business-header">
                        @if(!empty($logoDataUri))
                            <div class="receipt-logo-container">
                                <img src="{{ $logoDataUri }}" alt="Logo" class="receipt-logo-img">
                            </div>
                        @endif

                        <div class="business-name">{{ $businessName }}</div>
                        @if(!empty($customerReceiptTitle))
                            <div class="slip-subtitle font-bold">{{ $customerReceiptTitle }}</div>
                        @endif

                        <div class="divider-dashed"></div>

                        @if(!empty($settings['business_address']))
                            <div class="business-info">{{ $settings['business_address'] }}</div>
                        @endif
                        @if(!empty($settings['business_phone']))
                            <div class="business-info">Tel: {{ $settings['business_phone'] }}</div>
                        @endif
                        @if(!empty($settings['business_email']))
                            <div class="business-info">Email: {{ $settings['business_email'] }}</div>
                        @endif
                    </div>

                    {{-- Order Details --}}
                    <div class="receipt-meta">
                        <div class="receipt-meta-row">
                            <span class="font-bold">Order #: {{ $order->reference_no }}</span>
                        </div>
                        <div class="receipt-meta-row">
                            <span>Date: {{ $order->created_at->format('d/m/y H:i') }}</span>
                            <span class="font-bold uppercase">{{ $order->order_type }}</span>
                        </div>
                        @if($order->customer_name)
                            <div class="receipt-meta-row">
                                <span>Customer: <strong>{{ $order->customer_name }}</strong></span>
                                @if($order->table_number)
                                    <span>Table: <strong>{{ $order->table_number }}</strong></span>
                                @endif
                            </div>
                        @endif
                        @if($order->table_number && !$order->customer_name)
                            <div class="receipt-meta-row">
                                <span>Table: <strong>{{ $order->table_number }}</strong></span>
                            </div>
                        @endif
                        @if($order->customer_phone)
                            <div class="receipt-meta-row">
                                <span>Phone: {{ $order->customer_phone }}</span>
                            </div>
                        @endif
                        <div class="receipt-meta-row">
                            <span>Payment: <strong>{{ $order->payment_method ?? 'Cash' }}</strong></span>
                        </div>
                    </div>

                    @if($order->order_type === 'Delivery' && $order->delivery_address)
                        <div class="divider-dashed"></div>
                        <div class="receipt-meta-row">
                            <span class="font-bold uppercase">Deliver To:</span>
                        </div>
                        <div class="receipt-meta-row">
                            <span>{{ $order->delivery_address }}</span>
                        </div>
                        @if($order->delivery_notes)
                            <div class="receipt-meta-row">
                                <span>Note: {{ $order->delivery_notes }}</span>
                            </div>
                        @endif
                    @endif

                    <div class="divider-dashed"></div>
                    <div class="items-heading">ITEMS:</div>
                    <div class="divider-dashed"></div>

                    {{-- Items Listing --}}
                    <div class="receipt-items">
                        @foreach($order->items as $item)
                            <div class="receipt-item">
                                <div class="receipt-item-row">
                                    <span class="receipt-item-name">{{ $item->quantity }}x {{ $item->product->name }}</span>
                                    <span class="receipt-item-price">{{ $currency }}{{ number_format($item->subtotal, 2) }}</span>
                                </div>
                                @if($item->options->count() > 0)
                                    @foreach($item->options as $opt)
                                        <div class="receipt-item-sub">
                                            + {{ $opt->option->name }} 
                                            @if($opt->price > 0)
                                                ({{ $currency }}{{ number_format($opt->price, 2) }})
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                                @if($item->modifiers && $item->modifiers->count() > 0)
                                    @foreach($item->modifiers as $mod)
                                        <div class="receipt-item-sub">
                                            + {{ $mod->modifier->name }}
                                            @if($mod->unit_price > 0)
                                                ({{ $currency }}{{ number_format($mod->unit_price, 2) }})
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                                @if($item->special_instructions)
                                    <div class="receipt-item-note">NOTE: {{ $item->special_instructions }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="divider-dashed"></div>

                    {{-- Totals --}}
                    @php
                        $subtotal = $order->total_amount + $order->discount_amount;
                    @endphp
                    <div class="receipt-totals">
                        <div class="receipt-total-row">
                            <span class="receipt-total-label">Subtotal</span>
                            <span class="receipt-total-val">{{ $currency }}{{ number_format($subtotal, 2) }}</span>
                        </div>

                        @if($order->discount_amount > 0)
                            <div class="receipt-total-row">
                                <span class="receipt-total-label">Discount</span>
                                <span class="receipt-total-val">-{{ $currency }}{{ number_format($order->discount_amount, 2) }}</span>
                            </div>
                        @endif

                        @if($order->service_charge > 0)
                            <div class="receipt-total-row">
                                <span class="receipt-total-label">Service Charge</span>
                                <span class="receipt-total-val">{{ $currency }}{{ number_format($order->service_charge, 2) }}</span>
                            </div>
                        @endif

                        @if($order->delivery_fee > 0)
                            <div class="receipt-total-row">
                                <span class="receipt-total-label">Delivery Fee</span>
                                <span class="receipt-total-val">{{ $currency }}{{ number_format($order->delivery_fee, 2) }}</span>
                            </div>
                        @endif

                        <div class="receipt-total-row receipt-grand-total">
                            <span class="receipt-total-label">TOTAL</span>
                            <span class="receipt-total-val">{{ $currency }}{{ number_format($order->total_amount, 2) }}</span>
                        </div>

                        @if(($order->payment_method ?? 'Cash') === 'Cash' && $order->amount_tendered !== null && $order->amount_tendered > 0)
                            <div class="receipt-total-row" style="margin-top: 3px;">
                                <span class="receipt-total-label">Cash Tendered</span>
                                <span class="receipt-total-val">{{ $currency }}{{ number_format($order->amount_tendered, 2) }}</span>
                            </div>
                            <div class="receipt-total-row">
                                <span class="receipt-total-label">Change</span>
                                <span class="receipt-total-val">{{ $currency }}{{ number_format($order->change_amount ?? 0, 2) }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="divider-dashed"></div>

                    {{-- Footer Message --}}
                    @if(($settings['show_receipt_footer'] ?? true) !== false)
                        <div class="receipt-footer">
                            <div class="footer-message">{{ $settings['receipt_footer_message'] ?? 'Thank you for your visit!' }}</div>
                            @if(!empty($settings['receipt_return_policy']))
                                <div class="return-policy">{{ $settings['receipt_return_policy'] }}</div>
                            @endif
                        </div>
                    @endif

                    {{-- Crystal-Clear 1-Bit Monochrome QR Code --}}
                    @if(($settings['show_receipt_qr_code'] ?? true) && !empty($reviewUrl))
                        <div class="receipt-qr-wrap">
                            <div class="receipt-qr-title">SCAN TO REVIEW & RATE ORDER</div>
                            <div class="qr-canvas-container">
                                <canvas id="receiptQrCanvas" class="qr-code-canvas" width="100" height="100"></canvas>
                            </div>
                            <div class="receipt-qr-url">{{ $reviewUrl }}</div>
                        </div>
                    @endif

                    <div class="divider-solid" style="margin-top: 8px;"></div>
                    <div style="text-align: center; font-size: 8px; margin-top: 3px; font-weight: bold;">
                        *** THANK YOU FOR YOUR ORDER ***
                    </div>
                </div>
            </div>
        </div>
        @endfor

    </div><!-- Closing receipt-wrapper -->

    {{-- ──── Embedded Pure JS QR Generator (0 External Dependencies) ──── --}}
    <script>
        // Minimal standalone QR Code Matrix Generator (QRCode TypeNumber 0 auto)
        (function(global) {
            function QR8BitByte(data) {
                this.mode = 4;
                this.data = data;
            }
            QR8BitByte.prototype = {
                getLength: function() { return this.data.length; },
                write: function(buffer) {
                    for (var i = 0; i < this.data.length; i++) {
                        buffer.put(this.data.charCodeAt(i), 8);
                    }
                }
            };
            function QRCode(typeNumber, errorCorrectLevel) {
                this.typeNumber = typeNumber;
                this.errorCorrectLevel = errorCorrectLevel;
                this.modules = null;
                this.moduleCount = 0;
                this.dataCache = null;
                this.dataList = [];
            }
            QRCode.prototype = {
                addData: function(data) {
                    this.dataList.push(new QR8BitByte(data));
                    this.dataCache = null;
                },
                isDark: function(row, col) {
                    if (row < 0 || this.moduleCount <= row || col < 0 || this.moduleCount <= col) {
                        throw new Error(row + "," + col);
                    }
                    return this.modules[row][col];
                },
                getModuleCount: function() { return this.moduleCount; },
                make: function() {
                    if (this.typeNumber < 1) {
                        var typeNumber = 1;
                        for (typeNumber = 1; typeNumber < 40; typeNumber++) {
                            var rsBlocks = QRRSBlock.getRSBlocks(typeNumber, this.errorCorrectLevel);
                            var buffer = new QRBitBuffer();
                            var totalDataCount = 0;
                            for (var i = 0; i < rsBlocks.length; i++) {
                                totalDataCount += rsBlocks[i].dataCount;
                            }
                            for (var i = 0; i < this.dataList.length; i++) {
                                var data = this.dataList[i];
                                buffer.put(data.mode, 4);
                                buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
                                data.write(buffer);
                            }
                            if (buffer.getLengthInBits() <= totalDataCount * 8) break;
                        }
                        this.typeNumber = typeNumber;
                    }
                    this.makeImpl(false, this.getBestMaskPattern());
                },
                makeImpl: function(test, maskPattern) {
                    this.moduleCount = this.typeNumber * 4 + 17;
                    this.modules = new Array(this.moduleCount);
                    for (var row = 0; row < this.moduleCount; row++) {
                        this.modules[row] = new Array(this.moduleCount);
                        for (var col = 0; col < this.moduleCount; col++) {
                            this.modules[row][col] = null;
                        }
                    }
                    this.setupPositionProbePattern(0, 0);
                    this.setupPositionProbePattern(this.moduleCount - 7, 0);
                    this.setupPositionProbePattern(0, this.moduleCount - 7);
                    this.setupPositionAdjustPattern();
                    this.setupTimingPattern();
                    this.setupTypeInfo(test, maskPattern);
                    if (this.typeNumber >= 7) {
                        this.setupTypeNumber(test);
                    }
                    if (this.dataCache == null) {
                        this.dataCache = QRCode.createData(this.typeNumber, this.errorCorrectLevel, this.dataList);
                    }
                    this.mapData(this.dataCache, maskPattern);
                },
                setupPositionProbePattern: function(row, col) {
                    for (var r = -1; r <= 7; r++) {
                        if (row + r <= -1 || this.moduleCount <= row + r) continue;
                        for (var c = -1; c <= 7; c++) {
                            if (col + c <= -1 || this.moduleCount <= col + c) continue;
                            if ((0 <= r && r <= 6 && (c == 0 || c == 6)) || (0 <= c && c <= 6 && (r == 0 || r == 6)) || (2 <= r && r <= 4 && 2 <= c && c <= 4)) {
                                this.modules[row + r][col + c] = true;
                            } else {
                                this.modules[row + r][col + c] = false;
                            }
                        }
                    }
                },
                getBestMaskPattern: function() {
                    var minLostPoint = 0, pattern = 0;
                    for (var i = 0; i < 8; i++) {
                        this.makeImpl(true, i);
                        var lostPoint = QRUtil.getLostPoint(this);
                        if (i == 0 || minLostPoint > lostPoint) {
                            minLostPoint = lostPoint;
                            pattern = i;
                        }
                    }
                    return pattern;
                },
                setupTimingPattern: function() {
                    for (var r = 8; r < this.moduleCount - 8; r++) {
                        if (this.modules[r][6] !== null) continue;
                        this.modules[r][6] = (r % 2 == 0);
                    }
                    for (var c = 8; c < this.moduleCount - 8; c++) {
                        if (this.modules[6][c] !== null) continue;
                        this.modules[6][c] = (c % 2 == 0);
                    }
                },
                setupPositionAdjustPattern: function() {
                    var pos = QRUtil.getPatternPosition(this.typeNumber);
                    for (var i = 0; i < pos.length; i++) {
                        for (var j = 0; j < pos.length; j++) {
                            var row = pos[i], col = pos[j];
                            if (this.modules[row][col] !== null) continue;
                            for (var r = -2; r <= 2; r++) {
                                for (var c = -2; c <= 2; c++) {
                                    this.modules[row + r][col + c] = (r == -2 || r == 2 || c == -2 || c == 2 || (r == 0 && c == 0));
                                }
                            }
                        }
                    }
                },
                setupTypeNumber: function(test) {
                    var bits = QRUtil.getBCHTypeNumber(this.typeNumber);
                    for (var i = 0; i < 18; i++) {
                        var mod = (!test && ((bits >> i) & 1) == 1);
                        this.modules[Math.floor(i / 3)][i % 3 + this.moduleCount - 8 - 3] = mod;
                        this.modules[i % 3 + this.moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
                    }
                },
                setupTypeInfo: function(test, maskPattern) {
                    var data = (this.errorCorrectLevel << 3) | maskPattern;
                    var bits = QRUtil.getBCHTypeInfo(data);
                    for (var i = 0; i < 15; i++) {
                        var mod = (!test && ((bits >> i) & 1) == 1);
                        if (i < 6) this.modules[i][8] = mod;
                        else if (i < 8) this.modules[i + 1][8] = mod;
                        else this.modules[this.moduleCount - 15 + i][8] = mod;
                        if (i < 8) this.modules[8][this.moduleCount - i - 1] = mod;
                        else if (i < 9) this.modules[8][15 - i - 1 + 1] = mod;
                        else this.modules[8][15 - i - 1] = mod;
                    }
                    this.modules[this.moduleCount - 8][8] = !test;
                },
                mapData: function(data, maskPattern) {
                    var inc = -1, row = this.moduleCount - 1, bitIndex = 7, byteIndex = 0;
                    for (var col = this.moduleCount - 1; col > 0; col -= 2) {
                        if (col == 6) col--;
                        while (true) {
                            for (var c = 0; c < 2; c++) {
                                if (this.modules[row][col - c] === null) {
                                    var dark = false;
                                    if (byteIndex < data.length) {
                                        dark = (((data[byteIndex] >>> bitIndex) & 1) == 1);
                                    }
                                    if (QRUtil.getMask(maskPattern, row, col - c)) dark = !dark;
                                    this.modules[row][col - c] = dark;
                                    bitIndex--;
                                    if (bitIndex == -1) {
                                        byteIndex++;
                                        bitIndex = 7;
                                    }
                                }
                            }
                            row += inc;
                            if (row < 0 || this.moduleCount <= row) {
                                row -= inc;
                                inc = -inc;
                                break;
                            }
                        }
                    }
                }
            };
            QRCode.createData = function(typeNumber, errorCorrectLevel, dataList) {
                var rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectLevel);
                var buffer = new QRBitBuffer();
                for (var i = 0; i < dataList.length; i++) {
                    var data = dataList[i];
                    buffer.put(data.mode, 4);
                    buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
                    data.write(buffer);
                }
                var totalDataCount = 0;
                for (var i = 0; i < rsBlocks.length; i++) totalDataCount += rsBlocks[i].dataCount;
                if (buffer.getLengthInBits() > totalDataCount * 8) throw new Error("Code length overflow");
                if (buffer.getLengthInBits() + 4 <= totalDataCount * 8) buffer.put(0, 4);
                while (buffer.getLengthInBits() % 8 != 0) buffer.putBit(false);
                while (true) {
                    if (buffer.getLengthInBits() >= totalDataCount * 8) break;
                    buffer.put(0xEC, 8);
                    if (buffer.getLengthInBits() >= totalDataCount * 8) break;
                    buffer.put(0x11, 8);
                }
                return QRCode.createBytes(buffer, rsBlocks);
            };
            QRCode.createBytes = function(buffer, rsBlocks) {
                var offset = 0, maxDcCount = 0, maxEcCount = 0;
                var dcdata = new Array(rsBlocks.length), ecdata = new Array(rsBlocks.length);
                for (var r = 0; r < rsBlocks.length; r++) {
                    var dcCount = rsBlocks[r].dataCount, ecCount = rsBlocks[r].totalCount - dcCount;
                    maxDcCount = Math.max(maxDcCount, dcCount);
                    maxEcCount = Math.max(maxEcCount, ecCount);
                    dcdata[r] = new Array(dcCount);
                    for (var i = 0; i < dcdata[r].length; i++) dcdata[r][i] = 0xff & buffer.buffer[i + offset];
                    offset += dcCount;
                    var rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
                    var rawPoly = new QRPolynomial(dcdata[r], rsPoly.getLength() - 1);
                    var modPoly = rawPoly.mod(rsPoly);
                    ecdata[r] = new Array(rsPoly.getLength() - 1);
                    for (var i = 0; i < ecdata[r].length; i++) {
                        var modIndex = i + modPoly.getLength() - ecdata[r].length;
                        ecdata[r][i] = (modIndex >= 0) ? modPoly.get(modIndex) : 0;
                    }
                }
                var totalCodeCount = 0;
                for (var i = 0; i < rsBlocks.length; i++) totalCodeCount += rsBlocks[i].totalCount;
                var data = new Array(totalCodeCount), index = 0;
                for (var i = 0; i < maxDcCount; i++) {
                    for (var r = 0; r < rsBlocks.length; r++) {
                        if (i < dcdata[r].length) data[index++] = dcdata[r][i];
                    }
                }
                for (var i = 0; i < maxEcCount; i++) {
                    for (var r = 0; r < rsBlocks.length; r++) {
                        if (i < ecdata[r].length) data[index++] = ecdata[r][i];
                    }
                }
                return data;
            };

            var QRMode = { MODE_NUMBER: 1, MODE_ALPHA_NUM: 2, MODE_8BIT_BYTE: 4, MODE_KANJI: 8 };
            var QRErrorCorrectLevel = { L: 1, M: 0, Q: 3, H: 2 };
            var QRMaskPattern = { PATTERN000: 0, PATTERN001: 1, PATTERN010: 2, PATTERN011: 3, PATTERN100: 4, PATTERN101: 5, PATTERN110: 6, PATTERN111: 7 };

            var QRUtil = {
                PATTERN_POSITION_TABLE: [
                    [], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34], [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50],
                    [6, 30, 54], [6, 32, 58], [6, 34, 62], [6, 26, 46, 66], [6, 26, 48, 70], [6, 26, 50, 74], [6, 30, 54, 78],
                    [6, 30, 56, 82], [6, 30, 58, 86], [6, 34, 62, 90], [6, 28, 50, 72, 94], [6, 26, 50, 74, 98], [6, 30, 54, 78, 102],
                    [6, 28, 54, 80, 106], [6, 32, 58, 84, 110], [6, 30, 58, 86, 114], [6, 34, 62, 90, 118], [6, 26, 50, 74, 98, 122],
                    [6, 30, 54, 78, 102, 126], [6, 26, 52, 78, 104, 130], [6, 30, 56, 82, 108, 134], [6, 34, 60, 86, 112, 138],
                    [6, 30, 58, 86, 114, 142], [6, 34, 62, 90, 118, 146], [6, 30, 54, 78, 102, 126, 150], [6, 24, 50, 76, 102, 128, 154],
                    [6, 28, 54, 80, 106, 132, 158], [6, 32, 58, 84, 110, 136, 162], [6, 26, 54, 82, 110, 138, 166], [6, 30, 58, 86, 114, 142, 170]
                ],
                G15: (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0),
                G18: (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0),
                G15_MASK: (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1),
                getBCHTypeInfo: function(data) {
                    var d = data << 10;
                    while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15) >= 0) {
                        d ^= (QRUtil.G15 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15)));
                    }
                    return ((data << 10) | d) ^ QRUtil.G15_MASK;
                },
                getBCHTypeNumber: function(data) {
                    var d = data << 12;
                    while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18) >= 0) {
                        d ^= (QRUtil.G18 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18)));
                    }
                    return (data << 12) | d;
                },
                getBCHDigit: function(data) {
                    var digit = 0;
                    while (data != 0) { digit++; data >>>= 1; }
                    return digit;
                },
                getPatternPosition: function(typeNumber) { return QRUtil.PATTERN_POSITION_TABLE[typeNumber - 1]; },
                getMask: function(maskPattern, i, j) {
                    switch (maskPattern) {
                        case QRMaskPattern.PATTERN000: return (i + j) % 2 == 0;
                        case QRMaskPattern.PATTERN001: return i % 2 == 0;
                        case QRMaskPattern.PATTERN010: return j % 3 == 0;
                        case QRMaskPattern.PATTERN011: return (i + j) % 3 == 0;
                        case QRMaskPattern.PATTERN100: return (Math.floor(i / 2) + Math.floor(j / 3)) % 2 == 0;
                        case QRMaskPattern.PATTERN101: return (i * j) % 2 + (i * j) % 3 == 0;
                        case QRMaskPattern.PATTERN110: return ((i * j) % 2 + (i * j) % 3) % 2 == 0;
                        case QRMaskPattern.PATTERN111: return ((i * j) % 3 + (i + j) % 2) % 2 == 0;
                        default: throw new Error("bad maskPattern:" + maskPattern);
                    }
                },
                getErrorCorrectPolynomial: function(errorCorrectLength) {
                    var a = new QRPolynomial([1], 0);
                    for (var i = 0; i < errorCorrectLength; i++) {
                        a = a.multiply(new QRPolynomial([1, QRMath.gexp(i)], 0));
                    }
                    return a;
                },
                getLengthInBits: function(mode, type) {
                    if (1 <= type && type < 10) {
                        switch (mode) {
                            case QRMode.MODE_NUMBER: return 10;
                            case QRMode.MODE_ALPHA_NUM: return 9;
                            case QRMode.MODE_8BIT_BYTE: return 8;
                            case QRMode.MODE_KANJI: return 8;
                            default: throw new Error("mode:" + mode);
                        }
                    } else if (type < 27) {
                        switch (mode) {
                            case QRMode.MODE_NUMBER: return 12;
                            case QRMode.MODE_ALPHA_NUM: return 11;
                            case QRMode.MODE_8BIT_BYTE: return 16;
                            case QRMode.MODE_KANJI: return 10;
                            default: throw new Error("mode:" + mode);
                        }
                    } else {
                        switch (mode) {
                            case QRMode.MODE_NUMBER: return 14;
                            case QRMode.MODE_ALPHA_NUM: return 13;
                            case QRMode.MODE_8BIT_BYTE: return 16;
                            case QRMode.MODE_KANJI: return 12;
                            default: throw new Error("mode:" + mode);
                        }
                    }
                },
                getLostPoint: function(qrCode) {
                    var moduleCount = qrCode.getModuleCount(), lostPoint = 0;
                    for (var row = 0; row < moduleCount; row++) {
                        for (var col = 0; col < moduleCount; col++) {
                            var sameCount = 0, dark = qrCode.isDark(row, col);
                            for (var r = -1; r <= 1; r++) {
                                if (row + r < 0 || moduleCount <= row + r) continue;
                                for (var c = -1; c <= 1; c++) {
                                    if (col + c < 0 || moduleCount <= col + c) continue;
                                    if (r == 0 && c == 0) continue;
                                    if (dark == qrCode.isDark(row + r, col + c)) sameCount++;
                                }
                            }
                            if (sameCount > 5) lostPoint += (3 + sameCount - 5);
                        }
                    }
                    return lostPoint;
                }
            };

            var QRMath = {
                glog: function(n) { if (n < 1) throw new Error("glog(" + n + ")"); return QRMath.LOG_TABLE[n]; },
                gexp: function(n) { while (n < 0) n += 255; while (n >= 256) n -= 255; return QRMath.EXP_TABLE[n]; },
                EXP_TABLE: new Array(256),
                LOG_TABLE: new Array(256)
            };
            for (var i = 0; i < 8; i++) QRMath.EXP_TABLE[i] = 1 << i;
            for (var i = 8; i < 256; i++) QRMath.EXP_TABLE[i] = QRMath.EXP_TABLE[i - 4] ^ QRMath.EXP_TABLE[i - 5] ^ QRMath.EXP_TABLE[i - 6] ^ QRMath.EXP_TABLE[i - 8];
            for (var i = 0; i < 255; i++) QRMath.LOG_TABLE[QRMath.EXP_TABLE[i]] = i;

            function QRPolynomial(num, shift) {
                if (num.length == undefined) throw new Error(num.length + "/" + shift);
                var offset = 0;
                while (offset < num.length && num[offset] == 0) offset++;
                this.num = new Array(num.length - offset + shift);
                for (var i = 0; i < num.length - offset; i++) this.num[i] = num[i + offset];
            }
            QRPolynomial.prototype = {
                get: function(index) { return this.num[index]; },
                getLength: function() { return this.num.length; },
                multiply: function(e) {
                    var num = new Array(this.getLength() + e.getLength() - 1);
                    for (var i = 0; i < this.getLength(); i++) {
                        for (var j = 0; j < e.getLength(); j++) {
                            num[i + j] ^= QRMath.gexp(QRMath.glog(this.get(i)) + QRMath.glog(e.get(j)));
                        }
                    }
                    return new QRPolynomial(num, 0);
                },
                mod: function(e) {
                    if (this.getLength() - e.getLength() < 0) return this;
                    var ratio = QRMath.glog(this.get(0)) - QRMath.glog(e.get(0));
                    var num = new Array(this.getLength());
                    for (var i = 0; i < this.getLength(); i++) num[i] = this.get(i);
                    for (var i = 0; i < e.getLength(); i++) num[i] ^= QRMath.gexp(QRMath.glog(e.get(i)) + ratio);
                    return new QRPolynomial(num, 0).mod(e);
                }
            };

            function QRRSBlock(totalCount, dataCount) {
                this.totalCount = totalCount;
                this.dataCount = dataCount;
            }
            QRRSBlock.RS_BLOCK_TABLE = [
                [1, 26, 19], [1, 26, 16], [1, 26, 13], [1, 26, 9],
                [1, 44, 34], [1, 44, 28], [1, 44, 22], [1, 44, 16],
                [1, 70, 55], [1, 70, 44], [2, 35, 17], [2, 35, 13],
                [1, 100, 80], [2, 50, 32], [2, 50, 24], [4, 25, 9],
                [1, 134, 108], [2, 67, 43], [2, 33, 15, 2, 34, 16], [2, 33, 11, 2, 34, 12],
                [2, 86, 68], [4, 43, 27], [4, 43, 19], [4, 43, 15],
                [2, 98, 78], [4, 49, 31], [2, 32, 14, 4, 33, 15], [4, 39, 13, 1, 40, 14],
                [2, 121, 97], [2, 60, 38, 2, 61, 39], [4, 40, 18, 2, 41, 19], [4, 40, 14, 2, 41, 15],
                [2, 146, 116], [3, 58, 36, 2, 59, 37], [4, 36, 16, 4, 37, 17], [4, 36, 12, 4, 37, 13],
                [2, 86, 68, 2, 87, 69], [4, 69, 43, 1, 70, 44], [6, 43, 19, 2, 44, 20], [6, 43, 15, 2, 44, 16]
            ];
            QRRSBlock.getRSBlocks = function(typeNumber, errorCorrectLevel) {
                var rsBlock = QRRSBlock.getRsBlockTable(typeNumber, errorCorrectLevel);
                if (rsBlock == undefined) throw new Error("bad rs block @ typeNumber:" + typeNumber + "/errorCorrectLevel:" + errorCorrectLevel);
                var length = rsBlock.length / 3, list = [];
                for (var i = 0; i < length; i++) {
                    var count = rsBlock[i * 3 + 0], totalCount = rsBlock[i * 3 + 1], dataCount = rsBlock[i * 3 + 2];
                    for (var j = 0; j < count; j++) list.push(new QRRSBlock(totalCount, dataCount));
                }
                return list;
            };
            QRRSBlock.getRsBlockTable = function(typeNumber, errorCorrectLevel) {
                switch (errorCorrectLevel) {
                    case QRErrorCorrectLevel.L: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 0];
                    case QRErrorCorrectLevel.M: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 1];
                    case QRErrorCorrectLevel.Q: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 2];
                    case QRErrorCorrectLevel.H: return QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + 3];
                    default: return undefined;
                }
            };

            function QRBitBuffer() {
                this.buffer = [];
                this.length = 0;
            }
            QRBitBuffer.prototype = {
                get: function(index) { return ((this.buffer[Math.floor(index / 8)] >>> (7 - index % 8)) & 1) == 1; },
                put: function(num, length) { for (var i = 0; i < length; i++) this.putBit(((num >>> (length - i - 1)) & 1) == 1); },
                getLengthInBits: function() { return this.length; },
                putBit: function(bit) {
                    var bufIndex = Math.floor(this.length / 8);
                    if (this.buffer.length <= bufIndex) this.buffer.push(0);
                    if (bit) this.buffer[bufIndex] |= (0x80 >>> (this.length % 8));
                    this.length++;
                }
            };

            global.QRCodeGen = {
                drawCanvasElement: function(canvas, text) {
                    if (!canvas || !text) return;
                    try {
                        var qr = new QRCode(0, QRErrorCorrectLevel.M);
                        qr.addData(text);
                        qr.make();

                        var moduleCount = qr.getModuleCount();
                        var margin = 2;
                        var targetSize = 96;
                        var totalModules = moduleCount + margin * 2;
                        var scale = Math.max(1, Math.floor(targetSize / totalModules));
                        var finalSize = totalModules * scale;

                        canvas.width = finalSize;
                        canvas.height = finalSize;
                        var ctx = canvas.getContext('2d');
                        ctx.imageSmoothingEnabled = false;
                        ctx.fillStyle = '#FFFFFF';
                        ctx.fillRect(0, 0, finalSize, finalSize);

                        ctx.fillStyle = '#000000';
                        for (var r = 0; r < moduleCount; r++) {
                            for (var c = 0; c < moduleCount; c++) {
                                if (qr.isDark(r, c)) {
                                    ctx.fillRect((c + margin) * scale, (r + margin) * scale, scale, scale);
                                }
                            }
                        }
                    } catch (e) {
                        console.error('QR Canvas paint failed:', e);
                    }
                },
                drawToCanvas: function(canvasId, text) {
                    var canvas = document.getElementById(canvasId);
                    if (canvas) {
                        this.drawCanvasElement(canvas, text);
                    }
                }
            };
        })(window);

        // ──── Interactivity & Print Controller ────
        function updateSlipVisibility() {
            var chkK = document.getElementById('chkKitchen');
            var chkB = document.getElementById('chkBarista');
            var chkC = document.getElementById('chkCustomer');

            var k = document.getElementById('kitchenReceipt');
            var b = document.getElementById('baristaReceipt');
            var c = document.getElementById('customerReceipt');
            var customerCopies = document.querySelectorAll('.customerReceipt');

            if (k && chkK) k.style.display = chkK.checked ? 'block' : 'none';
            if (b && chkB) b.style.display = chkB.checked ? 'block' : 'none';
            if (c && chkC) c.style.display = chkC.checked ? 'block' : 'none';
            if (chkC) {
                customerCopies.forEach(function(c) {
                    c.style.display = chkC.checked ? 'block' : 'none';
                });
            }
        }

        function setPaperWidth(width) {
            document.querySelectorAll('.size-btn').forEach(function(btn) { btn.classList.remove('active'); });
            var papers = document.querySelectorAll('.receipt-paper');
            papers.forEach(function(paper) {
                paper.classList.remove('paper-58mm', 'paper-80mm', 'paper-auto');
                if (width === '80mm') {
                    paper.classList.add('paper-80mm');
                    document.getElementById('btnWidth80')?.classList.add('active');
                } else if (width === 'auto') {
                    paper.classList.add('paper-auto');
                    document.getElementById('btnWidthAuto')?.classList.add('active');
                } else {
                    paper.classList.add('paper-58mm');
                    document.getElementById('btnWidth58')?.classList.add('active');
                }
            });
            localStorage.setItem('pos_receipt_width_pref', width);
        }

        function printReceipts() {
            updateSlipVisibility();
            setTimeout(function() {
                window.print();
            }, 100);
        }

        // ──── Initialization ────
        window.addEventListener('DOMContentLoaded', function() {
            // Restore saved paper width preference
            var savedWidth = localStorage.getItem('pos_receipt_width_pref') || '58mm';
            setPaperWidth(savedWidth);

            // Paint QR code canvas (supports single and multiple printed receipt copies)
            var qrText = @json($reviewUrl ?? ($qrUrl ?? ''));
            if (qrText && window.QRCodeGen) {
                document.querySelectorAll('.qr-code-canvas').forEach(function(canvas) {
                    window.QRCodeGen.drawCanvasElement(canvas, qrText);
                });
            }

            // Auto-print support if requested via URL
            var urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('autoprint') === '1') {
                setTimeout(function() {
                    printReceipts();
                }, 350);
            }
        });
    </script>
</body>
</html>