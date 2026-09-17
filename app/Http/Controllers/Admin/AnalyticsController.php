<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
        Carbon::setLocale('id');
    }

    public function index(Request $request)
    {
        $request->validate([
            'tahun' => 'nullable|integer|date_format:Y',
            'bulan' => 'nullable|integer|between:1,12',
            'tab' => 'nullable|string|in:ringkasan,tahunan',
        ]);

        $user = $request->user();
        $selectedTahun = $request->input('tahun', now()->year);
        $selectedBulan = $request->input('bulan', now()->month);
        
        $managedKelasIds = null;
        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
        }

        return Inertia::render('Admin/Analytics/Index', [
            'filters' => [
                'tahun' => (int)$selectedTahun, 
                'bulan' => (int)$selectedBulan,
                'tab' => $request->input('tab', 'ringkasan'),
            ],
            'availableYears' => range(date('Y'), date('Y') - 5),
            
            // Lazy load summary data
            'summary_data' => Inertia::lazy(fn () => $this->analyticsService->getSummaryData(
                $selectedBulan,
                $selectedTahun,
                $managedKelasIds
            )),

            // Lazy load yearly trends
            'yearly_trends' => Inertia::lazy(fn () => $this->analyticsService->getYearlyTrendData(
                $selectedTahun,
                $managedKelasIds
            )),
        ]);
    }
}
