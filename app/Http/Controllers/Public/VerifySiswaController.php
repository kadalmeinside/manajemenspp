<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VerifySiswaController extends Controller
{
    /**
     * Menampilkan halaman verifikasi ID Card siswa.
     */
    public function show($id)
    {
        $siswa = Siswa::with('kelas')->findOrFail($id);

        return Inertia::render('Public/VerifySiswa', [
            'siswa' => [
                'nama_siswa' => $siswa->nama_siswa,
                'nis' => $siswa->nis,
                'kelas' => $siswa->kelas ? $siswa->kelas->nama_kelas : '-',
                'status_siswa' => $siswa->status_siswa,
            ],
            'pageTitle' => 'Verifikasi Siswa'
        ]);
    }
}
