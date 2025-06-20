<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DisplayController extends Controller
{
    /**
     * Menampilkan halaman display lobi.
     */
    public function index()
    {
        // Untuk saat ini, kita hanya merender view.
        // Di masa depan, Anda bisa mengambil data video, banner,
        // dan running text dari database dan mengirimkannya sebagai props.
        return Inertia::render('Public/Display', [
            'pageTitle' => 'Informasi & Jadwal',
        ]);
    }
}
