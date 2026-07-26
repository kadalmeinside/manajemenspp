<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\Setting;
use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use App\Models\UserAgreement;
use App\Models\Invoice;

class ResignationController extends Controller
{
    public function showForm(Request $request, Siswa $siswa)
    {
        // Pastikan belum resign
        if ($siswa->status_siswa === 'Keluar') {
            return Inertia::render('Public/Resignation/Success', [
                'siswa' => $siswa->only(['nama_siswa', 'nis']),
                'message' => 'Siswa sudah berstatus Keluar / Mengundurkan Diri.'
            ]);
        }

        // Ambil ID dokumen legal yang diset di settings
        $legalDocId = Setting::where('key', 'legal_doc_resignation')->value('value');
        $legalDocument = null;
        if ($legalDocId) {
            $legalDocument = LegalDocument::find($legalDocId);
        }

        $tanggalResign = $request->query('tanggal_resign');

        return Inertia::render('Public/Resignation/Form', [
            'siswa' => $siswa->load(['kelas', 'user'])->only(['id_siswa', 'nama_siswa', 'nis', 'kelas', 'user', 'email_wali', 'nomor_telepon_wali']),
            'legalDocument' => $legalDocument,
            'tanggalResign' => $tanggalResign,
            'submitUrl' => \Illuminate\Support\Facades\URL::signedRoute('public.resignation.submit', [
                'siswa' => $siswa->id_siswa,
                'tanggal_resign' => $tanggalResign
            ])
        ]);
    }

    public function submitForm(Request $request, Siswa $siswa)
    {
        if ($siswa->status_siswa === 'Keluar') {
            return redirect()->route('welcome');
        }

        $tanggalResign = $request->query('tanggal_resign');

        $validated = $request->validate([
            'parent_name' => 'required|string|max:255',
            'student_name' => 'required|string|max:255',
            'reason' => 'required|string',
            'agreement_accepted' => 'required|accepted',
            'legal_document_id' => 'required|exists:legal_documents,id',
        ]);

        DB::transaction(function () use ($siswa, $validated, $request, $tanggalResign) {
            // Ubah status
            $siswa->update(['status_siswa' => 'Keluar']);

            // Buat record di user_agreements
            UserAgreement::create([
                'user_id' => $siswa->id_user,
                'id_siswa' => $siswa->id_siswa,
                'legal_document_id' => $validated['legal_document_id'],
                'agreed_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => json_encode([
                    'type' => 'resignation',
                    'reason' => $validated['reason'],
                    'parent_name' => $validated['parent_name'],
                    'student_name' => $validated['student_name'],
                    'tanggal_resign' => $tanggalResign,
                ])
            ]);

            // Hapus tagihan PENDING yang periodenya SETELAH tanggal_resign
            if ($tanggalResign) {
                Invoice::where('id_siswa', $siswa->id_siswa)
                    ->where('status', 'PENDING')
                    ->where(function($q) use ($tanggalResign) {
                        $q->where('periode_tagihan', '>', $tanggalResign)
                          ->orWhere(function($q2) use ($tanggalResign) {
                              $q2->whereNull('periode_tagihan')
                                 ->where('due_date', '>', $tanggalResign);
                          });
                    })
                    ->delete();
            }
        });

        return Inertia::render('Public/Resignation/Success', [
            'siswa' => $siswa->only(['nama_siswa', 'nis']),
            'message' => 'Pengunduran diri berhasil diproses. Seluruh tagihan yang bersangkutan setelah tanggal pengunduran diri telah dibatalkan.'
        ]);
    }
}
