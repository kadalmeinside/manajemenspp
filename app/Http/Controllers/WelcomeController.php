<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WelcomeController extends Controller
{
    /**
     * Tampilkan halaman utama / landing page.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Welcome', [
            'canLogin'    => \Illuminate\Support\Facades\Route::has('login'),
            'canRegister' => \Illuminate\Support\Facades\Route::has('register'),
            'userIp'      => $request->ip(),
            'allKelas'    => Kelas::orderBy('nama_kelas')->get()->map(fn ($kelas) => [
                'nama_kelas' => $kelas->nama_kelas,
                'deskripsi'  => Str::limit($kelas->deskripsi, 50),
                'gambar'     => 'https://placehold.co/400x300/e2e8f0/4a5563?text=' . urlencode($kelas->nama_kelas),
            ]),
        ]);
    }
}
