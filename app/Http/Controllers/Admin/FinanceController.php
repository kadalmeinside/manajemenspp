<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Invoice;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        // Metric 1: Total Pemasukan Kotor (Total paid invoices, top-level only)
        $totalGross = Invoice::where('status', 'PAID')->whereNull('parent_payment_id')->sum('total_amount');
        
        // Metric 2: Total Biaya Layanan (Total admin fees of paid invoices, top-level only)
        $totalFee = Invoice::where('status', 'PAID')->whereNull('parent_payment_id')->sum('admin_fee');
        
        // Metric 3: Total Pendapatan Bersih (Total net amounts, top-level only)
        // If amount represents net, use it, otherwise gross - fee
        // Let's rely on amount as the net
        $totalNet = Invoice::where('status', 'PAID')->whereNull('parent_payment_id')->sum('amount');
        
        // Metric 4: Total Penarikan (Total COMPLETED withdrawals)
        $totalWithdraw = Withdrawal::where('status', 'COMPLETED')->sum('amount');
        
        // Metric 5: Dana Mengendap
        $pendingBalance = $totalNet - $totalWithdraw;

        // Query param for tabs (invoices vs withdrawals)
        $tab = $request->input('tab', 'invoices');

        $invoices = [];
        $withdrawals = [];

        $gateway = $request->input('gateway', '');

        if ($tab === 'invoices') {
            $invoicesQuery = Invoice::with(['siswa' => function($q) {
                $q->with('kelas');
            }])
            ->where('status', 'PAID')
            ->whereNull('parent_payment_id')
            ->orderBy('paid_at', 'desc');

            if ($gateway) {
                $invoicesQuery->where('payment_gateway', $gateway);
            }

            $invoices = $invoicesQuery->paginate(15)->withQueryString();
        } else {
            $withdrawals = Withdrawal::orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        }

        return Inertia::render('Admin/Finance/Index', [
            'metrics' => [
                'total_gross' => $totalGross,
                'total_fee' => $totalFee,
                'total_net' => $totalNet,
                'total_withdraw' => $totalWithdraw,
                'pending_balance' => $pendingBalance,
            ],
            'invoices' => $invoices,
            'withdrawals' => $withdrawals,
            'currentTab' => $tab,
            'gateway' => $gateway,
        ]);
    }
}
