<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RegistrationSuccessController extends Controller
{
    /**
     * Menampilkan halaman setelah pendaftaran dan pembayaran berhasil.
     */
    public function show(Request $request, Siswa $siswa)
    {
        // URL is now signed via middleware

        $siswa->load('kelas');

        return Inertia::render('Public/RegistrationSuccess', [
            'pageTitle' => 'Pendaftaran Berhasil',
            'siswaName' => $siswa->nama_siswa,
            'siswaNis'  => $siswa->nis,
            'cabangName' => $siswa->kelas ? $siswa->kelas->nama_kelas : 'Persija',
            'adminContact' => '0811-2626-323',
            'instagramUrl' => 'https://www.instagram.com/persija.ac/',
        ]);
    }

    /**
     * Menampilkan halaman pending payment.
     */
    public function showPending(Request $request, \App\Models\PendingRegistration $pending)
    {
        $kelas = \App\Models\Kelas::find($pending->id_kelas);
        return Inertia::render('Public/RegistrationPending', [
            'pageTitle'  => 'Menunggu Pembayaran',
            'siswaName'  => $pending->nama_siswa,
            'cabangName' => $kelas ? $kelas->nama_kelas : 'Persija',
            'paymentUrl' => $pending->xendit_payment_url,
            'expiresAt'  => $pending->expires_at,
        ]);
    }
}
