<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Siap Diunduh</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .header {
            background-color: #FF7A2F;
            padding: 32px 40px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .body {
            padding: 32px 40px;
        }
        .body p {
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 16px;
        }
        .detail-card {
            background-color: #fff5f0;
            border-left: 4px solid #FF7A2F;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 24px 0;
        }
        .detail-card table {
            width: 100%;
            border-collapse: collapse;
        }
        .detail-card td {
            padding: 6px 0;
            font-size: 14px;
        }
        .detail-card td:first-child {
            color: #666666;
            width: 40%;
        }
        .detail-card td:last-child {
            font-weight: 600;
            color: #222222;
        }
        .badge {
            display: inline-block;
            background-color: #FF7A2F;
            color: #ffffff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px 40px;
            text-align: center;
            font-size: 12px;
            color: #999999;
            border-top: 1px solid #eeeeee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Laporan Siap Diunduh</h1>
        </div>
        <div class="body">
            <p>Halo,</p>
            <p>
                Laporan yang Anda minta telah selesai diproses dan terlampir pada email ini.
            </p>

            <div class="detail-card">
                <table>
                    <tr>
                        <td>Jenis Laporan</td>
                        <td>
                            @if($type === 'sales')
                                <span class="badge">Penjualan</span>
                            @else
                                <span class="badge">Stok Opname</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td>Format File</td>
                        <td>{{ strtoupper($format) }}</td>
                    </tr>
                    <tr>
                        <td>Nama File</td>
                        <td>{{ $filename }}</td>
                    </tr>
                    <tr>
                        <td>Waktu Selesai</td>
                        <td>{{ now()->format('d F Y, H:i') }} WIB</td>
                    </tr>
                </table>
            </div>

            <p>
                File laporan terlampir pada email ini. Silakan buka lampiran untuk mengunduh laporan Anda.
            </p>
            <p>Terima kasih telah menggunakan sistem laporan kami. 🍽️</p>
        </div>
        <div class="footer">
            <p>Email ini dikirim secara otomatis. Mohon jangan membalas email ini.</p>
        </div>
    </div>
</body>
</html>
