<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Siswa;
use App\Models\StudentLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use App\Services\XenditService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class StudentLeaveController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a newly created leave request.
     * Accessible by Public (via /cek-spp) and Student (via Dashboard).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_siswa' => 'required|exists:siswa,id_siswa',
            'months' => 'required|array|min:1',
            'months.*' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:' . date('Y'),
            'reason' => 'required|string|max:500',
        ]);

        $existsMonths = [];
        foreach ($validated['months'] as $month) {
            $exists = StudentLeave::where('id_siswa', $validated['id_siswa'])
                ->where('month', $month)
                ->where('year', $validated['year'])
                ->whereIn('status', ['pending', 'approved'])
                ->exists();
            if ($exists) {
                $existsMonths[] = $month;
            }
        }

        if (!empty($existsMonths)) {
            $monthNames = array_map(function($m) { 
                return \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F'); 
            }, $existsMonths);
            return Redirect::back()->withErrors(['error' => 'Sudah ada pengajuan cuti untuk bulan: ' . implode(', ', $monthNames)]);
        }

        foreach ($validated['months'] as $month) {
            StudentLeave::create([
                'id_siswa' => $validated['id_siswa'],
                'month' => $month,
                'year' => $validated['year'],
                'reason' => $validated['reason'],
                'status' => 'pending',
            ]);
        }

        return Redirect::back()->with([
            'type' => 'success',
            'message' => count($validated['months']) . ' pengajuan cuti berhasil dikirim dan menunggu persetujuan admin.',
        ]);
    }

    /**
     * Display a listing of the resource (Admin only).
     */
    public function index(Request $request)
    {
        $this->authorize('manage_all_tagihan'); // Assuming same permission as invoices for now

        $query = StudentLeave::with(['siswa.kelas', 'approver'])->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return Inertia::render('Admin/Leaves/Index', [
            'leaves' => $query->paginate(10)->withQueryString()->through(fn ($leave) => [
                'id' => $leave->id,
                'siswa_nama' => $leave->siswa?->nama_siswa,
                'kelas_nama' => $leave->siswa?->kelas?->nama_kelas,
                'month_year' => $leave->month . '/' . $leave->year,
                'reason' => $leave->reason,
                'status' => $leave->status,
                'approved_by' => $leave->approver?->name,
                'created_at' => $leave->created_at->format('d M Y'),
                'period_date' => $leave->year . '-' . str_pad($leave->month, 2, '0', STR_PAD_LEFT) . '-01',
                'invoice_status' => \App\Models\Invoice::where('id_siswa', $leave->id_siswa)
                    ->where('type', 'spp')
                    ->whereMonth('periode_tagihan', $leave->month)
                    ->whereYear('periode_tagihan', $leave->year)
                    ->value('status'),
            ]),
            'filters' => $request->only(['status']),
            'pageTitle' => 'Pengajuan Cuti Siswa',
            'siswaOptions' => \App\Models\Siswa::where('status_siswa', 'Aktif')->select('id_siswa', 'nama_siswa', 'nis')->orderBy('nama_siswa')->get(),
        ]);
    }

    /**
     * Approve the specified leave request.
     */
    public function approve(Request $request, StudentLeave $studentLeave, XenditService $xenditService)
    {
        $this->authorize('manage_all_tagihan');

        if ($studentLeave->status !== 'pending') {
            return Redirect::back()->with(['type' => 'error', 'message' => 'Status pengajuan tidak valid.']);
        }

        $resultMessage = 'Pengajuan cuti disetujui.';

        try {
            \DB::transaction(function () use ($request, $studentLeave, $xenditService, &$resultMessage) {
                $studentLeave->update([
                    'status' => 'approved',
                    'approved_by' => $request->user()->id,
                ]);

                $this->processLeaveInvoice($studentLeave, $request->user()->id, $xenditService, $resultMessage);
            });
        } catch (\Exception $e) {
            Log::error('Error approving student leave: ' . $e->getMessage());
            return Redirect::back()->with(['type' => 'error', 'message' => 'Terjadi kesalahan saat memproses persetujuan: ' . $e->getMessage()]);
        }

        return Redirect::back()->with(['type' => 'success', 'message' => $resultMessage]);
    }

    /**
     * Helper to process invoice for an approved leave.
     */
    private function processLeaveInvoice(StudentLeave $studentLeave, $userId, XenditService $xenditService, &$resultMessage)
    {
        // Check for EXISTING pending invoice
        $invoice = Invoice::where('id_siswa', $studentLeave->id_siswa)
            ->where('type', 'spp')
            ->whereMonth('periode_tagihan', $studentLeave->month)
            ->whereYear('periode_tagihan', $studentLeave->year)
            ->where('status', 'PENDING')
            ->first();

        if ($invoice) {
            $cutiAmount = (float) (\App\Models\Setting::where('key', 'spp_cuti_amount')->value('value') ?? 250000);
            
            $invoice->update([
                'amount' => $cutiAmount,
                'total_amount' => $cutiAmount + $invoice->admin_fee, 
                'description' => $invoice->description . ' (CUTI)',
            ]);
            $resultMessage .= ' Invoice bulan tersebut telah diperbarui menjadi Rp ' . number_format($cutiAmount, 0, ',', '.');
        } else {
            $siswa = $studentLeave->siswa;
            if (!$siswa) $siswa = Siswa::with('user')->find($studentLeave->id_siswa);
            
            $periodeTagihan = Carbon::create($studentLeave->year, $studentLeave->month, 1)->startOfMonth();
            Carbon::setLocale('id');
            $deskripsi = "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa} (NIS: {$siswa->nis}) (CUTI)";
            $cutiAmount = (float) (\App\Models\Setting::where('key', 'spp_cuti_amount')->value('value') ?? 250000);
            $amount = $cutiAmount;
            $adminFee = 0;
            $totalAmount = $amount + $adminFee;
            
            $dueDate = $periodeTagihan->copy()->day(10)->endOfDay();
            if ($dueDate->isPast()) {
                $dueDate = now()->addDays(7)->endOfDay();
            }

            $invoice = Invoice::create([
                'id_siswa' => $siswa->id_siswa,
                'user_id' => $userId,
                'type' => 'spp',
                'description' => $deskripsi,
                'periode_tagihan' => $periodeTagihan,
                'amount' => $amount,
                'admin_fee' => $adminFee,
                'total_amount' => $totalAmount,
                'due_date' => $dueDate,
                'status' => 'PENDING',
                'external_id_xendit' => 'SPP-'.$siswa->id_siswa.'-'.$studentLeave->year.str_pad($studentLeave->month, 2, '0', STR_PAD_LEFT).'-'.strtoupper(Str::random(6)),
            ]);

            $payerInfo = ['email' => $siswa->user?->email, 'name' => $siswa->nama_siswa, 'phone' => $siswa->nomor_telepon_wali];
            $xenditInvoiceData = $xenditService->createInvoice(
                (float)$amount, (float)$adminFee, $deskripsi,
                $payerInfo, $invoice->external_id_xendit, route('payment.success'),
                route('payment.failure'), $dueDate, ['email']
            );

            if ($xenditInvoiceData && isset($xenditInvoiceData['invoice_url'])) {
                $invoice->update([
                    'xendit_invoice_id' => $xenditInvoiceData['id'],
                    'xendit_payment_url' => $xenditInvoiceData['invoice_url'],
                    'status' => $xenditInvoiceData['status'],
                ]);
                $resultMessage .= ' Invoice baru otomatis dibuat sebesar Rp 250.000.';
            } else {
                Log::error('Gagal membuat invoice Xendit otomatis untuk Cuti ID: '.$studentLeave->id);
                $resultMessage .= ' Invoice dibuat lokal, tapi gagal generate Link Pembayaran (Xendit).';
            }
        }
    }

    /**
     * Store a newly created leave request directly from Admin (automatically approved).
     */
    public function storeAdmin(Request $request, XenditService $xenditService)
    {
        $this->authorize('manage_all_tagihan');

        $validated = $request->validate([
            'id_siswa' => 'required|exists:siswa,id_siswa',
            'months' => 'required|array|min:1',
            'months.*' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:' . date('Y'),
            'reason' => 'required|string|max:500',
        ]);

        $existsMonths = [];
        foreach ($validated['months'] as $month) {
            $exists = StudentLeave::where('id_siswa', $validated['id_siswa'])
                ->where('month', $month)
                ->where('year', $validated['year'])
                ->whereIn('status', ['pending', 'approved'])
                ->exists();
            if ($exists) {
                $existsMonths[] = $month;
            }
        }

        if (!empty($existsMonths)) {
            $monthNames = array_map(function($m) { 
                return \Carbon\Carbon::create(null, $m, 1)->translatedFormat('F'); 
            }, $existsMonths);
            return Redirect::back()->withErrors(['error' => 'Sudah ada pengajuan cuti untuk bulan: ' . implode(', ', $monthNames)]);
        }

        $resultMessage = 'Cuti berhasil ditambahkan dan otomatis disetujui.';

        try {
            \DB::transaction(function () use ($validated, $request, $xenditService, &$resultMessage) {
                foreach ($validated['months'] as $month) {
                    $studentLeave = StudentLeave::create([
                        'id_siswa' => $validated['id_siswa'],
                        'month' => $month,
                        'year' => $validated['year'],
                        'reason' => $validated['reason'],
                        'status' => 'approved',
                        'approved_by' => $request->user()->id,
                    ]);

                    $this->processLeaveInvoice($studentLeave, $request->user()->id, $xenditService, $resultMessage);
                }
            });
        } catch (\Exception $e) {
            Log::error('Error storing admin leave: ' . $e->getMessage());
            return Redirect::back()->with(['type' => 'error', 'message' => 'Terjadi kesalahan saat memproses data cuti: ' . $e->getMessage()]);
        }

        return Redirect::back()->with(['type' => 'success', 'message' => $resultMessage]);
    }

    /**
     * Reject the specified leave request.
     */
    public function reject(Request $request, StudentLeave $studentLeave)
    {
        $this->authorize('manage_all_tagihan');

        if ($studentLeave->status !== 'pending') {
             return Redirect::back()->with(['type' => 'error', 'message' => 'Status pengajuan tidak valid.']);
        }

        $studentLeave->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
        ]);

        return Redirect::back()->with(['type' => 'success', 'message' => 'Pengajuan cuti ditolak.']);
    }

    /**
     * Cancel an approved leave request.
     */
    public function cancel(Request $request, StudentLeave $studentLeave)
    {
        $this->authorize('manage_all_tagihan');

        if ($studentLeave->status !== 'approved') {
             return Redirect::back()->with(['type' => 'error', 'message' => 'Hanya cuti yang disetujui yang dapat dibatalkan.']);
        }

        $resultMessage = 'Cuti berhasil dibatalkan dan tagihan SPP dikembalikan ke nominal normal.';

        try {
            \DB::transaction(function () use ($request, $studentLeave, &$resultMessage) {
                // Find invoice
                $invoice = Invoice::where('id_siswa', $studentLeave->id_siswa)
                    ->where('type', 'spp')
                    ->whereMonth('periode_tagihan', $studentLeave->month)
                    ->whereYear('periode_tagihan', $studentLeave->year)
                    ->first();

                if ($invoice) {
                    if (in_array(strtoupper($invoice->status), ['PAID', 'SETTLED'])) {
                        throw new \Exception('Tagihan SPP untuk bulan cuti ini sudah lunas. Cuti tidak dapat dibatalkan.');
                    }

                    // Revert invoice amount to normal SPP
                    $siswa = $studentLeave->siswa;
                    if (!$siswa) $siswa = Siswa::with('kelas')->find($studentLeave->id_siswa);
                    
                    $jumlahSPP = $siswa->jumlah_spp_custom ?? $siswa->kelas->biaya_spp_default ?? 0;
                    $adminFee = $invoice->admin_fee ?? 0;

                    $newDescription = str_replace(' (CUTI)', '', $invoice->description);

                    $invoice->update([
                        'amount' => $jumlahSPP,
                        'total_amount' => $jumlahSPP + $adminFee,
                        'description' => $newDescription,
                    ]);
                }

                $studentLeave->update([
                    'status' => 'cancelled',
                    'approved_by' => $request->user()->id,
                ]);
            });
        } catch (\Exception $e) {
            return Redirect::back()->with(['type' => 'error', 'message' => $e->getMessage()]);
        }

        return Redirect::back()->with(['type' => 'success', 'message' => $resultMessage]);
    }
}
