<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class CekSppController extends Controller
{
    /**
     * Menampilkan form awal untuk input nomor telepon.
     */
    public function showForm(): Response
    {
        // Bersihkan session pencarian sebelumnya
        session()->forget('search_phone_number');

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

        // Simpan nomor telepon ke session untuk ditampilkan kembali di form
        session(['search_phone_number' => $phoneInput]);

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
        $siswa->load('user'); // Load relasi user jika ada

        $pendingSppInvoices = $siswa->invoices()
                               ->where('type', 'spp')
                               ->where('status', 'PENDING')
                               ->orderBy('periode_tagihan', 'asc')
                               ->get();

        $lastPaidInvoice = $siswa->invoices()
                                ->where('type', 'spp')
                                ->where('status', 'PAID')
                                ->orderBy('periode_tagihan', 'desc')
                                ->first();

        return Inertia::render('Public/CekSpp', [
            'pageTitle' => 'Tagihan SPP',
            'selectedSiswa' => [ // Kirim data siswa yang sudah terpilih
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
}
