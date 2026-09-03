<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMassInvoices;
use App\Models\Invoice;
use App\Models\JobBatch;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Notifications\InvoiceCreatedNotification;
use App\Services\NotificationService;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Throwable; 

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $user = $request->user();
        $query = Invoice::with(['siswa.user', 'siswa.kelas'])->orderBy('created_at', 'desc');

        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
            
            $query->whereHas('siswa', function ($q_siswa) use ($managedKelasIds) {
                $q_siswa->whereIn('id_kelas', $managedKelasIds);
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                  ->orWhere('external_id_xendit', 'LIKE', "%{$search}%")
                  ->orWhereHas('siswa', function ($q_siswa) use ($search) {
                      $q_siswa->where('nama_siswa', 'LIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('kelas_id') && $request->input('kelas_id') !== '') {
            $query->whereHas('siswa', function ($q_siswa) use ($request) {
                $q_siswa->where('id_kelas', $request->input('kelas_id'));
            });
        }

        if ($request->filled('type') && $request->input('type') !== '') {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status') && $request->input('status') !== '') {
            $status = $request->input('status');
            if ($status === 'TUNGGAKAN') {
                $query->whereIn('status', ['PENDING', 'EXPIRED'])
                      ->where('due_date', '<', now()->format('Y-m-d'));
            } else {
                $query->where('status', $status);
            }
        }

        // Filter periode bulan & tahun
        if ($request->filled('periode_bulan') && $request->input('periode_bulan') !== '') {
            $query->whereMonth('periode_tagihan', $request->input('periode_bulan'));
        }
        if ($request->filled('periode_tahun') && $request->input('periode_tahun') !== '') {
            $query->whereYear('periode_tagihan', $request->input('periode_tahun'));
        }

        $invoiceList = $query->paginate(15)->withQueryString();
        $statusPembayaranOptions = ['PENDING', 'PAID', 'EXPIRED', 'FAILED', 'REFUNDED', 'TUNGGAKAN'];

        $allKelasQuery = Kelas::orderBy('nama_kelas');
        $allSiswaQuery = Siswa::with(['user:id,email', 'kelas:id_kelas,nama_kelas'])
            ->select('id_siswa', 'nama_siswa', 'id_kelas', 'id_user', 'jumlah_spp_custom', 'admin_fee_custom')
            ->orderBy('nama_siswa', 'asc');
        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
            $allKelasQuery->whereIn('id_kelas', $managedKelasIds);
            $allSiswaQuery->whereIn('id_kelas', $managedKelasIds);
        }

        // Ambil tahun-tahun yang tersedia berdasarkan data invoice
        $availableYears = Invoice::selectRaw('YEAR(periode_tagihan) as year')
            ->whereNotNull('periode_tagihan')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
        if (empty($availableYears)) {
            $availableYears = [date('Y')];
        }
        
        return Inertia::render('Admin/Invoices/Index', [
            'invoiceList' => $invoiceList->through(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'siswa_nama' => $invoice->siswa?->nama_siswa ?? 'Siswa Dihapus',
                    'kelas_nama' => $invoice->siswa?->kelas?->nama_kelas ?? '-',
                    'description' => $invoice->description,
                    'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                    'status' => $invoice->status,
                    'payment_method' => $invoice->payment_method, // 'manual' atau null (xendit)
                    'due_date_formatted' => Carbon::parse($invoice->due_date)->isoFormat('D MMM YY'),
                    'paid_at_formatted' => $invoice->paid_at ? Carbon::parse($invoice->paid_at)->isoFormat('D MMM YY') : null,
                    'xendit_payment_url' => $invoice->xendit_payment_url,
                    'created_at_formatted' => $invoice->created_at->isoFormat('D MMM YY, HH:mm'),
                    'recreated_from_id' => $invoice->recreated_from_id,
                ];
            }),
            'filters' => $request->only(['search', 'kelas_id', 'status', 'periode_bulan', 'periode_tahun']),
            'allSiswa' => $allSiswaQuery->get(),
            'allKelas' => $allKelasQuery->get(['id_kelas', 'nama_kelas', 'biaya_spp_default']),
            'allStatus' => $statusPembayaranOptions,
            'availableYears' => $availableYears,
            'can' => ['create_invoice' => $request->user()->can('manage_all_tagihan')]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * Saat ini tidak digunakan (pembuatan invoice dilakukan via store() langsung).
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $validated = $request->validate([
            'id_siswa' => 'required|uuid|exists:siswa,id_siswa',
            'periode_tagihan_bulan' => 'required|integer|min:1|max:12',
            'periode_tagihan_tahun' => 'required|integer',
            'jumlah_spp_ditagih' => 'required|numeric|min:1',
            'admin_fee_ditagih' => 'nullable|numeric|min:0', // Admin fee ini sekarang diabaikan untuk total
            'tanggal_jatuh_tempo' => 'required|date|after_or_equal:today',
            'send_notification' => 'nullable|boolean',
        ]);

        $siswa = Siswa::with('user')->findOrFail($validated['id_siswa']);
        $periodeTagihan = Carbon::create($validated['periode_tagihan_tahun'], $validated['periode_tagihan_bulan'], 1)->startOfMonth();

        try {
            DB::transaction(function () use ($request, $validated, $siswa, $periodeTagihan) {
                $existingInvoice = Invoice::where('id_siswa', $siswa->id_siswa)
                    ->where('type', 'spp')
                    ->whereDate('periode_tagihan', $periodeTagihan->toDateString())
                    ->first();

                if ($existingInvoice) {
                    throw new \Exception('Tagihan SPP untuk siswa ini pada periode tersebut sudah ada.');
                }

                if (!$siswa->mulai_spp_date) {
                    throw new \Exception('Siswa ini belum memiliki jadwal mulai SPP (Tentukan terlebih dahulu di menu Siswa).');
                }

                if ($periodeTagihan->startOfMonth()->lt($siswa->mulai_spp_date->startOfMonth())) {
                    throw new \Exception('Gagal membuat tagihan. Jadwal mulai SPP siswa ini ('.$siswa->mulai_spp_date->isoFormat('MMM Y').') lebih lambat dari bulan tagihan yang Anda buat.');
                }

                // ### PERBAIKAN: Admin fee tidak lagi menjadi bagian dari total tagihan bulanan ###
                
                // Cek Cuti
                $approvedLeave = \App\Models\StudentLeave::where('id_siswa', $siswa->id_siswa)
                    ->where('month', $periodeTagihan->month)
                    ->where('year', $periodeTagihan->year)
                    ->where('status', 'approved')
                    ->first();

                // Baca nominal cuti dari Settings (bisa diubah dari dashboard admin)
                $cutiAmount = (float) (\App\Models\Setting::where('key', 'spp_cuti_amount')->value('value') ?? 250000);
                $sppAmount = $approvedLeave ? $cutiAmount : (float) $validated['jumlah_spp_ditagih'];
                
                Carbon::setLocale('id');
                $deskripsi = "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa} (NIS: {$siswa->nis})";
                
                if ($approvedLeave) {
                    $deskripsi .= " (CUTI)";
                }

                $invoice = Invoice::create([
                    'id_siswa' => $siswa->id_siswa,
                    'user_id' => $request->user()->id,
                    'type' => 'spp',
                    'description' => $deskripsi,
                    'periode_tagihan' => $periodeTagihan,
                    'amount' => $sppAmount,
                    'admin_fee' => 0, // Admin fee di tagihan bulanan selalu 0
                    'total_amount' => $sppAmount, // Total amount hanya sebesar SPP
                    'due_date' => Carbon::parse($validated['tanggal_jatuh_tempo'])->endOfDay(),
                    'status' => 'PENDING',
                ]);

                if ($request->boolean('send_notification')) {
                    $this->sendInvoiceNotification($siswa, $invoice);
                }
            });
        } catch (Throwable $e) {
            Log::error('Gagal membuat invoice individual: ' . $e->getMessage());
            return Redirect::back()->withErrors(['periode_tagihan_bulan' => $e->getMessage()])->withInput();
        }

        return Redirect::route('admin.invoices.index')->with([
            'type' => 'success',
            'message' => 'Invoice berhasil dibuat.'
        ]);
    }

    public function bulkStore(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $validated = $request->validate([
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'periode_tagihan_bulan' => 'required|integer|min:1|max:12',
            'periode_tagihan_tahun' => 'required|integer',
            'tanggal_jatuh_tempo' => 'required|date|after_or_equal:today',
            'jenis_jumlah_spp' => 'required|string|in:default,manual',
            'jumlah_spp_manual' => 'nullable|required_if:jenis_jumlah_spp,manual|numeric|min:0',
            'jenis_admin_fee' => 'required|string|in:default,manual',
            'admin_fee_manual' => 'nullable|numeric|min:0',
            'send_notification' => 'nullable|boolean',
        ]);

        $kelas = Kelas::findOrFail($validated['id_kelas']);
        $periodeTagihan = Carbon::create($validated['periode_tagihan_tahun'], $validated['periode_tagihan_bulan'], 1)->startOfMonth();
        $tanggalJatuhTempo = Carbon::parse($validated['tanggal_jatuh_tempo'])->endOfDay();

        $siswaDiKelas = Siswa::where('id_kelas', $kelas->id_kelas)
                            ->where('status_siswa', 'Aktif')
                            ->whereNotNull('mulai_spp_date')
                            ->whereDate('mulai_spp_date', '<=', $periodeTagihan->toDateString())
                            ->whereDoesntHave('invoices', fn($q) => $q->where('type', 'spp')->whereDate('periode_tagihan', $periodeTagihan->toDateString()))
                            ->with('user')->get();

        if ($siswaDiKelas->isEmpty()) {
            return Redirect::route('admin.invoices.index')->with([
                'type' => 'warning',
                'message' => 'Tidak ada siswa aktif di kelas ini yang belum memiliki tagihan untuk periode tersebut.'
            ]);
        }
        
        Carbon::setLocale('id');
        $successCount = 0;
        $failCount = 0;

        DB::transaction(function () use ($siswaDiKelas, $validated, $kelas, $periodeTagihan, $tanggalJatuhTempo, $request, &$successCount, &$failCount) {
            foreach ($siswaDiKelas as $siswa) {
                try {
                    $jumlahSPP = ($validated['jenis_jumlah_spp'] === 'manual') ? $validated['jumlah_spp_manual'] : ($siswa->jumlah_spp_custom ?? $kelas->biaya_spp_default ?? 0);
                    
                    // Cek Cuti
                    $approvedLeave = \App\Models\StudentLeave::where('id_siswa', $siswa->id_siswa)
                        ->where('month', $periodeTagihan->month)
                        ->where('year', $periodeTagihan->year)
                        ->where('status', 'approved')
                        ->first();

                    $cutiAmount = (float) (\App\Models\Setting::where('key', 'spp_cuti_amount')->value('value') ?? 250000);
                    $sppAmount = $approvedLeave ? $cutiAmount : (float) $jumlahSPP;

                    if ($sppAmount <= 0) {
                        continue;
                    }

                    $deskripsi = "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa} (NIS: {$siswa->nis})";
                    if ($approvedLeave) {
                        $deskripsi .= " (CUTI)";
                    }
                    
                    $invoice = Invoice::create([
                        'id_siswa' => $siswa->id_siswa,
                        'user_id' => $request->user()->id,
                        'type' => 'spp',
                        'description' => $deskripsi,
                        'periode_tagihan' => $periodeTagihan,
                        'amount' => $sppAmount,
                        'admin_fee' => 0, // Admin fee di tagihan bulanan selalu 0
                        'total_amount' => $sppAmount, // Total amount hanya sebesar SPP
                        'due_date' => $tanggalJatuhTempo,
                        'status' => 'PENDING',
                    ]);

                    if ($request->boolean('send_notification')) {
                        $this->sendInvoiceNotification($siswa, $invoice);
                    }
                    
                    $successCount++;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("[Bulk Store Sync] Gagal memproses invoice untuk siswa: {$siswa->id_siswa}. Error: " . $e->getMessage());
                    $failCount++;
                    continue; 
                }
            }
        });

        $message = "Proses pembuatan tagihan massal selesai. Berhasil: {$successCount}, Gagal/Dilewati: {$failCount}.";

        return Redirect::route('admin.invoices.index')->with([
            'type' => $failCount > 0 ? 'warning' : 'success',
            'message' => $message
        ]);
    }

    /**
     * Kirim notifikasi email ke wali siswa ketika invoice dibuat.
     * Digunakan oleh store() dan bulkStore() saat opsi 'send_notification' diaktifkan.
     */
    private function sendInvoiceNotification(Siswa $siswa, Invoice $invoice): void
    {
        if ($siswa->user && $siswa->user->email) {
            $siswa->user->notify(new InvoiceCreatedNotification($invoice));
        } else {
            Log::info("Notifikasi untuk invoice {$invoice->id} dilewati karena tidak ada email wali.");
        }
    }

    /**
     * Dispatch a job to create invoices for all active students.
     */
    public function bulkStoreAll(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $validated = $request->validate([
            'periode_tagihan_bulan' => 'required|integer|min:1|max:12',
            'periode_tagihan_tahun' => 'required|integer',
            'tanggal_jatuh_tempo' => 'required|date|after_or_equal:today',
            'send_whatsapp_notif' => 'nullable|boolean',
        ]);
        
        $jobBatch = JobBatch::create([
            'name' => "Generate Tagihan Massal - Semua Siswa Aktif",
            'user_id' => $request->user()->id,
        ]);

        $jobParams = [
            'id_kelas' => null, // null menandakan untuk semua siswa
            'bulan' => $validated['periode_tagihan_bulan'],
            'tahun' => $validated['periode_tagihan_tahun'],
            'jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
            'jenis_jumlah_spp' => 'default',
            'jumlah_spp_manual' => null,
            'jenis_admin_fee' => 'default',
            'admin_fee_manual' => null,
            'send_whatsapp_notif' => $request->boolean('send_whatsapp_notif'),
        ];

        GenerateMassInvoices::dispatch($jobBatch, $jobParams);

        return Redirect::route('admin.invoices.index')->with([
            'message' => 'Proses generate tagihan untuk semua siswa aktif telah dimulai di latar belakang.',
            'type' => 'info'
        ]);
    }


    public function recreate(Request $request, Invoice $invoice, XenditService $xenditService)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        if ($invoice->status !== 'EXPIRED') {
            return Redirect::back()->with([
                'message' => 'Hanya invoice EXPIRED yang dapat dibuat ulang.',
                'type' => 'error'
            ]);
        }

        $originalInvoiceId = $invoice->recreated_from_id ?? $invoice->id;

        $existingPending = Invoice::where('recreated_from_id', $originalInvoiceId)
                                  ->where('status', 'PENDING')
                                  ->exists();
        
        if ($existingPending) {
            return Redirect::back()->with([
                'message' => 'Sudah ada invoice pengganti yang aktif (status PENDING) untuk tagihan ini.',
                'type' => 'error'
            ]);
        }

        try {
            DB::transaction(function () use ($request, $invoice, $xenditService) {
                
                $lockedInvoice = Invoice::where('id', $invoice->id)->lockForUpdate()->first();
                $recreatedInvoice = $lockedInvoice->replicate([
                    'id', 'xendit_invoice_id', 'xendit_payment_url', 'external_id_xendit', 
                    'status', 'paid_at', 'recreated_from_id'
                ]);

                $recreatedInvoice->recreated_from_id = $originalInvoiceId; 
                $recreatedInvoice->status = 'PENDING';
                $recreatedInvoice->due_date = now()->addDays(3)->endOfDay();
                $recreatedInvoice->description = $lockedInvoice->description;
                // Limit total length to 50 for Midtrans
                $baseId = substr($lockedInvoice->external_id_xendit, 0, 30); 
                $recreatedInvoice->external_id_xendit = 'RE-'.$baseId . '-' . strtoupper(Str::random(4));

                // Panggil Xendit
                $payerInfo = ['email' => $lockedInvoice->siswa->user?->email, 'name' => $lockedInvoice->siswa->nama_siswa, 'phone' => $lockedInvoice->siswa->nomor_telepon_wali];
                
                $xenditInvoiceData = $xenditService->createInvoice(
                    (float)$recreatedInvoice->amount, (float)$recreatedInvoice->admin_fee, $recreatedInvoice->description,
                    $payerInfo, $recreatedInvoice->external_id_xendit, route('payment.success'),
                    route('payment.failure'), Carbon::parse($recreatedInvoice->due_date), ['email', 'whatsapp']
                );

                if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
                    throw new \Exception('Gagal membuat link pembayaran baru di Xendit.');
                }

                $recreatedInvoice->xendit_invoice_id = $xenditInvoiceData['id'];
                $recreatedInvoice->xendit_payment_url = $xenditInvoiceData['invoice_url'];
                $recreatedInvoice->save();

            });

        } catch (Throwable $e) {
            Log::error('Gagal membuat ulang invoice: ' . $e->getMessage());
            return Redirect::back()->withErrors(['recreate_error' => $e->getMessage()])->withInput();
        }

        return Redirect::back()->with(['message' => 'Invoice baru berhasil dibuat ulang.', 'type' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Invoice $invoice, XenditService $xenditService)
    {
        if (!$request->user()->can('manage_all_tagihan')) { 
            abort(403);
        }

        if ($invoice->status !== 'PENDING') {
            return Redirect::back()->with([
                'message' => 'Hanya invoice dengan status PENDING yang dapat dibatalkan.',
                'type'    => 'error'
            ]);
        }

        // Jika invoice tidak punya xendit_invoice_id, berarti dibuat manual (tidak via Xendit)
        // Langsung cancel secara lokal tanpa perlu panggil API Xendit
        if (!$invoice->xendit_invoice_id) {
            $invoice->update(['status' => 'EXPIRED']);
            return Redirect::back()->with([
                'message' => 'Invoice manual berhasil dibatalkan.',
                'type'    => 'success'
            ]);
        }

        // Invoice punya xendit_invoice_id — expire via Xendit API
        $xenditResponse = $xenditService->expireInvoice($invoice->xendit_invoice_id);

        if ($xenditResponse && isset($xenditResponse['status']) && $xenditResponse['status'] === 'EXPIRED') {
            $invoice->update([
                'status'                  => 'EXPIRED',
                'xendit_callback_payload' => $xenditResponse,
            ]);
            return Redirect::back()->with([
                'message' => 'Invoice berhasil dibatalkan.',
                'type'    => 'success'
            ]);
        }

        Log::error('Gagal membatalkan invoice di Xendit.', ['invoice_id' => $invoice->id]);
        return Redirect::back()->with([
            'message' => 'Gagal membatalkan invoice di sisi penyedia pembayaran. Silakan coba lagi.',
            'type'    => 'error'
        ]);
    }

    public function markAsPaid(Request $request, Invoice $invoice, \App\Services\XenditService $xenditService)
    {
        // Pastikan hanya admin yang berwenang yang bisa mengakses
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        // Hanya invoice PENDING yang bisa ditandai lunas
        if ($invoice->status !== 'PENDING') {
            return Redirect::back()->with([
                'message' => 'Hanya invoice dengan status PENDING yang dapat ditandai lunas.',
                'type' => 'error'
            ]);
        }

        // Expire Xendit invoice jika ada (Pencegahan Double Payment)
        if ($invoice->xendit_invoice_id) {
            try {
                $xenditService->expireInvoice($invoice->xendit_invoice_id);
            } catch (\Exception $e) {
                Log::error('Gagal membatalkan invoice di Xendit saat Tandai Lunas manual.', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Handle bukti pembayaran if uploaded
        $buktiPath = null;
        if ($request->hasFile('bukti_pembayaran')) {
            $buktiPath = $request->file('bukti_pembayaran')->store('bukti_pembayaran', 'public');
        }

        // Update status invoice
        $invoice->update([
            'status' => 'PAID',
            'paid_at' => now(),
            'payment_method' => 'manual', // Tandai sebagai pembayaran manual
            'bukti_pembayaran' => $buktiPath,
        ]);

        $siswa = clone $invoice->siswa;
        
        app(NotificationService::class)->sendToAdmins([
            'title' => 'Pembayaran Manual',
            'message' => "Pembayaran manual untuk {$siswa->nama_siswa} telah dikonfirmasi.",
            'type' => 'payment_manual',
            'url' => '/admin/invoices'
        ], $siswa->id_kelas ?? null);

        // Jika tagihan pendaftaran, ubah status siswa menjadi Aktif
        if ($invoice->type === 'pendaftaran') {
            $siswa = $invoice->siswa;
            if ($siswa && strtolower($siswa->status_siswa) !== 'aktif') {
                $siswa->update(['status_siswa' => 'Aktif']);
                Log::info('[Manual Pay] Siswa berhasil diaktifkan setelah bayar pendaftaran secara manual.', [
                    'id_siswa' => $siswa->id_siswa,
                    'invoice_id' => $invoice->id
                ]);
            }
        }

        // Kirim notifikasi sukses
        return Redirect::back()->with([
            'type' => 'success',
            'message' => 'Invoice berhasil ditandai sebagai LUNAS.'
        ]);
    }

    /**
     * Export paid invoices by date range.
     */
    public function exportPaid(Request $request)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $start = Carbon::parse($request->input('start_date'));
        $end = Carbon::parse($request->input('end_date'));
        
        if ($start->diffInDays($end) > 365) {
            return Redirect::back()->with([
                'message' => 'Rentang tanggal maksimal adalah 1 tahun.',
                'type' => 'error'
            ]);
        }

        $filename = 'rekap-invoice-lunas-' . date('YmdHis') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PaidInvoicesExport($request->input('start_date'), $request->input('end_date')), 
            $filename
        );
    }
}
