<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\LegalDocument;
use App\Models\Siswa;
use App\Models\User;
use App\Models\UserAgreement;
use App\Models\Invoice;
use App\Mail\RegistrationSuccess;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
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
            'pageTitle' => 'Formulir Pendaftaran Umum',
            'allKelas' => $allKelas,
            'registrationFee' => 0, // Disesuaikan nanti
            'termsDocument' => $terms,
        ]);
    }

    /**
     * Menampilkan form pendaftaran.
     */
    public function createAcademy()
    {
        $academyClass = Kelas::where('nama_kelas', 'Persija Academy')->firstOrFail();
        
        $docId = \App\Models\Setting::where('key', 'legal_doc_registration_academy')->value('value');
        $terms = $docId ? LegalDocument::find($docId) : LegalDocument::latest('published_at')->first();

        return Inertia::render('Public/RegisterAcademy', [
            'pageTitle' => 'Formulir Pendaftaran Siswa Academy',
            'academyClass' => [
                'id_kelas' => $academyClass->id_kelas,
                'nama_kelas' => $academyClass->nama_kelas,
                'biaya_pendaftaran_normal' => (float) $academyClass->biaya_pendaftaran,
                'biaya_pendaftaran_saat_ini' => $academyClass->getBiayaPendaftaranSaatIni(),
            ],
            'termsDocument' => $terms,
        ]);
    }

    public function createSs()
    {
        $docId = \App\Models\Setting::where('key', 'legal_doc_registration_ss')->value('value');
        $terms = $docId ? LegalDocument::find($docId) : LegalDocument::latest('published_at')->first();

        $allKelas = Kelas::where('deskripsi', 'Soccer School')
                         ->orderBy('nama_kelas')
                         ->get();

        return Inertia::render('Public/RegisterSs', [
            'pageTitle' => 'Formulir Pendaftaran Siswa Soccer School',
            'allKelas' => $allKelas->map(function ($kelas) {
                return [
                    'id_kelas' => $kelas->id_kelas,
                    'nama_kelas' => $kelas->nama_kelas,
                    'kode_cabang' => $kelas->kode_cabang,
                    'biaya_pendaftaran_normal' => (float) $kelas->biaya_pendaftaran,
                    'biaya_pendaftaran_saat_ini' => $kelas->getBiayaPendaftaranSaatIni(),
                ];
            }),
            'termsDocument' => $terms,
        ]);
    }

    /**
     * Memvalidasi kode promo secara real-time.
     */
    public function validatePromoCode(Request $request)
    {
        $validated = $request->validate([
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'kode_promo' => 'required|string',
        ]);

        $kelas = Kelas::findOrFail($validated['id_kelas']);
        $hargaNormal = (float) $kelas->biaya_pendaftaran;
        $hargaSetelahDiskon = $kelas->getBiayaPendaftaranSaatIni($validated['kode_promo']);

        if ($hargaSetelahDiskon < $hargaNormal) {
            return response()->json([
                'success' => true,
                'message' => 'Kode promo berhasil diterapkan!',
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
            'email' => 'required|email'
        ]);

        $user = User::where('email', $validated['email'])->with('siswas')->first();

        if ($user) {
            $childrenNames = $user->siswas->pluck('nama_siswa')->toArray();
            return response()->json([
                'exists' => true,
                'children' => $childrenNames
            ]);
        }

        return response()->json([
            'exists' => false,
            'children' => []
        ]);
    }

    /**
     * Menyimpan data pendaftaran, membuat user & siswa pending, dan membuat invoice.
     */
    public function store(Request $request, XenditService $xenditService)
    {
        $messages = [
            'nama_siswa.required' => 'Nama lengkap siswa wajib diisi.',
            'nama_siswa.string' => 'Nama siswa harus berupa teks.',
            'nama_siswa.max' => 'Nama siswa maksimal 255 karakter.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'id_kelas.required' => 'Pilihan cabang atau kelas wajib diisi.',
            'id_kelas.exists' => 'Cabang atau kelas yang dipilih tidak valid.',
            'user_name.required' => 'Nama lengkap wali wajib diisi.',
            'user_name.string' => 'Nama wali harus berupa teks.',
            'user_name.max' => 'Nama wali maksimal 255 karakter.',
            'email_wali.required' => 'Alamat email wali wajib diisi.',
            'email_wali.email' => 'Format alamat email tidak valid.',
            'nomor_telepon_wali.required' => 'Nomor WhatsApp wali wajib diisi.',
            'user_password.required' => 'Password wajib diisi.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan yang berlaku.',
            'legal_document_id.required' => 'Dokumen persetujuan wajib diisi.',
            'kode_promo.exists' => 'Kode promo tidak ditemukan atau tidak valid.',
        ];

        $validated = $request->validate([
            'nama_siswa' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'user_name' => 'required|string|max:255',
            'email_wali' => 'required|string|email|max:255', // Hapus unique:users,email
            'nomor_telepon_wali' => 'required|string|max:20',
            // Kita hapus confirmed di sini, karena form konfirmasi password mungkin disembunyikan di frontend jika email exists.
            'user_password' => ['required', Rules\Password::defaults()],
            'terms' => 'accepted',
            'legal_document_id' => 'required|exists:legal_documents,id',
            'kode_promo' => 'nullable|string|exists:promos,kode_promo',
        ], $messages);

        // Format proper case
        $validated['nama_siswa'] = \Illuminate\Support\Str::title(strtolower(trim($validated['nama_siswa'])));
        $validated['user_name'] = \Illuminate\Support\Str::title(strtolower(trim($validated['user_name'])));

        $kelas = Kelas::findOrFail($validated['id_kelas']);

        // Cek apakah email sudah ada
        $existingUser = User::where('email', $validated['email_wali'])->with('siswas')->first();

        if ($existingUser) {
            // Verifikasi password
            if (!Hash::check($validated['user_password'], $existingUser->password)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'user_password' => ['Email ini sudah terdaftar. Silakan masukkan password yang benar untuk menambah anak.'],
                ]);
            }

            // Cek duplikasi nama anak
            $existingChildren = $existingUser->siswas->pluck('nama_siswa')->map(fn($name) => strtolower(trim($name)))->toArray();
            if (in_array(strtolower(trim($validated['nama_siswa'])), $existingChildren)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'nama_siswa' => ['Anak dengan nama ini sudah terdaftar di akun Anda.'],
                ]);
            }
        }

        try {
            $invoiceUrl = DB::transaction(function () use ($validated, $kelas, $xenditService, $request, $existingUser) {
                $biayaFinal = $kelas->getBiayaPendaftaranSaatIni($validated['kode_promo']);
                $adminFee = (float)($kelas->admin_fee_custom ?? 0);
                $totalAmount = $biayaFinal + $adminFee;

                if ($existingUser) {
                    $user = $existingUser;
                } else {
                    $user = User::create([
                        'name' => $validated['user_name'],
                        'email' => $validated['email_wali'],
                        'password' => Hash::make($validated['user_password']),
                    ]);
                    $user->assignRole('siswa');
                }

                $siswa = Siswa::create([
                    'nama_siswa' => $validated['nama_siswa'],
                    'tanggal_lahir' => $validated['tanggal_lahir'],
                    'id_kelas' => $kelas->id_kelas,
                    'id_user' => $user->id,
                    'nomor_telepon_wali' => $validated['nomor_telepon_wali'],
                    'status_siswa' => 'pending_payment',
                    'tanggal_bergabung' => now(),
                    'jumlah_spp_custom' => $kelas->biaya_spp_default,
                    'admin_fee_custom' => $adminFee,
                ]);
                $siswa->generateNis();

                UserAgreement::create([
                    'user_id' => $user->id,
                    'id_siswa' => $siswa->id_siswa,
                    'legal_document_id' => $validated['legal_document_id'],
                    'agreed_at' => now(),
                    'ip_address' => $request->ip(),
                ]);

                $deskripsi = "Biaya Pendaftaran - {$siswa->nama_siswa} (NIS: {$siswa->nis})";
                $invoice = Invoice::create([
                    'id_siswa' => $siswa->id_siswa,
                    'user_id' => $user->id,
                    'type' => 'pendaftaran',
                    'description' => $deskripsi,
                    'amount' => $biayaFinal,
                    'admin_fee' => $adminFee,
                    'total_amount' => $totalAmount,
                    'due_date' => now()->addMinutes(30),
                    'status' => 'PENDING',
                    'external_id_xendit' => 'REG-'.$siswa->id_siswa.'-'.strtoupper(Str::random(6)),
                ]);

                $successUrl = route('registration.success', ['siswa' => $siswa->id_siswa]);

                $payerInfo = ['email' => $user->email, 'name' => $user->name, 'phone' => $siswa->nomor_telepon_wali];
                $xenditInvoiceData = $xenditService->createInvoice($biayaFinal, $adminFee, $deskripsi, $payerInfo, $invoice->external_id_xendit, $successUrl, route('payment.failure'), now()->addMinutes(30), ['email', 'whatsapp']);

                if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
                    throw new \Exception('Gagal membuat link pembayaran pendaftaran.');
                }

                $invoice->update([
                    'xendit_invoice_id' => $xenditInvoiceData['id'],
                    'xendit_payment_url' => $xenditInvoiceData['invoice_url'],
                ]);
                
                $dataForEmail = [
                    'nama_wali' => $user->name,
                    'nama_siswa' => $siswa->nama_siswa,
                    'nis' => $siswa->nis,
                    'email_wali' => $user->email,
                ];
                
                try {
                    Mail::to($user->email)->send(new RegistrationSuccess($dataForEmail));
                } catch (\Exception $mailEx) {
                    Log::warning('Gagal mengirim email pendaftaran: ' . $mailEx->getMessage());
                    // Lanjutkan proses meskipun email gagal
                }

                return $xenditInvoiceData['invoice_url'];
            });

            return Inertia::location($invoiceUrl);

        } catch (Throwable $e) {
            Log::error('Gagal memproses pendaftaran: ' . $e->getMessage());
            return back()->withErrors(['general' => 'Terjadi kesalahan saat memproses pendaftaran. Silakan coba lagi.']);
        }
    }
}
