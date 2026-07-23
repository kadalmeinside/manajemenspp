<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MaintenanceController extends Controller
{
    /**
     * Menampilkan halaman maintenance.
     */
    public function show()
    {
        return Inertia::render('Public/Maintenance', [
            'pageTitle' => 'Situs dalam Perbaikan',
        ]);
    }
}
