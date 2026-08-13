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
            
            $incomeTypes = ['spp', 'pendaftaran', 'pembayaran_spp_gabungan'];

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
                ->whereMonth('paid_at', $month)
                ->whereYear('paid_at', $year);

            if ($managedKelasIds) {
                $query->whereHas('siswa', fn($q) => $q->whereIn('id_kelas', $managedKelasIds));
            }

            // Using application-level calculation for simplicity with SQLite compatibility
            $invoices = $query->get(['payment_method', 'total_amount']);
            
            $xenditTotal = 0;
            $xenditRevenue = 0;
            $manualTotal = 0;
            $manualRevenue = 0;
            
            foreach ($invoices as $inv) {
                if ($inv->payment_method === 'manual') {
                    $manualTotal++;
                    $manualRevenue += $inv->total_amount;
                } else {
                    // Null or anything else is assumed Xendit/Gateway
                    $xenditTotal++;
                    $xenditRevenue += $inv->total_amount;
                }
            }

            return [
                'labels' => ['Xendit (Otomatis)', 'Manual'],
                'data_count' => [$xenditTotal, $manualTotal],
                'data_revenue' => [$xenditRevenue, $manualRevenue]
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
                $q->where('status_siswa', 'Resign')
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
}
