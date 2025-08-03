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

        // Mapping nama bulan ke nomor bulan
        $months = [
            'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4, 'MEI' => 5, 'JUNI' => 6,
            'JULI' => 7, 'AGUSTUS' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
        ];
        
        $totalRows = count(file($filePath)) - 1;
        $progressBar = $this->output->createProgressBar($totalRows);

        $this->info("Membaca {$totalRows} baris data siswa...");
        
        Carbon::setLocale('id');
        
        $progressBar->start();

        while (($row = fgetcsv($file)) !== false) {
            if (empty(array_filter($row))) {
                $progressBar->advance();
                continue;
            }
            
            $data = array_combine($header, $row);

            try {
                DB::transaction(function () use ($data, $months) {
                    $sppDefault = (int) filter_var($data['SPP'], FILTER_SANITIZE_NUMBER_INT);
                    if ($sppDefault <= 0) {
                        // Lewati baris jika data SPP tidak valid
                        return;
                    }

                    $kelas = Kelas::firstOrCreate(
                        ['nama_kelas' => trim($data['Cabang'])],
                        ['biaya_spp_default' => $sppDefault]
                    );

                    $tanggalBergabung = null;
                    if (!empty(trim($data['TGL DAFTAR']))) {
                        $tanggalBergabung = Carbon::createFromFormat('d-M-y', trim($data['TGL DAFTAR']))->startOfDay();
                    } else {
                        $tanggalBergabung = now()->startOfDay();
                    }

                    $siswa = Siswa::updateOrCreate(
                        [
                            'nama_siswa' => trim($data['NAMA MURID']),
                            'nomor_telepon_wali' => trim($data['No. Telp']),
                        ],
                        [
                            'id_kelas' => $kelas->id_kelas,
                            'status_siswa' => 'Aktif',
                            'tanggal_bergabung' => $tanggalBergabung,
                            'jumlah_spp_custom' => $sppDefault,
                            'id_user' => null,
                        ]
                    );

                    if (!$siswa->nis) {
                        $siswa->generateNis();
                    }

                    // ### PERBAIKAN ###: Logika dinamis untuk menentukan tahun invoice
                    foreach ($months as $monthName => $monthNumber) {
                        if (isset($data[$monthName]) && !empty($data[$monthName])) {
                            
                            // Tentukan tahun yang benar untuk invoice
                            // Jika bulan invoice lebih kecil dari bulan daftar, berarti itu tahun depannya.
                            // Contoh: Daftar Sep 2024, bayar Jan -> Jan 2025
                            $invoiceYear = ($monthNumber < $tanggalBergabung->month) 
                                ? $tanggalBergabung->year + 1 
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
                                    'amount' => (int) filter_var($data[$monthName], FILTER_SANITIZE_NUMBER_INT),
                                    'total_amount' => (int) filter_var($data[$monthName], FILTER_SANITIZE_NUMBER_INT),
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
                $this->error("\n Gagal memproses baris untuk siswa: " . ($data['NAMA MURID'] ?? 'N/A') . ". Error: " . $e->getMessage());
                Log::error("Import Gagal: " . ($data['NAMA MURID'] ?? 'N/A'), ['error' => $e->getMessage()]);
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        fclose($file);
        $this->info("\n\nProses impor data lama telah selesai.");
        return 0;
    }
}
