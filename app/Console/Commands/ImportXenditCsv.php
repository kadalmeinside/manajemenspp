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
        $feeIdx = array_search('Total Fee Amount', $header);
        $vatIdx = array_search('Total VAT Amount', $header);
        $descriptionIdx = array_search('Description', $header);

        $countWithdrawals = 0;
        $countInvoices = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $type = strtoupper($data[$typeIdx] ?? '');
            $referenceId = $data[$referenceIdx] ?? null;

            if (!$referenceId) {
                continue;
            }

            // --- PROSES PAYMENT (INVOICES) ---
            if ($type === 'PAYMENT') {
                $fee = (int) ($data[$feeIdx] ?? 0);
                $vat = (int) ($data[$vatIdx] ?? 0);
                $totalFee = $fee + $vat;

                if ($totalFee > 0) {
                    // Update invoice biasa
                    $updated = \App\Models\Invoice::where('external_id_xendit', $referenceId)
                        ->where(function($q) {
                            $q->whereNull('admin_fee')->orWhere('admin_fee', 0);
                        })
                        ->update(['admin_fee' => $totalFee]);
                    
                    if ($updated) {
                        $countInvoices++;
                    }
                }
                continue;
            }

            // --- PROSES WITHDRAWAL ---
            if ($type !== 'WITHDRAWAL') {
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

            $countWithdrawals++;
        }

        fclose($handle);

        $this->info("Successfully imported {$countWithdrawals} withdrawals and synced admin fee for {$countInvoices} invoices from CSV.");
        return 0;
    }
}
