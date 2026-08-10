<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\Invoice;
use App\Models\JobBatch;
use App\Models\StudentLeave;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __construct()
    {
        Carbon::setLocale('id');
    }

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

        $totalSiswaAktifQuery = Siswa::whereIn('status_siswa', ['Aktif', 'Cuti']);
        $siswaBaruBulanIniQuery = Siswa::whereMonth('created_at', $selectedBulan)
            ->whereYear('created_at', $selectedTahun)
            ->whereHas('invoices', fn($q) => $q->where('type', 'pendaftaran')->where('status', 'PAID'));
            
        $siswaBaruBulanLaluQuery = Siswa::whereMonth('created_at', $previousMonthDate->month)
            ->whereYear('created_at', $previousMonthDate->year)
            ->whereHas('invoices', fn($q) => $q->where('type', 'pendaftaran')->where('status', 'PAID'));
        
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
        
        // ### PEMBARUAN: Hitung pendapatan Xendit vs Manual ###
        $pendapatanXenditBulanIni = (clone $pendapatanBulanIniQuery)->whereNull('payment_method')->sum('total_amount');
        $pendapatanManualBulanIni = (clone $pendapatanBulanIniQuery)->where('payment_method', 'manual')->sum('total_amount');
        
        $tagihanTertundaBulanIniCount = $tagihanTertundaBulanIniQuery->count();
        $tagihanTertundaBulanIniAmount = (clone $tagihanTertundaBulanIniQuery)->sum('total_amount');

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
        
        // 1. Aktivitas Invoice (Pending & Lunas)
        $invoiceActivityQuery = Invoice::with('siswa')
            ->whereIn('status', ['PAID', 'PENDING'])
            ->whereNull('parent_payment_id')
            ->latest('updated_at');
            
        // 2. Aktivitas Cuti (Disetujui)
        $leaveActivityQuery = StudentLeave::with('siswa')
            ->where('status', 'approved')
            ->latest('updated_at');
            
        // 3. Aktivitas Resign
        $resignActivityQuery = Siswa::where('status_siswa', 'Resign')
            ->latest('updated_at');

        $siswaPerKelasQuery = Kelas::withCount(['siswa' => fn($q) => $q->where('status_siswa', 'Aktif')])->orderBy('nama_kelas');

        if ($managedKelasIds) {
            $invoiceActivityQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $leaveActivityQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $resignActivityQuery->whereIn('id_kelas', $managedKelasIds);
            $siswaPerKelasQuery->whereIn('id_kelas', $managedKelasIds);
        }

        $siswaPerKelas = $siswaPerKelasQuery->get();
        
        $activities = collect();
        
        foreach ($invoiceActivityQuery->limit(20)->get() as $inv) {
            if ($inv->type === 'pendaftaran' && $inv->status === 'PENDING') {
                $activities->push([
                    'type' => 'pendaftaran_pending',
                    'title' => 'Pendaftaran Baru',
                    'description' => ($inv->siswa?->nama_siswa ?? 'Siswa') . ' mendaftar (menunggu pembayaran)',
                    'date' => $inv->created_at,
                    'id_siswa' => $inv->id_siswa,
                    'amount' => $inv->total_amount,
                    'payment_method' => $inv->payment_method
                ]);
            } elseif ($inv->type === 'pendaftaran' && $inv->status === 'PAID') {
                $activities->push([
                    'type' => 'pendaftaran_lunas',
                    'title' => 'Pendaftaran Lunas',
                    'description' => ($inv->siswa?->nama_siswa ?? 'Siswa') . ' melunasi pendaftaran',
                    'date' => $inv->paid_at ?? $inv->updated_at,
                    'id_siswa' => $inv->id_siswa,
                    'amount' => $inv->total_amount,
                    'payment_method' => $inv->payment_method
                ]);
            } elseif ($inv->status === 'PAID') {
                $desc = ($inv->siswa?->nama_siswa ?? 'Siswa') . ' telah membayar tagihan';
                if (in_array($inv->type, ['spp', 'pembayaran_spp_gabungan']) && $inv->periode_tagihan) {
                    $bulan = \Carbon\Carbon::parse($inv->periode_tagihan)->translatedFormat('F Y');
                    $desc .= ' untuk SPP bulan ' . $bulan;
                }
                
                $title = 'Pembayaran ' . strtoupper(str_replace('_', ' ', $inv->type));
                if (in_array($inv->type, ['spp', 'pembayaran_spp_gabungan'])) {
                    $title = 'Pembayaran SPP';
                }

                $activities->push([
                    'type' => 'pembayaran_lunas',
                    'title' => $title,
                    'description' => $desc,
                    'date' => $inv->paid_at ?? $inv->updated_at,
                    'id_siswa' => $inv->id_siswa,
                    'amount' => $inv->total_amount,
                    'payment_method' => $inv->payment_method
                ]);
            }
        }
        
        foreach ($leaveActivityQuery->limit(10)->get() as $leave) {
            $activities->push([
                'type' => 'cuti_disetujui',
                'title' => 'Cuti Disetujui',
                'description' => ($leave->siswa?->nama_siswa ?? 'Siswa') . ' disetujui untuk cuti',
                'date' => $leave->updated_at,
                'id_siswa' => $leave->id_siswa,
                'amount' => null,
                'payment_method' => null
            ]);
        }
        
        foreach ($resignActivityQuery->limit(10)->get() as $resign) {
            $activities->push([
                'type' => 'siswa_resign',
                'title' => 'Siswa Resign',
                'description' => $resign->nama_siswa . ' telah resign',
                'date' => $resign->updated_at,
                'id_siswa' => $resign->id_siswa,
                'amount' => null,
                'payment_method' => null
            ]);
        }
        
        // Urutkan semua aktivitas berdasarkan tanggal terbaru, dan ambil 20 teratas
        $aktivitasPublik = $activities->sortByDesc('date')->take(20)->values()->map(function($act) {
            $act['date_formatted'] = \Carbon\Carbon::parse($act['date'])->diffForHumans();
            if ($act['amount']) {
                $act['amount_formatted'] = 'Rp ' . number_format($act['amount'], 0, ',', '.');
            }
            return $act;
        });
        
        $latestJobs = JobBatch::with('user:id,name')->latest()->limit(5)->get();

        // --- Data Alert Cards ---
        // 1. Pengajuan cuti yang belum diproses
        $cutiPendingCount = StudentLeave::where('status', 'pending')->count();

        // 2. Invoice EXPIRED bulan ini yang belum direcreate
        $expiredInvoicesCount = Invoice::where('status', 'EXPIRED')
            ->where('type', 'spp')
            ->whereMonth('periode_tagihan', $selectedBulan)
            ->whereYear('periode_tagihan', $selectedTahun)
            ->whereNull('recreated_from_id') // hanya invoice original (bukan rekonstruksi)
            ->doesntHave('recreatedInvoice') // belum punya versi baru
            ->count();

        // 3. Siswa aktif yang belum punya tagihan SPP bulan ini
        $siswaAktifIdsQuery = Siswa::where('status_siswa', 'Aktif');
        if ($managedKelasIds) {
            $siswaAktifIdsQuery->whereIn('id_kelas', $managedKelasIds);
        }
        $siswaAktifIds = $siswaAktifIdsQuery->pluck('id_siswa');
        
        $siswaYangSudahTagihanIdsQuery = Invoice::where('type', 'spp')
            ->whereMonth('periode_tagihan', $selectedBulan)
            ->whereYear('periode_tagihan', $selectedTahun);
        if ($managedKelasIds) {
            $siswaYangSudahTagihanIdsQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
        }
        $siswaYangSudahTagihanIds = $siswaYangSudahTagihanIdsQuery->pluck('id_siswa');
        
        $siswaTanpaTagihanCount = $siswaAktifIds->diff($siswaYangSudahTagihanIds)->count();

        // 4. Siswa aktif tanpa konfigurasi nominal SPP (jumlah_spp_custom 0 atau null)
        $siswaTanpaSppConfigQuery = Siswa::where('status_siswa', 'Aktif')
            ->where(function($q) {
                $q->whereNull('jumlah_spp_custom')->orWhere('jumlah_spp_custom', '<=', 0);
            });
        if ($managedKelasIds) {
            $siswaTanpaSppConfigQuery->whereIn('id_kelas', $managedKelasIds);
        }
        $siswaTanpaSppConfigCount = $siswaTanpaSppConfigQuery->count();

        // 5. Pendaftar lunas yang menunggu SPP
        $pendaftarMenungguSppCountQuery = Siswa::whereNull('mulai_spp_date')
            ->whereHas('invoices', function ($q) {
                $q->where('type', 'pendaftaran')->where('status', 'PAID');
            });
        if ($managedKelasIds) {
            $pendaftarMenungguSppCountQuery->whereIn('id_kelas', $managedKelasIds);
        }
        $pendaftarMenungguSppCount = $pendaftarMenungguSppCountQuery->count();

        // 6. Total Tunggakan Keseluruhan (Semua Waktu)
        $totalTunggakanKeseluruhanQuery = Invoice::where('status', 'PENDING')->where('type', 'spp');
        if ($managedKelasIds) {
            $totalTunggakanKeseluruhanQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
        }
        $totalTunggakanKeseluruhanAmount = $totalTunggakanKeseluruhanQuery->sum('total_amount');
        $totalTunggakanKeseluruhanCount = $totalTunggakanKeseluruhanQuery->count();

        // --- Data Tahunan (Annual) ---
        $pendapatanTahunanQuery = Invoice::where('status', 'PAID')
            ->whereIn('type', $incomeInvoiceTypes)
            ->whereYear('paid_at', $selectedTahun);
            
        $semuaTagihanTahunanQuery = Invoice::where('type', 'spp')
            ->whereYear('periode_tagihan', $selectedTahun);
            
        $tagihanLunasTahunanQuery = Invoice::where('type', 'spp')
            ->where('status', 'PAID')
            ->whereYear('periode_tagihan', $selectedTahun);
            
        $pendaftarTahunanQuery = Siswa::select(DB::raw('MONTH(tanggal_bergabung) as bulan'), DB::raw('count(*) as total'))
            ->whereYear('tanggal_bergabung', $selectedTahun)
            ->whereHas('invoices', function ($q) {
                $q->where('type', 'pendaftaran')->where('status', 'PAID');
            });

        if ($managedKelasIds) {
            $pendapatanTahunanQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $semuaTagihanTahunanQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $tagihanLunasTahunanQuery->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            $pendaftarTahunanQuery->whereIn('id_kelas', $managedKelasIds);
        }

        $pendapatanTahunan = $pendapatanTahunanQuery->sum('total_amount');
        
        $semuaTagihanTahunanCount = $semuaTagihanTahunanQuery->count();
        $tagihanLunasTahunanCount = $tagihanLunasTahunanQuery->count();
        $paymentRate = $semuaTagihanTahunanCount > 0 ? round(($tagihanLunasTahunanCount / $semuaTagihanTahunanCount) * 100, 1) : 0;

        $pendaftarTahunanRaw = $pendaftarTahunanQuery->groupBy('bulan')->pluck('total', 'bulan');
        $dataGrafikPendaftar = [];
        $labelsGrafikPendaftar = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthName = Carbon::create(null, $i, 1)->isoFormat('MMM');
            $labelsGrafikPendaftar[] = $monthName;
            $dataGrafikPendaftar[] = $pendaftarTahunanRaw->get($i, 0);
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_siswa' => ['value' => $totalSiswaAktif],
                'siswa_baru' => ['value' => $siswaBaruBulanIni, 'change' => $calculateChange($siswaBaruBulanIni, $siswaBaruBulanLalu)],
                // ### PEMBARUAN: Kirim data pendapatan dalam struktur baru ###
                'pendapatan' => [
                    'total' => $pendapatanBulanIni,
                    'change' => $calculateChange($pendapatanBulanIni, $pendapatanBulanLalu),
                    'xendit' => $pendapatanXenditBulanIni,
                    'manual' => $pendapatanManualBulanIni,
                ],
                'tagihan_tertunda' => [
                    'count' => $tagihanTertundaBulanIniCount,
                    'total_amount' => $tagihanTertundaBulanIniAmount,
                ],
                'total_tunggakan' => [
                    'count' => $totalTunggakanKeseluruhanCount, 
                    'total_amount' => $totalTunggakanKeseluruhanAmount
                ],
                'siswa_tanpa_tagihan' => ['count' => $siswaTanpaTagihanCount],
            ],
            'annual_stats' => [
                'pendapatan_total' => $pendapatanTahunan,
                'payment_rate' => $paymentRate,
                'tagihan_lunas_count' => $tagihanLunasTahunanCount,
                'tagihan_semua_count' => $semuaTagihanTahunanCount,
            ],
            'grafikPendaftar' => ['labels' => $labelsGrafikPendaftar, 'data' => $dataGrafikPendaftar],
            'grafikPendapatan' => ['labels' => array_values($labelsGrafikPendapatan), 'data' => array_values($dataGrafikPendapatan)],
            'grafikStatusTagihan' => ['labels' => $statusTagihanBulanIni->keys(), 'data' => $statusTagihanBulanIni->values()],
            'aktivitasPublik' => $aktivitasPublik,
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
            'alerts' => [
                'cuti_pending'         => $cutiPendingCount,
                'expired_invoices'     => $expiredInvoicesCount,
                'siswa_tanpa_tagihan'  => $siswaTanpaTagihanCount,
                'siswa_tanpa_spp_config' => $siswaTanpaSppConfigCount,
                'pendaftar_menunggu_spp' => $pendaftarMenungguSppCount,
            ],
        ]);
    }
}

