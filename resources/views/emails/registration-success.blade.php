<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Berhasil</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f0f4f8; font-family: Arial, sans-serif; padding: 24px 16px; color: #2d3748; }
        .email-wrapper { max-width: 620px; margin: 0 auto; }

        .logo-bar { background: #ffffff; border-radius: 12px 12px 0 0; padding: 24px 36px; text-align: center; border-bottom: 3px solid #e53e3e; }
        .logo-bar img { height: 56px; width: auto; }
        .logo-bar .app-name { font-size: 20px; font-weight: 800; color: #e53e3e; letter-spacing: -0.5px; }
        .logo-bar .app-tagline { font-size: 11px; color: #718096; margin-top: 3px; }

        .success-banner { background: linear-gradient(135deg, #276749, #38a169); padding: 28px 36px; text-align: center; }
        .success-banner .banner-icon { margin-bottom: 10px; }
        .success-banner h1 { color: #fff; font-size: 20px; font-weight: 700; }
        .success-banner p { color: #c6f6d5; font-size: 13px; margin-top: 5px; }

        .content { background: #ffffff; padding: 32px 36px; }
        .greeting { font-size: 14px; color: #4a5568; margin-bottom: 20px; line-height: 1.8; }

        .steps-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        .steps-table td { padding: 14px 10px; text-align: center; background: #f7fafc; border: 1px solid #e2e8f0; width: 33.33%; }
        .steps-table td:first-child { border-radius: 8px 0 0 8px; }
        .steps-table td:last-child { border-radius: 0 8px 8px 0; }
        .step-title { font-size: 11px; color: #718096; margin-top: 5px; }
        .step-done { font-size: 11px; font-weight: 700; color: #38a169; }
        .step-soon { font-size: 11px; font-weight: 700; color: #3182ce; }

        .info-card { background: #f7fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-bottom: 16px; }
        .info-card-title { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
        .info-row-table { width: 100%; border-collapse: collapse; }
        .info-row-table tr { border-bottom: 1px solid #edf2f7; }
        .info-row-table tr:last-child { border-bottom: none; }
        .info-row-table td { padding: 9px 0; font-size: 13px; }
        .info-row-table td.lbl { color: #718096; width: 45%; }
        .info-row-table td.val { color: #2d3748; font-weight: 600; text-align: right; }
        .badge-aktif { background: #c6f6d5; color: #22543d; padding: 2px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }

        .warning-box { background: #fffbeb; border: 1px solid #f6e05e; border-left: 4px solid #d69e2e; border-radius: 6px; padding: 14px 18px; margin-bottom: 16px; font-size: 13px; color: #7b341e; line-height: 1.7; }
        .attachment-box { background: #ebf8ff; border: 1px solid #bee3f8; border-left: 4px solid #3182ce; border-radius: 6px; padding: 14px 18px; margin-bottom: 20px; font-size: 13px; color: #2c5282; line-height: 1.7; }

        .cta-wrap { text-align: center; margin: 24px 0 16px; }
        .cta-btn { display: inline-block; background: #e53e3e; color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-weight: 700; font-size: 13px; }

        .footer { background: #f7fafc; border-radius: 0 0 12px 12px; padding: 20px 36px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #a0aec0; font-size: 11px; line-height: 1.8; }
        .footer a { color: #718096; }
    </style>
</head>
<body>
    <div class="email-wrapper">

        @php
            $appLogo = \App\Models\Setting::where('key', 'app_logo_cek_spp')->value('value')
                    ?? \App\Models\Setting::where('key', 'app_logo')->value('value');
            $appName = \App\Models\Setting::where('key', 'app_name')->value('value') ?? config('app.name');
            $appAddress = \App\Models\Setting::where('key', 'app_address')->value('value') ?? '';
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
                <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="26" cy="26" r="26" fill="rgba(255,255,255,0.15)"/>
                    <path d="M15 26L22 33L37 18" stroke="white" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>Pendaftaran &amp; Pembayaran Berhasil!</h1>
            <p>Selamat bergabung bersama {{ $appName }}</p>
        </div>

        <!-- Main Content -->
        <div class="content">
            <p class="greeting">
                Halo, <strong>{{ $registrationData['nama_wali'] }}</strong>,<br><br>
                Selamat! Pendaftaran siswa Anda telah berhasil kami proses dan pembayaran pendaftaran telah kami terima. Berikut adalah ringkasan data pendaftaran dan akun Anda:
            </p>

            <!-- Progress Steps -->
            <table class="steps-table">
                <tr>
                    <td>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#c6f6d5"/><path d="M7 12l3.5 3.5L17 9" stroke="#276749" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <div class="step-title">Daftar</div>
                        <div class="step-done">Selesai</div>
                    </td>
                    <td>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#c6f6d5"/><path d="M7 12l3.5 3.5L17 9" stroke="#276749" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <div class="step-title">Pembayaran</div>
                        <div class="step-done">Lunas</div>
                    </td>
                    <td>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" fill="#dbeafe"/><path d="M12 8v4l2.5 2.5" stroke="#3182ce" stroke-width="2.5" stroke-linecap="round"/></svg>
                        <div class="step-title">Mulai Latihan</div>
                        <div class="step-soon">Segera</div>
                    </td>
                </tr>
            </table>

            <!-- Data Siswa -->
            <div class="info-card">
                <div class="info-card-title">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:4px"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="#9ca3af" stroke-width="2" stroke-linecap="round"/></svg>
                    Data Siswa
                </div>
                <table class="info-row-table">
                    <tr>
                        <td class="lbl">Nama Siswa</td>
                        <td class="val">{{ $registrationData['nama_siswa'] }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">NIS / ID Siswa</td>
                        <td class="val">{{ $registrationData['nis'] }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Status</td>
                        <td class="val"><span class="badge-aktif">Aktif</span></td>
                    </tr>
                </table>
            </div>

            <!-- Data Akun -->
            <div class="info-card">
                <div class="info-card-title">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:4px"><rect x="3" y="11" width="18" height="11" rx="2" stroke="#9ca3af" stroke-width="2"/><path d="M7 11V7a5 5 0 0110 0v4" stroke="#9ca3af" stroke-width="2" stroke-linecap="round"/></svg>
                    Akun Login Portal Wali
                </div>
                <table class="info-row-table">
                    <tr>
                        <td class="lbl">Email Login</td>
                        <td class="val">{{ $registrationData['email_wali'] }}</td>
                    </tr>
                    @if(isset($registrationData['password']) && $registrationData['password'])
                    <tr>
                        <td class="lbl">Password</td>
                        <td class="val"><strong>{{ $registrationData['password'] }}</strong></td>
                    </tr>
                    @endif
                </table>
            </div>

            @if(isset($registrationData['password']) && $registrationData['password'])
            <div class="warning-box">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:6px"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" stroke="#d69e2e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <strong>Harap simpan password ini!</strong><br>
                Gunakan email dan password di atas untuk masuk ke Portal Wali. Segera ubah password Anda setelah login pertama kali untuk keamanan akun.
            </div>
            @endif

            @isset($invoice)
            @if($invoice)
            <div class="attachment-box">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" style="vertical-align:middle;margin-right:6px"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" stroke="#3182ce" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <strong>Kuitansi Pembayaran Pendaftaran terlampir (PDF).</strong><br>
                File kuitansi pembayaran pendaftaran telah kami lampirkan pada email ini. Simpan sebagai bukti pembayaran resmi Anda.
            </div>
            @endif
            @endisset

            <div class="cta-wrap">
                <a href="{{ url('/login') }}" class="cta-btn">Masuk ke Portal Wali</a>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>
                Email ini dikirim otomatis oleh sistem. Harap tidak membalas email ini.<br>
                Jika ada pertanyaan, hubungi admin {{ $appName }}.<br><br>
                &copy; {{ date('Y') }} {{ $appName }}. All rights reserved.
            </p>
        </div>

    </div>
</body>
</html>