<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\MutasiSiswa;
use App\Models\Kelas;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MutasiSiswaController extends Controller
{
    public function store(Request $request, Siswa $siswa)
    {
        $request->validate([
            'to_kelas_id' => 'required|exists:kelas,id_kelas',
            'spp_baru' => 'nullable|numeric|min:0',
            'start_month' => 'required|date_format:Y-m',
        ]);

        if ($siswa->id_kelas === $request->to_kelas_id) {
            return back()->withErrors(['to_kelas_id' => 'Siswa sudah berada di kelas/cabang tersebut.']);
        }

        // Cek apakah ada mutasi pending
        $hasPending = $siswa->mutasiSiswas()->where('status', 'PENDING')->exists();
        if ($hasPending) {
            return back()->with('error', 'Siswa masih memiliki permohonan mutasi yang pending.');
        }

        MutasiSiswa::create([
            'siswa_id' => $siswa->id_siswa,
            'from_kelas_id' => $siswa->id_kelas,
            'to_kelas_id' => $request->to_kelas_id,
            'spp_baru' => $request->spp_baru,
            'start_month' => $request->start_month,
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Permohonan mutasi berhasil dibuat.');
    }

    public function regenerate(MutasiSiswa $mutasi)
    {
        if ($mutasi->status !== 'PENDING' && $mutasi->status !== 'EXPIRED') {
            return back()->with('error', 'Hanya mutasi pending/expired yang dapat diperbarui.');
        }

        $mutasi->update([
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
            'status' => 'PENDING',
        ]);

        return back()->with('success', 'Link mutasi berhasil diperbarui.');
    }

    public function cancel(MutasiSiswa $mutasi)
    {
        if ($mutasi->status !== 'PENDING' && $mutasi->status !== 'EXPIRED') {
            return back()->with('error', 'Mutasi tidak dapat dibatalkan.');
        }

        $mutasi->update(['status' => 'CANCELLED']);

        return back()->with('success', 'Permohonan mutasi berhasil dibatalkan.');
    }
}
