<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\InsufficientSppDataException;
use App\Http\Controllers\Controller;
use App\Mail\RegistrationSuccess;
use App\Models\Invoice;
use App\Models\Siswa;
use App\Models\User;
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
use Inertia\Response;
use Throwable;

class CekSppController extends Controller
{
    /**
     * Menampilkan form awal untuk cek SPP.
     */
    public function showForm(Request $request): Response
    {
        return Inertia::render('Public/CekSpp', [
            'pageTitle' => 'Cek SPP Siswa',
            'searchedPhone' => $request->old('nomor_telepon_wali'),
        ]);
    }

    /**
     * Mencari siswa berdasarkan nomor telepon dan menampilkan daftar pilihan.
     */
    public function findSiswaByPhone(Request $request)
    {
        $validated = $request->validate([
            'nomor_telepon_wali' => 'required|string',
        ]);

        $phoneInput = $validated['nomor_telepon_wali'];
        // Normalisasi nomor telepon untuk pencarian yang lebih fleksibel
        $normalizedPhone = preg_replace('/[\s\-\+]/', '', $phoneInput);
        $possibleFormats = [
            $normalizedPhone,
            '0' . ltrim($normalizedPhone, '62'),
            '62' . ltrim($normalizedPhone, '0')
        ];

        $foundSiswa = Siswa::whereIn('nomor_telepon_wali', array_unique($possibleFormats))
                           ->whereNotIn('status_siswa', ['Keluar', 'Non-Aktif'])
                           ->with('kelas:id_kelas,nama_kelas')
                           ->get();

        if ($foundSiswa->isEmpty()) {
            return Redirect::back()->withInput()->withErrors(['lookup' => 'Nomor telepon tidak terdaftar atau siswa berstatus tidak aktif.']);
        }

        // Jika hanya ditemukan satu siswa, langsung arahkan ke halaman tagihan
        if ($foundSiswa->count() === 1) {
            $siswa = $foundSiswa->first();
            session(['verified_spp_siswa_id' => $siswa->id_siswa]);
            return Redirect::route('tagihan.spp.show', $siswa->id_siswa);
        }

        // Jika lebih dari satu, tampilkan halaman pilihan
        return Inertia::render('Public/CekSpp', [
            'pageTitle' => 'Pilih Siswa',
            'foundSiswa' => $foundSiswa->map(fn($siswa) => [
                'id_siswa' => $siswa->id_siswa,
                'nama_siswa' => $siswa->nama_siswa,
                'kelas_nama' => $siswa->kelas?->nama_kelas ?? 'Tanpa Kelas',
            ]),
            'searchedPhone' => $phoneInput,
        ]);
    }

    /**
     * Memproses pemilihan siswa jika ada lebih dari satu siswa dengan nomor yang sama.
     */
    public function selectSiswa(Request $request)
    {
        $validated = $request->validate([
            'id_siswa' => 'required|uuid|exists:siswa,id_siswa'
        ]);

        session(['verified_spp_siswa_id' => $validated['id_siswa']]);
        return Redirect::route('tagihan.spp.show', $validated['id_siswa']);
    }

    /**
     * Menampilkan halaman tagihan lengkap untuk siswa yang dipilih.
     */
    public function showTagihan(Request $request, Siswa $siswa)
    {
        if (session('verified_spp_siswa_id') !== $siswa->id_siswa) {
            return Redirect::route('tagihan.spp.form')->withErrors(['error' => 'Sesi Anda tidak valid atau telah berakhir. Silakan cari kembali data siswa.']);
        }

        $siswa->load('user');

        $pendingSppInvoices = $siswa->invoices()
                               ->where('type', 'spp')->where('status', 'PENDING')
                               ->orderBy('periode_tagihan', 'asc')->get();

        $paidSppInvoices = $siswa->invoices()
            ->where('type', 'spp')->where('status', 'PAID')
            ->orderBy('periode_tagihan', 'asc')->get();

        // Riwayat pembayaran: semua invoice non-pending (PAID, EXPIRED, FAILED, SETTLED)
        $historySppInvoices = $siswa->invoices()
            ->where('type', 'spp')
            ->whereNotIn('status', ['PENDING'])
            ->orderBy('periode_tagihan', 'desc')
            ->get();

        $lastPaidInvoice = $paidSppInvoices->last();
        
        // Tentukan periode terakhir yang relevan (mana yang lebih baru)
        $lastPendingPeriod = $pendingSppInvoices->last()?->periode_tagihan;
        $lastPaidPeriod    = $lastPaidInvoice?->periode_tagihan;
        $lastPeriod        = null;
        if ($lastPendingPeriod && $lastPaidPeriod) {
            $lastPeriod = $lastPendingPeriod->isAfter($lastPaidPeriod) ? $lastPendingPeriod : $lastPaidPeriod;
        } else {
            $lastPeriod = $lastPendingPeriod ?: $lastPaidPeriod;
        }

        $pendingLeaves = \App\Models\StudentLeave::where('id_siswa', $siswa->id_siswa)
            ->whereIn('status', ['pending', 'approved'])
            ->get();
        $pendingLeaveMonths = $pendingLeaves->map(fn($leave) => $leave->year . '-' . $leave->month);

        $missingAgreement = null;
        $settings = \App\Models\Setting::whereIn('key', [
            'legal_doc_registration_academy',
            'legal_doc_registration_ss',
            'legal_doc_registration_public',
        ])->pluck('value', 'key');

        $requiredDocId = null;
        $siswa->load('kelas');
        if ($siswa->kelas) {
            if ($siswa->kelas->nama_kelas === 'Persija Academy') {
                $requiredDocId = $settings['legal_doc_registration_academy'] ?? null;
            } elseif ($siswa->kelas->deskripsi === 'Soccer School') {
                $requiredDocId = $settings['legal_doc_registration_ss'] ?? null;
            } else {
                $requiredDocId = $settings['legal_doc_registration_public'] ?? null;
            }
        }

        if (!$requiredDocId) {
            $fallback = \App\Models\LegalDocument::where('type', 'terms_and_conditions')
                            ->whereNotNull('published_at')
                            ->latest('version')
                            ->first();
            $requiredDocId = $fallback ? $fallback->id : null;
        }

        if ($requiredDocId) {
            $hasAgreed = \App\Models\UserAgreement::where('id_siswa', $siswa->id_siswa)
                ->where('legal_document_id', $requiredDocId)
                ->exists();

            if (!$hasAgreed) {
                $missingAgreement = [
                    'document' => \App\Models\LegalDocument::find($requiredDocId),
                    'siswa' => [[
                        'id_siswa' => $siswa->id_siswa,
                        'nama_siswa' => $siswa->nama_siswa,
                    ]]
                ];
            }
        }

        return Inertia::render('Public/CekSpp', [
            'pageTitle'    => 'Tagihan SPP',
            'missing_agreement' => $missingAgreement,
            'selectedSiswa' => [
                'id_siswa'        => $siswa->id_siswa,
                'nama_siswa'      => $siswa->nama_siswa,
                'nis'             => $siswa->nis,
                'jumlah_spp_custom' => (float) $siswa->jumlah_spp_custom,
                'admin_fee_custom'  => (float) $siswa->admin_fee_custom,
                'has_user_account'  => $siswa->user()->exists(),
                'mulai_spp_date'    => $siswa->mulai_spp_date ? $siswa->mulai_spp_date->format('Y-m-d') : null,
            ],
            'sppInvoices'  => $pendingSppInvoices->map(fn($invoice) => [
                'id'                     => $invoice->id,
                'description'            => $invoice->description,
                'total_amount'           => (float) $invoice->total_amount,
                'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                'status'                 => $invoice->status,
                'periode_tagihan'        => $invoice->periode_tagihan->format('Y-m-d'),
                'is_projected'           => false,
            ]),
            // Riwayat pembayaran untuk tab History
            'historyInvoices' => $historySppInvoices->map(fn($invoice) => [
                'id'                     => $invoice->id,
                'description'            => $invoice->description,
                'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                'status'                 => $invoice->status,
                'periode_tagihan'        => $invoice->periode_tagihan->format('Y-m-d'),
                'payment_method'         => $invoice->payment_method,
                'paid_at_formatted'      => $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->isoFormat('D MMM YYYY, HH:mm') : null,
            ]),
            'paidMonths'         => $paidSppInvoices->map(fn($invoice) => $invoice->periode_tagihan->format('Y-n')),
            'pendingLeaveMonths' => $pendingLeaveMonths,
            'lastPeriod'         => $lastPeriod ? $lastPeriod->format('Y-m-d') : null,
            'active_gateway'     => config('payment.active_gateway') ?? \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit',
        ]);
    }
    
    /**
     * Membuat akun user baru dan menautkannya ke data siswa.
     */
    public function createUserAndLink(Request $request, Siswa $siswa)
    {
        if (session('verified_spp_siswa_id') !== $siswa->id_siswa) {
            return Redirect::route('tagihan.spp.form')->withErrors(['error' => 'Sesi tidak valid.']);
        }

        if ($siswa->user()->exists()) {
            return Redirect::back()->withErrors(['form' => 'Siswa ini sudah memiliki akun.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        
        $siswa->update(['id_user' => $user->id]);
        $user->assignRole('siswa');

        $dataForEmail = [
            'nama_wali' => $user->name,
            'nama_siswa' => $siswa->nama_siswa,
            'nis' => $siswa->nis,
            'login_url' => route('login'),
            'email_wali' => $user->email,
        ];

        try {
            Mail::to($user->email)->send(new RegistrationSuccess($dataForEmail));
        } catch (\Exception $e) {
            Log::error('Gagal mengirim email pendaftaran: ' . $e->getMessage());
        }

        return Redirect::route('tagihan.spp.show', $siswa->id_siswa)->with([
            'type' => 'success',
            'message' => 'Akun berhasil dibuat! Anda sekarang bisa login melalui halaman utama.'
        ]);
    }

    /**
     * Membuat pembayaran gabungan untuk siswa yang dipilih.
     */
    public function createSppPayment(Request $request, Siswa $siswa, \App\Services\PaymentService $paymentService)
    {
        if (session('verified_spp_siswa_id') !== $siswa->id_siswa) {
            return Redirect::route('tagihan.spp.form')->withErrors(['error' => 'Sesi tidak valid.']);
        }

        $validated = $request->validate([
            'periods' => 'required|array|min:1',
            'periods.*' => 'required|date_format:Y-m-d',
            'paymentType' => 'nullable|string',
            'bankCode' => 'nullable|string',
        ]);
        
        $periods = collect($validated['periods'])->sort()->values();
        $paymentType = $validated['paymentType'] ?? null;
        $bankCode = $validated['bankCode'] ?? null;

        try {
            $parentInvoice = $paymentService->createUnifiedPayment($siswa, $periods, null, $paymentType, $bankCode);

            $activeGateway = config('payment.active_gateway') ?? \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';
            
            if ($activeGateway === 'gapura') {
                return Inertia::location(route('tagihan.spp.custom_pay', ['invoice' => $parentInvoice->id]));
            }

            return Inertia::location($parentInvoice->xendit_payment_url);

        } catch (InsufficientSppDataException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat pembayaran SPP: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan halaman sukses setelah pembayaran SPP.
     */
    public function showSuccess(Request $request, Siswa $siswa): Response
    {
        $invoice = null;
        if ($request->has('invoice_id')) {
            $invoice = \App\Models\Invoice::find($request->invoice_id);
        }

        return Inertia::render('Public/SppSuccess', [
            'pageTitle' => 'Pembayaran Berhasil',
            'siswaName' => $siswa->nama_siswa,
            'siswaNis'  => $siswa->nis,
            'invoice' => $invoice,
        ]);
    }
}

