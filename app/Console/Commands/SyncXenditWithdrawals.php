<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncXenditWithdrawals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xendit:sync-withdrawals';
    protected $description = 'Sync historical disbursements/withdrawals from Xendit';

    public function handle()
    {
        $secretKey = env('XENDIT_API_KEY');
        if (!$secretKey) {
            $this->error('XENDIT_API_KEY is not set.');
            return 1;
        }

        $this->info('Fetching disbursements from Xendit...');

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth($secretKey, '')
                ->get('https://api.xendit.co/disbursements');

            if ($response->successful()) {
                $disbursements = $response->json();
                
                // If it's a paginated response, the data might be in 'data' array.
                // According to Xendit docs, GET /disbursements returns a JSON array directly or data depending on API version.
                // Let's assume it returns an array of objects.
                $items = isset($disbursements['data']) ? $disbursements['data'] : $disbursements;

                $count = 0;
                foreach ($items as $item) {
                    $disbursementId = $item['id'] ?? null;
                    if (!$disbursementId) continue;

                    $completedAt = null;
                    if (isset($item['updated']) && $item['status'] === 'COMPLETED') {
                        $completedAt = \Carbon\Carbon::parse($item['updated']);
                    }

                    \App\Models\Withdrawal::updateOrCreate(
                        ['xendit_disbursement_id' => $disbursementId],
                        [
                            'amount' => $item['amount'] ?? 0,
                            'status' => $item['status'] ?? 'PENDING',
                            'bank_code' => $item['bank_code'] ?? null,
                            'account_name' => $item['account_holder_name'] ?? ($item['account_name'] ?? null),
                            'account_number' => $item['account_number'] ?? null,
                            'completed_at' => $completedAt,
                            'payload' => $item
                        ]
                    );
                    $count++;
                }

                $this->info("Successfully synced {$count} withdrawals.");
            } else {
                $this->error('Failed to fetch disbursements. Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
