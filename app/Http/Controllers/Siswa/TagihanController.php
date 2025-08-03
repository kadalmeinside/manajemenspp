<?php

namespace App\Http\Controllers\Siswa;

use App\Exceptions\InsufficientSppDataException;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class TagihanController extends Controller
{
    /**
     * Menampilkan daftar tagihan milik siswa yang sedang login.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole(['siswa', 'siswa_wali'])) {
            abort(403);
        }

        $siswa = $user->siswa;
        if (!$siswa) {
            return Inertia::render('Siswa/Tagihan/Index', [
                'sppInvoices' => [],
                'lastPaidPeriod' => null, // Kirim null jika siswa tidak ada
                'siswa' => null,
                'pageTitle' => 'Tagihan Saya',
                'errorMessage' => 'Data siswa tidak dapat ditemukan.'
            ]);
        }

        // 1. Ambil SEMUA invoice SPP yang statusnya PENDING
        $pendingSppInvoices = $siswa->invoices()
                               ->where('type', 'spp')
                               ->where('status', 'PENDING')
                               ->orderBy('periode_tagihan', 'asc')
                               ->get();

        // 2. Cari SATU invoice SPP terakhir yang statusnya PAID
        $lastPaidInvoice = $siswa->invoices()
                                ->where('type', 'spp')
                                ->where('status', 'PAID')
                                ->orderBy('periode_tagihan', 'desc')
                                ->first();

        return Inertia::render('Siswa/Tagihan/Index', [
            // Kirim daftar invoice PENDING yang sudah ada
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
            
            // Kirim periode terakhir yang lunas, atau null jika tidak ada
            'lastPaidPeriod' => $lastPaidInvoice ? $lastPaidInvoice->periode_tagihan->format('Y-m-d') : null,
            
            'siswa' => [
                'id_siswa' => $siswa->id_siswa,
                'jumlah_spp_custom' => (float) $siswa->jumlah_spp_custom,
                'admin_fee_custom' => (float) $siswa->admin_fee_custom,
            ],
            'pageTitle' => 'Tagihan SPP',
        ]);
    }

    // public function index(Request $request): Response
    // {
    //     if (!$request->user()->hasRole(['siswa', 'siswa_wali'])) {
    //         abort(403);
    //     }

    //     $siswa = $request->user()->siswa;
    //     if (!$siswa) {
    //         return Inertia::render('Siswa/Tagihan/Index', [
    //             'tagihanList' => (object)['data' => []],
    //             'pageTitle' => 'Tagihan SPP Saya',
    //             'error_message' => 'Data siswa tidak dapat ditemukan untuk akun Anda.'
    //         ]);
    //     }

    //     $tagihanList = TagihanSpp::where('id_siswa', $siswa->id_siswa)
    //                             ->orderBy('periode_tagihan', 'desc')
    //                             ->paginate(10)->withQueryString();

    //     return Inertia::render('Siswa/Tagihan/Index', [
    //         'tagihanList' => $tagihanList->through(function ($tagihan) {
    //             return [
    //                 'id_tagihan' => $tagihan->id_tagihan,
    //                 'periode_tagihan' => Carbon::parse($tagihan->periode_tagihan)->isoFormat('MMMM YYYY'),
    //                 'total_tagihan_formatted' => 'Rp ' . number_format($tagihan->total_tagihan, 0, ',', '.'),
    //                 'status_pembayaran_xendit' => $tagihan->status_pembayaran_xendit,
    //                 'tanggal_jatuh_tempo' => Carbon::parse($tagihan->tanggal_jatuh_tempo)->isoFormat('D MMMM YYYY'),
    //                 'can_pay' => in_array($tagihan->status_pembayaran_xendit, ['PENDING']),
    //             ];
    //         }),
    //         'pageTitle' => 'Tagihan SPP Saya',
    //         // Kirimkan Xendit Client Key ke frontend
    //         'xendit_client_key' => config('xendit.client_key'),
    //     ]);
    // }

    /**
     * Membuat token sesi pembayaran Xendit Snap.
     */
    public function createPaymentToken(Request $request, Invoice $invoice, XenditService $xenditService)
    {
        if ($request->user()->siswa->id_siswa !== $invoice->siswa_id) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
        }

        if ($invoice->xendit_invoice_id) {
            return response()->json([
                'token' => $invoice->xendit_invoice_id,
            ]);
        }
        
        return response()->json(['error' => 'Gagal membuat sesi pembayaran.'], 500);
    }

    /**
     * Membuat satu invoice induk untuk membayar beberapa invoice anak.
     */
    public function createBulkPayment(Request $request, XenditService $xenditService)
    {
        $userSiswaId = $request->user()->siswa->id_siswa;

        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => ['required', 'uuid', Rule::exists('invoices', 'id')->where('siswa_id', $userSiswaId)],
        ]);

        try {
            $parentInvoice = DB::transaction(function () use ($validated, $xenditService, $request) {
                
                $childInvoices = Invoice::with('siswa.user')
                                    ->whereIn('id', $validated['invoice_ids'])
                                    ->where('status', 'PENDING')
                                    ->lockForUpdate()
                                    ->orderBy('periode_tagihan', 'asc') // Urutkan untuk validasi
                                    ->get();

                if ($childInvoices->isEmpty() || ($childInvoices->count() !== count($validated['invoice_ids']))) {
                    throw new \Exception('Beberapa invoice yang dipilih tidak valid atau sudah dibayar.');
                }
                
                // === VALIDASI PEMBAYARAN BERURUTAN ===
                $allPendingSppInvoices = $childInvoices->first()->siswa->invoices()
                                          ->where('type', 'spp')
                                          ->where('status', 'PENDING')
                                          ->orderBy('periode_tagihan', 'asc')
                                          ->get();
                
                $expectedNextInvoice = $allPendingSppInvoices->first();

                if ($childInvoices->first()->id !== $expectedNextInvoice->id) {
                    throw new \Exception('Pembayaran harus dimulai dari tagihan terlama ('.Carbon::parse($expectedNextInvoice->periode_tagihan)->isoFormat('MMMM YYYY').').');
                }

                for ($i = 0; $i < $childInvoices->count(); $i++) {
                    if ($childInvoices[$i]->id !== $allPendingSppInvoices[$i]->id) {
                        throw new \Exception('Pembayaran tagihan SPP harus dilakukan secara berurutan. Tidak boleh ada bulan yang dilompati.');
                    }
                }
                // === AKHIR VALIDASI ===

                $siswa = $childInvoices->first()->siswa;
                $totalAmount = $childInvoices->sum('total_amount');
                $invoiceCount = $childInvoices->count();

                // Buat invoice induk baru
                $parentInvoice = Invoice::create([
                    'siswa_id' => $siswa->id_siswa,
                    'user_id' => $request->user()->id,
                    'type' => 'pembayaran_gabungan',
                    'description' => "Pembayaran Gabungan untuk {$invoiceCount} tagihan - {$siswa->nama_siswa}",
                    'periode_tagihan' => null,
                    'amount' => $totalAmount,
                    'admin_fee' => 0,
                    'total_amount' => $totalAmount,
                    'due_date' => now()->addDay(),
                    'status' => 'PENDING',
                    'external_id_xendit' => 'BULK-'.$siswa->id_siswa.'-'.strtoupper(Str::random(10)),
                ]);

                // Hubungkan invoice induk dengan invoice anak
                $parentInvoice->childInvoices()->attach($childInvoices->pluck('id'));

                // Buat invoice di Xendit
                $payerInfo = ['email' => $siswa->user?->email, 'name' => $siswa->nama_siswa, 'phone' => $siswa->nomor_telepon_wali];
                
                $xenditInvoiceData = $xenditService->createInvoice(
                    $totalAmount, 0, $parentInvoice->description, $payerInfo,
                    $parentInvoice->external_id_xendit, route('payment.success'),
                    route('payment.failure'), Carbon::parse($parentInvoice->due_date)
                );

                if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
                    throw new \Exception('Gagal membuat link pembayaran gabungan di Xendit.');
                }

                // Update invoice induk dengan detail dari Xendit
                $parentInvoice->update([
                    'xendit_invoice_id' => $xenditInvoiceData['id'],
                    'xendit_payment_url' => $xenditInvoiceData['invoice_url'],
                ]);

                return $parentInvoice;
            });

            // Redirect ke halaman pembayaran Xendit
            return Inertia::location($parentInvoice->xendit_payment_url);

        } catch (Throwable $e) {
            Log::error('Gagal membuat pembayaran gabungan: ' . $e->getMessage());
            return Redirect::back()->withErrors(['bulk_payment_error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function createUnifiedPayment(Request $request, XenditService $xenditService)
    {
        // 1. Validasi input dari frontend
        $validated = $request->validate([
            'periods' => 'required|array|min:1',
            'periods.*' => 'required|date_format:Y-m-d',
        ]);

        $siswa = $request->user()->siswa;
        $periods = collect($validated['periods'])->sort()->values();

        try {
            // 2. Gunakan transaksi database untuk memastikan semua proses aman
            $parentInvoice = DB::transaction(function () use ($periods, $siswa, $xenditService, $request) {
                
                // 3. LOGIKA "BERSIHKAN DAN GANTIKAN"
                // Cari dan hapus semua invoice induk pembayaran sebelumnya yang masih PENDING
                $oldParentInvoices = Invoice::where('id_siswa', $siswa->id_siswa)
                    ->where('type', 'pembayaran_spp_gabungan')
                    ->where('status', 'PENDING')
                    ->get();

                foreach ($oldParentInvoices as $oldParent) {
                    // (Sangat direkomendasikan) Panggil Xendit untuk mematikan link lama
                    if ($oldParent->xendit_invoice_id) {
                        $xenditService->expireInvoice($oldParent->xendit_invoice_id);
                    }
                    // Hapus record dari database Anda
                    $oldParent->delete();
                }

                // 4. Hitung total pembayaran secara akurat
                $totalSpp = 0;

                // Hitung dari invoice yang sudah ada
                $existingInvoices = $siswa->invoices()
                    ->whereIn('periode_tagihan', $periods->toArray())
                    ->where('type', 'spp')
                    ->get();
                $totalSpp += $existingInvoices->sum('total_amount');
                
                // Hitung dari invoice proyeksi (yang belum ada)
                $existingPeriods = $existingInvoices->pluck('periode_tagihan')->map(fn($p) => $p->format('Y-m-d'));
                $projectedPeriods = $periods->diff($existingPeriods);

                if ($projectedPeriods->isNotEmpty()) {
                    $sppPerBulan = (float)($siswa->jumlah_spp_custom ?? 0);
                    if ($sppPerBulan <= 0) {
                        throw new InsufficientSppDataException('Data nominal SPP Anda belum diatur untuk membuat tagihan baru.');
                    }
                    $totalSpp += $projectedPeriods->count() * $sppPerBulan;
                }

                // Tambahkan admin fee satu kali di akhir
                $adminFee = (float)($siswa->admin_fee_custom ?? 0);
                $totalAmount = $totalSpp + $adminFee;

                if ($totalAmount <= 0) {
                    throw new \Exception("Total tagihan tidak valid (Rp 0).");
                }

                // 5. Buat deskripsi yang seragam
                Carbon::setLocale('id');
                $startPeriod = Carbon::parse($periods->first());
                $endPeriod = Carbon::parse($periods->last());
                $description = "Pembayaran SPP Gabungan ({$periods->count()} Bulan: {$startPeriod->isoFormat('MMMM YYYY')} - {$endPeriod->isoFormat('MMMM YYYY')}) - {$siswa->nama_siswa} (NIS: {$siswa->nis})";

                // 6. Buat invoice induk yang baru
                $parentInvoice = Invoice::create([
                    'id_siswa' => $siswa->id_siswa,
                    'user_id' => $request->user()->id,
                    'type' => 'pembayaran_spp_gabungan',
                    'description' => $description,
                    'periode_tagihan' => $startPeriod,
                    'amount' => $totalSpp,
                    'admin_fee' => $adminFee,
                    'total_amount' => $totalAmount,
                    'due_date' => now()->addDay(),
                    'status' => 'PENDING',
                    'external_id_xendit' => 'UNIF-'.$siswa->id_siswa.'-'.strtoupper(Str::random(10)),
                ]);

                // 7. Panggil XenditService dengan rincian biaya
                $payerInfo = ['email' => $siswa->user?->email, 'name' => $siswa->nama_siswa, 'phone' => $siswa->nomor_telepon_wali];
                
                $xenditInvoiceData = $xenditService->createInvoice(
                    $totalSpp,
                    $adminFee,
                    $parentInvoice->description, 
                    $payerInfo,
                    $parentInvoice->external_id_xendit, 
                    route('payment.success'), 
                    route('payment.failure'), 
                    now()->addDay()
                );

                if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
                    throw new \Exception('Gagal membuat link pembayaran gabungan di Xendit.');
                }
                
                $parentInvoice->update([
                    'xendit_invoice_id' => $xenditInvoiceData['id'],
                    'xendit_payment_url' => $xenditInvoiceData['invoice_url'],
                ]);

                return $parentInvoice;
            });

            // 8. Arahkan siswa ke halaman pembayaran Xendit
            return Inertia::location($parentInvoice->xendit_payment_url);

        } catch (InsufficientSppDataException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('Gagal membuat pembayaran terpadu: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'Terjadi kesalahan sistem, silakan coba lagi nanti.']);
        }
    }
}