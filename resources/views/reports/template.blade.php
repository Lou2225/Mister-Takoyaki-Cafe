<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #1e293b;
            background: #ffffff;
        }

        /* ── Page Layout ── */
        .page { padding: 0; }

        /* ── Header ── */
        .header-bar {
            background-color: #0f172a;
            padding: 22px 32px 18px;
            border-bottom: 4px solid #f97316;
        }
        .header-brand {
            font-size: 10px;
            color: #f97316;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 6px;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
            line-height: 1.2;
        }
        .header-subtitle {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* ── Meta Bar ── */
        .meta-bar {
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 32px;
        }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td {
            font-size: 8.5px;
            color: #64748b;
            padding: 1px 0;
        }
        .meta-table .meta-label {
            font-weight: bold;
            color: #334155;
            width: 90px;
        }

        /* ── Section wrapper ── */
        .content { padding: 20px 32px; }

        /* ── KPI Grid ── */
        .kpi-section { margin-bottom: 24px; }
        .section-title {
            font-size: 8px;
            font-weight: bold;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }

        .kpi-grid { width: 100%; border-collapse: separate; border-spacing: 6px; }
        .kpi-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #f97316;
            border-radius: 4px;
            padding: 10px 12px;
            width: 24%;
            vertical-align: top;
        }
        .kpi-label {
            font-size: 7px;
            font-weight: bold;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 5px;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }

        /* ── Data Sections ── */
        .data-section { margin-bottom: 24px; }
        .data-section-title {
            font-size: 9px;
            font-weight: bold;
            color: #0f172a;
            background-color: #f1f5f9;
            border-left: 3px solid #f97316;
            padding: 7px 10px;
            margin-bottom: 0;
        }

        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead th {
            background-color: #0f172a;
            color: #f8fafc;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 10px;
            text-align: left;
            border: none;
        }
        .data-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }
        .data-table tbody td {
            padding: 6px 10px;
            font-size: 8.5px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }
        .data-table tfoot td {
            padding: 6px 10px;
            font-size: 8px;
            color: #64748b;
            font-style: italic;
            background-color: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        /* Status badges */
        .badge-low    { color: #dc2626; font-weight: bold; }
        .badge-good   { color: #16a34a; font-weight: bold; }
        .badge-warn   { color: #d97706; font-weight: bold; }
        .badge-active { color: #2563eb; font-weight: bold; }

        /* Highlight row for critical alerts */
        .row-critical td { background-color: #fff1f2 !important; color: #991b1b; }

        /* ── Footer ── */
        .footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #0f172a;
            padding: 8px 32px;
        }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td {
            font-size: 7.5px;
            color: #94a3b8;
            vertical-align: middle;
        }
        .footer-table .footer-right { text-align: right; }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 16px 0;
        }

        /* ── Empty state ── */
        .empty-state {
            padding: 10px;
            font-size: 8.5px;
            color: #94a3b8;
            font-style: italic;
            background-color: #f8fafc;
            border: 1px dashed #e2e8f0;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- ── Footer (fixed) ── --}}
    <div class="footer-bar">
        <table class="footer-table">
            <tr>
                <td>Mister Takoyaki Cafe &mdash; Confidential Internal Report</td>
                <td class="footer-right">Generated: {{ $generatedAt ?? now()->format('F d, Y h:i A') }}</td>
            </tr>
        </table>
    </div>

    {{-- ── Header ── --}}
    <div class="header-bar">
        <div class="header-brand">Mister Takoyaki Cafe &mdash; Operations Intelligence</div>
        <div class="header-title">{{ $title }}</div>
        @if(!empty($subtitle))
            <div class="header-subtitle">{{ $subtitle }}</div>
        @endif
    </div>

    {{-- ── Meta Bar ── --}}
    <div class="meta-bar">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Report Period</td>
                <td>{{ $period }}</td>
                <td style="width: 40px;"></td>
                <td class="meta-label">Branch / Scope</td>
                <td>{{ $branch }}</td>
                <td style="width: 40px;"></td>
                <td class="meta-label">Generated On</td>
                <td>{{ $generatedAt ?? now()->format('F d, Y h:i A') }}</td>
            </tr>
        </table>
    </div>

    <div class="content">

        {{-- ── KPI Summary ── --}}
        @if(!empty($kpis))
        <div class="kpi-section">
            <div class="section-title">Key Performance Indicators</div>
            @php
                $kpiChunks = array_chunk(array_map(
                    fn($k, $v) => ['label' => $k, 'value' => $v],
                    array_keys($kpis), array_values($kpis)
                ), 4, true);
            @endphp

            @foreach($kpiChunks as $row)
            <table class="kpi-grid">
                <tr>
                    @foreach($row as $item)
                    <td class="kpi-card">
                        <div class="kpi-label">{{ $item['label'] }}</div>
                        <div class="kpi-value">{{ $item['value'] }}</div>
                    </td>
                    @endforeach
                    {{-- Fill empty cells if row < 4 --}}
                    @for($i = count($row); $i < 4; $i++)
                    <td style="width: 24%;"></td>
                    @endfor
                </tr>
            </table>
            @endforeach
        </div>
        @endif

        {{-- ── Data Sections ── --}}
        @if(!empty($sections))
            @foreach($sections as $section)
            <div class="data-section">
                <div class="data-section-title">{{ $section['title'] }}</div>

                @if(!empty($section['rows']))
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach($section['headers'] as $header)
                                <th>{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section['rows'] as $i => $row)
                        @php
                            $statusCols = array_map('strtolower', (array)$row);
                            $isCritical = in_array('low stock', $statusCols) || in_array('expired', $statusCols);
                        @endphp
                        <tr @if($isCritical && !empty($section['highlight'])) class="row-critical" @endif>
                            @foreach($row as $j => $cell)
                            <td>
                                @if(in_array(strtolower((string)$cell), ['low stock', 'expired']))
                                    <span class="badge-low">{{ $cell }}</span>
                                @elseif(strtolower((string)$cell) === 'good')
                                    <span class="badge-good">{{ $cell }}</span>
                                @elseif(strtolower((string)$cell) === 'active')
                                    <span class="badge-active">{{ $cell }}</span>
                                @else
                                    {{ $cell }}
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                    <div class="empty-state">{{ $section['empty'] ?? 'No data available.' }}</div>
                @endif
            </div>
            @endforeach

        {{-- ── Legacy tableData support (fallback) ── --}}
        @elseif(!empty($tableData))
        <div class="data-section">
            <div class="data-section-title">Breakdown Details</div>
            <table class="data-table">
                <thead>
                    <tr>
                        @foreach(array_keys((array)$tableData[0]) as $header)
                            <th>{{ str_replace('_', ' ', $header) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($tableData as $row)
                    <tr>
                        @foreach((array)$row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>{{-- end .content --}}

</body>
</html>
