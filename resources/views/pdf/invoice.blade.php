<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice Pembayaran</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; line-height: 1.6; }
        .invoice-container { width: 100%; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #10b981; padding-bottom: 20px; }
        .header h1 { margin: 0; color: #10b981; font-size: 28px; }
        .header p { margin: 5px 0 0 0; color: #6b7280; font-size: 14px; }
        .details-container { width: 100%; margin-bottom: 30px; }
        .details-table { width: 100%; border-collapse: collapse; }
        .details-table td { padding: 5px 0; vertical-align: top; }
        .details-table td.label { font-weight: bold; width: 150px; color: #4b5563; }
        .status-badge { display: inline-block; padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .status-paid { background-color: #d1fae5; color: #047857; border: 1px solid #34d399; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .invoice-table th, .invoice-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .invoice-table th { background-color: #f3f4f6; color: #374151; font-weight: bold; font-size: 13px; text-transform: uppercase; }
        .invoice-table td.right { text-align: right; }
        .invoice-table th.right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f9fafb; }
        .total-row td { border-top: 2px solid #d1d5db; border-bottom: none; font-size: 16px; color: #111827; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 20px; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <h1>KUITANSI PEMBAYARAN</h1>
            <p>Invoice #{{ $invoice->id }}</p>
            <p>Tanggal: {{ $invoice->paid_at ? $invoice->paid_at->format('d M Y H:i') : now()->format('d M Y H:i') }}</p>
        </div>

        <div class="details-container">
            <table class="details-table">
                <tr>
                    <td class="label">Nama Siswa:</td>
                    <td>{{ $invoice->siswa->nama_siswa ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">NIS:</td>
                    <td>{{ $invoice->siswa->nis ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Status:</td>
                    <td><span class="status-badge status-paid">LUNAS</span></td>
                </tr>
                <tr>
                    <td class="label">Metode Pembayaran:</td>
                    <td>{{ strtoupper($invoice->payment_method ?? 'Transfer / Online') }}</td>
                </tr>
            </table>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoice->description }}</td>
                    <td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->admin_fee > 0)
                <tr>
                    <td>Biaya Admin</td>
                    <td class="right">Rp {{ number_format($invoice->admin_fee, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="right">TOTAL</td>
                    <td class="right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            <p>Terima kasih atas pembayaran Anda.</p>
            <p>Dokumen ini merupakan tanda terima pembayaran yang sah dan diterbitkan secara otomatis oleh sistem.</p>
        </div>
    </div>
</body>
</html>
