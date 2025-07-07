<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Ulang Berhasil</title>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { font-size: 24px; font-weight: bold; color: #28a745; margin-bottom: 20px; text-align: center; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .details-table td { padding: 8px; border-bottom: 1px solid #eee; }
        .details-table td:first-child { font-weight: bold; width: 150px; }
        .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
        .logo-container { text-align: center; margin-bottom: 24px; }
        .button-container { text-align: center; margin: 30px 0; }
        .login-button {
            background-color: #e53e3e;
            color: #ffffff;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">

        <div class="logo-container">
            <img src="https://siswa.persijadevelopment.id/images/logo-black.png" alt="Persija Development" style="height: 48px; width: auto;">
        </div>

        <div class="header">Pendaftaran Ulang Berhasil!</div>
        <p>
            Halo, <strong>{{ $registrationData['nama_wali'] }}</strong>,
        </p>
        <p>
            Terima kasih telah melakukan pendaftaran ulang untuk ananda kami. Data telah berhasil kami simpan dengan detail sebagai berikut:
        </p>
        <table class="details-table">
            <tr>
                <td>NIS Baru</td>
                <td><strong>{{ $registrationData['nis'] }}</strong></td>
            </tr>
            <tr>
                <td>Nama Siswa</td>
                <td>{{ $registrationData['nama_siswa'] }}</td>
            </tr>
            <tr>
                <td>Email Wali (Login)</td>
                <td>{{ $registrationData['email_wali'] }}</td>
            </tr>
        </table>

        <div class="button-container">
            <a href="https://siswa.persijadevelopment.id/login" class="login-button">Login ke Akun Anda</a>
        </div>
        <p class="footer">
            Anda sekarang dapat login menggunakan email dan password yang telah Anda buat. Harap simpan informasi ini dengan baik.
            <br><br>
            Jika tombol di atas tidak berfungsi, silakan salin dan tempel URL berikut di browser Anda:
            <br>
            <a href="https://siswa.persijadevelopment.id/login">https://siswa.persijadevelopment.id/login</a>
        </p>
    </div>
</body>
</html>