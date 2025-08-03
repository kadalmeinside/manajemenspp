<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Invoice;
use App\Models\JobBatch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tahun' => 'nullable|integer|date_format:Y',
            'bulan' => 'nullable|integer|between:1,12',
        ]);
        
        $user = $request->user();
        $selectedTahun = $request->input('tahun', now()->year);
        $selectedBulan = $request->input('bulan', now()->month);
        $selectedDate = Carbon::create($selectedTahun, $selectedBulan, 1);
        $previousMonthDate = $selectedDate->copy()->subMonth();

        $managedKelasIds = null;
        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
        }

        $calculateChange = function ($current, $previous) {
            if ($previous == 0) { return $current > 0 ? 100.0 : 0.0; }
            return (($current - $previous) / $previous) * 100;
        };

        $incomeInvoiceTypes = ['spp', 'pendaftaran']; 

        $totalSiswaAktifQuery = Siswa::where('status_siswa', 'Aktif');
        $siswaBaruBulanIniQuery = Siswa::whereMonth('created_at', $selectedBulan)->whereYear('created_at', $selectedTahun);
        $siswaBaruBulanLaluQuery = Siswa::whereMonth('created_at', $previousMonthDate->month)->whereYear('created_at', $previousMonthDate->year);
        
        $pendapatanBulanIniQuery = Invoice::where('status', 'PAID')
            ->whereIn('type', $incomeInvoiceTypes) 
            ->whereMonth('paid_at', $selectedBulan)
            ->whereYear('paid_at', $selectedTahun);

        $pendapatanBulanLaluQuery = Invoice::where('status', 'PAID')
            ->whereIn('type', $incomeInvoiceTypes)
            ->whereMonth('paid_at', $previousMonthDate->month)
            ->whereYear('paid_at', $previousMonthDate->year);

        $tagihanTertundaBulanIniQuery = Invoice::where('status', 'PENDING')->where('type', 'spp')->whereMonth('periode_tagihan', $selectedBulan)->whereYear('periode_tagihan', $selectedTahun);

        if ($managedKelasIds) {
            $totalSiswaAktifQuery->whereIn('id_kelas', $managedKelasIds);
            $siswaBaruBulanIniQuery->whereIn('id_kelas', $managedKelasIds);
            $siswaBaruBulanLaluQuery->whereIn('id_kelas', $managedKelasIds);
            $pendapatanBulanIniQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $pendapatanBulanLaluQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $tagihanTertundaBulanIniQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
        }

        $totalSiswaAktif = $totalSiswaAktifQuery->count();
        $siswaBaruBulanIni = $siswaBaruBulanIniQuery->count();
        $siswaBaruBulanLalu = $siswaBaruBulanLaluQuery->count();
        $pendapatanBulanIni = $pendapatanBulanIniQuery->sum('total_amount');
        $pendapatanBulanLalu = $pendapatanBulanLaluQuery->sum('total_amount');
        $tagihanTertundaBulanIni = $tagihanTertundaBulanIniQuery->count();

        $pendapatanPerBulanQuery = Invoice::select(DB::raw('YEAR(paid_at) as tahun'), DB::raw('MONTH(paid_at) as bulan'), DB::raw('SUM(total_amount) as total'))
            ->where('status', 'PAID')
            ->whereIn('type', $incomeInvoiceTypes)
            ->whereBetween('paid_at', [$selectedDate->copy()->subMonths(5)->startOfMonth(), $selectedDate->copy()->endOfMonth()]);

        $statusTagihanBulanIniQuery = Invoice::select('status', DB::raw('count(*) as total'))->where('type', 'spp')->whereMonth('periode_tagihan', $selectedBulan)->whereYear('periode_tagihan', $selectedTahun);

        if ($managedKelasIds) {
            $pendapatanPerBulanQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $statusTagihanBulanIniQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
        }
        
        $pendapatanPerBulan = $pendapatanPerBulanQuery->groupBy('tahun', 'bulan')->orderBy('tahun', 'asc')->orderBy('bulan', 'asc')->get();
        $statusTagihanBulanIni = $statusTagihanBulanIniQuery->groupBy('status')->pluck('total', 'status');
        $labelsGrafikPendapatan = [];
        $dataGrafikPendapatan = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = $selectedDate->copy()->subMonths($i);
            $labelsGrafikPendapatan[] = $date->isoFormat('MMM YY');
            $dataGrafikPendapatan[$date->format('Y-n')] = 0;
        }
        foreach ($pendapatanPerBulan as $pendapatan) {
            $key = $pendapatan->tahun . '-' . $pendapatan->bulan;
            if (isset($dataGrafikPendapatan[$key])) { $dataGrafikPendapatan[$key] = $pendapatan->total; }
        }
        
        $pembayaranTerakhirQuery = Invoice::where('status', 'PAID')
            ->whereIn('type', $incomeInvoiceTypes)
            ->with('siswa')
            ->latest('paid_at');

        $siswaBaruQuery = Siswa::latest('tanggal_bergabung');
        $siswaPerKelasQuery = Kelas::withCount(['siswa' => fn($q) => $q->where('status_siswa', 'Aktif')])->orderBy('nama_kelas');

        if ($managedKelasIds) {
            $pembayaranTerakhirQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $siswaBaruQuery->whereIn('id_kelas', $managedKelasIds);
            $siswaPerKelasQuery->whereIn('id_kelas', $managedKelasIds);
        }

        $pembayaranTerakhir = $pembayaranTerakhirQuery->limit(5)->get();
        $siswaBaru = $siswaBaruQuery->limit(5)->get();
        $siswaPerKelas = $siswaPerKelasQuery->get();
        
        $latestJobs = JobBatch::with('user:id,name')->latest()->limit(5)->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_siswa' => ['value' => $totalSiswaAktif],
                'siswa_baru' => ['value' => $siswaBaruBulanIni, 'change' => $calculateChange($siswaBaruBulanIni, $siswaBaruBulanLalu)],
                'pendapatan' => ['value' => $pendapatanBulanIni, 'change' => $calculateChange($pendapatanBulanIni, $pendapatanBulanLalu)],
                'tagihan_tertunda' => ['value' => $tagihanTertundaBulanIni],
            ],
            'grafikPendapatan' => ['labels' => array_values($labelsGrafikPendapatan), 'data' => array_values($dataGrafikPendapatan)],
            'grafikStatusTagihan' => ['labels' => $statusTagihanBulanIni->keys(), 'data' => $statusTagihanBulanIni->values()],
            'pembayaranTerakhir' => $pembayaranTerakhir->map(fn($invoice) => ['nama_siswa' => $invoice->siswa?->nama_siswa ?? 'N/A', 'total_tagihan_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'), 'tanggal_bayar' => Carbon::parse($invoice->paid_at)->diffForHumans()]),
            'siswaBaru' => $siswaBaru->map(fn($s) => ['nama_siswa' => $s->nama_siswa, 'tanggal_bergabung' => Carbon::parse($s->tanggal_bergabung)->isoFormat('D MMM YY')]),
            'siswaPerKelas' => $siswaPerKelas->map(fn($k) => ['nama_kelas' => $k->nama_kelas, 'jumlah_siswa' => $k->siswa_count]),
            'latestJobs' => $latestJobs->map(fn($job) => [
                'id' => $job->id,
                'name' => Str::limit($job->name, 35),
                'status' => $job->status,
                'user_name' => $job->user->name,
                'created_at' => $job->created_at->diffForHumans(),
                'progress' => $job->total_items > 0 ? (int)(($job->processed_items / $job->total_items) * 100) : 0,
            ]),
            'filters' => ['tahun' => (int)$selectedTahun, 'bulan' => (int)$selectedBulan],
            'availableYears' => range(date('Y'), date('Y') - 5),
        ]);
    }
}

