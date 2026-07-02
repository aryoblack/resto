<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Stok Inventaris</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            background: #ffffff;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #27ae60;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #27ae60;
            margin-bottom: 4px;
        }
        .header p {
            font-size: 11px;
            color: #666666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #27ae60;
            background-color: #eafaf1;
            padding: 6px 10px;
            margin-bottom: 8px;
            border-left: 4px solid #27ae60;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            padding: 6px 10px;
            border: 1px solid #dddddd;
            width: 50%;
        }
        .summary-grid .label {
            font-size: 10px;
            color: #888888;
            display: block;
        }
        .summary-grid .value {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
        }
        .summary-grid .value-critical {
            font-size: 14px;
            font-weight: bold;
            color: #e74c3c;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.data-table thead tr {
            background-color: #27ae60;
            color: #ffffff;
        }
        table.data-table thead th {
            padding: 7px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
        }
        table.data-table thead th.text-right {
            text-align: right;
        }
        table.data-table thead th.text-center {
            text-align: center;
        }
        table.data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table.data-table tbody tr.critical-row {
            background-color: #fdecea;
        }
        table.data-table tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #eeeeee;
            font-size: 10px;
        }
        table.data-table tbody td.text-right {
            text-align: right;
        }
        table.data-table tbody td.text-center {
            text-align: center;
        }
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-normal {
            background-color: #27ae60;
            color: #ffffff;
        }
        .badge-critical {
            background-color: #e74c3c;
            color: #ffffff;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #dddddd;
            padding-top: 8px;
            text-align: right;
            font-size: 9px;
            color: #aaaaaa;
        }
    </style>
</head>
<body>

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="header">
        <h1>Laporan Stok Inventaris</h1>
        <p>
            Tanggal Ekspor: <strong>{{ now()->format('d M Y, H:i') }} WIB</strong>
        </p>
    </div>

    {{-- ── Summary ─────────────────────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Ringkasan</div>
        <table class="summary-grid">
            <tr>
                <td>
                    <span class="label">Total Bahan Baku</span>
                    <span class="value">{{ number_format($report['total_items'], 0, ',', '.') }} item</span>
                </td>
                <td>
                    <span class="label">Bahan Baku Stok Kritis</span>
                    <span class="{{ $report['critical_count'] > 0 ? 'value-critical' : 'value' }}">
                        {{ number_format($report['critical_count'], 0, ',', '.') }} item
                    </span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Inventory Table ─────────────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Daftar Inventaris</div>
        @if(count($report['items']) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:30px;">#</th>
                        <th>Nama Bahan</th>
                        <th class="text-center">Satuan</th>
                        <th class="text-right">Stok Saat Ini</th>
                        <th class="text-right">Stok Minimal</th>
                        <th>Supplier</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['items'] as $index => $item)
                        <tr class="{{ $item['is_critical'] ? 'critical-row' : '' }}">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>{{ $item['ingredient_name'] }}</td>
                            <td class="text-center">{{ $item['unit'] }}</td>
                            <td class="text-right">{{ number_format($item['current_stock'], 2, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($item['min_stock'], 2, ',', '.') }}</td>
                            <td>{{ $item['supplier'] ?? '-' }}</td>
                            <td class="text-center">
                                @if($item['is_critical'])
                                    <span class="badge badge-critical">KRITIS</span>
                                @else
                                    <span class="badge badge-normal">Normal</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:#999999; font-style:italic; padding:8px 0;">Tidak ada data inventaris.</p>
        @endif
    </div>

    {{-- ── Footer ──────────────────────────────────────────────────────────── --}}
    <div class="footer">
        Laporan ini dibuat secara otomatis oleh sistem. &copy; {{ now()->year }}
    </div>

</body>
</html>
