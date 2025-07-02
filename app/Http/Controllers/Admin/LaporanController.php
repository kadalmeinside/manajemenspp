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
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }
        $user = $request->user();
        $selectedTahun = $request->input('tahun', now()->year);
        $selectedKelasId = $request->input('kelas_id');

        $siswaQuery = Siswa::query()
            ->with('kelas')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_siswa', 'asc');

        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
            
            // Filter siswa hanya dari kelas yang dikelola
            $siswaQuery->whereIn('id_kelas', $managedKelasIds);

            // Jika admin kelas memilih filter kelas, pastikan itu adalah kelas yang dia kelola
            if ($selectedKelasId && !$managedKelasIds->contains($selectedKelasId)) {
                abort(403, 'Anda tidak memiliki akses ke kelas ini.');
            }
        } else {
            // Jika super admin, filter seperti biasa
            if ($selectedKelasId) {
                $siswaQuery->where('id_kelas', $selectedKelasId);
            }
        }

        $siswas = $siswaQuery->get();
        $siswaIds = $siswas->pluck('id_siswa');

        $invoices = Invoice::whereIn('id_siswa', $siswaIds)
            ->whereYear('periode_tagihan', $selectedTahun)
            ->where('type', 'spp')
            ->get()
            ->keyBy(function ($item) {
                return $item->id_siswa . '-' . Carbon::parse($item->periode_tagihan)->month;
            });

        $laporanData = $siswas->map(function ($siswa) use ($invoices) {
            $statuses = [];
            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $key = $siswa->id_siswa . '-' . $bulan;
                if (isset($invoices[$key])) {
                    $statuses[$bulan] = $invoices[$key]->status; 
                } else {
                    $statuses[$bulan] = 'N/A'; 
                }
            }
            return [
                'id_siswa' => $siswa->id_siswa,
                'nama_siswa' => $siswa->nama_siswa,
                'nama_kelas' => $siswa->kelas->nama_kelas ?? 'N/A',
                'statuses' => $statuses,
            ];
        });

        $allKelasQuery = Kelas::orderBy('nama_kelas');
        if ($user->hasRole('admin_kelas')) {
            $allKelasQuery->whereIn('id_kelas', $user->managedClasses()->pluck('kelas.id_kelas'));
        }
        
        $allKelas = $allKelasQuery->get(['id_kelas', 'nama_kelas']);
        $years = range(date('Y'), date('Y') - 5);

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