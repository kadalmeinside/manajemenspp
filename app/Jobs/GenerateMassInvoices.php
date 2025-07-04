<?php

namespace App\Jobs;

use App\Events\MassInvoiceJobStatusUpdated;
use App\Models\Invoice;
use App\Models\JobBatch;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class GenerateMassInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected JobBatch $jobBatch;
    protected array $params;

    public function __construct(JobBatch $jobBatch, array $params)
    {
        $this->jobBatch = $jobBatch;
        $this->params = $params;
    }

    public function handle(XenditService $xenditService): void
    {
        $this->jobBatch->update(['status' => 'processing', 'started_at' => now()]);
        MassInvoiceJobStatusUpdated::dispatch($this->jobBatch, 'processing', 0, 'Memulai proses... Mengambil data siswa.');

        $params = $this->params;
        \Carbon\Carbon::setLocale('id');
        $periodeTagihan = Carbon::createFromDate($params['tahun'], $params['bulan'], 1)->startOfMonth();
        $tanggalJatuhTempo = Carbon::parse($params['jatuh_tempo'])->endOfDay();
        
        $siswasToBillQuery = Siswa::where('status_siswa', 'Aktif')
            ->whereDoesntHave('invoices', fn($q) => $q->where('type', 'spp')->whereDate('periode_tagihan', $periodeTagihan->toDateString()))
            ->with(['user', 'kelas']);

        if (!empty($params['id_kelas'])) {
            $siswasToBillQuery->where('id_kelas', $params['id_kelas']);
        }
        
        $siswasToBill = $siswasToBillQuery->get();
        $totalSiswa = $siswasToBill->count();

        $this->jobBatch->update(['total_items' => $totalSiswa]);
        if ($totalSiswa === 0) {
            $message = 'Tidak ada siswa yang perlu ditagih untuk periode ini.';
            if (!empty($params['id_kelas'])) {
                $kelas = Kelas::find($params['id_kelas']);
                $message = "Tidak ada siswa aktif di kelas '{$kelas->nama_kelas}' yang belum memiliki tagihan.";
            }
            $this->jobBatch->update(['status' => 'finished', 'finished_at' => now(), 'output' => $message]);
            MassInvoiceJobStatusUpdated::dispatch($this->jobBatch, 'finished', 100, $message);
            return;
        }

        Log::info("[JOB] Found {$totalSiswa} students to bill.");
        $berhasilDibuat = 0;
        $gagalDibuat = 0;
        
        foreach ($siswasToBill as $siswa) {
            try {
                DB::transaction(function () use ($siswa, $xenditService, $periodeTagihan, $tanggalJatuhTempo) {
                    $kelas = $siswa->kelas;
                    $jumlahSPP = ($this->params['jenis_jumlah_spp'] === 'manual') ? $this->params['jumlah_spp_manual'] : ($siswa->jumlah_spp_custom ?? $kelas->biaya_spp_default ?? 0);
                    $adminFee = ($this->params['jenis_admin_fee'] === 'manual') ? ($this->params['admin_fee_manual'] ?? 0) : ($siswa->admin_fee_custom ?? 0);
                    $totalTagihan = (float)($jumlahSPP + $adminFee);

                    if ($totalTagihan < 10000) {
                        return;
                    }

                    $deskripsiInvoice = "SPP {$periodeTagihan->isoFormat('MMMM Y')} - {$siswa->nama_siswa} (NIS: {$siswa->nis})";

                    $invoice = Invoice::create([
                        'id_siswa' => $siswa->id_siswa,
                        'user_id' => $this->jobBatch->user_id,
                        'type' => 'spp',
                        'description' => $deskripsiInvoice,
                        'periode_tagihan' => $periodeTagihan,
                        'amount' => $jumlahSPP,
                        'admin_fee' => $adminFee,
                        'total_amount' => $totalTagihan,
                        'due_date' => $tanggalJatuhTempo,
                        'status' => 'PENDING',
                        'external_id_xendit' => 'SPP-'.$siswa->id_siswa.'-'.$this->params['tahun'].str_pad($this->params['bulan'], 2, '0', STR_PAD_LEFT).'-'.strtoupper(Str::random(6)),
                    ]);

                    $payerInfo = ['email' => $siswa->user?->email, 'name' => $siswa->nama_siswa, 'phone' => $siswa->nomor_telepon_wali];
                    $notificationChannels = ($this->params['send_whatsapp_notif'] ?? false) ? ['email', 'whatsapp'] : ['email'];

                    $xenditInvoiceData = $xenditService->createInvoice((float)$jumlahSPP, (float)$adminFee, $deskripsiInvoice, $payerInfo, $invoice->external_id_xendit, route('payment.success'), route('payment.failure'), $tanggalJatuhTempo, $notificationChannels);

                    if (!$xenditInvoiceData || !isset($xenditInvoiceData['invoice_url'])) {
                        throw new \Exception("Gagal membuat link pembayaran Xendit untuk siswa: {$siswa->nama_siswa}");
                    }

                    $invoice->update(['xendit_invoice_id' => $xenditInvoiceData['id'], 'xendit_payment_url' => $xenditInvoiceData['invoice_url'], 'status' => $xenditInvoiceData['status']]);
                });

                $berhasilDibuat++;
            } catch (Throwable $e) {
                $gagalDibuat++;
                Log::error("[Mass Invoice Job] Gagal memproses invoice untuk siswa: {$siswa->id_siswa}. Error: " . $e->getMessage());
                continue;
            } finally {
                $this->jobBatch->increment('processed_items');
                $progress = $totalSiswa > 0 ? (int)(($this->jobBatch->processed_items / $totalSiswa) * 100) : 100;
                MassInvoiceJobStatusUpdated::dispatch($this->jobBatch, 'processing', $progress, "Memproses... ({$this->jobBatch->processed_items}/{$totalSiswa})");
            }
        }

        $summaryMessage = "Proses selesai. {$berhasilDibuat} tagihan berhasil dibuat. {$gagalDibuat} gagal.";
        $this->jobBatch->update(['status' => 'finished', 'finished_at' => now(), 'output' => $summaryMessage]);
        MassInvoiceJobStatusUpdated::dispatch($this->jobBatch, 'finished', 100, $summaryMessage);
    }

    public function failed(Throwable $exception): void
    {
        if ($this->jobBatch) {
            $errorMessage = 'Proses Gagal Total: Terjadi kesalahan tak terduga.';
            $this->jobBatch->update(['status' => 'failed', 'finished_at' => now(), 'output' => $errorMessage]);
            Log::error('[JOB] GenerateMassInvoices FAILED.', ['error' => $exception->getMessage(), 'job_batch_id' => $this->jobBatch->id]);
            MassInvoiceJobStatusUpdated::dispatch($this->jobBatch, 'failed', 100, $errorMessage);
        }
    }
}
