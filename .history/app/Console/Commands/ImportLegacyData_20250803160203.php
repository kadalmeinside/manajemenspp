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
        
        // Asumsi tahun untuk invoice yang sudah berjalan adalah 2024, berdasarkan TGL DAFTAR
        $invoiceYear = 2024;

        // Hitung total baris untuk progress bar
        $totalRows = count(file($filePath)) - 1;
        $progressBar = $this->output->createProgressBar($totalRows);

        $this->info("Membaca {$totalRows} baris data siswa...");
        
        // ### PERBAIKAN ###: Atur lokal Carbon ke Bahasa Indonesia
        Carbon::setLocale('id');
        
        $progressBar->start();

        while (($row = fgetcsv($file)) !== false) {
            // Lewati baris kosong
            if (empty(array_filter($row))) {
                $progressBar->advance();
                continue;
            }
            
            $data = array_combine($header, $row);

            try {
                DB::transaction(function () use ($data, $months, $invoiceYear) {
                    // 1. Cari atau buat Kelas (Cabang)
                    $kelas = Kelas::firstOrCreate(
                        ['nama_kelas' => trim($data['Cabang'])],
                        ['biaya_spp_default' => (int) filter_var($data['SPP'], FILTER_SANITIZE_NUMBER_INT)]
                    );

                    // ### PERBAIKAN ###: Tambahkan pengecekan jika tanggal daftar kosong
                    $tanggalBergabung = null;
                    if (!empty(trim($data['TGL DAFTAR']))) {
                        $tanggalBergabung = Carbon::createFromFormat('d-M-y', trim($data['TGL DAFTAR']))->startOfDay();
                    } else {
                        // Jika tanggal kosong, gunakan tanggal hari ini sebagai default
                        $tanggalBergabung = now()->startOfDay();
                    }

                    // 2. Buat atau perbarui data Siswa (tanpa User)
                    $siswa = Siswa::updateOrCreate(
                        [
                            'nama_siswa' => trim($data['NAMA MURID']),
                            'nomor_telepon_wali' => trim($data['No. Telp']),
                        ],
                        [
                            'id_kelas' => $kelas->id_kelas,
                            'status_siswa' => 'Aktif',
                            'tanggal_bergabung' => $tanggalBergabung,
                            'jumlah_spp_custom' => (int) filter_var($data['SPP'], FILTER_SANITIZE_NUMBER_INT),
                            'id_user' => null,
                        ]
                    );

                    if (!$siswa->nis) {
                        $siswa->generateNis();
                    }

                    // 3. Buat Invoice untuk setiap bulan yang sudah dibayar
                    foreach ($months as $monthName => $monthNumber) {
                        if (isset($data[$monthName]) && !empty($data[$monthName])) {
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
                $this->error("\n Gagal memproses baris untuk siswa: " . $data['NAMA MURID'] . ". Error: " . $e->getMessage());
                Log::error("Import Gagal: " . $data['NAMA MURID'], ['error' => $e->getMessage()]);
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        fclose($file);
        $this->info("\n\nProses impor data lama telah selesai.");
        return 0;
    }
}
