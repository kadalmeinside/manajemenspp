<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\MutasiSiswa;
use App\Models\LegalDocument;
use App\Models\UserAgreement;

class MutasiController extends Controller
{
    public function show($token)
    {
        $mutasi = MutasiSiswa::with(['siswa', 'fromKelas', 'toKelas'])
            ->where('token', $token)
            ->firstOrFail();

        // Expire check
        if ($mutasi->status === 'PENDING' && $mutasi->expires_at < now()) {
            $mutasi->update(['status' => 'EXPIRED']);
        }

        $document = LegalDocument::where('name', 'pindah-cabang-terms')->first();

        return Inertia::render('Public/Mutasi/Show', [
            'mutasi' => $mutasi,
            'document' => $document,
        ]);
    }

    public function approve(Request $request, $token)
    {
        $mutasi = MutasiSiswa::with('siswa')->where('token', $token)->firstOrFail();

        if ($mutasi->status !== 'PENDING') {
            return back()->with('error', 'Status mutasi tidak valid atau sudah diproses.');
        }

        if ($mutasi->expires_at < now()) {
            $mutasi->update(['status' => 'EXPIRED']);
            return back()->with('error', 'Link mutasi sudah kedaluwarsa.');
        }

        $request->validate([
            'agreed_by' => 'required|string|max:255',
            'agree_terms' => 'accepted',
            'legal_document_id' => 'required|exists:legal_documents,id',
        ]);

        // Simpan User Agreement
        UserAgreement::create([
            'user_id' => null, // Karena public
            'id_siswa' => $mutasi->siswa_id,
            'legal_document_id' => $request->legal_document_id,
            'agreed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'type' => 'mutasi_cabang',
                'mutasi_id' => $mutasi->id,
                'agreed_by' => $request->agreed_by,
            ],
        ]);

        // Update mutasi
        $mutasi->update([
            'status' => 'APPROVED',
            'agreed_by' => $request->agreed_by,
            'agreed_at' => now(),
        ]);

        // Update siswa
        $mutasi->siswa->update([
            'id_kelas' => $mutasi->to_kelas_id,
            'jumlah_spp_custom' => $mutasi->spp_baru,
        ]);

        return back()->with('success', 'Mutasi cabang berhasil disetujui. Data siswa telah diperbarui.');
    }
}
