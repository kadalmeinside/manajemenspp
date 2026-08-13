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
        ]);

        $user = $request->user();
        $selectedTahun = $request->input('tahun', now()->year);
        $selectedBulan = $request->input('bulan', now()->month);
        
        $managedKelasIds = null;
        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
        }

        // Only load data if the component explicitly asks for it via lazy evaluation
        // This makes the initial page load fast.
        
        return Inertia::render('Admin/Analytics/Index', [
            'filters' => [
                'tahun' => (int)$selectedTahun, 
                'bulan' => (int)$selectedBulan
            ],
            'availableYears' => range(date('Y'), date('Y') - 5),
            
            // Lazy-loaded data using Inertia's closure syntax
            'revenue_mom' => Inertia::lazy(fn () => $this->analyticsService->getRevenueMoM($selectedBulan, $selectedTahun, $managedKelasIds)),
            'payment_rate' => Inertia::lazy(fn () => $this->analyticsService->getPaymentRatePerClass($selectedBulan, $selectedTahun, $managedKelasIds)),
            'registration_trends' => Inertia::lazy(fn () => $this->analyticsService->getRegistrationTrends(6, $managedKelasIds)),
            'payment_methods' => Inertia::lazy(fn () => $this->analyticsService->getPaymentMethods($selectedBulan, $selectedTahun, $managedKelasIds)),
            'resignation_rate' => Inertia::lazy(fn () => $this->analyticsService->getResignationRatePerClass($selectedBulan, $selectedTahun, $managedKelasIds)),
        ]);
    }
}
