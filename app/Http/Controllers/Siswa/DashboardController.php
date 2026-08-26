<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use App\Traits\HandlesActiveSiswa;

class DashboardController extends Controller
{
    use HandlesActiveSiswa;
    /**
     * Menampilkan halaman dashboard untuk siswa yang login.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Ambil semua siswa yang terhubung dengan user ini (status Aktif/Cuti)
        $siswas = $user->siswas()
            ->whereNotIn('status_siswa', ['Keluar', 'Non-Aktif'])
            ->with(['kelas']) // Eager load kelas jika dibutuhkan
            ->get();

        if ($siswas->isEmpty()) {
            return Inertia::render('Siswa/Dashboard', [
                'pageTitle' => 'Dashboard Keluarga',
                'errorMessage' => 'Data siswa tidak dapat ditemukan untuk akun Anda.'
            ]);
        }

        // Kumpulkan data ringkasan untuk setiap siswa
        $familySummary = $siswas->map(function ($siswa) {
            // Tagihan tertunggak (PENDING / EXPIRED sampai bulan ini)
            $overdueInvoices = $siswa->invoices()
                ->where('type', 'spp')
                ->whereIn('status', ['PENDING', 'EXPIRED'])
                ->where('periode_tagihan', '<=', now())
                ->orderBy('periode_tagihan', 'asc')
                ->get();
                
            $overdueTotalAmount = $overdueInvoices->sum('total_amount');
            
            // Pesanan Toko yang PENDING
            $pendingStoreOrders = $siswa->orders()
                ->where('status', 'PENDING')
                ->orderBy('created_at', 'asc')
                ->get();
                
            $pendingStoreTotalAmount = $pendingStoreOrders->sum('total_amount');
            
            $totalPaidQuery = $siswa->invoices()->where('type', 'spp')->where('status', 'PAID');
            $totalUnpaidQuery = $siswa->invoices()
                ->where('type', 'spp')
                ->whereIn('status', ['PENDING', 'EXPIRED'])
                ->where('periode_tagihan', '<=', now()->endOfMonth());
            
            // Jangan gabungkan total SPP + Toko. SPP terisolasi di overdueTotal
            $combinedOverdueTotalAmount = $overdueTotalAmount;
            $combinedOverdueCount = $overdueInvoices->count();

            return [
                'id_siswa' => $siswa->id_siswa,
                'nama_siswa' => $siswa->nama_siswa,
                'kelas' => $siswa->kelas ? $siswa->kelas->nama_kelas : 'Tanpa Kelas',
                'nis' => $siswa->nis,
                'overdueInvoices' => $overdueInvoices->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'type' => 'spp',
                        'description' => $invoice->description,
                        'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                        'periode_formatted' => Carbon::parse($invoice->periode_tagihan)->isoFormat('MMMM YYYY'),
                    ];
                }),
                'pendingStoreOrders' => $pendingStoreOrders->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'type' => 'store',
                        'description' => 'Tagihan Toko: ' . $order->order_number,
                        'total_amount_formatted' => 'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                        'periode_formatted' => $order->created_at->isoFormat('DD MMMM YYYY'),
                    ];
                }),
                'overdueTotal' => [
                    'amount' => $combinedOverdueTotalAmount,
                    'formatted' => 'Rp ' . number_format($combinedOverdueTotalAmount, 0, ',', '.'),
                    'count' => $combinedOverdueCount,
                ],
                'paymentSummary' => [
                    'total_paid_formatted' => 'Rp ' . number_format($totalPaidQuery->sum('total_amount'), 0, ',', '.'),
                    'total_unpaid_formatted' => 'Rp ' . number_format($totalUnpaidQuery->sum('total_amount'), 0, ',', '.'),
                    'total_paid_count' => $totalPaidQuery->count(),
                    'total_unpaid_count' => $totalUnpaidQuery->count(),
                ],
            ];
        });

        // Hitung total gabungan (semua anak)
        $grandTotalOverdue = $familySummary->sum(fn($s) => $s['overdueTotal']['amount']);
        $grandTotalCount = $familySummary->sum(fn($s) => $s['overdueTotal']['count']);

        return Inertia::render('Siswa/Dashboard', [
            'pageTitle' => 'Dashboard Keluarga',
            'familySummary' => $familySummary,
            'grandTotal' => [
                'formatted' => 'Rp ' . number_format($grandTotalOverdue, 0, ',', '.'),
                'count' => $grandTotalCount
            ],
        ]);
    }
}
