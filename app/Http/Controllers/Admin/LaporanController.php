<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Invoice;
use App\Exports\LaporanSppExport;
use Inertia\Inertia;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function pembayaranBulanan(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $user = $request->user();

        // Validasi dan siapkan filter
        $request->validate([
            'tahun' => 'nullable|integer',
            'kelas_id' => 'nullable|uuid|exists:kelas,id_kelas',
            'search' => 'nullable|string|max:100',
        ]);

        $selectedTahun = $request->input('tahun', now()->year);
        $selectedKelasId = $request->input('kelas_id');
        $searchQuery = $request->input('search');

        // Query utama sekarang ada pada Siswa dengan paginasi
        $siswaQuery = Siswa::query()
            ->with('kelas')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_siswa', 'asc');

        // Terapkan filter hak akses untuk Admin Kelas
        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
            $siswaQuery->whereIn('id_kelas', $managedKelasIds);
            if ($selectedKelasId && !$managedKelasIds->contains($selectedKelasId)) {
                abort(403, 'Anda tidak memiliki akses ke kelas ini.');
            }
        }

        // Terapkan filter dari form
        if ($selectedKelasId) {
            $siswaQuery->where('id_kelas', $selectedKelasId);
        }
        if ($searchQuery) {
            $siswaQuery->where('nama_siswa', 'LIKE', "%{$searchQuery}%");
        }

        // Ambil data siswa per halaman
        $siswas = $siswaQuery->paginate(15)->withQueryString();
        $siswaIdsOnPage = $siswas->pluck('id_siswa');

        // Ambil semua invoice yang relevan HANYA untuk siswa di halaman ini
        $invoices = Invoice::whereIn('id_siswa', $siswaIdsOnPage)
            ->whereYear('periode_tagihan', $selectedTahun)
            ->where('type', 'spp')
            ->get()
            ->keyBy(function ($item) {
                return $item->id_siswa . '-' . Carbon::parse($item->periode_tagihan)->month;
            });

        // Buat data laporan dengan memetakan siswa yang sudah dipaginasi
        $laporanData = $siswas->through(function ($siswa) use ($invoices) {
            $statuses = [];
            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $key = $siswa->id_siswa . '-' . $bulan;
                $statuses[$bulan] = [
                    'status' => $invoices[$key]->status ?? 'N/A',
                    'invoice_id' => $invoices[$key]->id ?? null,
                    // ### PERUBAHAN: Menambahkan metode pembayaran ###
                    'payment_method' => $invoices[$key]->payment_method ?? null,
                ];
            }
            return [
                'id_siswa' => $siswa->id_siswa,
                'nama_siswa' => $siswa->nama_siswa,
                'nama_kelas' => $siswa->kelas->nama_kelas ?? 'N/A',
                'statuses' => $statuses,
            ];
        });
        
        // Data untuk filter di frontend
        $allKelasQuery = Kelas::orderBy('nama_kelas');
        if ($user->hasRole('admin_kelas')) {
            $allKelasQuery->whereIn('id_kelas', $user->managedClasses()->pluck('kelas.id_kelas'));
        }

        return Inertia::render('Admin/Laporan/PembayaranBulanan', [
            'pageTitle' => 'Laporan Rekap SPP Tahunan',
            'laporanData' => $laporanData,
            'allKelas' => $allKelasQuery->get(['id_kelas', 'nama_kelas']),
            'availableYears' => range(date('Y'), date('Y') - 5),
            'filters' => [
                'tahun'    => (int)$selectedTahun,
                'kelas_id' => $selectedKelasId,
                'search'   => $searchQuery,
            ]
        ]);
    }

    public function export(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $request->validate([
            'tahun'    => 'nullable|integer',
            'kelas_id' => 'nullable|uuid|exists:kelas,id_kelas',
            'search'   => 'nullable|string|max:100',
        ]);

        $tahun   = (int)$request->input('tahun', now()->year);
        $kelasId = $request->input('kelas_id');
        $search  = $request->input('search');

        $filename = 'laporan-spp-' . $tahun . '.xlsx';

        return Excel::download(new LaporanSppExport($tahun, $kelasId, $search), $filename);
    }

    private function buildAktivitasQuery($search, $typeFilter, $startDate, $endDate, $sort, $kelasId)
    {
        $invoices = DB::table('invoices')
            ->join('siswa', 'invoices.id_siswa', '=', 'siswa.id_siswa')
            ->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->select(
                'invoices.id as raw_id',
                DB::raw("CASE 
                    WHEN invoices.type = 'pendaftaran' AND invoices.status = 'PENDING' THEN 'pendaftaran_pending'
                    WHEN invoices.type = 'pendaftaran' AND invoices.status = 'PAID' THEN 'pendaftaran_lunas'
                    ELSE 'pembayaran_lunas' END as type"),
                DB::raw("CASE 
                    WHEN invoices.type = 'pendaftaran' AND invoices.status = 'PENDING' THEN 'Pendaftaran Baru'
                    WHEN invoices.type = 'pendaftaran' AND invoices.status = 'PAID' THEN 'Pendaftaran Lunas'
                    ELSE CONCAT('Pembayaran ', UPPER(REPLACE(invoices.type, '_', ' '))) END as title"),
                DB::raw("CONCAT(siswa.nama_siswa, ' ', CASE 
                    WHEN invoices.type = 'pendaftaran' AND invoices.status = 'PENDING' THEN 'mendaftar (menunggu pembayaran)'
                    WHEN invoices.type = 'pendaftaran' AND invoices.status = 'PAID' THEN 'melunasi pendaftaran'
                    ELSE 'telah membayar tagihan' END) as description"),
                'invoices.total_amount as amount',
                DB::raw("COALESCE(invoices.paid_at, invoices.updated_at) as date"),
                'siswa.id_siswa',
                'siswa.nama_siswa',
                'siswa.id_kelas',
                'kelas.nama_kelas',
                'invoices.periode_tagihan',
                'invoices.type as original_type'
            )
            ->whereIn('invoices.status', ['PAID', 'PENDING']);

        $leaves = DB::table('student_leaves')
            ->join('siswa', 'student_leaves.id_siswa', '=', 'siswa.id_siswa')
            ->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->select(
                'student_leaves.id as raw_id',
                DB::raw("'cuti_disetujui' as type"),
                DB::raw("'Cuti Disetujui' as title"),
                DB::raw("CONCAT(siswa.nama_siswa, ' disetujui untuk cuti') as description"),
                DB::raw("NULL as amount"),
                'student_leaves.updated_at as date',
                'siswa.id_siswa',
                'siswa.nama_siswa',
                'siswa.id_kelas',
                'kelas.nama_kelas',
                DB::raw("NULL as periode_tagihan"),
                DB::raw("NULL as original_type")
            )
            ->where('student_leaves.status', 'approved');

        $resigns = DB::table('siswa')
            ->leftJoin('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->select(
                'siswa.id_siswa as raw_id',
                DB::raw("'siswa_resign' as type"),
                DB::raw("'Siswa Resign' as title"),
                DB::raw("CONCAT(siswa.nama_siswa, ' telah resign') as description"),
                DB::raw("NULL as amount"),
                'siswa.updated_at as date',
                'siswa.id_siswa',
                'siswa.nama_siswa',
                'siswa.id_kelas',
                'kelas.nama_kelas',
                DB::raw("NULL as periode_tagihan"),
                DB::raw("NULL as original_type")
            )
            ->where('siswa.status_siswa', 'Resign');

        $sub = $invoices->union($leaves)->union($resigns);
        
        // Use fromSub to ensure correct binding order instead of DB::raw and mergeBindings
        $query = DB::query()->fromSub($sub, 'activities');

        if ($search) {
            $query->where('nama_siswa', 'LIKE', "%{$search}%");
        }
        if ($typeFilter) {
            $query->where('type', $typeFilter);
        }
        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }
        if ($kelasId) {
            $query->where('id_kelas', $kelasId);
        }

        $sortDirection = $sort === 'asc' ? 'asc' : 'desc';
        $query->orderBy('date', $sortDirection);

        return $query;
    }

    public function aktivitas(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $search = $request->input('search');
        $typeFilter = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sort = $request->input('sort', 'desc');
        $kelasId = $request->input('kelas_id');

        $query = $this->buildAktivitasQuery($search, $typeFilter, $startDate, $endDate, $sort, $kelasId);

        $activities = $query->paginate(20)->withQueryString()->through(function($act) {
            $desc = $act->description;
            if ($act->original_type === 'spp' && $act->periode_tagihan) {
                $bulan = \Carbon\Carbon::parse($act->periode_tagihan)->isoFormat('MMMM YYYY');
                $desc .= ' bulan ' . $bulan;
            }
            
            return [
                'id' => $act->raw_id,
                'type' => $act->type,
                'title' => $act->title,
                'description' => $desc,
                'amount' => $act->amount ? 'Rp ' . number_format($act->amount, 0, ',', '.') : null,
                'date' => \Carbon\Carbon::parse($act->date)->diffForHumans(),
                'date_full' => \Carbon\Carbon::parse($act->date)->isoFormat('D MMM YYYY, HH:mm'),
                'id_siswa' => $act->id_siswa,
                'nama_siswa' => $act->nama_siswa,
                'nama_kelas' => $act->nama_kelas ?? '-'
            ];
        });

        // Hitung statistik bulanan & harian dari tabel aslinya
        $todayTotal = Invoice::whereIn('status', ['PAID', 'SETTLED'])
            ->whereDate('paid_at', Carbon::today())
            ->whereNull('parent_payment_id')
            ->sum('total_amount');

        $monthTotal = Invoice::whereIn('status', ['PAID', 'SETTLED'])
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->whereNull('parent_payment_id')
            ->sum('total_amount');

        $kelasList = Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas']);

        return Inertia::render('Admin/Laporan/Aktivitas', [
            'pageTitle' => 'Riwayat Aktivitas Publik',
            'activities' => $activities,
            'kelasList' => $kelasList,
            'filters' => $request->only(['search', 'type', 'start_date', 'end_date', 'sort', 'kelas_id']),
            'stats' => [
                'today' => 'Rp ' . number_format($todayTotal, 0, ',', '.'),
                'month' => 'Rp ' . number_format($monthTotal, 0, ',', '.'),
            ]
        ]);
    }

    public function exportAktivitas(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        // Validate date range max 31 days to prevent memory exhaustion
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = \Carbon\Carbon::parse($request->input('start_date'));
        $end = \Carbon\Carbon::parse($request->input('end_date'));
        if ($start->diffInDays($end) > 31) {
            abort(400, 'Rentang tanggal maksimal adalah 31 hari untuk mencegah kegagalan ekspor.');
        }

        // Tingkatkan batas waktu dan memori untuk laporan dengan ribuan data
        set_time_limit(300);
        ini_set('memory_limit', '2G');

        $search = $request->input('search');
        $typeFilter = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sort = $request->input('sort', 'desc');
        $kelasId = $request->input('kelas_id');
        $groupBy = filter_var($request->input('group_by', false), FILTER_VALIDATE_BOOLEAN);
        $format = $request->input('format', 'pdf');

        $query = $this->buildAktivitasQuery($search, $typeFilter, $startDate, $endDate, $sort, $kelasId);
        $data = $query->get()->map(function($act) {
            if ($act->original_type === 'spp' && $act->periode_tagihan) {
                $bulan = \Carbon\Carbon::parse($act->periode_tagihan)->isoFormat('MMMM YYYY');
                $act->description .= ' bulan ' . $bulan;
            }
            $act->nama_kelas = $act->nama_kelas ?? '-';
            return $act;
        });

        if ($groupBy) {
            $data = $data->groupBy('type');
        }

        $kelasName = null;
        if ($kelasId) {
            $k = Kelas::find($kelasId);
            if ($k) $kelasName = $k->nama_kelas;
        }

        $viewData = [
            'data' => $data,
            'groupBy' => $groupBy,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'typeFilter' => $typeFilter,
            'kelasName' => $kelasName,
        ];

        if ($format === 'excel') {
            $filename = 'laporan-aktivitas-' . date('YmdHis') . '.xlsx';
            return Excel::download(new \App\Exports\AktivitasExport($viewData), $filename);
        }

        // Default PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.aktivitas', $viewData);
        return $pdf->download('laporan-aktivitas-' . date('YmdHis') . '.pdf');
    }
}