<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard untuk siswa yang login.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $siswa = $user->siswa;

        if (!$siswa) {
            return Inertia::render('Siswa/Dashboard', [
                'pageTitle' => 'Dashboard',
                'errorMessage' => 'Data siswa tidak dapat ditemukan untuk akun Anda.'
            ]);
        }

        // --- PERUBAHAN LOGIKA ---
        // Mengambil SEMUA tagihan tertunggak (bukan hanya satu)
        $overdueInvoices = $siswa->invoices()
            ->where('type', 'spp')
            ->whereIn('status', ['PENDING', 'EXPIRED'])
            ->where('periode_tagihan', '<=', now()) // Kondisi: sampai dengan bulan ini
            ->orderBy('periode_tagihan', 'asc')
            ->get();

        // Menghitung total dari tagihan yang tertunggak
        $overdueTotalAmount = $overdueInvoices->sum('total_amount');
        // --- AKHIR PERUBAHAN LOGIKA ---


        // Ringkasan pembayaran keseluruhan (tetap sama)
        $totalPaid = $siswa->invoices()->where('status', 'PAID')->sum('total_amount');
        $totalUnpaid = $siswa->invoices()->whereIn('status', ['PENDING', 'EXPIRED'])->sum('total_amount');

        return Inertia::render('Siswa/Dashboard', [
            'pageTitle' => 'Dashboard',
            'siswaName' => $siswa->nama_siswa,
            
            // --- PROPS BARU ---
            // Mengirim daftar tagihan tertunggak
            'overdueInvoices' => $overdueInvoices->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'description' => $invoice->description,
                    'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                    'periode_formatted' => Carbon::parse($invoice->periode_tagihan)->isoFormat('MMMM YYYY'),
                ];
            }),
            // Mengirim total nominal tertunggak
            'overdueTotal' => [
                'formatted' => 'Rp ' . number_format($overdueTotalAmount, 0, ',', '.'),
                'count' => $overdueInvoices->count(),
            ],
            // --- AKHIR PROPS BARU ---

            'paymentSummary' => [
                'total_paid_formatted' => 'Rp ' . number_format($totalPaid, 0, ',', '.'),
                'total_unpaid_formatted' => 'Rp ' . number_format($totalUnpaid, 0, ',', '.'),
                'total_paid_count' => $siswa->invoices()->where('status', 'PAID')->count(),
                'total_unpaid_count' => $siswa->invoices()->whereIn('status', ['PENDING', 'EXPIRED'])->count(),
            ],
        ]);
    }
}
