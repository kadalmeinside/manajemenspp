<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Invoice;
use Inertia\Inertia;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function pembayaranBulanan(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) { // Sesuaikan permission jika perlu
            abort(403);
        }

        // 1. Tentukan filter tahun dan kelas
        $selectedTahun = $request->input('tahun', now()->year);
        $selectedKelasId = $request->input('kelas_id');

        // 2. Ambil query builder untuk siswa, terapkan filter jika ada
        $siswaQuery = Siswa::query()
            ->with('kelas')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_siswa', 'asc');

        if ($selectedKelasId) {
            $siswaQuery->where('id_kelas', $selectedKelasId);
        }

        $siswas = $siswaQuery->get();
        $siswaIds = $siswas->pluck('id_siswa');

        // 3. Ambil semua invoice SPP untuk siswa-siswa tersebut pada tahun yang dipilih
        $invoices = Invoice::whereIn('id_siswa', $siswaIds)
            ->whereYear('periode_tagihan', $selectedTahun)
            ->where('type', 'spp') // <-- FILTER PENTING: Hanya ambil invoice tipe 'spp'
            ->get()
            ->keyBy(function ($item) {
                // Buat key unik: 'idSiswa-bulan'
                return $item->id_siswa . '-' . Carbon::parse($item->periode_tagihan)->month;
            });

        // 4. Proses dan struktur data untuk dikirim ke view
        $laporanData = $siswas->map(function ($siswa) use ($invoices) {
            $statuses = [];
            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $key = $siswa->id_siswa . '-' . $bulan;
                // Cek apakah ada invoice untuk siswa ini di bulan ini
                if (isset($invoices[$key])) {
                    $statuses[$bulan] = $invoices[$key]->status; // Gunakan kolom 'status'
                } else {
                    $statuses[$bulan] = 'N/A'; // Not Available
                }
            }
            return [
                'id_siswa' => $siswa->id_siswa,
                'nama_siswa' => $siswa->nama_siswa,
                'nama_kelas' => $siswa->kelas->nama_kelas ?? 'N/A',
                'statuses' => $statuses, // Array status dari bulan 1 s/d 12
            ];
        });

        // 5. Data untuk filter dropdown di frontend
        $allKelas = Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas']);
        $years = range(date('Y'), date('Y') - 5);

        // 6. Kirim semua data ke komponen Vue
        return Inertia::render('Admin/Laporan/PembayaranBulanan', [
            'laporanData' => $laporanData,
            'allKelas' => $allKelas,
            'years' => $years,
            'filters' => [
                'tahun' => (int)$selectedTahun,
                'kelas_id' => $selectedKelasId,
            ]
        ]);
    }
}