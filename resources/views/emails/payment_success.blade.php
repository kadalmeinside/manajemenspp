<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f5f5f5; font-family: Arial, sans-serif; padding: 24px 16px; color: #1a1a1a; }
        .email-wrapper { max-width: 620px; margin: 0 auto; }

        /* Logo Bar */
        .logo-bar { background: #ffffff; border-radius: 12px 12px 0 0; padding: 22px 36px; text-align: center; border-bottom: 3px solid #e8600a; }
        .logo-bar img { height: 56px; width: auto; }
        .logo-bar .app-name { font-size: 20px; font-weight: 800; color: #e8600a; }
        .logo-bar .app-tagline { font-size: 11px; color: #888888; margin-top: 3px; }

        /* Banner */
        .success-banner { background: #ffffff; padding: 32px 36px; text-align: center; border-bottom: 1px solid #eeeeee; }
        .success-banner h1 { color: #1a1a1a; font-size: 20px; font-weight: 800; margin-top: 14px; }
        .success-banner p { color: #555555; font-size: 13px; margin-top: 6px; }

        /* Animated Check Icon */
        .check-circle-wrap { display: inline-block; margin-bottom: 4px; }
        .check-bg { fill: #22c55e; }
        .check-ring { fill: none; stroke: rgba(255,255,255,0.4); stroke-width: 3; }
        .check-path {
            fill: none;
            stroke: #ffffff;
            stroke-width: 4;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 40;
            stroke-dashoffset: 40;
            animation: drawCheck 0.6s ease 0.3s forwards;
        }
        @keyframes drawCheck { to { stroke-dashoffset: 0; } }
        .check-circle-anim {
            animation: popIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            transform-origin: center;
            transform: scale(0);
        }
        @keyframes popIn { to { transform: scale(1); } }

        /* Content */
        .content { background: #ffffff; padding: 32px 36px; }
        .greeting { font-size: 14px; color: #333333; margin-bottom: 24px; line-height: 1.9; }

        /* Info Card */
        .info-card { background: #ffffff; border: 1px solid #fddcbf; border-radius: 10px; padding: 18px 22px; margin-bottom: 14px; }
        .info-card-title { font-size: 10px; color: #e8600a; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; margin-bottom: 10px; padding-bottom: 7px; border-bottom: 1px solid #fddcbf; }
        .info-row-table { width: 100%; border-collapse: collapse; }
        .info-row-table tr { border-bottom: 1px solid #fce9d4; }
        .info-row-table tr:last-child { border-bottom: none; }
        .info-row-table td { padding: 9px 0; font-size: 13px; }
        .info-row-table td.lbl { color: #888888; width: 45%; }
        .info-row-table td.val { color: #1a1a1a; font-weight: 600; text-align: right; }

        /* Total Card */
        .total-card { background: #ffffff; border: 2px solid #e8600a; border-radius: 10px; padding: 16px 24px; margin-bottom: 16px; }
        .total-table { width: 100%; border-collapse: collapse; }
        .total-table td { padding: 0; }
        .total-table td.t-label { font-size: 13px; font-weight: 700; color: #1a1a1a; }
        .total-table td.t-amount { font-size: 20px; font-weight: 800; color: #e8600a; text-align: right; }

        /* Attachment */
        .attachment-box { background: #ffffff; border: 1px solid #fddcbf; border-left: 4px solid #e8600a; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #444444; line-height: 1.7; }

        /* CTA */
        .cta-wrap { text-align: center; margin: 22px 0 14px; }
        .cta-btn { display: inline-block; background: #e8600a; color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-weight: 700; font-size: 13px; }

        /* Footer */
        .footer { background: #ffffff; border-radius: 0 0 12px 12px; padding: 18px 36px; text-align: center; border-top: 2px solid #fddcbf; }
        .footer p { color: #aaaaaa; font-size: 11px; line-height: 1.8; }

        /* Mobile */
        @media only screen and (max-width: 480px) {
            body { padding: 0 !important; }
            .email-wrapper { border-radius: 0; }
            .logo-bar { padding: 16px 16px; border-radius: 0; }
            .success-banner { padding: 24px 16px; }
            .content { padding: 20px 16px; }
            .footer { padding: 16px 16px; border-radius: 0; }
            .info-card { padding: 14px 14px; }
            .total-card { padding: 14px 14px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">

        @php
            $appLogoValue = \App\Models\Setting::where('key', 'app_logo_cek_spp')->value('value');
            if (empty($appLogoValue)) {
                $appLogoValue = \App\Models\Setting::where('key', 'app_logo')->value('value');
            }
            $appLogo = !empty($appLogoValue) ? $appLogoValue : null;

            $appNameValue = \App\Models\Setting::where('key', 'app_name')->value('value');
            $appName = !empty($appNameValue) ? $appNameValue : config('app.name');

            $kopNamaValue = \App\Models\Setting::where('key', 'kop_surat_nama')->value('value');
            $kopNama = !empty($kopNamaValue) ? $kopNamaValue : $appName;
        @endphp

        <!-- Logo Bar -->
        <div class="logo-bar">
            @if($appLogo)
                <img src="{{ url('storage/' . $appLogo) }}" alt="{{ $kopNama }}">
            @else
                <div class="app-name">{{ $kopNama }}</div>
                <div class="app-tagline">Sistem Manajemen Akademi</div>
            @endif
        </div>

        <!-- Success Banner -->
        <div class="success-banner">
            <div class="check-circle-wrap">
                <svg class="check-circle-anim" width="72" height="72" viewBox="0 0 72 72">
                    <circle class="check-bg" cx="36" cy="36" r="36"/>
                    <circle class="check-ring" cx="36" cy="36" r="28"/>
                    <path class="check-path" d="M22 36 L31 45 L50 26"/>
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
                <div class="info-card-title">Rincian Pembayaran</div>
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
                        <td class="t-label">Total Dibayar</td>
                        <td class="t-amount">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <!-- Attachment Notice -->
            <div class="attachment-box">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:6px"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" stroke="#888888" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
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
                Jika ada pertanyaan, hubungi admin {{ $kopNama }}.<br><br>
                &copy; {{ date('Y') }} {{ $kopNama }}. All rights reserved.
            </p>
        </div>

    </div>
</body>
</html>
