<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateMassInvoices;
use App\Models\Invoice;
use App\Models\JobBatch;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Notifications\InvoiceCreatedNotification;
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

        if ($request->filled('status') && $request->input('status') !== '') {
            $query->where('status', $request->input('status'));
        }

        $invoiceList = $query->paginate(10)->withQueryString();
        $statusPembayaranOptions = ['PENDING', 'PAID', 'EXPIRED', 'FAILED', 'REFUNDED'];

        $allKelasQuery = Kelas::orderBy('nama_kelas');
        $allSiswaQuery = Siswa::with('user:id,email')->orderBy('nama_siswa');
        if ($user->hasRole('admin_kelas')) {
            $managedKelasIds = $user->managedClasses()->pluck('kelas.id_kelas');
            $allKelasQuery->whereIn('id_kelas', $managedKelasIds);
            $allSiswaQuery->whereIn('id_kelas', $managedKelasIds);
        }

        //dd($allSiswaQuery->get(['id_siswa', 'nama_siswa','email_wali', 'id_user', 'jumlah_spp_custom', 'admin_fee_custom', 'id_kelas']));
        
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
                    'recreated_from_id' => $invoice->recreated_from_id,
                ];
            }),
            'filters' => $request->only(['search', 'kelas_id', 'status']),
            'allSiswa' => $allSiswaQuery->get(),
            'allKelas' => $allKelasQuery->get(['id_kelas', 'nama_kelas', 'biaya_spp_default']),
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

    // public function store(Request $request, XenditService $xenditService)
    // {
    //     if (!$request->user()->can('manage_all_tagihan')) {
    //         abort(403);
    //     }

    //     $validated = $request->validate([
    //         'id_siswa' => 'required|uuid|exists:siswa,id_siswa',
    //         'periode_tagihan_bulan' => 'required|integer|min:1|max:12',
    //         'periode_tagihan_tahun' => 'required|integer',
    //         'jumlah_spp_ditagih' => 'required|numeric|min:10000',
    //         'admin_fee_ditagih' => 'nullable|numeric|min:0',
    //         'tanggal_jatuh_tempo' => 'required|date|after_or_equal:today',
    //         'send_whatsapp_notif' => 'nullable|boolean',
    //     ]);

    //     $siswa = Siswa::with('user')->findOrFail($validated['id_siswa']);
    //     $bulan = $validated['periode_tagihan_bulan'];
    //     $tahun = $validated['periode_tagihan_tahun'];
    //     \Carbon\Carbon::setLocale('id');
    //     $periodeTagihan = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        
    //     try {
    //         DB::transaction(function () use ($request, $validated, $siswa, $periodeTagihan, $xenditService, $tahun, $bulan) {
                
    //             $lockedSiswa = Siswa::where('id_siswa', $validated['id_siswa'])->lockForUpdate()->first();

    //             $existingInvoice = Invoice::where('id_siswa', $lockedSiswa->id_siswa)
    //                 ->where('type', 'spp')
    //                 ->whereDate('periode_tagihan', $periodeTagihan->toDateString())
    //                 ->first();

    //             if ($existingInvoice) {
    //                 throw new \Exception('Tagihan SPP untuk siswa ini pada periode tersebut sudah ada.');
    //             }

    //             $adminFee = $validated['admin_fee_ditagih'] ?? 0;
    //             $totalTagihan = (float)($validated['jumlah_spp_ditagih'] + $adminFee);
    //             $deskripsi = "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$lockedSiswa->nama_siswa} (NIS: {$lockedSiswa->nis})";

    //             $invoice = Invoice::create([
    //                 'id_siswa' => $lockedSiswa->id_siswa,
    //                 'user_id' => $request->user()->id,
    //                 'type' => 'spp',
    //                 'description' => $deskripsi,
    //                 'periode_tagihan' => $periodeTagihan,
    //                 'amount' => $validated['jumlah_spp_ditagih'],
    //                 'admin_fee' => $adminFee,
    //                 'total_amount' => $totalTagihan,
    //                 'due_date' => Carbon::parse($validated['tanggal_jatuh_tempo'])->endOfDay(),
    //                 'status' => 'PENDING',
    //                 'external_id_xendit' => 'SPP-'.$lockedSiswa->id_siswa.'-'.$tahun.str_pad($bulan, 2, '0', STR_PAD_LEFT).'-'.strtoupper(Str::random(6)),
    //             ]);

    //             $payerInfo = ['email' => $siswa->user?->email, 'name' => $siswa->nama_siswa, 'phone' => $siswa->nomor_telepon_wali];
    //             $notificationChannels = $request->boolean('send_whatsapp_notif') ? ['email', 'whatsapp'] : ['email'];
                
    //             $xenditInvoiceData = $xenditService->createInvoice(
    //                 (float)$invoice->amount, (float)$invoice->admin_fee, $invoice->description,
    //                 $payerInfo, $invoice->external_id_xendit, route('payment.success'),
    //                 route('payment.failure'), Carbon::parse($invoice->due_date), $notificationChannels
    //             );

    //             if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
    //                 throw new \Exception('Gagal membuat invoice pembayaran di Xendit.');
    //             }

    //             $invoice->update([
    //                 'xendit_invoice_id' => $xenditInvoiceData['id'],
    //                 'xendit_payment_url' => $xenditInvoiceData['invoice_url'],
    //                 'status' => $xenditInvoiceData['status'],
    //             ]);
    //         });

    //     } catch (Throwable $e) {
    //         Log::error('Gagal membuat invoice individual: ' . $e->getMessage());
    //         return Redirect::back()->withErrors(['periode_tagihan_bulan' => $e->getMessage()])->withInput();
    //     }

    //     return Redirect::route('admin.invoices.index')->with([
    //         'message' => 'Invoice berhasil dibuat dan link pembayaran telah digenerate.',
    //         'type' => 'success',
    //     ]);
    // }

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

                // ### PERBAIKAN: Admin fee tidak lagi menjadi bagian dari total tagihan bulanan ###
                $sppAmount = (float) $validated['jumlah_spp_ditagih'];
                Carbon::setLocale('id');
                $deskripsi = "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa} (NIS: {$siswa->nis})";

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

        return Redirect::route('admin.invoices.index')->with('flash', [
            'type' => 'success',
            'message' => 'Invoice berhasil dibuat.'
        ]);
    }

    // public function bulkStore(Request $request, XenditService $xenditService)
    // {
    //     if (!$request->user()->can('manage_all_tagihan')) {
    //         abort(403);
    //     }

    //     $validated = $request->validate([
    //         'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
    //         'periode_tagihan_bulan' => 'required|integer|min:1|max:12',
    //         'periode_tagihan_tahun' => 'required|integer',
    //         'tanggal_jatuh_tempo' => 'required|date|after_or_equal:today',
    //         'jenis_jumlah_spp' => 'required|string|in:default,manual',
    //         'jumlah_spp_manual' => 'nullable|required_if:jenis_jumlah_spp,manual|numeric|min:0',
    //         'jenis_admin_fee' => 'required|string|in:default,manual',
    //         'admin_fee_manual' => 'nullable|numeric|min:0',
    //         'send_whatsapp_notif' => 'nullable|boolean',
    //     ]);

    //     $kelas = Kelas::findOrFail($validated['id_kelas']);
    //     $bulan = $validated['periode_tagihan_bulan'];
    //     $tahun = $validated['periode_tagihan_tahun'];
    //     \Carbon\Carbon::setLocale('id');
    //     $periodeTagihan = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
    //     $tanggalJatuhTempo = Carbon::parse($validated['tanggal_jatuh_tempo'])->endOfDay();

    //     $siswaDiKelas = Siswa::where('id_kelas', $kelas->id_kelas)
    //                         ->where('status_siswa', 'Aktif')
    //                         ->whereDoesntHave('invoices', function ($query) use ($periodeTagihan) {
    //                             $query->where('type', 'spp')->whereDate('periode_tagihan', $periodeTagihan->toDateString());
    //                         })->with('user')->get();

    //     if ($siswaDiKelas->isEmpty()) {
    //         return Redirect::route('admin.invoices.index')->with([
    //             'message' => 'Tidak ada siswa aktif di kelas ' . $kelas->nama_kelas . ' yang belum memiliki tagihan untuk periode ini.',
    //             'type' => 'warning'
    //         ]);
    //     }

    //     $berhasilDibuat = 0;
    //     $gagalDibuat = 0;
        
    //     foreach ($siswaDiKelas as $siswa) {
    //         try {
    //             DB::transaction(function () use ($siswa, $validated, $kelas, $periodeTagihan, $tanggalJatuhTempo, $request, $xenditService, $tahun, $bulan) {
    //                 $jumlahSPP = ($validated['jenis_jumlah_spp'] === 'manual') ? $validated['jumlah_spp_manual'] : ($siswa->jumlah_spp_custom ?? $kelas->biaya_spp_default ?? 0);
    //                 $adminFee = ($validated['jenis_admin_fee'] === 'manual') ? ($validated['admin_fee_manual'] ?? 0) : ($siswa->admin_fee_custom ?? 0);
    //                 $totalTagihan = (float)($jumlahSPP + $adminFee);

    //                 if ($totalTagihan < 10000) {
    //                     return;
    //                 }

    //                 $deskripsi = "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa} (NIS: {$siswa->nis})";

    //                 $invoice = Invoice::create([
    //                     'id_siswa' => $siswa->id_siswa,
    //                     'user_id' => $request->user()->id,
    //                     'type' => 'spp',
    //                     'description' => $deskripsi,
    //                     'periode_tagihan' => $periodeTagihan,
    //                     'amount' => $jumlahSPP,
    //                     'admin_fee' => $adminFee,
    //                     'total_amount' => $totalTagihan,
    //                     'due_date' => $tanggalJatuhTempo,
    //                     'status' => 'PENDING',
    //                     'external_id_xendit' => 'SPP-'.$siswa->id_siswa.'-'.$tahun.str_pad($bulan, 2, '0', STR_PAD_LEFT).'-'.strtoupper(Str::random(6)),
    //                 ]);

    //                 $payerInfo = ['email' => $siswa->user?->email, 'name' => $siswa->nama_siswa, 'phone' => $siswa->nomor_telepon_wali];
    //                 $notificationChannels = $request->boolean('send_whatsapp_notif') ? ['email', 'whatsapp'] : ['email'];
                    
    //                 $xenditInvoiceData = $xenditService->createInvoice((float)$jumlahSPP, (float)$adminFee, $invoice->description, $payerInfo, $invoice->external_id_xendit, route('payment.success'), route('payment.failure'), $tanggalJatuhTempo, $notificationChannels);

    //                 if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
    //                     throw new \Exception("Gagal membuat link pembayaran Xendit untuk siswa: {$siswa->nama_siswa}");
    //                 }

    //                 $invoice->update(['xendit_invoice_id' => $xenditInvoiceData['id'], 'xendit_payment_url' => $xenditInvoiceData['invoice_url'], 'status' => $xenditInvoiceData['status']]);
    //             });

    //             $berhasilDibuat++;

    //         } catch (Throwable $e) {
    //             $gagalDibuat++;
    //             Log::error("[Bulk Store Sync] Gagal memproses invoice untuk siswa: {$siswa->id_siswa}. Error: " . $e->getMessage());
    //             continue; 
    //         }
    //     }

    //     $message = "Proses selesai. {$berhasilDibuat} tagihan berhasil dibuat.";
    //     if ($gagalDibuat > 0) { $message .= " {$gagalDibuat} tagihan gagal dibuat."; }
    //     if ($berhasilDibuat === 0 && $gagalDibuat === 0 && $siswaDiKelas->isNotEmpty()) { $message = 'Tidak ada tagihan baru yang dibuat (kemungkinan semua siswa sudah memiliki tagihan atau jumlah tagihan 0).'; }

    //     return Redirect::route('admin.invoices.index')->with(['message' => $message, 'type' => ($gagalDibuat > 0) ? 'warning' : 'success']);
    // }

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
                            ->whereDoesntHave('invoices', fn($q) => $q->where('type', 'spp')->whereDate('periode_tagihan', $periodeTagihan->toDateString()))
                            ->with('user')->get();

        if ($siswaDiKelas->isEmpty()) {
            return Redirect::route('admin.invoices.index')->with('flash', [
                'type' => 'warning',
                'message' => 'Tidak ada siswa aktif di kelas ini yang belum memiliki tagihan untuk periode tersebut.'
            ]);
        }
        
        Carbon::setLocale('id');
        foreach ($siswaDiKelas as $siswa) {
            try {
                DB::transaction(function () use ($siswa, $validated, $kelas, $periodeTagihan, $tanggalJatuhTempo, $request) {
                    $jumlahSPP = ($validated['jenis_jumlah_spp'] === 'manual') ? $validated['jumlah_spp_manual'] : ($siswa->jumlah_spp_custom ?? $kelas->biaya_spp_default ?? 0);
                    
                    if ($jumlahSPP <= 0) return;

                    $deskripsi = "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa} (NIS: {$siswa->nis})";

                    // ### PERBAIKAN: Admin fee tidak lagi menjadi bagian dari total tagihan bulanan ###
                    $invoice = Invoice::create([
                        'id_siswa' => $siswa->id_siswa,
                        'user_id' => $request->user()->id,
                        'type' => 'spp',
                        'description' => $deskripsi,
                        'periode_tagihan' => $periodeTagihan,
                        'amount' => $jumlahSPP,
                        'admin_fee' => 0, // Admin fee di tagihan bulanan selalu 0
                        'total_amount' => $jumlahSPP, // Total amount hanya sebesar SPP
                        'due_date' => $tanggalJatuhTempo,
                        'status' => 'PENDING',
                    ]);

                    if ($request->boolean('send_notification')) {
                        $this->sendInvoiceNotification($siswa, $invoice);
                    }
                });
            } catch (Throwable $e) {
                Log::error("[Bulk Store Sync] Gagal memproses invoice untuk siswa: {$siswa->id_siswa}. Error: " . $e->getMessage());
                continue; 
            }
        }

        return Redirect::route('admin.invoices.index')->with('flash', [
            'type' => 'success',
            'message' => 'Proses pembuatan tagihan massal selesai.'
        ]);
    }

    /**
     * Fungsi baru untuk mengirim notifikasi.
     */
    private function sendInvoiceNotification(Siswa $siswa, Invoice $invoice)
    {
        // Cek jika user (wali) memiliki email
        if ($siswa->user && $siswa->user->email) {
            // Kirim notifikasi via email
            // Anda perlu membuat class Notifikasi: php artisan make:notification InvoiceCreatedNotification
            $siswa->user->notify(new InvoiceCreatedNotification($invoice));
        } else {
            // Jika tidak ada email, lewati (skip)
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
                $recreatedInvoice->external_id_xendit = 'RE-'.$lockedInvoice->external_id_xendit . '-' . strtoupper(Str::random(2));

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
                'type' => 'error'
            ]);
        }

        $xenditResponse = $xenditService->expireInvoice($invoice->xendit_invoice_id);

        if ($xenditResponse && isset($xenditResponse['status']) && $xenditResponse['status'] === 'EXPIRED') {
            $invoice->update([
                'status' => 'EXPIRED',
                'xendit_callback_payload' => $xenditResponse,
            ]);
            return Redirect::back()->with([
                'message' => 'Invoice berhasil dibatalkan.',
                'type' => 'success'
            ]);
        }

        Log::error('Gagal membatalkan invoice di Xendit.', ['invoice_id' => $invoice->id]);
        return Redirect::back()->with([
            'message' => 'Gagal membatalkan invoice di sisi penyedia pembayaran. Silakan coba lagi.',
            'type' => 'error'
        ]);
    }

    public function markAsPaid(Request $request, Invoice $invoice)
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

        // Update status invoice
        $invoice->update([
            'status' => 'PAID',
            'paid_at' => now(),
            'payment_method' => 'manual', // Tandai sebagai pembayaran manual
        ]);

        // Kirim notifikasi sukses
        return Redirect::back()->with('flash', [
            'type' => 'success',
            'message' => 'Invoice berhasil ditandai sebagai LUNAS.'
        ]);
    }

}
