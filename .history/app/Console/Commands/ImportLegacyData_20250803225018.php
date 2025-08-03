<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportLegacyData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:legacy-data {--path=data_lama.csv : The path to the CSV file inside storage/app directory.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import data siswa dan invoice historis dari file CSV.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses impor data lama...');

        $filePath = storage_path('app/' . $this->option('path'));

        if (!file_exists($filePath)) {
            $this->error("File tidak ditemukan di: {$filePath}");
            return 1;
        }

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file); // Baca baris pertama sebagai header

        $months = [
            'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6,
            'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
        ];
        
        $totalRows = count(file($filePath)) - 1;
        $progressBar = $this->output->createProgressBar($totalRows);

        $this->info("Membaca {$totalRows} baris data siswa...");
        
        $progressBar->start();

        while (($row = fgetcsv($file)) !== false) {
            if (empty(array_filter($row))) {
                $progressBar->advance();
                continue;
            }
            
            $data = array_combine($header, $row);

            try {
                DB::transaction(function () use ($data, $months) {
                    $sppDefault = (int) $data['spp'];
                    if ($sppDefault <= 0) {
                        return;
                    }

                    $kelas = Kelas::firstOrCreate(
                        ['nama_kelas' => trim($data['cabang'])],
                        ['biaya_spp_default' => $sppDefault]
                    );

                    $tanggalBergabung = !empty(trim($data['tgl_daftar'])) ? new Carbon($data['tgl_daftar']) : now();

                    $siswa = Siswa::updateOrCreate(
                        [
                            'nama_siswa' => trim($data['nama_murid']),
                            'nomor_telepon_wali' => trim($data['no_telp']),
                        ],
                        [
                            'id_kelas' => $kelas->id_kelas,
                            'status_siswa' => 'Aktif',
                            'tanggal_bergabung' => $tanggalBergabung->startOfDay(),
                            'jumlah_spp_custom' => $sppDefault,
                            'id_user' => null,
                        ]
                    );

                    if (!$siswa->nis) {
                        $siswa->generateNis();
                    }

                    foreach ($months as $monthName => $monthNumber) {
                        if (isset($data[$monthName]) && !empty($data[$monthName])) {
                            $invoiceYear = ($monthNumber < $tanggalBergabung->month && $tanggalBergabung->year == 2024) 
                                ? 2025 
                                : $tanggalBergabung->year;

                            $periodeTagihan = Carbon::create($invoiceYear, $monthNumber, 1)->startOfMonth();
                            
                            Invoice::updateOrCreate(
                                [
                                    'id_siswa' => $siswa->id_siswa,
                                    'type' => 'spp',
                                    'periode_tagihan' => $periodeTagihan,
                                ],
                                [
                                    'description' => "SPP {$periodeTagihan->isoFormat('MMMM YYYY')} - {$siswa->nama_siswa}",
                                    'amount' => (int) $data[$monthName],
                                    'total_amount' => (int) $data[$monthName],
                                    'admin_fee' => 0,
                                    'status' => 'PAID',
                                    'paid_at' => $periodeTagihan->copy()->endOfMonth(),
                                    'due_date' => $periodeTagihan->copy()->endOfMonth(),
                                ]
                            );
                        }
                    }
                });
            } catch (Throwable $e) {
                $this->error("\n Gagal memproses baris untuk siswa: " . ($data['nama_murid'] ?? 'N/A') . ". Error: " . $e->getMessage());
                Log::error("Import Gagal: " . ($data['nama_murid'] ?? 'N/A'), ['error' => $e->getMessage()]);
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        fclose($file);
        $this->info("\n\nProses impor data lama telah selesai.");
        return 0;
    }
}
