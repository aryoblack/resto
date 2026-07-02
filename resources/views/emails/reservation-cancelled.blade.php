<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Dibatalkan</title>
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
        .reason-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 16px 0;
            font-size: 14px;
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
            <h1>❌ Reservasi Dibatalkan</h1>
        </div>
        <div class="body">
            <p>Halo, <strong>{{ $reservation->user?->name ?? 'Pelanggan' }}</strong>!</p>
            <p>
                Kami mohon maaf, reservasi Anda telah dibatalkan. Berikut adalah detail reservasi yang dibatalkan:
            </p>

            <div class="detail-card">
                <table>
                    <tr>
                        <td>ID Reservasi</td>
                        <td>#{{ $reservation->id }}</td>
                    </tr>
                    <tr>
                        <td>Nomor Meja</td>
                        <td>{{ $reservation->table?->table_number ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal</td>
                        <td>{{ $reservation->date?->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Waktu</td>
                        <td>{{ substr($reservation->time, 0, 5) }} WIB</td>
                    </tr>
                    <tr>
                        <td>Jumlah Tamu</td>
                        <td>{{ $reservation->number_of_people }} orang</td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td><span class="badge">Dibatalkan</span></td>
                    </tr>
                </table>
            </div>

            @if($reason)
            <div class="reason-box">
                <strong>Alasan Pembatalan:</strong><br>
                {{ $reason }}
            </div>
            @endif

            <p>
                Jika Anda ingin membuat reservasi baru atau memiliki pertanyaan,
                silakan hubungi kami atau kunjungi aplikasi kami.
            </p>
            <p>Kami berharap dapat melayani Anda di lain waktu. 🙏</p>
        </div>
        <div class="footer">
            <p>Email ini dikirim secara otomatis. Mohon jangan membalas email ini.</p>
        </div>
    </div>
</body>
</html>
