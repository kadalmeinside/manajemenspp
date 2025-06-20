<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\TagihanSpp;
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
            'pageTitle' => 'Cek Tagihan Pembayaran',
        ]);
    }

    /**
     * Memproses form dan menampilkan status tagihan.
     */
    public function checkStatus(Request $request)
    {
        $validated = $request->validate([
            'id_siswa' => 'required|string|max:50',
            'tanggal_lahir' => 'required|date',
        ]);

        $siswa = Siswa::where('id_siswa', $validated['id_siswa']) 
                     ->whereDate('tanggal_lahir', $validated['tanggal_lahir'])
                     ->first();

        if (!$siswa) {
            return back()->withErrors(['lookup' => 'Kombinasi ID Siswa dan Tanggal Lahir tidak ditemukan.']);
        }

        $tagihanList = TagihanSpp::where('id_siswa', $siswa->id_siswa)
            ->orderBy('periode_tagihan', 'desc')
            ->get();

        return Inertia::render('Public/CekTagihan', [
            'pageTitle' => 'Status Tagihan - ' . $siswa->nama_siswa,
            'siswa' => [
                'nama_siswa' => $siswa->nama_siswa,
                'id_siswa' => $siswa->id_siswa,
            ],
            'tagihanList' => $tagihanList->map(function ($tagihan) {
                return [
                    'periode_tagihan' => Carbon::parse($tagihan->periode_tagihan)->isoFormat('MMMM YYYY'),
                    'total_tagihan_formatted' => 'Rp ' . number_format($tagihan->total_tagihan, 0, ',', '.'),
                    'status_pembayaran_xendit' => $tagihan->status_pembayaran_xendit,
                    'xendit_payment_url' => $tagihan->xendit_payment_url,
                    'can_pay' => in_array($tagihan->status_pembayaran_xendit, ['PENDING']),
                ];
            }),
        ]);
    }
}
