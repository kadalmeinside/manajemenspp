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

class CheckTagihanController extends Controller
{
    /**
     * Menampilkan form awal untuk cek tagihan.
     */
    public function showCheckForm(): Response
    {
        session()->forget('checked_siswa_id');
        return Inertia::render('Public/CheckTagihan', [
            'pageTitle' => 'Cek Status Pembayaran',
        ]);
    }

    /**
     * Memvalidasi data siswa dan menyimpan ID ke session jika valid.
     */
    public function findSiswa(Request $request)
    {
        $validated = $request->validate([
            'nomor_telepon_wali' => 'required|string',
            'tanggal_lahir' => 'required|date',
        ]);

        $phoneInput = $validated['nomor_telepon_wali'];
        $normalizedPhone = preg_replace('/[\s\-\+]/', '', $phoneInput);

        $siswa = Siswa::where(function ($query) use ($normalizedPhone) {
                $query->where('nomor_telepon_wali', $normalizedPhone)
                      ->orWhere('nomor_telepon_wali', '0' . ltrim($normalizedPhone, '62'))
                      ->orWhere('nomor_telepon_wali', '62' . ltrim($normalizedPhone, '0'));
            })
            ->whereDate('tanggal_lahir', $validated['tanggal_lahir'])
            ->first();

        if (!$siswa) {
            return Redirect::back()->withErrors(['lookup' => 'Data siswa tidak ditemukan. Pastikan No. Telepon dan Tanggal Lahir sudah benar.']);
        }

        session(['checked_siswa_id' => $siswa->id_siswa]);
        return Redirect::route('tagihan.check_result');
    }

    /**
     * Menampilkan halaman hasil: tagihan dan form profil jika diperlukan.
     */
    public function showResult(Request $request)
    {
        $siswaId = session('checked_siswa_id');
        if (!$siswaId) {
            return Redirect::route('tagihan.check_form');
        }

        $siswa = Siswa::with('user')->findOrFail($siswaId);

        // Ambil daftar invoice PENDING yang sudah dibuat admin
        $pendingSppInvoices = $siswa->invoices()
                               ->where('type', 'spp')
                               ->where('status', 'PENDING')
                               ->orderBy('periode_tagihan', 'asc')
                               ->get();

        // ### PERBAIKAN ###: Cari juga invoice terakhir yang sudah lunas
        $lastPaidInvoice = $siswa->invoices()
                                ->where('type', 'spp')
                                ->where('status', 'PAID')
                                ->orderBy('periode_tagihan', 'desc')
                                ->first();

        return Inertia::render('Public/CheckTagihan', [
            'pageTitle' => 'Status Pembayaran',
            'siswa' => [
                'id_siswa' => $siswa->id_siswa,
                'nama_siswa' => $siswa->nama_siswa,
                'nis' => $siswa->nis,
                'jumlah_spp_custom' => (float) $siswa->jumlah_spp_custom,
                'admin_fee_custom' => (float) $siswa->admin_fee_custom,
                'has_user_account' => $siswa->user()->exists(),
            ],
            'sppInvoices' => $pendingSppInvoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'description' => $invoice->description,
                    'total_amount' => (float) $invoice->total_amount,
                    'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                    'status' => $invoice->status,
                    'periode_tagihan' => $invoice->periode_tagihan->format('Y-m-d'),
                    'is_projected' => false,
                ];
            }),
            // ### PERBAIKAN ###: Kirim periode terakhir yang lunas ke frontend
            'lastPaidPeriod' => $lastPaidInvoice ? $lastPaidInvoice->periode_tagihan->format('Y-m-d') : null,
        ]);
    }

    /**
     * Membuat akun user baru dan menautkannya ke data siswa.
     */
    public function createUserAndLink(Request $request)
    {
        $siswaId = session('checked_siswa_id');
        if (!$siswaId) {
            return Redirect::route('tagihan.check_form');
        }
        
        $siswa = Siswa::findOrFail($siswaId);
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

        // ### PERBAIKAN ###
        // Mengubah kunci 'user_name' menjadi 'nama_wali' agar sesuai dengan template email
        $dataForEmail = [
            'nama_wali' => $user->name, // <-- Kunci diubah di sini
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

        return Redirect::route('tagihan.check_result')
            ->with('type', 'success')
            ->with('message', 'Akun berhasil dibuat! Silakan masuk untuk melanjutkan.');
    }
    
    /**
     * Membuat pembayaran gabungan untuk pengguna publik (non-login).
     */
    public function createPublicPayment(Request $request, \App\Services\PaymentService $paymentService)
    {
        $siswaId = session('checked_siswa_id');
        if (!$siswaId) {
            return Redirect::route('tagihan.check_form')->withErrors(['error' => 'Sesi Anda telah berakhir, silakan cari data siswa kembali.']);
        }

        $validated = $request->validate([
            'periods' => 'required|array|min:1',
            'periods.*' => 'required|date_format:Y-m-d',
        ]);

        $siswa = Siswa::with('user')->findOrFail($siswaId);
        $periods = collect($validated['periods'])->sort()->values();

        try {
            // Gunakan PaymentService (refactored dari kode yang duplicate)
            $parentInvoice = $paymentService->createUnifiedPayment($siswa, $periods, $siswa->user?->id);

            return Inertia::location($parentInvoice->xendit_payment_url);

        } catch (\App\Exceptions\InsufficientSppDataException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat pembayaran publik: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan sistem, silakan coba lagi nanti.']);
        }
    }
}
