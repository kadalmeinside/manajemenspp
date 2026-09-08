<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
            color: #374151;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            background-color: #10b981;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
        }
        .content p {
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 15px;
        }
        .summary-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-weight: 700;
            font-size: 16px;
        }
        .label {
            color: #6b7280;
        }
        .value {
            color: #111827;
            font-weight: 500;
            text-align: right;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
        }
        .button {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 500;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Pembayaran Berhasil Diterima</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $invoice->siswa->user->name ?? 'Bapak/Ibu' }}</strong>,</p>
            <p>Terima kasih. Kami telah menerima pembayaran Anda untuk <strong>{{ $invoice->siswa->nama_siswa ?? 'Siswa' }}</strong>. Rincian tagihan yang telah dilunasi adalah sebagai berikut:</p>

            <div class="summary-box">
                <div class="summary-row">
                    <span class="label">No. Tagihan / Invoice:</span>
                    <span class="value">#{{ $invoice->id }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">Deskripsi:</span>
                    <span class="value">{{ $invoice->description }}</span>
                </div>
                <div class="summary-row">
                    <span class="label">Tanggal Bayar:</span>
                    <span class="value">{{ $invoice->paid_at ? $invoice->paid_at->format('d M Y, H:i') : now()->format('d M Y, H:i') }} WIB</span>
                </div>
                <div class="summary-row">
                    <span class="label">Total Dibayar:</span>
                    <span class="value">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <p>Kami juga telah melampirkan file PDF Kuitansi Pembayaran di dalam email ini untuk keperluan administrasi Anda.</p>
            
            <p style="text-align: center;">
                <a href="{{ config('app.url') }}" class="button">Masuk ke Dashboard</a>
            </p>
        </div>
        <div class="footer">
            <p>Pesan ini dihasilkan secara otomatis oleh sistem. Harap tidak membalas email ini.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
