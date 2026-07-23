<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SwitchSiswaController extends Controller
{
    public function __invoke(Request $request, $id_siswa)
    {
        $user = $request->user();
        
        // Pastikan id_siswa ini memang milik user yang login
        $validSiswa = $user->siswas()->where('id_siswa', $id_siswa)->exists();
        
        if ($validSiswa) {
            session(['active_siswa_id' => $id_siswa]);
        }

        return Redirect::back();
    }
}
