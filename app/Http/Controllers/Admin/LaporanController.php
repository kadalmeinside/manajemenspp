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

    public function riwayatPembayaran(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $query = Invoice::with(['siswa' => function($q) {
            $q->select('id_siswa', 'nama_siswa', 'id_kelas')->with('kelas:id_kelas,nama_kelas');
        }])
        ->whereIn('status', ['PAID', 'SETTLED'])
        ->whereNotNull('paid_at')
        ->whereNull('parent_payment_id');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('siswa', function($q) use ($search) {
                $q->where('nama_siswa', 'LIKE', "%{$search}%");
            });
        }

        $payments = $query->orderBy('paid_at', 'desc')->paginate(20)->withQueryString()->through(function($invoice) {
            $isSingle = $invoice->type === 'pembayaran_spp_gabungan' && is_array($invoice->selected_periods) && count($invoice->selected_periods) === 1;
            
            return [
                'id' => $invoice->id,
                'siswa_nama' => $invoice->siswa?->nama_siswa ?? 'N/A',
                'kelas_nama' => $invoice->siswa?->kelas?->nama_kelas ?? 'N/A',
                'type' => $invoice->type,
                'is_single_gabungan' => $isSingle,
                'description' => $invoice->description,
                'total_amount' => $invoice->total_amount,
                'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                'payment_method' => $invoice->payment_method ?? 'Transfer Bank (Lainnya)',
                'paid_at' => Carbon::parse($invoice->paid_at)->isoFormat('D MMM YYYY, HH:mm'),
            ];
        });

        // Hitung total penerimaan hari ini & bulan ini (tanpa double count)
        $todayTotal = Invoice::whereIn('status', ['PAID', 'SETTLED'])
            ->whereDate('paid_at', Carbon::today())
            ->whereNull('parent_payment_id')
            ->sum('total_amount');

        $monthTotal = Invoice::whereIn('status', ['PAID', 'SETTLED'])
            ->whereMonth('paid_at', Carbon::now()->month)
            ->whereYear('paid_at', Carbon::now()->year)
            ->whereNull('parent_payment_id')
            ->sum('total_amount');

        return Inertia::render('Admin/Laporan/RiwayatPembayaran', [
            'pageTitle' => 'Riwayat Pembayaran (Log)',
            'payments' => $payments,
            'filters' => $request->only(['search', 'type']),
            'stats' => [
                'today' => 'Rp ' . number_format($todayTotal, 0, ',', '.'),
                'month' => 'Rp ' . number_format($monthTotal, 0, ',', '.'),
            ]
        ]);
    }
}