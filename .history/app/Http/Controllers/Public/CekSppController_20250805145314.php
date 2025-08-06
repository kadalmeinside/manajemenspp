<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\InsufficientSppDataException;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Siswa;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class CekSppController extends Controller
{
    /**
     * Menampilkan form awal untuk input nomor telepon.
     */
    public function showForm(): Response
    {
        return Inertia::render('Public/CekSpp', [
            'pageTitle' => 'Cek SPP Siswa',
        ]);
    }

    /**
     * Mencari semua siswa berdasarkan nomor telepon dan menampilkan daftar pilihan.
     */
    public function findSiswaByPhone(Request $request)
    {
        $validated = $request->validate([
            'nomor_telepon_wali' => 'required|string',
        ]);

        $phoneInput = $validated['nomor_telepon_wali'];
        $normalizedPhone = preg_replace('/[\s\-\+]/', '', $phoneInput);

        $siswas = Siswa::where(function ($query) use ($normalizedPhone) {
                $query->where('nomor_telepon_wali', $normalizedPhone)
                      ->orWhere('nomor_telepon_wali', '0' . ltrim($normalizedPhone, '62'))
                      ->orWhere('nomor_telepon_wali', '62' . ltrim($normalizedPhone, '0'));
            })
            ->with('kelas:id_kelas,nama_kelas')
            ->orderBy('nama_siswa')
            ->get();

        if ($siswas->isEmpty()) {
            return Redirect::back()->withErrors(['lookup' => 'Nomor telepon tidak terdaftar. Pastikan nomor yang Anda masukkan sudah benar.']);
        }

        // Kirim daftar siswa yang ditemukan ke halaman yang sama
        return Inertia::render('Public/CekSpp', [
            'pageTitle' => 'Pilih Siswa',
            'foundSiswa' => $siswas->map(fn($siswa) => [
                'id_siswa' => $siswa->id_siswa,
                'nama_siswa' => $siswa->nama_siswa,
                'kelas_nama' => $siswa->kelas?->nama_kelas ?? 'Belum ada kelas',
            ]),
            'searchedPhone' => $phoneInput,
        ]);
    }

    /**
     * Menampilkan halaman tagihan lengkap untuk siswa yang dipilih.
     */
    public function showTagihan(Siswa $siswa)
    {
        $siswa->load('user');

        $pendingSppInvoices = $siswa->invoices()->where('type', 'spp')->where('status', 'PENDING')->orderBy('periode_tagihan', 'asc')->get();
        $lastPaidInvoice = $siswa->invoices()->where('type', 'spp')->where('status', 'PAID')->orderBy('periode_tagihan', 'desc')->first();

        return Inertia::render('Public/CekSpp', [
            'pageTitle' => 'Tagihan SPP',
            'selectedSiswa' => [
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
            'lastPaidPeriod' => $lastPaidInvoice ? $lastPaidInvoice->periode_tagihan->format('Y-m-d') : null,
        ]);
    }

    /**
     * Membuat pembayaran gabungan untuk alur Cek SPP.
     */
    public function createSppPayment(Request $request, Siswa $siswa, XenditService $xenditService)
    {
        $validated = $request->validate([
            'periods' => 'required|array|min:1',
            'periods.*' => 'required|date_format:Y-m-d',
        ]);

        $periods = collect($validated['periods'])->sort()->values();

        try {
            $parentInvoice = DB::transaction(function () use ($periods, $siswa, $xenditService, $request) {
                
                $oldParentInvoices = Invoice::where('id_siswa', $siswa->id_siswa)
                    ->where('type', 'pembayaran_spp_gabungan')
                    ->where('status', 'PENDING')
                    ->get();

                foreach ($oldParentInvoices as $oldParent) {
                    if ($oldParent->xendit_invoice_id) {
                        $xenditService->expireInvoice($oldParent->xendit_invoice_id);
                    }
                    $oldParent->delete();
                }

                $totalSpp = 0;
                $existingInvoices = $siswa->invoices()->whereIn('periode_tagihan', $periods->toArray())->where('type', 'spp')->get();
                $totalSpp += $existingInvoices->sum('total_amount');
                $existingPeriods = $existingInvoices->pluck('periode_tagihan')->map(fn($p) => $p->format('Y-m-d'));
                $projectedPeriods = $periods->diff($existingPeriods);

                if ($projectedPeriods->isNotEmpty()) {
                    $sppPerBulan = (float)($siswa->jumlah_spp_custom ?? 0);
                    if ($sppPerBulan <= 0) throw new InsufficientSppDataException('Data nominal SPP belum diatur.');
                    $totalSpp += $projectedPeriods->count() * $sppPerBulan;
                }

                $adminFee = (float)($siswa->admin_fee_custom ?? 0);
                $totalAmount = $totalSpp + $adminFee;

                if ($totalAmount <= 0) throw new \Exception("Total tagihan tidak valid (Rp 0).");

                Carbon::setLocale('id');
                $startPeriod = Carbon::parse($periods->first());
                $endPeriod = Carbon::parse($periods->last());
                $description = "Pembayaran SPP Gabungan ({$periods->count()} Bulan: {$startPeriod->isoFormat('MMMM YYYY')} - {$endPeriod->isoFormat('MMMM YYYY')}) - {$siswa->nama_siswa} (NIS: {$siswa->nis})";

                $parentInvoice = Invoice::create([
                    'id_siswa' => $siswa->id_siswa,
                    'user_id' => $siswa->user?->id,
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

                $payerInfo = ['email' => $siswa->user?->email, 'name' => $siswa->nama_siswa, 'phone' => $siswa->nomor_telepon_wali];
                
                $xenditInvoiceData = $xenditService->createInvoice($totalSpp, $adminFee, $parentInvoice->description, $payerInfo, $parentInvoice->external_id_xendit, route('payment.success'), route('payment.failure'), now()->addDay());

                if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
                    throw new \Exception('Gagal membuat link pembayaran gabungan di Xendit.');
                }
                
                $parentInvoice->update(['xendit_invoice_id' => $xenditInvoiceData['id'], 'xendit_payment_url' => $xenditInvoiceData['invoice_url']]);

                return $parentInvoice;
            });

            return Inertia::location($parentInvoice->xendit_payment_url);

        } catch (InsufficientSppDataException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('Gagal membuat pembayaran publik: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Terjadi kesalahan sistem, silakan coba lagi nanti.']);
        }
    }
}
