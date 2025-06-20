<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class TagihanController extends Controller
{
    /**
     * Menampilkan daftar tagihan milik siswa yang sedang login.
     */
    public function index(Request $request): Response
    {
        if (!$request->user()->hasRole(['siswa', 'siswa_wali'])) {
            abort(403);
        }

        $siswa = $request->user()->siswa;
        if (!$siswa) {
            return Inertia::render('Siswa/Tagihan/Index', [
                'invoiceList' => (object)['data' => [], 'links' => []],
                'pageTitle' => 'Tagihan Saya',
                'error_message' => 'Data siswa tidak dapat ditemukan untuk akun Anda.'
            ]);
        }

        // PERUBAHAN: Gunakan relasi invoices() dari model Siswa
        $invoiceList = $siswa->invoices()
                             ->orderBy('created_at', 'desc')
                             ->paginate(10)
                             ->withQueryString();

        return Inertia::render('Siswa/Tagihan/Index', [
            // PERUBAHAN: Ganti nama prop menjadi 'invoiceList' untuk konsistensi
            'invoiceList' => $invoiceList->through(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'description' => $invoice->description,
                    'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                    'status' => $invoice->status,
                    'due_date_formatted' => Carbon::parse($invoice->due_date)->isoFormat('D MMMM YYYY'),
                    'xendit_payment_url' => $invoice->xendit_payment_url,
                    'can_pay' => in_array($invoice->status, ['PENDING']),
                ];
            }),
            'pageTitle' => 'Tagihan Saya',
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
}