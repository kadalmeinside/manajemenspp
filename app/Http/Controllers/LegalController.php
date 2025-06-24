<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LegalController extends Controller
{
    /**
     * Menampilkan halaman Syarat dan Ketentuan.
     */
    public function terms(): Response
    {
        return Inertia::render('Legal/Terms', [
            'pageTitle' => 'Syarat dan Ketentuan',
        ]);
    }

    /**
     * Menampilkan halaman Kebijakan Pengembalian Dana.
     */
    public function refund(): Response
    {
        return Inertia::render('Legal/Refund', [
            'pageTitle' => 'Kebijakan Pengembalian Dana',
        ]);
    }

    /**
     * Menampilkan halaman Kebijakan Privasi.
     */
    public function privacy(): Response
    {
        return Inertia::render('Legal/Privacy', [
            'pageTitle' => 'Kebijakan Privasi',
        ]);
    }
}
