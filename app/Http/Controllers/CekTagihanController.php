<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CekTagihanController extends Controller
{
    /**
     * Menampilkan form untuk cek tagihan.
     */
    public function showForm()
    {
        return Inertia::render('Public/CekTagihan', [
            'pageTitle' => 'Cek Tagihan Soccer School',
        ]);
    }

    /**
     * Memproses form dan menampilkan status tagihan.
     */
    public function checkStatus(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|string|exists:siswa,nis',
            'tanggal_lahir' => 'required|date',
        ]);

        $siswa = Siswa::where('nis', $validated['nis'])
                     ->whereDate('tanggal_lahir', $validated['tanggal_lahir'])
                     ->first();

        if (!$siswa) {
            return back()->withErrors(['lookup' => 'Kombinasi NIS dan Tanggal Lahir tidak ditemukan.']);
        }

        $invoiceList = $siswa->invoices()->orderBy('created_at', 'asc')->get();

        return Inertia::render('Public/CekTagihan', [
            'pageTitle' => 'Status Tagihan - ' . $siswa->nama_siswa,
            'siswa' => [
                'nama_siswa' => $siswa->nama_siswa,
                'nis' => $siswa->nis,
            ],
            'invoiceList' => $invoiceList->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'description' => $invoice->description,
                    'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                    'status' => $invoice->status,
                    'due_date_formatted' => Carbon::parse($invoice->due_date)->isoFormat('D MMMM YYYY'),
                    'can_pay' => in_array($invoice->status, ['PENDING']),
                    'xendit_payment_url' => $invoice->xendit_payment_url,
                ];
            }),
        ]);
    }
}
