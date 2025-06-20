<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMassInvoices;
use App\Models\Invoice;
use App\Models\JobBatch;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;

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

        $query = Invoice::with(['siswa.user', 'siswa.kelas'])->orderBy('created_at', 'desc');

        // Filter berdasarkan pencarian
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

        // Filter berdasarkan kelas siswa
        if ($request->filled('kelas_id') && $request->input('kelas_id') !== '') {
            $query->whereHas('siswa', function ($q_siswa) use ($request) {
                $q_siswa->where('id_kelas', $request->input('kelas_id'));
            });
        }

        // Filter berdasarkan status pembayaran
        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }

        $invoiceList = $query->paginate(10)->withQueryString();
        $statusPembayaranOptions = ['PENDING', 'PAID', 'EXPIRED', 'FAILED', 'REFUNDED'];

        return Inertia::render('Admin/Invoices/Index', [
            'invoiceList' => $invoiceList->through(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'siswa_nama' => $invoice->siswa?->nama_siswa ?? 'Siswa Dihapus',
                    'kelas_nama' => $invoice->siswa?->kelas?->nama_kelas ?? '-',
                    'description' => $invoice->description,
                    'total_amount_formatted' => 'Rp ' . number_format($invoice->total_amount, 0, ',', '.'),
                    'status' => $invoice->status,
                    'due_date_formatted' => Carbon::parse($invoice->due_date)->isoFormat('D MMM YY'),
                    'xendit_payment_url' => $invoice->xendit_payment_url,
                    'created_at_formatted' => $invoice->created_at->isoFormat('D MMM YY, HH:mm'),
                ];
            }),
            'filters' => $request->only(['search', 'kelas_id', 'status']),
            'allSiswa' => Siswa::orderBy('nama_siswa')->with('user')->get(['id_siswa', 'nama_siswa', 'id_user', 'jumlah_spp_custom', 'admin_fee_custom', 'id_kelas']),
            'allKelas' => Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas', 'biaya_spp_default']),
            'allStatus' => $statusPembayaranOptions,
            'can' => ['create_invoice' => $request->user()->can('manage_all_tagihan')]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     if (!$request->user()->can('manage_all_tagihan')) {
    //         abort(403);
    //     }

    //     // Validasi
    //     $validated = $request->validate([
    //         'id_siswa' => 'required|uuid|exists:siswa,id_siswa',
    //         'periode_tagihan_bulan' => 'required|integer|min:1|max:12',
    //         'periode_tagihan_tahun' => 'required|integer|min:' . (date('Y') - 5) . '|max:' . (date('Y') + 5), // Rentang 5 tahun
    //         'jumlah_spp_ditagih' => 'required|numeric|min:0',
    //         'admin_fee_ditagih' => 'nullable|numeric|min:0',
    //         'tanggal_jatuh_tempo' => 'required|date|after_or_equal:today', // Tidak boleh di masa lalu
    //     ]);

    //     $bulan = $validated['periode_tagihan_bulan'];
    //     $tahun = $validated['periode_tagihan_tahun'];
    //     // Buat tanggal periode tagihan, misalnya selalu tanggal 1 setiap bulan
    //     $periodeTagihan = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
    //     $tanggalJatuhTempo = Carbon::parse($validated['tanggal_jatuh_tempo'])->endOfDay(); // Set ke akhir hari

    //     // Cek apakah tagihan untuk siswa dan periode ini sudah ada
    //     $existingTagihan = TagihanSpp::where('id_siswa', $validated['id_siswa'])
    //         ->whereDate('periode_tagihan', $periodeTagihan->toDateString())
    //         ->first();

    //     if ($existingTagihan) {
    //         return Redirect::back()->withErrors(['periode_tagihan_bulan' => 'Tagihan untuk siswa ini pada periode tersebut sudah ada.'])->withInput();
    //     }

    //     $adminFee = $validated['admin_fee_ditagih'] ?? 0;
    //     $totalTagihan = $validated['jumlah_spp_ditagih'] + $adminFee;

    //     TagihanSpp::create([
    //         'id_siswa' => $validated['id_siswa'],
    //         'periode_tagihan' => $periodeTagihan,
    //         'jumlah_spp_ditagih' => $validated['jumlah_spp_ditagih'],
    //         'admin_fee_ditagih' => $adminFee,
    //         'total_tagihan' => $totalTagihan,
    //         'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
    //         'status_pembayaran_xendit' => 'PENDING', // Default status
    //         // xendit_invoice_id dan xendit_payment_url akan diisi nanti saat integrasi Xendit
    //     ]);

    //     return Redirect::route('admin.tagihan_spp.index')->with([
    //         'message' => 'Tagihan SPP berhasil dibuat.',
    //         'type' => 'success'
    //     ]);
    // }

    public function store(Request $request, XenditService $xenditService)
    {
        if (!$request->user()->can('manage_all_tagihan')) {
            abort(403);
        }

        $validated = $request->validate([
            'id_siswa' => 'required|uuid|exists:siswa,id_siswa',
            'periode_tagihan_bulan' => 'required|integer|min:1|max:12',
            'periode_tagihan_tahun' => 'required|integer',
            'jumlah_spp_ditagih' => 'required|numeric|min:10000',
            'admin_fee_ditagih' => 'nullable|numeric|min:0',
            'tanggal_jatuh_tempo' => 'required|date|after_or_equal:today',
            'send_whatsapp_notif' => 'nullable|boolean',
        ]);

        $siswa = Siswa::with('user')->findOrFail($validated['id_siswa']);
        $bulan = $validated['periode_tagihan_bulan'];
        $tahun = $validated['periode_tagihan_tahun'];
        $periodeTagihan = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $tanggalJatuhTempo = Carbon::parse($validated['tanggal_jatuh_tempo'])->endOfDay();

        // Cek duplikasi invoice
        $existingInvoice = Invoice::where('id_siswa', $validated['id_siswa'])
            ->where('type', 'spp')
            ->whereDate('periode_tagihan', $periodeTagihan->toDateString())
            ->first();

        if ($existingInvoice) {
            return Redirect::back()->withErrors(['periode_tagihan_bulan' => 'Tagihan SPP untuk siswa ini pada periode tersebut sudah ada.'])->withInput();
        }
        
        $adminFee = $validated['admin_fee_ditagih'] ?? 0;
        $totalTagihan = (float)($validated['jumlah_spp_ditagih'] + $adminFee);

        $invoice = Invoice::create([
            'id_siswa' => $siswa->id_siswa,
            'user_id' => $request->user()->id,
            'type' => 'spp',
            'description' => "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa}",
            'periode_tagihan' => $periodeTagihan,
            'amount' => $validated['jumlah_spp_ditagih'],
            'admin_fee' => $adminFee,
            'total_amount' => $totalTagihan,
            'due_date' => $tanggalJatuhTempo,
            'status' => 'PENDING',
            'external_id_xendit' => 'SPP-'.$siswa->id_siswa.'-'.$tahun.str_pad($bulan, 2, '0', STR_PAD_LEFT).'-'.strtoupper(Str::random(6)),
        ]);
        
        $payerInfo = [
            'email' => $siswa->user?->email,
            'name' => $siswa->nama_siswa,
            'phone' => $siswa->nomor_telepon_wali,
        ];

        $notificationChannels = $request->boolean('send_whatsapp_notif') ? ['email', 'whatsapp'] : ['email'];
        
        $xenditInvoiceData = $xenditService->createInvoice(
            (float)$invoice->amount,
            (float)$invoice->admin_fee,
            $invoice->description,
            $payerInfo,
            $invoice->external_id_xendit,
            route('payment.success'),
            route('payment.failure'),
            Carbon::parse($invoice->due_date),
            $notificationChannels
        );

        $flashMessage = 'Invoice berhasil dibuat.';
        $flashType = 'success';

        if ($xenditInvoiceData && isset($xenditInvoiceData['invoice_url'])) {
            $invoice->update([
                'xendit_invoice_id' => $xenditInvoiceData['id'],
                'xendit_payment_url' => $xenditInvoiceData['invoice_url'],
                'status' => $xenditInvoiceData['status'],
            ]);
        } else {
            Log::error('Gagal membuat invoice Xendit untuk invoice ID: ' . $invoice->id);
            $flashMessage = 'Invoice berhasil dibuat di sistem, tetapi gagal membuat link pembayaran Xendit.';
            $flashType = 'warning';
        }

        return Redirect::route('admin.invoices.index')->with([
            'message' => $flashMessage,
            'type' => $flashType,
        ]);
    }

    public function bulkStore(Request $request, XenditService $xenditService)
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
            'send_whatsapp_notif' => 'nullable|boolean',
        ]);

        $kelas = Kelas::findOrFail($validated['id_kelas']);
        $bulan = $validated['periode_tagihan_bulan'];
        $tahun = $validated['periode_tagihan_tahun'];
        $periodeTagihan = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $tanggalJatuhTempo = Carbon::parse($validated['tanggal_jatuh_tempo'])->endOfDay();

        $siswaDiKelas = Siswa::where('id_kelas', $kelas->id_kelas)
                            ->where('status_siswa', 'Aktif')
                            ->whereDoesntHave('invoices', function ($query) use ($periodeTagihan) {
                                $query->where('type', 'spp')->whereDate('periode_tagihan', $periodeTagihan->toDateString());
                            })->with('user')->get();

        if ($siswaDiKelas->isEmpty()) {
            return Redirect::route('admin.invoices.index')->with([
                'message' => 'Tidak ada siswa aktif di kelas ' . $kelas->nama_kelas . ' yang belum memiliki tagihan untuk periode ini.',
                'type' => 'warning'
            ]);
        }

        $berhasilDibuat = 0;
        $gagalXendit = 0;
        
        foreach ($siswaDiKelas as $siswa) {
            $jumlahSPP = ($validated['jenis_jumlah_spp'] === 'manual') ? $validated['jumlah_spp_manual'] : ($siswa->jumlah_spp_custom ?? $kelas->biaya_spp_default ?? 0);
            $adminFee = ($validated['jenis_admin_fee'] === 'manual') ? ($validated['admin_fee_manual'] ?? 0) : ($siswa->admin_fee_custom ?? 0);
            $totalTagihan = (float)($jumlahSPP + $adminFee);

            if ($totalTagihan < 10000) { continue; }

            $invoice = Invoice::create([
                'id_siswa' => $siswa->id_siswa,
                'user_id' => $request->user()->id,
                'type' => 'spp',
                'description' => "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa}",
                'periode_tagihan' => $periodeTagihan,
                'amount' => $jumlahSPP,
                'admin_fee' => $adminFee,
                'total_amount' => $totalTagihan,
                'due_date' => $tanggalJatuhTempo,
                'status' => 'PENDING',
                'external_id_xendit' => 'SPP-'.$siswa->id_siswa.'-'.$tahun.str_pad($bulan, 2, '0', STR_PAD_LEFT).'-'.strtoupper(Str::random(6)),
            ]);

            $payerInfo = ['email' => $siswa->user?->email, 'name' => $siswa->nama_siswa, 'phone' => $siswa->nomor_telepon_wali];
            $notificationChannels = $request->boolean('send_whatsapp_notif') ? ['email', 'whatsapp'] : ['email'];
            
            $xenditInvoiceData = $xenditService->createInvoice((float)$jumlahSPP, (float)$adminFee, $invoice->description, $payerInfo, $invoice->external_id_xendit, route('payment.success'), route('payment.failure'), $tanggalJatuhTempo, $notificationChannels);

            if ($xenditInvoiceData && isset($xenditInvoiceData['invoice_url'])) {
                $invoice->update(['xendit_invoice_id' => $xenditInvoiceData['id'], 'xendit_payment_url' => $xenditInvoiceData['invoice_url'], 'status' => $xenditInvoiceData['status']]);
                $berhasilDibuat++;
            } else {
                $gagalXendit++;
                Log::error("[Bulk Store] Gagal membuat invoice Xendit untuk siswa: {$siswa->id_siswa}", ['external_id' => $invoice->external_id_xendit]);
            }
        }

        $message = "{$berhasilDibuat} tagihan berhasil dibuat dengan invoice Xendit.";
        if ($gagalXendit > 0) { $message .= " {$gagalXendit} tagihan gagal terhubung ke Xendit."; }
        if ($berhasilDibuat === 0 && $gagalXendit === 0 && $siswaDiKelas->isNotEmpty()) { $message = 'Tidak ada tagihan baru yang dibuat (kemungkinan semua siswa sudah memiliki tagihan atau jumlah tagihan 0).'; }

        return Redirect::route('admin.invoices.index')->with(['message' => $message, 'type' => ($gagalXendit > 0 || ($berhasilDibuat === 0 && $siswaDiKelas->isNotEmpty())) ? 'warning' : 'success']);
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

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
