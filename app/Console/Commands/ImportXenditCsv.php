<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Withdrawal;
use Carbon\Carbon;

class ImportXenditCsv extends Command
{
    protected $signature = 'xendit:import-csv {file}';
    protected $description = 'Import historical withdrawals from Xendit Transactions CSV';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $handle = fopen($file, "r");
        if ($handle === false) {
            $this->error("Cannot open file: {$file}");
            return 1;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            $this->error("Empty CSV file.");
            return 1;
        }

        // Cari index kolom
        $statusIdx = array_search('Status', $header);
        $typeIdx = array_search('Type', $header);
        $amountIdx = array_search('Amount', $header);
        $bankCodeIdx = array_search('Payment Channel', $header);
        $accountNumberIdx = array_search('Account Number', $header);
        $referenceIdx = array_search('Reference', $header); // we use Reference as xendit_disbursement_id
        $completedAtIdx = array_search('Actual Settlement Time', $header);
        $paymentTimeIdx = array_search('Payment Time', $header);
        $descriptionIdx = array_search('Description', $header);

        $count = 0;

        while (($data = fgetcsv($handle)) !== false) {
            // Hanya proses tipe WITHDRAWAL
            if (!isset($data[$typeIdx]) || strtoupper($data[$typeIdx]) !== 'WITHDRAWAL') {
                continue;
            }

            $referenceId = $data[$referenceIdx] ?? null;
            if (!$referenceId) {
                continue;
            }

            // Status: SUCCESSFUL -> COMPLETED, PENDING -> PENDING, FAILED -> FAILED
            $rawStatus = strtoupper($data[$statusIdx] ?? '');
            $status = 'PENDING';
            if ($rawStatus === 'SUCCESSFUL') $status = 'COMPLETED';
            elseif ($rawStatus === 'FAILED') $status = 'FAILED';

            // Tanggal Selesai
            $completedAtStr = $data[$completedAtIdx] ?? $data[$paymentTimeIdx] ?? null;
            $completedAt = null;
            if ($completedAtStr) {
                try {
                    $completedAt = Carbon::parse($completedAtStr);
                } catch (\Exception $e) {}
            }

            $bankCode = $data[$bankCodeIdx] ?? null;
            if (str_starts_with($bankCode, 'ID_')) {
                $bankCode = substr($bankCode, 3); // ID_BCA -> BCA
            }

            // Coba ambil nama akun dari deskripsi (misal: "Persija Development AWD31082026" -> "Persija Development")
            $description = $data[$descriptionIdx] ?? '';
            $accountName = trim(preg_replace('/AWD\d+/', '', $description));

            Withdrawal::updateOrCreate(
                ['xendit_disbursement_id' => $referenceId],
                [
                    'amount' => (int) ($data[$amountIdx] ?? 0),
                    'status' => $status,
                    'bank_code' => $bankCode,
                    'account_name' => $accountName ?: null,
                    'account_number' => $data[$accountNumberIdx] ?? null,
                    'completed_at' => $completedAt,
                    'payload' => [
                        'source' => 'csv_import',
                        'raw_data' => array_combine($header, $data)
                    ]
                ]
            );

            $count++;
        }

        fclose($handle);

        $this->info("Successfully imported {$count} withdrawals from CSV.");
        return 0;
    }
}
