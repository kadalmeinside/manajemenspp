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
            'kelas' => 'nullable|integer',
        ]);

        $user = $request->user();
        $selectedTahun = $request->input('tahun', now()->year);
        $selectedBulan = $request->input('bulan', now()->month);
        $selectedKelasId = $request->input('kelas', null);
        
        $managedKelasIds = null; // Unfiltered base allowed ids (for class comparison)
        
        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
        }

        $filteredKelasIds = $managedKelasIds; // The filtered ids (for all other metrics)
        
        if ($selectedKelasId) {
            $kelasInCabang = collect([$selectedKelasId]);
            if ($filteredKelasIds) {
                // Intersect allowed classes with the selected class
                $filteredKelasIds = $filteredKelasIds->intersect($kelasInCabang);
            } else {
                // Admin has no restrictions, but selected a class, so restrict to that class
                $filteredKelasIds = $kelasInCabang;
            }
        }
        
        // Get unique available classes (that the user is allowed to see)
        $availableKelasQuery = \App\Models\Kelas::query();
        if ($managedKelasIds) {
            $availableKelasQuery->whereIn('id_kelas', $managedKelasIds);
        }
        $availableKelas = $availableKelasQuery->get(['id_kelas', 'nama_kelas']);

        // Only load data if the component explicitly asks for it via lazy evaluation
        // This makes the initial page load fast.
        
        return Inertia::render('Admin/Analytics/Index', [
            'filters' => [
                'tahun' => (int)$selectedTahun, 
                'bulan' => (int)$selectedBulan,
                'kelas' => $selectedKelasId
            ],
            'availableYears' => range(date('Y'), date('Y') - 5),
            'availableKelas' => $availableKelas,
            
            // Lazy-loaded data using Inertia's closure syntax
            // Filtered by class:
            'revenue_mom' => Inertia::lazy(fn () => $this->analyticsService->getRevenueMoM($selectedBulan, $selectedTahun, $filteredKelasIds)),
            'payment_rate' => Inertia::lazy(fn () => $this->analyticsService->getPaymentRatePerClass($selectedBulan, $selectedTahun, $filteredKelasIds)),
            'registration_trends' => Inertia::lazy(fn () => $this->analyticsService->getRegistrationTrends(6, $filteredKelasIds)),
            'payment_methods' => Inertia::lazy(fn () => $this->analyticsService->getPaymentMethods($selectedBulan, $selectedTahun, $filteredKelasIds)),
            'resignation_rate' => Inertia::lazy(fn () => $this->analyticsService->getResignationRatePerClass($selectedBulan, $selectedTahun, $filteredKelasIds)),
            
            // Advanced Analytics (Filtered)
            'mrr_data' => Inertia::lazy(fn () => $this->analyticsService->getMRR($filteredKelasIds)),
            'aging_receivables' => Inertia::lazy(fn () => $this->analyticsService->getAgingReceivables($filteredKelasIds)),
            'cltv_data' => Inertia::lazy(fn () => $this->analyticsService->getCLTV($filteredKelasIds)),
            'time_to_pay' => Inertia::lazy(fn () => $this->analyticsService->getTimeToPay($filteredKelasIds)),
            
            // Class Comparison Analytics (Unfiltered by selected class, but still restricted by role)
            'revenue_per_kelas' => Inertia::lazy(fn () => $this->analyticsService->getRevenuePerKelas($selectedBulan, $selectedTahun, $managedKelasIds)),
            'registration_per_kelas' => Inertia::lazy(fn () => $this->analyticsService->getRegistrationPerKelas($selectedBulan, $selectedTahun, $managedKelasIds)),
            'payment_transition' => Inertia::lazy(fn () => $this->analyticsService->getPaymentTransitionPerKelas($selectedBulan, $selectedTahun, $managedKelasIds)),
        ]);
    }
}
