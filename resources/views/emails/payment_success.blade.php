<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f0f4f8; font-family: Arial, sans-serif; padding: 24px 16px; color: #2d3748; }
        .email-wrapper { max-width: 620px; margin: 0 auto; }

        .logo-bar { background: #ffffff; border-radius: 12px 12px 0 0; padding: 24px 36px; text-align: center; border-bottom: 3px solid #e53e3e; }
        .logo-bar img { height: 56px; width: auto; }
        .logo-bar .app-name { font-size: 20px; font-weight: 800; color: #e53e3e; letter-spacing: -0.5px; }
        .logo-bar .app-tagline { font-size: 11px; color: #718096; margin-top: 3px; }

        .success-banner { background: linear-gradient(135deg, #1e3a8a, #1a56db); padding: 28px 36px; text-align: center; }
        .success-banner .banner-icon { margin-bottom: 10px; }
        .success-banner h1 { color: #fff; font-size: 20px; font-weight: 700; }
        .success-banner p { color: #bfdbfe; font-size: 13px; margin-top: 5px; }

        .content { background: #ffffff; padding: 32px 36px; }
        .greeting { font-size: 14px; color: #4a5568; margin-bottom: 20px; line-height: 1.8; }

        .info-card { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-bottom: 16px; }
        .info-card-title { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
        .info-row-table { width: 100%; border-collapse: collapse; }
        .info-row-table tr { border-bottom: 1px solid #edf2f7; }
        .info-row-table tr:last-child { border-bottom: none; }
        .info-row-table td { padding: 9px 0; font-size: 13px; }
        .info-row-table td.lbl { color: #718096; width: 45%; }
        .info-row-table td.val { color: #2d3748; font-weight: 600; text-align: right; }

        .total-card { background: #eff6ff; border: 2px solid #3182ce; border-radius: 10px; padding: 16px 24px; margin-bottom: 16px; }
        .total-inner { display: flex; justify-content: space-between; align-items: center; }
        .total-label { font-size: 13px; font-weight: 700; color: #1e3a8a; }
        .total-amount { font-size: 20px; font-weight: 800; color: #1e3a8a; }

        /* Email-safe total using table */
        .total-table { width: 100%; border-collapse: collapse; }
        .total-table td { padding: 0; }
        .total-table td.t-label { font-size: 13px; font-weight: 700; color: #1e3a8a; }
        .total-table td.t-amount { font-size: 20px; font-weight: 800; color: #1e3a8a; text-align: right; }

        .attachment-box { background: #ebf8ff; border: 1px solid #bee3f8; border-left: 4px solid #3182ce; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #2c5282; line-height: 1.7; }

        .cta-wrap { text-align: center; margin: 24px 0 16px; }
        .cta-btn { display: inline-block; background: #1a56db; color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-weight: 700; font-size: 13px; }

        .footer { background: #f7fafc; border-radius: 0 0 12px 12px; padding: 20px 36px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #a0aec0; font-size: 11px; line-height: 1.8; }
    </style>
</head>
<body>
    <div class="email-wrapper">

        @php
            $appLogo = \App\Models\Setting::where('key', 'app_logo_cek_spp')->value('value')
                    ?? \App\Models\Setting::where('key', 'app_logo')->value('value');
            $appName = \App\Models\Setting::where('key', 'app_name')->value('value') ?? config('app.name');
        @endphp

        <!-- Logo Bar -->
        <div class="logo-bar">
            @if($appLogo)
                <img src="{{ url('storage/' . $appLogo) }}" alt="{{ $appName }}">
            @else
                <div class="app-name">{{ $appName }}</div>
                <div class="app-tagline">Sistem Manajemen Akademi</div>
            @endif
        </div>

        <!-- Success Banner -->
        <div class="success-banner">
            <div class="banner-icon">
                <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
                    <circle cx="26" cy="26" r="26" fill="rgba(255,255,255,0.15)"/>
                    <rect x="14" y="18" width="24" height="16" rx="3" stroke="white" stroke-width="2.5"/>
                    <path d="M14 22h24M20 28h4" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </div>
            <h1>Pembayaran Berhasil Diterima</h1>
            <p>Terima kasih atas kepercayaan Anda</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <p class="greeting">
                Halo, <strong>{{ $invoice->siswa->user->name ?? 'Bapak/Ibu' }}</strong>,<br><br>
                Terima kasih. Kami telah menerima pembayaran untuk siswa <strong>{{ $invoice->siswa->nama_siswa ?? 'Siswa' }}</strong>. Berikut adalah rincian tagihan yang telah dilunasi:
            </p>

            <!-- Rincian Pembayaran -->
            <div class="info-card">
                <div class="info-card-title">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:4px"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke="#9ca3af" stroke-width="2" stroke-linecap="round"/></svg>
                    Rincian Pembayaran
                </div>
                <table class="info-row-table">
                    <tr>
                        <td class="lbl">No. Invoice</td>
                        <td class="val">#{{ substr($invoice->id, 0, 8) }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Nama Siswa</td>
                        <td class="val">{{ $invoice->siswa->nama_siswa ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Deskripsi</td>
                        <td class="val">{{ $invoice->description }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Tanggal Bayar</td>
                        <td class="val">{{ $invoice->paid_at ? $invoice->paid_at->format('d M Y, H:i') : now()->format('d M Y, H:i') }} WIB</td>
                    </tr>
                    @if($invoice->payment_method)
                    <tr>
                        <td class="lbl">Metode Pembayaran</td>
                        <td class="val">{{ strtoupper($invoice->payment_method) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="lbl">Jumlah SPP</td>
                        <td class="val">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Biaya Admin</td>
                        <td class="val">Rp {{ number_format($invoice->admin_fee ?? 0, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <!-- Total -->
            <div class="total-card">
                <table class="total-table">
                    <tr>
                        <td class="t-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:6px"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" fill="#1e3a8a"/></svg>
                            Total Dibayar
                        </td>
                        <td class="t-amount">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <!-- Attachment Notice -->
            <div class="attachment-box">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:6px"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" stroke="#3182ce" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <strong>Kuitansi PDF terlampir.</strong><br>
                File kuitansi pembayaran telah kami lampirkan pada email ini. Simpan sebagai bukti pembayaran resmi untuk keperluan administrasi Anda.
            </div>

            <!-- CTA -->
            <div class="cta-wrap">
                <a href="{{ config('app.url') }}" class="cta-btn">Lihat Riwayat Tagihan</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                Email ini dikirim otomatis oleh sistem. Harap tidak membalas email ini.<br>
                &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
            </p>
        </div>

    </div>
</body>
</html>
