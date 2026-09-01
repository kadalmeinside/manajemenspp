<?php

namespace App\Console\Commands;

use App\Models\Siswa;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixMulaiSppDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'spp:fix-mulai-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memperbaiki data mulai_spp_date yang kosong pada siswa aktif';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pembersihan data mulai_spp_date...');

        $siswas = Siswa::where('status_siswa', 'Aktif')->whereNull('mulai_spp_date')->get();
        $count = 0;

        foreach ($siswas as $siswa) {
            // Cari invoice SPP tertua
            $oldestInvoice = Invoice::where('id_siswa', $siswa->id_siswa)
                                    ->where('type', 'spp')
                                    ->orderBy('periode_tagihan', 'asc')
                                    ->first();

            if ($oldestInvoice && $oldestInvoice->periode_tagihan) {
                // Set ke periode tagihan tertua
                $siswa->mulai_spp_date = Carbon::parse($oldestInvoice->periode_tagihan)->startOfMonth();
            } else {
                // Set ke tanggal bergabung
                $siswa->mulai_spp_date = Carbon::parse($siswa->tanggal_bergabung)->startOfMonth();
            }

            $siswa->save();
            $count++;
        }

        $this->info("Berhasil memperbarui data mulai_spp_date untuk {$count} siswa.");
    }
}
