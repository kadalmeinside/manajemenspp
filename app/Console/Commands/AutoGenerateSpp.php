<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobBatch;
use App\Jobs\GenerateMassInvoices;
use Carbon\Carbon;

class AutoGenerateSpp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spp:auto-generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis generate tagihan SPP massal untuk bulan berikutnya (dijalankan tiap tanggal 25)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai Auto Generate SPP...');

        // Target bulan adalah bulan depan
        $targetDate = Carbon::now()->addMonth();
        $targetBulan = $targetDate->month;
        $targetTahun = $targetDate->year;
        
        // Default jatuh tempo: Akhir bulan target (Bisa disesuaikan jika perlu)
        $jatuhTempo = $targetDate->copy()->endOfMonth()->toDateString();

        $jobBatch = JobBatch::create([
            'name' => "Generate Tagihan Otomatis SPP - " . $targetDate->isoFormat('MMMM YYYY'),
            'user_id' => 1, // Sistem/Admin ID (Default user ID 1)
        ]);

        $jobParams = [
            'id_kelas' => null, // Semua siswa
            'bulan' => $targetBulan,
            'tahun' => $targetTahun,
            'jatuh_tempo' => $jatuhTempo,
            'jenis_jumlah_spp' => 'default',
            'jumlah_spp_manual' => null,
            'jenis_admin_fee' => 'default',
            'admin_fee_manual' => null,
            'send_whatsapp_notif' => false, // Set true jika ingin otomatis mengirim notif WA
        ];

        GenerateMassInvoices::dispatch($jobBatch, $jobParams);

        $this->info("JobBatch {$jobBatch->id} berhasil di-dispatch untuk tagihan {$targetDate->isoFormat('MMMM YYYY')}.");
        return Command::SUCCESS;
    }
}
