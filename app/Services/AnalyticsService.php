<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get summary data globally and per class
     */
    public function getSummaryData($month, $year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_summary_{$month}_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        
        return Cache::remember($cacheKey, 60, function () use ($month, $year, $managedKelasIds) {
            // 1. Get all classes
            $kelasQuery = Kelas::orderBy('nama_kelas');
            if ($managedKelasIds) {
                $kelasQuery->whereIn('id_kelas', $managedKelasIds);
            }
            $kelasList = $kelasQuery->get(['id_kelas', 'nama_kelas']);
            
            // 2. Pendapatan (Invoices PAID, spp/pendaftaran, paid_at match)
            $pendapatanQuery = Invoice::where('status', 'PAID')
                ->whereIn('type', ['spp', 'pendaftaran'])
                ->whereMonth('paid_at', $month)
                ->whereYear('paid_at', $year)
                ->join('siswa', 'invoices.id_siswa', '=', 'siswa.id_siswa')
                ->selectRaw('siswa.id_kelas, SUM(invoices.total_amount) as total')
                ->groupBy('siswa.id_kelas');
            if ($managedKelasIds) {
                $pendapatanQuery->whereIn('siswa.id_kelas', $managedKelasIds);
            }
            $pendapatanByKelas = $pendapatanQuery->pluck('total', 'id_kelas')->toArray();

            // 3. Pendaftar (Siswa created_at match, has PAID pendaftaran)
            $pendaftarQuery = Siswa::whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->whereHas('invoices', fn($q) => $q->where('type', 'pendaftaran')->where('status', 'PAID'))
                ->selectRaw('id_kelas, COUNT(*) as count')
                ->groupBy('id_kelas');
            if ($managedKelasIds) {
                $pendaftarQuery->whereIn('id_kelas', $managedKelasIds);
            }
            $pendaftarByKelas = $pendaftarQuery->pluck('count', 'id_kelas')->toArray();

            // 4. Cuti (\App\Models\StudentLeave approved, month/year match)
            $cutiQuery = \App\Models\StudentLeave::where('status', 'approved')
                ->where('month', $month)
                ->where('year', $year)
                ->join('siswa', 'student_leaves.id_siswa', '=', 'siswa.id_siswa')
                ->selectRaw('siswa.id_kelas, COUNT(*) as count')
                ->groupBy('siswa.id_kelas');
            if ($managedKelasIds) {
                $cutiQuery->whereIn('siswa.id_kelas', $managedKelasIds);
            }
            $cutiByKelas = $cutiQuery->pluck('count', 'id_kelas')->toArray();

            // 5. Keluar (Siswa status_siswa Keluar, updated_at match)
            $keluarQuery = Siswa::whereIn('status_siswa', ['Keluar', 'Resign'])
                ->whereMonth('updated_at', $month)
                ->whereYear('updated_at', $year)
                ->selectRaw('id_kelas, COUNT(*) as count')
                ->groupBy('id_kelas');
            if ($managedKelasIds) {
                $keluarQuery->whereIn('id_kelas', $managedKelasIds);
            }
            $keluarByKelas = $keluarQuery->pluck('count', 'id_kelas')->toArray();
            
            // 6. Siswa Aktif saat ini
            $aktifQuery = Siswa::where('status_siswa', 'Aktif')
                ->selectRaw('id_kelas, COUNT(*) as count')
                ->groupBy('id_kelas');
            if ($managedKelasIds) {
                $aktifQuery->whereIn('id_kelas', $managedKelasIds);
            }
            $aktifByKelas = $aktifQuery->pluck('count', 'id_kelas')->toArray();
            
            // Aggregate Global
            $global = [
                'pendapatan' => array_sum($pendapatanByKelas),
                'pendaftar' => array_sum($pendaftarByKelas),
                'cuti' => array_sum($cutiByKelas),
                'keluar' => array_sum($keluarByKelas),
                'siswa_aktif' => array_sum($aktifByKelas),
            ];
            
            // Build Per Kelas Array
            $perKelas = [];
            foreach ($kelasList as $kelas) {
                $id = $kelas->id_kelas;
                $perKelas[] = [
                    'id_kelas' => $id,
                    'nama_kelas' => $kelas->nama_kelas,
                    'siswa_aktif' => $aktifByKelas[$id] ?? 0,
                    'pendapatan' => $pendapatanByKelas[$id] ?? 0,
                    'pendaftar' => $pendaftarByKelas[$id] ?? 0,
                    'cuti' => $cutiByKelas[$id] ?? 0,
                    'keluar' => $keluarByKelas[$id] ?? 0,
                ];
            }
            
            // Optionally add "Tanpa Kelas" if any metrics exist for null class
            $tanpaKelasData = [
                'id_kelas' => null,
                'nama_kelas' => 'Tanpa Kelas',
                'siswa_aktif' => $aktifByKelas[''] ?? 0,
                'pendapatan' => $pendapatanByKelas[''] ?? 0,
                'pendaftar' => $pendaftarByKelas[''] ?? 0,
                'cuti' => $cutiByKelas[''] ?? 0,
                'keluar' => $keluarByKelas[''] ?? 0,
            ];
            
            if ($tanpaKelasData['siswa_aktif'] > 0 || $tanpaKelasData['pendapatan'] > 0 || $tanpaKelasData['pendaftar'] > 0 || $tanpaKelasData['cuti'] > 0 || $tanpaKelasData['keluar'] > 0) {
                $perKelas[] = $tanpaKelasData;
            }
            
            return [
                'global' => $global,
                'per_kelas' => $perKelas,
            ];
        });
    }

    /**
     * Get payment rate per class for a specific month and year.
     * (Tagihan Lunas / Total Tagihan) * 100 per kelas.
     */
    public function getPaymentRatePerClass($month, $year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_payment_rate_{$month}_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($month, $year, $managedKelasIds) {
            $query = DB::table('kelas')
                ->select(
                    'kelas.id_kelas',
                    'kelas.nama_kelas',
                    DB::raw('COUNT(invoices.id) as total_invoices'),
                    DB::raw('SUM(CASE WHEN invoices.status = "PAID" THEN 1 ELSE 0 END) as paid_invoices')
                )
                ->leftJoin('siswa', 'kelas.id_kelas', '=', 'siswa.id_kelas')
                ->leftJoin('invoices', function($join) use ($month, $year) {
                    $join->on('siswa.id_siswa', '=', 'invoices.id_siswa')
                         ->where('invoices.type', 'spp')
                         ->whereMonth('invoices.periode_tagihan', $month)
                         ->whereYear('invoices.periode_tagihan', $year);
                })
                ->whereNull('siswa.deleted_at')
                ->groupBy('kelas.id_kelas', 'kelas.nama_kelas');

            if ($managedKelasIds) {
                $query->whereIn('kelas.id_kelas', $managedKelasIds);
            }

            $results = $query->get();

            $rates = [];
            foreach ($results as $row) {
                $rate = $row->total_invoices > 0 ? round(($row->paid_invoices / $row->total_invoices) * 100, 1) : 0;
                $rates[] = [
                    'nama_kelas' => $row->nama_kelas,
                    'payment_rate' => $rate,
                    'total' => $row->total_invoices,
                    'paid' => $row->paid_invoices
                ];
            }
            
            // Sort by payment rate descending
            usort($rates, fn($a, $b) => $b['payment_rate'] <=> $a['payment_rate']);
            return $rates;
        });
    }

    /**
     * Get registration trends (new students) for the last X months.
     */
    public function getRegistrationTrends($months = 6, $managedKelasIds = null)
    {
        $cacheKey = "analytics_reg_trends_{$months}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($months, $managedKelasIds) {
            $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
            
            $isSqlite = DB::connection()->getDriverName() === 'sqlite';
            $monthYearFormat = $isSqlite ? 'strftime("%Y-%m", created_at)' : 'DATE_FORMAT(created_at, "%Y-%m")';
            
            $query = Siswa::select(
                    DB::raw("$monthYearFormat as month_year"),
                    DB::raw('COUNT(*) as total')
                )
                ->where('created_at', '>=', $startDate)
                ->whereHas('invoices', function ($q) {
                    $q->where('type', 'pendaftaran')->where('status', 'PAID');
                })
                ->groupBy('month_year')
                ->orderBy('month_year');

            if ($managedKelasIds) {
                $query->whereIn('id_kelas', $managedKelasIds);
            }

            $rawResults = $query->pluck('total', 'month_year')->toArray();
            
            $labels = [];
            $data = [];
            
            for ($i = $months - 1; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $key = $date->format('Y-m');
                $labels[] = $date->isoFormat('MMM YYYY');
                $data[] = $rawResults[$key] ?? 0;
            }
            
            return [
                'labels' => $labels,
                'data' => $data
            ];
        });
    }

    /**
     * Get Revenue Month-over-Month comparison.
     */
    public function getRevenueMoM($month, $year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_revenue_mom_{$month}_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($month, $year, $managedKelasIds) {
            $currentDate = Carbon::createFromDate($year, $month, 1);
            $previousDate = $currentDate->copy()->subMonth();
            
            $incomeTypes = ['spp', 'pendaftaran'];

            $query = Invoice::where('status', 'PAID')->whereIn('type', $incomeTypes);
            if ($managedKelasIds) {
                $query->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            }

            $currentRevenue = (clone $query)
                ->whereMonth('paid_at', $currentDate->month)
                ->whereYear('paid_at', $currentDate->year)
                ->sum('total_amount');

            $previousRevenue = (clone $query)
                ->whereMonth('paid_at', $previousDate->month)
                ->whereYear('paid_at', $previousDate->year)
                ->sum('total_amount');

            $percentageChange = 0;
            if ($previousRevenue > 0) {
                $percentageChange = (($currentRevenue - $previousRevenue) / $previousRevenue) * 100;
            } elseif ($currentRevenue > 0) {
                $percentageChange = 100;
            }

            return [
                'current' => $currentRevenue,
                'previous' => $previousRevenue,
                'change_percentage' => round($percentageChange, 1),
                'is_positive' => $percentageChange >= 0
            ];
        });
    }

    /**
     * Get Payment Methods distribution (Manual vs Xendit).
     */
    public function getPaymentMethods($month, $year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_payment_methods_{$month}_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($month, $year, $managedKelasIds) {
            $query = Invoice::where('status', 'PAID')
                ->whereIn('type', ['spp', 'pendaftaran'])
                ->whereMonth('paid_at', $month)
                ->whereYear('paid_at', $year);

            if ($managedKelasIds) {
                $query->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            }

            // Using application-level calculation for simplicity with SQLite compatibility
            $invoices = $query->get(['payment_method', 'payment_gateway', 'total_amount']);
            
            $methodCounts = [];
            $methodRevenues = [];
            
            foreach ($invoices as $inv) {
                // Determine group
                $group = 'Online';
                if ($inv->payment_method === 'manual') {
                    $group = 'Manual';
                } else if ($inv->payment_gateway) {
                    $group = ucfirst($inv->payment_gateway);
                    if (strtolower($group) === 'midtrans_custom') {
                        $group = 'Midtrans Custom';
                    }
                }
                
                if (!isset($methodCounts[$group])) {
                    $methodCounts[$group] = 0;
                    $methodRevenues[$group] = 0;
                }
                
                $methodCounts[$group]++;
                $methodRevenues[$group] += $inv->total_amount;
            }

            return [
                'labels' => array_keys($methodCounts),
                'data_count' => array_values($methodCounts),
                'data_revenue' => array_values($methodRevenues)
            ];
        });
    }

    /**
     * Get resignation count per class for a given month.
     */
    public function getResignationRatePerClass($month, $year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_resign_{$month}_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($month, $year, $managedKelasIds) {
            $query = Kelas::withCount(['siswa' => function ($q) use ($month, $year) {
                $q->where('status_siswa', 'Keluar')
                  ->whereMonth('updated_at', $month)
                  ->whereYear('updated_at', $year); // Asumsi updated_at adalah tanggal resign
            }])->orderBy('nama_kelas');

            if ($managedKelasIds) {
                $query->whereIn('id_kelas', $managedKelasIds);
            }

            $results = $query->get();

            $labels = [];
            $data = [];
            
            foreach ($results as $kelas) {
                $labels[] = $kelas->nama_kelas;
                $data[] = $kelas->siswa_count;
            }

            return [
                'labels' => $labels,
                'data' => $data
            ];
        });
    }
    /**
     * Get MRR and ARR
     */
    public function getMRR($managedKelasIds = null)
    {
        $cacheKey = "analytics_mrr_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        return Cache::remember($cacheKey, 1800, function () use ($managedKelasIds) {
            $query = Siswa::with('kelas')->where('status_siswa', 'Aktif');
            if ($managedKelasIds) {
                $query->whereIn('id_kelas', $managedKelasIds);
            }
            $siswaList = $query->get();
            $mrr = 0;
            foreach ($siswaList as $siswa) {
                if ($siswa->jumlah_spp_custom > 0) {
                    $mrr += $siswa->jumlah_spp_custom;
                } else if ($siswa->kelas) {
                    $mrr += $siswa->kelas->biaya_spp_default;
                }
            }
            return [
                'mrr' => $mrr,
                'arr' => $mrr * 12
            ];
        });
    }

    /**
     * Get Aging Receivables
     */
    public function getAgingReceivables($managedKelasIds = null)
    {
        $cacheKey = "analytics_aging_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        return Cache::remember($cacheKey, 1800, function () use ($managedKelasIds) {
            $query = Invoice::where('type', 'spp')
                ->whereIn('status', ['PENDING', 'EXPIRED'])
                ->where('due_date', '<', now()->format('Y-m-d'));
                
            if ($managedKelasIds) {
                $query->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            }
            
            $invoices = $query->get(['due_date', 'total_amount']);
            
            $bucket1 = 0; // 1-30 days
            $bucket2 = 0; // 31-60 days
            $bucket3 = 0; // >60 days
            
            $now = Carbon::now();
            
            foreach ($invoices as $inv) {
                $days = $now->diffInDays(Carbon::parse($inv->due_date));
                if ($days <= 30) {
                    $bucket1 += $inv->total_amount;
                } elseif ($days <= 60) {
                    $bucket2 += $inv->total_amount;
                } else {
                    $bucket3 += $inv->total_amount;
                }
            }
            
            return [
                'labels' => ['1-30 Hari', '31-60 Hari', '> 60 Hari'],
                'data' => [$bucket1, $bucket2, $bucket3]
            ];
        });
    }

    /**
     * Get CLTV
     */
    public function getCLTV($managedKelasIds = null)
    {
        $cacheKey = "analytics_cltv_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        return Cache::remember($cacheKey, 1800, function () use ($managedKelasIds) {
            $mrrData = $this->getMRR($managedKelasIds);
            
            $activeQuery = Siswa::where('status_siswa', 'Aktif');
            if ($managedKelasIds) {
                $activeQuery->whereIn('id_kelas', $managedKelasIds);
            }
            $activeCount = $activeQuery->count();
            
            $arpu = $activeCount > 0 ? ($mrrData['mrr'] / $activeCount) : 0;
            
            $resignQuery = Siswa::whereIn('status_siswa', ['Resign', 'Keluar']);
            if ($managedKelasIds) {
                $resignQuery->whereIn('id_kelas', $managedKelasIds);
            }
            $resignStudents = $resignQuery->get(['created_at', 'updated_at']);
            
            $totalMonths = 0;
            $resignCount = $resignStudents->count();
            
            if ($resignCount > 0) {
                foreach ($resignStudents as $siswa) {
                    $months = $siswa->created_at->diffInMonths($siswa->updated_at);
                    $totalMonths += max(1, $months); // At least 1 month
                }
                $avgRetention = $totalMonths / $resignCount;
            } else {
                $avgRetention = 12; // Fallback 12 months
            }
            
            return [
                'arpu' => $arpu,
                'avg_retention_months' => round($avgRetention, 1),
                'cltv' => $arpu * $avgRetention
            ];
        });
    }

    /**
     * Get Time to Pay
     */
    public function getTimeToPay($managedKelasIds = null)
    {
        $cacheKey = "analytics_time_to_pay_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        return Cache::remember($cacheKey, 1800, function () use ($managedKelasIds) {
            $query = Invoice::where('type', 'spp')->where('status', 'PAID')->whereNotNull('paid_at');
            if ($managedKelasIds) {
                $query->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            }
            
            $invoices = $query->limit(500)->latest('paid_at')->get(['created_at', 'paid_at']);
            
            if ($invoices->isEmpty()) {
                return ['avg_days' => 0];
            }
            
            $totalDays = 0;
            foreach ($invoices as $inv) {
                $totalDays += $inv->created_at->diffInDays($inv->paid_at);
            }
            
            return [
                'avg_days' => round($totalDays / $invoices->count(), 1)
            ];
        });
    }

    /**
     * Get Revenue comparison across classes (Kelas)
     */
    public function getRevenuePerKelas($month, $year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_revenue_kelas_{$month}_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        return Cache::remember($cacheKey, 1800, function () use ($month, $year, $managedKelasIds) {
            $query = Invoice::where('status', 'PAID')
                ->whereIn('type', ['spp', 'pendaftaran'])
                ->whereMonth('paid_at', $month)
                ->whereYear('paid_at', $year)
                ->with(['siswa' => function($q) {
                    $q->withTrashed();
                }, 'siswa.kelas' => function($q) {
                    $q->select('id_kelas', 'nama_kelas');
                }]);

            if ($managedKelasIds) {
                $query->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            }

            $invoices = $query->get(['id', 'total_amount', 'id_siswa']);
            
            $classRevenue = [];
            
            foreach ($invoices as $inv) {
                $kelasLabel = $inv->siswa?->kelas?->nama_kelas ?? 'Tanpa Kelas';
                
                if (!isset($classRevenue[$kelasLabel])) {
                    $classRevenue[$kelasLabel] = 0;
                }
                $classRevenue[$kelasLabel] += $inv->total_amount;
            }
            
            return [
                'labels' => array_keys($classRevenue),
                'data' => array_values($classRevenue)
            ];
        });
    }

    /**
     * Get New Registrations comparison across classes (Kelas)
     */
    public function getRegistrationPerKelas($month, $year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_reg_kelas_{$month}_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        return Cache::remember($cacheKey, 1800, function () use ($month, $year, $managedKelasIds) {
            $query = Siswa::withTrashed()
                ->with(['kelas' => function($q) {
                    $q->select('id_kelas', 'nama_kelas');
                }])
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->whereHas('invoices', function ($q) {
                    $q->where('type', 'pendaftaran')->where('status', 'PAID');
                });

            if ($managedKelasIds) {
                $query->whereIn('id_kelas', $managedKelasIds);
            }

            $siswaList = $query->get(['id_siswa', 'id_kelas']);
            
            $classCounts = [];
            
            foreach ($siswaList as $siswa) {
                $kelasLabel = $siswa->kelas?->nama_kelas ?? 'Tanpa Kelas';
                
                if (!isset($classCounts[$kelasLabel])) {
                    $classCounts[$kelasLabel] = 0;
                }
                $classCounts[$kelasLabel]++;
            }
            
            return [
                'labels' => array_keys($classCounts),
                'data' => array_values($classCounts)
            ];
        });
    }

    /**
     * Get Payment Transition (Manual vs Xendit vs Unpaid) across classes
     */
    public function getPaymentTransitionPerKelas($month, $year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_pay_trans_kelas_{$month}_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        return Cache::remember($cacheKey, 1800, function () use ($month, $year, $managedKelasIds) {
            $query = Invoice::where('type', 'spp')
                ->whereMonth('periode_tagihan', $month)
                ->whereYear('periode_tagihan', $year)
                ->with(['siswa' => function($q) {
                    $q->withTrashed();
                }, 'siswa.kelas' => function($q) {
                    $q->select('id_kelas', 'nama_kelas');
                }]);

            if ($managedKelasIds) {
                $query->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            }

            $invoices = $query->get(['id', 'status', 'payment_method', 'id_siswa']);
            
            $stats = [];
            
            foreach ($invoices as $inv) {
                $kelasLabel = $inv->siswa?->kelas?->nama_kelas ?? 'Tanpa Kelas';
                
                if (!isset($stats[$kelasLabel])) {
                    $stats[$kelasLabel] = [
                        'unpaid' => 0, 
                        'manual' => 0, 
                        'online' => 0, 
                        'total' => 0
                    ];
                }
                
                $stats[$kelasLabel]['total']++;
                
                if ($inv->status === 'PAID') {
                    if ($inv->payment_method !== 'manual') {
                        $stats[$kelasLabel]['online']++;
                    } else {
                        $stats[$kelasLabel]['manual']++;
                    }
                } else {
                    $stats[$kelasLabel]['unpaid']++;
                }
            }
            
            // Convert to percentages
            $labels = [];
            $unpaidPct = [];
            $manualPct = [];
            $onlinePct = [];
            
            foreach ($stats as $kelas => $data) {
                $labels[] = $kelas;
                if ($data['total'] > 0) {
                    $unpaidPct[] = round(($data['unpaid'] / $data['total']) * 100, 1);
                    $manualPct[] = round(($data['manual'] / $data['total']) * 100, 1);
                    $onlinePct[] = round(($data['online'] / $data['total']) * 100, 1);
                } else {
                    $unpaidPct[] = 0;
                    $manualPct[] = 0;
                    $onlinePct[] = 0;
                }
            }
            
            return [
                'labels' => $labels,
                'unpaid' => $unpaidPct,
                'manual' => $manualPct,
                'online' => $onlinePct,
                'raw_stats' => $stats
            ];
        });
    }

    /**
     * Get yearly trends data (Revenue and Pendaftar per month per class)
     */
    public function getYearlyTrendData($year, $managedKelasIds = null)
    {
        $cacheKey = "analytics_yearly_trend_{$year}_" . ($managedKelasIds ? implode('_', $managedKelasIds->toArray()) : 'all');
        
        return Cache::remember($cacheKey, 1800, function () use ($year, $managedKelasIds) {
            $kelasQuery = Kelas::orderBy('nama_kelas');
            if ($managedKelasIds) {
                $kelasQuery->whereIn('id_kelas', $managedKelasIds);
            }
            $kelasList = $kelasQuery->get(['id_kelas', 'nama_kelas']);
            $kelasMap = $kelasList->pluck('nama_kelas', 'id_kelas')->toArray();
            
            // Initialize arrays for 12 months (0-11)
            $labels = [];
            for ($m = 1; $m <= 12; $m++) {
                $labels[] = Carbon::createFromDate($year, $m, 1)->isoFormat('MMM');
            }
            
            $revenue = [];
            $pendaftar = [];
            $keluar = [];
            
            foreach ($kelasMap as $id => $name) {
                $revenue[$name] = array_fill(0, 12, 0);
                $pendaftar[$name] = array_fill(0, 12, 0);
                $keluar[$name] = array_fill(0, 12, 0);
            }
            $revenue['Tanpa Kelas'] = array_fill(0, 12, 0);
            $pendaftar['Tanpa Kelas'] = array_fill(0, 12, 0);
            $keluar['Tanpa Kelas'] = array_fill(0, 12, 0);

            $isSqlite = DB::connection()->getDriverName() === 'sqlite';

            // 1. Revenue
            $revenueQuery = Invoice::where('status', 'PAID')
                ->whereIn('type', ['spp', 'pendaftaran'])
                ->whereYear('paid_at', $year)
                ->join('siswa', 'invoices.id_siswa', '=', 'siswa.id_siswa');
                
            if ($isSqlite) {
                $revenueQuery->selectRaw('siswa.id_kelas, cast(strftime("%m", invoices.paid_at) as integer) as month, SUM(invoices.total_amount) as total')
                             ->groupBy('siswa.id_kelas', DB::raw('cast(strftime("%m", invoices.paid_at) as integer)'));
            } else {
                $revenueQuery->selectRaw('siswa.id_kelas, MONTH(invoices.paid_at) as month, SUM(invoices.total_amount) as total')
                             ->groupBy('siswa.id_kelas', 'month');
            }
            
            if ($managedKelasIds) {
                $revenueQuery->whereIn('siswa.id_kelas', $managedKelasIds);
            }
            
            foreach ($revenueQuery->get() as $row) {
                $kName = $kelasMap[$row->id_kelas] ?? 'Tanpa Kelas';
                $mIndex = intval($row->month) - 1; // 0-based array index
                if (isset($revenue[$kName][$mIndex])) {
                    $revenue[$kName][$mIndex] += $row->total;
                }
            }

            // 2. Pendaftar
            $pendaftarQuery = Siswa::whereYear('created_at', $year)
                ->whereHas('invoices', fn($q) => $q->where('type', 'pendaftaran')->where('status', 'PAID'));
                
            if ($isSqlite) {
                $pendaftarQuery->selectRaw('id_kelas, cast(strftime("%m", created_at) as integer) as month, COUNT(*) as count')
                             ->groupBy('id_kelas', DB::raw('cast(strftime("%m", created_at) as integer)'));
            } else {
                $pendaftarQuery->selectRaw('id_kelas, MONTH(created_at) as month, COUNT(*) as count')
                             ->groupBy('id_kelas', 'month');
            }
            
            if ($managedKelasIds) {
                $pendaftarQuery->whereIn('id_kelas', $managedKelasIds);
            }
            
            foreach ($pendaftarQuery->get() as $row) {
                $kName = $kelasMap[$row->id_kelas] ?? 'Tanpa Kelas';
                $mIndex = intval($row->month) - 1;
                if (isset($pendaftar[$kName][$mIndex])) {
                    $pendaftar[$kName][$mIndex] += $row->count;
                }
            }
            
            // 3. Siswa Keluar
            $keluarQuery = Siswa::whereIn('status_siswa', ['Keluar', 'Resign'])
                ->whereYear('updated_at', $year);
                
            if ($isSqlite) {
                $keluarQuery->selectRaw('id_kelas, cast(strftime("%m", updated_at) as integer) as month, COUNT(*) as count')
                             ->groupBy('id_kelas', DB::raw('cast(strftime("%m", updated_at) as integer)'));
            } else {
                $keluarQuery->selectRaw('id_kelas, MONTH(updated_at) as month, COUNT(*) as count')
                             ->groupBy('id_kelas', 'month');
            }
            
            if ($managedKelasIds) {
                $keluarQuery->whereIn('id_kelas', $managedKelasIds);
            }
            
            foreach ($keluarQuery->get() as $row) {
                $kName = $kelasMap[$row->id_kelas] ?? 'Tanpa Kelas';
                $mIndex = intval($row->month) - 1;
                if (isset($keluar[$kName][$mIndex])) {
                    $keluar[$kName][$mIndex] += $row->count;
                }
            }
            
            // Clean up empty 'Tanpa Kelas'
            if (array_sum($revenue['Tanpa Kelas']) == 0) unset($revenue['Tanpa Kelas']);
            if (array_sum($pendaftar['Tanpa Kelas']) == 0) unset($pendaftar['Tanpa Kelas']);
            if (array_sum($keluar['Tanpa Kelas']) == 0) unset($keluar['Tanpa Kelas']);
            
            return [
                'labels' => $labels,
                'revenue' => $revenue,
                'pendaftar' => $pendaftar,
                'keluar' => $keluar,
            ];
        });
    }
}
