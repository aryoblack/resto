<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Penjualan</title>
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
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            color: #2c3e50;
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
            color: #2c3e50;
            background-color: #ecf0f1;
            padding: 6px 10px;
            margin-bottom: 8px;
            border-left: 4px solid #2c3e50;
        }
        .summary-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            padding: 6px 10px;
            border: 1px solid #dddddd;
            width: 33.33%;
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
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        table.data-table thead tr {
            background-color: #2c3e50;
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
        table.data-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table.data-table tbody tr:hover {
            background-color: #f0f4f8;
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
        .footer {
            margin-top: 30px;
            border-top: 1px solid #dddddd;
            padding-top: 8px;
            text-align: right;
            font-size: 9px;
            color: #aaaaaa;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .badge-rank {
            background-color: #f39c12;
            color: #ffffff;
        }
    </style>
</head>
<body>

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="header">
        <h1>Laporan Penjualan</h1>
        <p>
            Periode:
            <strong>
                @if($report['period'] === 'daily') Harian
                @elseif($report['period'] === 'weekly') Mingguan
                @else Bulanan
                @endif
            </strong>
            &nbsp;|&nbsp;
            {{ \Carbon\Carbon::parse($report['date_from'])->format('d M Y') }}
            &ndash;
            {{ \Carbon\Carbon::parse($report['date_to'])->format('d M Y') }}
        </p>
        <p style="margin-top:4px; font-size:10px; color:#999999;">
            Dicetak pada: {{ now()->format('d M Y, H:i') }} WIB
        </p>
    </div>

    {{-- ── Summary ─────────────────────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Ringkasan</div>
        <table class="summary-grid">
            <tr>
                <td>
                    <span class="label">Total Pendapatan</span>
                    <span class="value">Rp {{ number_format($report['summary']['total_revenue'], 0, ',', '.') }}</span>
                </td>
                <td>
                    <span class="label">Jumlah Pesanan</span>
                    <span class="value">{{ number_format($report['summary']['order_count'], 0, ',', '.') }}</span>
                </td>
                <td>
                    <span class="label">Rata-rata Nilai Pesanan</span>
                    <span class="value">Rp {{ number_format($report['summary']['avg_order_value'], 0, ',', '.') }}</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── Top 5 Menu ──────────────────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">5 Menu Terlaris</div>
        @if(count($report['top_menus']) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Nama Menu</th>
                        <th class="text-right">Qty Terjual</th>
                        <th class="text-right">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['top_menus'] as $index => $menu)
                        <tr>
                            <td class="text-center">
                                <span class="badge badge-rank">{{ $index + 1 }}</span>
                            </td>
                            <td>{{ $menu['menu_name'] }}</td>
                            <td class="text-right">{{ number_format($menu['total_quantity'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($menu['total_revenue'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:#999999; font-style:italic; padding:8px 0;">Tidak ada data menu pada periode ini.</p>
        @endif
    </div>

    {{-- ── Payment Method Breakdown ─────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Rincian per Metode Pembayaran</div>
        @if(count($report['payment_method_breakdown']) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Metode Pembayaran</th>
                        <th class="text-right">Jumlah Transaksi</th>
                        <th class="text-right">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['payment_method_breakdown'] as $method => $breakdown)
                        <tr>
                            <td>{{ strtoupper($method) }}</td>
                            <td class="text-right">{{ number_format($breakdown['count'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($breakdown['revenue'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:#999999; font-style:italic; padding:8px 0;">Tidak ada data pembayaran pada periode ini.</p>
        @endif
    </div>

    {{-- ── Period Detail ────────────────────────────────────────────────────── --}}
    <div class="section">
        <div class="section-title">Detail per Periode</div>
        @if(count($report['data']) > 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th class="text-right">Total Pendapatan</th>
                        <th class="text-right">Jumlah Pesanan</th>
                        <th class="text-right">Rata-rata Nilai Pesanan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($report['data'] as $row)
                        <tr>
                            <td>{{ $row['period_label'] }}</td>
                            <td class="text-right">Rp {{ number_format($row['total_revenue'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['order_count'], 0, ',', '.') }}</td>
                            <td class="text-right">Rp {{ number_format($row['average_order_value'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="color:#999999; font-style:italic; padding:8px 0;">Tidak ada data pada periode ini.</p>
        @endif
    </div>

    {{-- ── Footer ──────────────────────────────────────────────────────────── --}}
    <div class="footer">
        Laporan ini dibuat secara otomatis oleh sistem. &copy; {{ now()->year }}
    </div>

</body>
</html>
