<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise Stock Alert</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; color: #111827; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background-color: #1f2937; color: #ffffff; text-align: center; padding: 20px; }
        .content { padding: 30px; }
        .section-title { font-size: 18px; font-weight: bold; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 20px; }
        .alert-row { padding: 15px; margin-bottom: 15px; border-radius: 6px; }
        .alert-danger { background-color: #fef2f2; border-left: 4px solid #ef4444; }
        .alert-warning { background-color: #fffbeb; border-left: 4px solid #f59e0b; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; margin-bottom: 5px; }
        .badge-danger { background-color: #fee2e2; color: #b91c1c; }
        .badge-warning { background-color: #fef3c7; color: #b45309; }
        table { w-full; max-width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 14px; }
        td, th { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .footer { text-align: center; padding: 20px; font-size: 12px; color: #6b7280; background-color: #f9fafb; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ \App\Services\ConfigurationService::getBusinessName() }}</h2>
            <p>Daily Stock Management Alert</p>
        </div>
        
        <div class="content">
            <p>Hello Admin,</p>
            <p>Here is the automated daily stock report detailing critical expirations and low inventory thresholds across your enterprise nodes.</p>

            @if(count($expiredBatches) > 0)
                <h3 class="section-title" style="color: #ef4444;">🚨 Expired Stock Found</h3>
                <p>The following batches have exceeded their expiration dates and must be disposed of immediately to ensure food safety compliance.</p>
                @foreach($expiredBatches as $branchName => $items)
                    <div class="alert-row alert-danger">
                        <strong>{{ $branchName }}</strong>
                        <table style="width: 100%;">
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $item['ingredient'] }}</td>
                                    <td>{{ $item['quantity'] }} remaining</td>
                                    <td style="color: #ef4444;">Expired: {{ Carbon\Carbon::parse($item['expiry_date'])->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach
            @endif

            @if(count($expiringBatches) > 0)
                <h3 class="section-title" style="color: #f59e0b;">⚠️ Batches Expiring within 7 Days</h3>
                <p>The following batches are nearing expiration. Prioritize FEFO consumption.</p>
                @foreach($expiringBatches as $branchName => $items)
                    <div class="alert-row alert-warning">
                        <strong>{{ $branchName }}</strong>
                        <table style="width: 100%;">
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $item['ingredient'] }}</td>
                                    <td>{{ $item['quantity'] }} remaining</td>
                                    <td style="color: #f59e0b;">Expiring: {{ Carbon\Carbon::parse($item['expiry_date'])->format('M d, Y') }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach
            @endif

            @if(count($lowStocks) > 0)
                <h3 class="section-title" style="color: #3b82f6;">📉 Low Stock Thresholds Breached</h3>
                <p>The following ingredients have dropped below their minimum par levels.</p>
                @foreach($lowStocks as $branchName => $items)
                    <div class="alert-row" style="background-color: #eff6ff; border-left: 4px solid #3b82f6;">
                        <strong>{{ $branchName }}</strong>
                        <table style="width: 100%;">
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $item['ingredient'] }}</td>
                                    <td style="color: #ef4444; font-weight: bold;">{{ $item['current'] }}</td>
                                    <td style="color: #6b7280;">Min required: {{ $item['minimum'] }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                @endforeach
            @endif

            @if(count($expiredBatches) === 0 && count($expiringBatches) === 0 && count($lowStocks) === 0)
                <div class="alert-row" style="background-color: #ecfdf5; border-left: 4px solid #10b981;">
                    <strong>All Clear!</strong>
                    <p>No critical stock issues detected. Inventory levels are optimal and no batches are expiring soon.</p>
                </div>
            @endif

            <p style="margin-top: 30px; font-size: 14px;">Log in to your dashboard for complete analytics and operational control.</p>
        </div>

        <div class="footer">
            Report Generated Automatically · {{ $dateReported }}<br>
            {{ \App\Services\ConfigurationService::getBusinessName() }} · Master POS System
        </div>
    </div>
</body>
</html>

