<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\LegalDocument;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Requests\StoreRegistrationRequest;
use App\Http\Requests\ValidatePromoRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Throwable;

class RegistrationController extends Controller
{
    /**
     * Menampilkan form pendaftaran umum.
     */
    public function create()
    {
        $docId = \App\Models\Setting::where('key', 'legal_doc_registration_public')->value('value');
        $terms = $docId ? LegalDocument::find($docId) : LegalDocument::latest('published_at')->first();

        $allKelas = Kelas::orderBy('nama_kelas')->get();

        return Inertia::render('Public/Registration', [
            'pageTitle'       => 'Formulir Pendaftaran Umum',
            'allKelas'        => $allKelas,
            'registrationFee' => 0,
            'termsDocument'   => $terms,
        ]);
    }

    /**
     * Menampilkan form pendaftaran Academy.
     */
    public function createAcademy()
    {
        $academyClass = Kelas::where('nama_kelas', 'Persija Academy')->firstOrFail();

        $docId = \App\Models\Setting::where('key', 'legal_doc_registration_academy')->value('value');
        $terms = $docId ? LegalDocument::find($docId) : LegalDocument::latest('published_at')->first();

        return Inertia::render('Public/RegisterAcademy', [
            'pageTitle'   => 'Formulir Pendaftaran Siswa Academy',
            'academyClass' => [
                'id_kelas'                  => $academyClass->id_kelas,
                'nama_kelas'                => $academyClass->nama_kelas,
                'biaya_pendaftaran_normal'   => (float) $academyClass->biaya_pendaftaran,
                'biaya_pendaftaran_saat_ini' => $academyClass->getBiayaPendaftaranSaatIni(),
            ],
            'termsDocument' => $terms,
        ]);
    }

    /**
     * Menampilkan form pendaftaran Soccer School.
     */
    public function createSs()
    {
        $docId = \App\Models\Setting::where('key', 'legal_doc_registration_ss')->value('value');
        $terms = $docId ? LegalDocument::find($docId) : LegalDocument::latest('published_at')->first();

        $allKelas = Kelas::where('deskripsi', 'Soccer School')
                         ->orderBy('nama_kelas')
                         ->get();

        return Inertia::render('Public/RegisterSs', [
            'pageTitle'     => 'Formulir Pendaftaran Siswa Soccer School',
            'allKelas'      => $allKelas->map(function ($kelas) {
                return [
                    'id_kelas'                  => $kelas->id_kelas,
                    'nama_kelas'                => $kelas->nama_kelas,
                    'kode_cabang'               => $kelas->kode_cabang,
                    'biaya_pendaftaran_normal'   => (float) $kelas->biaya_pendaftaran,
                    'biaya_pendaftaran_saat_ini' => $kelas->getBiayaPendaftaranSaatIni(),
                ];
            }),
            'termsDocument' => $terms,
        ]);
    }

    /**
     * Memvalidasi kode promo secara real-time.
     */
    public function validatePromoCode(ValidatePromoRequest $request)
    {
        $validated = $request->validated();

        $kelas          = Kelas::findOrFail($validated['id_kelas']);
        $hargaNormal    = (float) $kelas->biaya_pendaftaran;
        $hargaSetelahDiskon = $kelas->getBiayaPendaftaranSaatIni($request->validated('kode_promo'));

        if ($hargaSetelahDiskon < $hargaNormal) {
            return response()->json([
                'success'   => true,
                'message'   => 'Kode promo berhasil diterapkan!',
                'new_price' => $hargaSetelahDiskon,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Kode promo tidak valid atau tidak berlaku untuk kelas ini.',
        ], 422);
    }

    /**
     * Mengecek apakah email sudah terdaftar dan mengembalikan daftar nama anak.
     */
    public function checkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $validated['email'])->with('siswas')->first();

        if ($user) {
            return response()->json([
                'exists'   => true,
                'children' => $user->siswas->pluck('nama_siswa')->toArray(),
            ]);
        }

        return response()->json([
            'exists'   => false,
            'children' => [],
        ]);
    }

    /**
     * Menyimpan aplikasi pendaftaran sementara dan memanggil Xendit.
     * Tidak membuat User/Siswa/Invoice.
     */
    public function store(StoreRegistrationRequest $request, XenditService $xenditService)
    {
        $validated = $request->validated();
        
        // Format proper case
        $validated['nama_siswa'] = Str::title(strtolower(trim($validated['nama_siswa'])));
        $validated['user_name']  = Str::title(strtolower(trim($validated['user_name'])));

        $kelas = Kelas::findOrFail($validated['id_kelas']);
        
        $kodePromoInput = $request->validated('kode_promo');
        // Deteksi existing user (Anak sudah ada dan aktif)
        $existingUser = User::where('email', $validated['email_wali'])->with('siswas')->first();
        if ($existingUser) {
            $existingChildren = $existingUser->siswas
                ->pluck('nama_siswa')
                ->map(fn($name) => strtolower(trim($name)))
                ->toArray();

            if (in_array(strtolower(trim($validated['nama_siswa'])), $existingChildren)) {
                return back()->withErrors([
                    'nama_siswa' => 'Anak dengan nama ini sudah terdaftar di akun Anda. Jika sudah terdaftar, silakan login.',
                ]);
            }
        }

        // Cek existing pending registration
        $pendingReg = PendingRegistration::where('email_wali', $validated['email_wali'])
            ->whereRaw('LOWER(nama_siswa) = ?', [strtolower(trim($validated['nama_siswa']))])
            ->where('status', '!=', 'paid')
            ->first();

        $biayaFinal  = $kelas->getBiayaPendaftaranSaatIni($kodePromoInput);
        $adminFee    = (float)($kelas->admin_fee_custom ?? 0);
        $totalAmount = $biayaFinal + $adminFee;
        $externalId  = 'PREG-' . strtoupper(Str::random(10));

        if ($pendingReg) {
            // Update existing pending registration
            // Jika ada invoice xendit lama yang pending, expire dulu
            if ($pendingReg->xendit_invoice_id && $pendingReg->status === 'pending') {
                try {
                    $xenditService->expireInvoice($pendingReg->xendit_invoice_id);
                } catch (\Exception $e) {
                    Log::warning('Failed to expire old xendit invoice: ' . $e->getMessage());
                }
            }
            
            $pendingReg->update([
                'nama_wali'          => $validated['user_name'],
                'tanggal_lahir'      => $validated['tanggal_lahir'],
                'id_kelas'           => $kelas->id_kelas,
                'nomor_telepon_wali' => $validated['nomor_telepon_wali'],
                'kode_promo'         => $kodePromoInput,
                'legal_document_id'  => $validated['legal_document_id'],
                'ip_address'         => $request->ip(),
                'amount'             => $biayaFinal,
                'admin_fee'          => $adminFee,
                'total_amount'       => $totalAmount,
                'xendit_external_id' => $externalId,
                'status'             => 'pending',
                'expires_at'         => now()->addMinutes(30),
            ]);
        } else {
            // Create new pending registration
            $pendingReg = PendingRegistration::create([
                'email_wali'         => $validated['email_wali'],
                'nama_wali'          => $validated['user_name'],
                'nama_siswa'         => $validated['nama_siswa'],
                'tanggal_lahir'      => $validated['tanggal_lahir'],
                'id_kelas'           => $kelas->id_kelas,
                'nomor_telepon_wali' => $validated['nomor_telepon_wali'],
                'kode_promo'         => $kodePromoInput,
                'legal_document_id'  => $validated['legal_document_id'],
                'ip_address'         => $request->ip(),
                'amount'             => $biayaFinal,
                'admin_fee'          => $adminFee,
                'total_amount'       => $totalAmount,
                'xendit_external_id' => $externalId,
                'status'             => 'pending',
                'expires_at'         => now()->addMinutes(30),
            ]);
        }

        // =============================================================
        // Panggil Xendit
        // =============================================================
        try {
            $successUrl = route('registration.success.pending', ['pending' => $pendingReg->id]);
            $payerInfo  = [
                'email' => $pendingReg->email_wali,
                'name'  => $pendingReg->nama_wali,
                'phone' => $pendingReg->nomor_telepon_wali,
            ];
            $deskripsi = "Biaya Pendaftaran - {$pendingReg->nama_siswa}";

            $xenditInvoiceData = $xenditService->createInvoice(
                $pendingReg->amount,
                $pendingReg->admin_fee,
                $deskripsi,
                $payerInfo,
                $pendingReg->xendit_external_id,
                $successUrl,
                route('payment.failure'),
                now()->addMinutes(30),
                ['email', 'whatsapp']
            );

            if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
                throw new \Exception('Xendit tidak mengembalikan invoice_url yang valid.');
            }

            $pendingReg->update([
                'xendit_invoice_id'  => $xenditInvoiceData['id'],
                'xendit_payment_url' => $xenditInvoiceData['invoice_url'],
            ]);

            // Increment kuota promo yang dipakai (Hold Quota)
            $appliedPromos = $kelas->getAppliedPromos($kodePromoInput);
            foreach ($appliedPromos as $promo) {
                $promo->increment('current_uses');
            }

            return Inertia::location($xenditInvoiceData['invoice_url']);

        } catch (Throwable $e) {
            Log::error('[Pendaftaran] Gagal panggil Xendit.', [
                'pending_id' => $pendingReg->id,
                'error'      => $e->getMessage(),
            ]);

            return back()->withErrors([
                'general' => 'Gagal membuat link pembayaran. Silakan coba beberapa saat lagi.',
            ]);
        }
    }
}
