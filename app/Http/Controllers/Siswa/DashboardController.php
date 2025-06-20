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

        // Ambil tagihan berikutnya yang belum lunas
        $upcomingInvoice = $siswa->invoices()
            ->whereIn('status', ['PENDING', 'EXPIRED'])
            ->orderBy('due_date', 'asc')
            ->first();

        // Ambil ringkasan pembayaran
        $totalPaid = $siswa->invoices()->where('status', 'PAID')->sum('total_amount');
        $totalUnpaid = $siswa->invoices()->whereIn('status', ['PENDING', 'EXPIRED'])->sum('total_amount');

        return Inertia::render('Siswa/Dashboard', [
            'pageTitle' => 'Dashboard',
            'siswaName' => $siswa->nama_siswa,
            'upcomingInvoice' => $upcomingInvoice ? [
                'id' => $upcomingInvoice->id,
                'description' => $upcomingInvoice->description,
                'total_amount_formatted' => 'Rp ' . number_format($upcomingInvoice->total_amount, 0, ',', '.'),
                'due_date_formatted' => Carbon::parse($upcomingInvoice->due_date)->isoFormat('D MMMM YYYY'),
                'can_pay' => in_array($upcomingInvoice->status, ['PENDING']),
                'xendit_payment_url' => $upcomingInvoice->xendit_payment_url,
                'status' => $upcomingInvoice->status,
            ] : null,
            'paymentSummary' => [
                'total_paid_formatted' => 'Rp ' . number_format($totalPaid, 0, ',', '.'),
                'total_unpaid_formatted' => 'Rp ' . number_format($totalUnpaid, 0, ',', '.'),
                'total_paid_count' => $siswa->invoices()->where('status', 'PAID')->count(),
                'total_unpaid_count' => $siswa->invoices()->whereIn('status', ['PENDING', 'EXPIRED'])->count(),
            ],
        ]);
    }
}
