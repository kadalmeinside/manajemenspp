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
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
        }
        .header p {
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }
        th {
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
            margin-top: 50px;
            width: 100%;
        }
        .signature-box {
            float: right;
            width: 300px;
            text-align: center;
        }
        .signature-line {
            margin-top: 70px;
            border-top: 1px solid #333;
            width: 100%;
        }
        .footer {
            position: fixed;
            bottom: 0;
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

    <div class="header">
        <h1>FORMULIR PERSETUJUAN PENDAFTARAN</h1>
        <p>Tanda Terima Registrasi Siswa Resmi</p>
    </div>

    <div class="content">
        <h2>Data Siswa</h2>
        <table>
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
        <table>
            <tr>
                <th>Nama Wali / Akun</th>
                <td>{{ $siswa->user->name ?? '-' }}</td>
            </tr>
            <tr>
                <th>Email Wali</th>
                <td>{{ $siswa->email_wali ?? '-' }}</td>
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
            <p>Mengetahui dan Menyetujui,</p>
            <p style="margin-top: -10px; color: #666; font-size: 12px;">
                (Persetujuan Elektronik tercatat dari IP: {{ $agreement->ip_address ?? 'Tidak tercatat' }})
            </p>
            <div class="signature-line"></div>
            <p style="margin-top: 5px;"><strong>{{ $siswa->user->name ?? 'Wali Siswa' }}</strong></p>
            <p style="margin-top: -5px; font-size: 10px; color: #777;">Disetujui pada: {{ $agreement->agreed_at->isoFormat('D MMMM YYYY, HH:mm') }}</p>
        </div>
    </div>

    <div class="footer">
        Dokumen ini di-generate secara otomatis oleh sistem pada {{ \Carbon\Carbon::now()->isoFormat('D MMMM Y, HH:mm') }} WIB.
        <br>
        ID Referensi: {{ $siswa->id_siswa }}
    </div>

</body>
</html>
