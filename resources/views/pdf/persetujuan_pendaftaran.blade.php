@php
    $appName = \App\Models\Setting::where('key', 'app_name')->value('value') ?? config('app.name');
    $kopSuratNama = \App\Models\Setting::where('key', 'kop_surat_nama')->value('value') ?? $appName;
    $kopSuratAlamat = \App\Models\Setting::where('key', 'kop_surat_alamat')->value('value');
    $kopSuratKontak = \App\Models\Setting::where('key', 'kop_surat_kontak')->value('value');
    $appLogo = \App\Models\Setting::where('key', 'app_logo')->value('value');
    
    if ($appLogo && file_exists(storage_path('app/public/' . $appLogo))) {
        $logoData = base64_encode(file_get_contents(storage_path('app/public/' . $appLogo)));
        $ext = pathinfo(storage_path('app/public/' . $appLogo), PATHINFO_EXTENSION);
        $logoSrc = 'data:image/' . $ext . ';base64,' . $logoData;
    } else {
        $logoSrc = null;
    }
    
    $qrData = "Dokumen Resmi. Disetujui oleh: " . ($siswa->user->name ?? 'Wali') . ". IP: " . ($agreement->ip_address ?? 'N/A') . ". Waktu: " . $agreement->agreed_at;
    $qrCode = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(80)->margin(0)->generate($qrData));
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Persetujuan Pendaftaran - {{ $siswa->nama_siswa }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }
        .kop-surat {
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .kop-surat table {
            width: 100%;
            border: none;
            margin-bottom: 0;
        }
        .kop-surat table td {
            border: none;
            padding: 0;
        }
        .kop-surat .nama {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .kop-surat .alamat {
            font-size: 12px;
            margin: 5px 0;
        }
        .kop-surat .kontak {
            font-size: 12px;
            margin: 0;
        }
        .doc-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .doc-title h2 {
            margin: 0;
            font-size: 18px;
        }
        .doc-title p {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
        }
        .content {
            margin-bottom: 30px;
        }
        .content h2 {
            font-size: 16px;
            margin-top: 0;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.data-table th, table.data-table td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        table.data-table th {
            width: 35%;
            color: #555;
        }
        .terms {
            font-size: 12px;
            text-align: justify;
            margin-top: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border: 1px solid #eee;
        }
        .terms ol {
            padding-left: 20px;
        }
        .signature-section {
            margin-top: 40px;
            width: 100%;
            page-break-inside: avoid;
        }
        .signature-box {
            float: right;
            width: 300px;
            text-align: center;
        }
        .signature-line {
            margin-top: 10px;
            border-top: 1px solid #333;
            width: 100%;
        }
        .footer {
            position: fixed;
            bottom: -20px;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>

    <div class="kop-surat">
        <table>
            <tr>
                <td style="width: 15%; text-align: left; vertical-align: middle;">
                    @if($logoSrc)
                    <img src="{{ $logoSrc }}" style="max-height: 80px; max-width: 80px;">
                    @endif
                </td>
                <td style="width: 70%; text-align: center; vertical-align: middle;">
                    <h1 class="nama">{{ $kopSuratNama }}</h1>
                    @if($kopSuratAlamat)
                        <p class="alamat">{{ $kopSuratAlamat }}</p>
                    @endif
                    @if($kopSuratKontak)
                        <p class="kontak">{{ $kopSuratKontak }}</p>
                    @endif
                </td>
                <td style="width: 15%;"></td>
            </tr>
        </table>
    </div>

    <div class="doc-title">
        <h2>FORMULIR PERSETUJUAN PENDAFTARAN</h2>
        <p>Tanda Terima Registrasi Siswa Resmi</p>
    </div>

    <div class="content">
        <h2>Data Siswa</h2>
        <table class="data-table">
            <tr>
                <th>Nomor Induk Siswa (NIS)</th>
                <td>{{ $siswa->nis ?? 'Dalam Proses' }}</td>
            </tr>
            <tr>
                <th>Nama Lengkap Siswa</th>
                <td>{{ $siswa->nama_siswa }}</td>
            </tr>
            <tr>
                <th>Tanggal Lahir</th>
                <td>{{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->isoFormat('D MMMM Y') : '-' }}</td>
            </tr>
            <tr>
                <th>Kelas Terdaftar</th>
                <td>{{ $siswa->kelas->nama_kelas ?? 'Belum ada kelas' }}</td>
            </tr>
            <tr>
                <th>Tanggal Bergabung</th>
                <td>{{ $siswa->tanggal_bergabung ? \Carbon\Carbon::parse($siswa->tanggal_bergabung)->isoFormat('D MMMM Y') : '-' }}</td>
            </tr>
        </table>

        <h2>Data Wali / Penanggung Jawab</h2>
        <table class="data-table">
            <tr>
                <th>Nama Wali / Akun</th>
                <td>{{ $siswa->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Email Wali</th>
                <td>{{ $siswa->user->email ?? '-' }}</td>
            </tr>
            <tr>
                <th>Nomor Telepon Wali</th>
                <td>{{ $siswa->nomor_telepon_wali ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="terms">
        <strong>Pernyataan Persetujuan (Versi {{ $agreement->document->version }}):</strong>
        <div style="margin-top: 10px;">
            {!! $agreement->document->content !!}
        </div>
        <p style="margin-top: 10px; font-weight: bold;">
            Dengan dicetaknya dokumen ini secara elektronik melalui sistem, Wali Siswa dianggap telah menyetujui seluruh ketentuan di atas pada tanggal pendaftaran.
        </p>
    </div>

    <div class="signature-section clearfix">
        <div class="signature-box">
            <p style="margin: 0;">Disetujui secara elektronik pada:</p>
            <p style="margin: 5px 0;"><strong>{{ \Carbon\Carbon::parse($agreement->agreed_at)->isoFormat('D MMMM Y, HH:mm') }} WIB</strong></p>
            
            <div style="margin: 15px 0;">
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code" style="height: 80px; width: 80px;">
            </div>
            
            <div class="signature-line">
                <p style="margin-top: 5px; margin-bottom: 0;"><strong>{{ $siswa->user->name ?? 'Wali Siswa' }}</strong></p>
                <p style="margin-top: 0; font-size: 10px; color: #777;">Tanda Tangan Digital / Persetujuan Sistem<br>IP: {{ $agreement->ip_address ?? 'Tidak tercatat' }}</p>
            </div>
        </div>
    </div>

    <div class="footer">
        Dokumen ini di-generate secara otomatis oleh sistem pada {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y, HH:mm') }} WIB.
        <br>
        ID Referensi: {{ $siswa->id_siswa }}
    </div>

</body>
</html>
