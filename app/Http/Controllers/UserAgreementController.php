<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserAgreement;
use Illuminate\Support\Facades\Auth;

class UserAgreementController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'legal_document_id' => 'required|exists:legal_documents,id',
            'id_siswa' => 'required|array',
            'id_siswa.*' => 'exists:siswa,id_siswa',
        ]);

        $userId = Auth::id();
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        foreach ($validated['id_siswa'] as $idSiswa) {
            UserAgreement::create([
                'user_id' => $userId,
                'id_siswa' => $idSiswa,
                'legal_document_id' => $validated['legal_document_id'],
                'agreed_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }

        return redirect()->back()->with('success', 'Terima kasih telah menyetujui Syarat dan Ketentuan.');
    }

    public function storePublic(Request $request)
    {
        $validated = $request->validate([
            'legal_document_id' => 'required|exists:legal_documents,id',
            'id_siswa' => 'required|array',
            'id_siswa.*' => 'exists:siswa,id_siswa',
        ]);

        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        foreach ($validated['id_siswa'] as $idSiswa) {
            UserAgreement::create([
                'user_id' => null, // Boleh null untuk jalur publik Cek SPP
                'id_siswa' => $idSiswa,
                'legal_document_id' => $validated['legal_document_id'],
                'agreed_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
        }

        return redirect()->back()->with('success', 'Terima kasih telah menyetujui Syarat dan Ketentuan.');
    }
}
