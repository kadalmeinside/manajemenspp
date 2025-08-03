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
    public function show(Request $request)
    {
        $siswaId = $request->session()->pull('registration_success_siswa_id');

        // if (!$siswaId) {
        //     return redirect()->route('welcome');
        // }

        // $siswa = Siswa::find($siswaId);

        // if (!$siswa) {
        //     return redirect()->route('welcome');
        // }
        dd($siswaId);

        return Inertia::render('Public/RegistrationSuccess', [
            'pageTitle' => 'Pendaftaran Berhasil',
            'siswaName' => $siswa->nama_siswa,
            'adminContact' => '0811-2626-323',
            'instagramUrl' => 'https://www.instagram.com/persija.ac/',
        ]);
    }
}
