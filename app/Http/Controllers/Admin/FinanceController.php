<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Invoice;
use App\Models\Withdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->input('period', 'this_month');
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $gateway = $request->input('gateway', '');
        $tab = $request->input('tab', 'invoices');

        // Resolve Date Range based on period
        $startDate = null;
        $endDate = null;
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'last_7_days':
                $startDate = $now->copy()->subDays(6)->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'this_month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
            case 'last_month':
                $startDate = $now->copy()->subMonth()->startOfMonth();
                $endDate = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'custom':
                if ($startDateInput) {
                    $startDate = Carbon::parse($startDateInput)->startOfDay();
                }
                if ($endDateInput) {
                    $endDate = Carbon::parse($endDateInput)->endOfDay();
                }
                break;
            case 'all':
            default:
                // No date restriction
                break;
        }

        // Base query for invoices within selected period
        $periodInvoicesQuery = Invoice::whereIn('status', ['PAID', 'SETTLED'])
            ->whereNull('parent_payment_id');

        if ($startDate && $endDate) {
            $periodInvoicesQuery->whereBetween('paid_at', [$startDate, $endDate]);
        } elseif ($startDate) {
            $periodInvoicesQuery->where('paid_at', '>=', $startDate);
        } elseif ($endDate) {
            $periodInvoicesQuery->where('paid_at', '<=', $endDate);
        }

        if ($gateway) {
            $periodInvoicesQuery->where('payment_gateway', $gateway);
        }

        // Metrics for Selected Period
        $periodGross = (clone $periodInvoicesQuery)->sum('total_amount');
        $periodFee = (clone $periodInvoicesQuery)->sum('admin_fee');
        $periodNet = (clone $periodInvoicesQuery)->sum('amount');
        $periodTransactionsCount = (clone $periodInvoicesQuery)->count();

        // Breakdown per Payment Channel / Gateway for Selected Period
        $channelBreakdown = (clone $periodInvoicesQuery)
            ->select(
                DB::raw("COALESCE(NULLIF(payment_method, ''), NULLIF(payment_gateway, ''), 'Manual/Lainnya') as channel"),
                DB::raw("COUNT(*) as transaction_count"),
                DB::raw("SUM(amount) as net_amount"),
                DB::raw("SUM(admin_fee) as fee_amount"),
                DB::raw("SUM(total_amount) as gross_amount")
            )
            ->groupBy('channel')
            ->orderByDesc('gross_amount')
            ->get()
            ->map(function ($item) {
                return [
                    'channel' => strtoupper(str_replace('_', ' ', $item->channel)),
                    'count' => (int)$item->transaction_count,
                    'net' => (float)$item->net_amount,
                    'net_formatted' => 'Rp ' . number_format($item->net_amount, 0, ',', '.'),
                    'fee' => (float)$item->fee_amount,
                    'fee_formatted' => 'Rp ' . number_format($item->fee_amount, 0, ',', '.'),
                    'gross' => (float)$item->gross_amount,
                    'gross_formatted' => 'Rp ' . number_format($item->gross_amount, 0, ',', '.'),
                ];
            });

        // All-Time metrics (for balance & total withdrawals)
        $allTimeNet = Invoice::whereIn('status', ['PAID', 'SETTLED'])->whereNull('parent_payment_id')->sum('amount');
        $totalWithdraw = Withdrawal::where('status', 'COMPLETED')->sum('amount');
        $pendingBalance = $allTimeNet - $totalWithdraw;

        // Data for Paginated Tabs
        $invoices = [];
        $withdrawals = [];

        if ($tab === 'invoices') {
            $invoicesQuery = (clone $periodInvoicesQuery)
                ->with(['siswa' => function ($q) {
                    $q->with('kelas');
                }])
                ->orderBy('paid_at', 'desc');

            $invoices = $invoicesQuery->paginate(15)->withQueryString()->through(function ($inv) {
                return [
                    'id' => $inv->id,
                    'payment_gateway' => $inv->payment_gateway ? strtoupper($inv->payment_gateway) : 'MANUAL',
                    'payment_method' => $inv->payment_method ? strtoupper($inv->payment_method) : '-',
                    'paid_at' => $inv->paid_at,
                    'siswa' => [
                        'nama_siswa' => $inv->siswa?->nama_siswa ?? 'Siswa Dihapus',
                        'kelas' => [
                            'nama_kelas' => $inv->siswa?->kelas?->nama_kelas ?? '-'
                        ]
                    ],
                    'amount' => (float)$inv->amount,
                    'amount_formatted' => 'Rp ' . number_format($inv->amount, 0, ',', '.'),
                    'admin_fee' => (float)($inv->admin_fee ?? 0),
                    'admin_fee_formatted' => $inv->admin_fee ? 'Rp ' . number_format($inv->admin_fee, 0, ',', '.') : 'Rp 0',
                    'total_amount' => (float)$inv->total_amount,
                    'total_amount_formatted' => 'Rp ' . number_format($inv->total_amount, 0, ',', '.'),
                ];
            });
        } else {
            $withdrawalsQuery = Withdrawal::orderBy('created_at', 'desc');
            if ($startDate && $endDate) {
                $withdrawalsQuery->whereBetween('created_at', [$startDate, $endDate]);
            }
            $withdrawals = $withdrawalsQuery->paginate(15)->withQueryString();
        }

        return Inertia::render('Admin/Finance/Index', [
            'metrics' => [
                'period_gross' => (float)$periodGross,
                'period_fee' => (float)$periodFee,
                'period_net' => (float)$periodNet,
                'period_transactions_count' => (int)$periodTransactionsCount,
                'total_withdraw' => (float)$totalWithdraw,
                'pending_balance' => (float)$pendingBalance,
                'all_time_net' => (float)$allTimeNet,
            ],
            'channelBreakdown' => $channelBreakdown,
            'invoices' => $invoices,
            'withdrawals' => $withdrawals,
            'currentTab' => $tab,
            'gateway' => $gateway,
            'filters' => [
                'period' => $period,
                'start_date' => $startDate ? $startDate->format('Y-m-d') : ($startDateInput ?? ''),
                'end_date' => $endDate ? $endDate->format('Y-m-d') : ($endDateInput ?? ''),
                'gateway' => $gateway,
            ]
        ]);
    }
}
