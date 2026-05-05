<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #1e293b; background-color: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 24px; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
        .header { background: #4f46e5; padding: 40px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.025em; text-transform: uppercase; }
        .content { padding: 40px; }
        .message-box { background: #f1f5f9; border-radius: 16px; padding: 24px; border-left: 4px solid #4f46e5; margin-bottom: 30px; }
        .message-box p { margin: 0; font-size: 15px; font-weight: 600; color: #334155; }
        .order-details { margin-top: 30px; border-top: 1px solid #e2e8f0; padding-top: 30px; }
        .order-details h2 { font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 20px; }
        .item-list { width: 100%; border-collapse: collapse; }
        .item-list th { text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; padding-bottom: 12px; border-bottom: 2px solid #f1f5f9; }
        .item-list td { padding: 16px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .item-name { font-weight: 700; color: #0f172a; }
        .item-qty { font-weight: 800; color: #4f46e5; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
        .footer { background: #f8fafc; padding: 30px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { margin: 0; font-size: 12px; font-weight: 500; color: #94a3b8; }
        .btn { display: inline-block; background: #4f46e5; color: #ffffff; padding: 14px 28px; border-radius: 12px; text-decoration: none; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em; margin-top: 20px; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); transition: all 0.2s; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Mister Takoyaki</h1>
        </div>
        <div class="content">
            <div class="message-box">
                <p>{{ $mailMessage }}</p>
            </div>

            <div class="order-details">
                <h2>Order Breakdown</h2>
                <table class="item-list">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th style="text-align: right;">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td class="item-name">{{ $item->ingredient->name }}</td>
                                <td class="item-qty" style="text-align: right;">
                                    {{ number_format($item->approved_quantity ?? $item->requested_quantity, 2) }} {{ $item->ingredient->unit }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div style="text-align: center;">
                <a href="{{ route('login') }}" class="btn">View in Dashboard</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Mister Takoyaki Cafe. All rights reserved.</p>
            <p>This is an automated system notification.</p>
        </div>
    </div>
</body>
</html>
