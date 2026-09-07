<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MidtransService implements PaymentGatewayInterface
{
    protected $serverKey;
    protected $isProduction;
    protected $baseUrl;

    public function __construct()
    {
        $this->serverKey = config('midtrans.server_key') ?? \App\Models\Setting::where('key', 'midtrans_server_key')->value('value');
        $this->isProduction = (config('midtrans.is_production') ?? \App\Models\Setting::where('key', 'midtrans_is_production')->value('value')) === '1';
        
        $this->baseUrl = $this->isProduction 
            ? 'https://app.midtrans.com/snap/v1' 
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }

    public function createInvoice(
        float $baseAmount,
        float $feeAmount,
        string $description,
        array $payerInfo,
        string $externalId,
        string $successRedirectUrl,
        string $failureRedirectUrl,
        Carbon $expiryDate,
        array $notificationChannels = ['email']
    ) {
        $encodedServerKey = base64_encode($this->serverKey . ':');
        $totalAmount = $baseAmount + $feeAmount;

        $invoiceDuration = $expiryDate->isFuture() ? (int) now()->diffInMinutes($expiryDate) : 0;
        $minDuration = 5; // 5 menit

        if ($invoiceDuration < $minDuration) {
            $invoiceDuration = $minDuration;
        }

        $payload = [
            'transaction_details' => [
                'order_id' => $externalId,
                'gross_amount' => (int) $totalAmount,
            ],
            'customer_details' => [
                'first_name' => $payerInfo['name'] ?? 'Siswa',
                'email' => $payerInfo['email'] ?? null,
                'phone' => $payerInfo['phone'] ?? null,
            ],
            'item_details' => [
                [
                    'id' => 'SPP',
                    'price' => (int) $baseAmount,
                    'quantity' => 1,
                    'name' => 'Pembayaran SPP'
                ]
            ],
            'enabled_payments' => $this->getPaymentMethods(),
            'custom_expiry' => [
                'expiry_duration' => $invoiceDuration,
                'unit' => 'minute'
            ],
            'callbacks' => [
                'finish' => $successRedirectUrl,
                'error' => $failureRedirectUrl,
            ]
        ];

        // Add fee item if exists
        if ($feeAmount > 0) {
            $payload['item_details'][] = [
                'id' => 'ADMIN_FEE',
                'price' => (int) $feeAmount,
                'quantity' => 1,
                'name' => 'Biaya Admin'
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedServerKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])
        ->connectTimeout(10)
        ->timeout(30)
        ->post("{$this->baseUrl}/transactions", $payload);

        if ($response->successful()) {
            $data = $response->json();
            // Translate Midtrans response to generic format so our controllers don't break
            // Midtrans returns { token, redirect_url }
            return [
                'id' => $externalId, // Midtrans doesn't return an invoice ID immediately, just token
                'external_id' => $externalId,
                'invoice_url' => $data['redirect_url'],
                'token' => $data['token']
            ];
        }

        Log::error('Midtrans Invoice Creation Failed', [
            'external_id' => $externalId,
            'status' => $response->status(),
            'body' => $response->body(),
            'sent_payload' => $payload
        ]);
        
        return null;
    }

    public function expireInvoice(string $invoiceId)
    {
        // For Midtrans, you can cancel a transaction using Core API
        // https://api.sandbox.midtrans.com/v2/{order_id}/cancel
        
        $coreApiUrl = $this->isProduction 
            ? 'https://api.midtrans.com/v2' 
            : 'https://api.sandbox.midtrans.com/v2';
            
        $encodedServerKey = base64_encode($this->serverKey . ':');

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedServerKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->post("{$coreApiUrl}/{$invoiceId}/cancel");

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('[Midtrans Service] Failed to cancel invoice.', [
            'order_id' => $invoiceId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return null;
    }

    public function getPaymentMethods(): array
    {
        $methods = ['gopay', 'qris'];

        $enableVa = \App\Models\Setting::where('key', 'enable_virtual_account')->value('value') ?? '1';

        if ($enableVa === '1') {
            $methods = array_merge($methods, [
                'bca_va', 'bni_va', 'bri_va', 'mandiri_va', 'permata_va'
            ]);
        }

        return $methods;
    }

    public function createCustomPayment(
        float $baseAmount,
        float $feeAmount,
        string $description,
        array $payerInfo,
        string $externalId,
        \Carbon\Carbon $expiryDate,
        string $paymentType,
        string $bankCode = ''
    ) {
        $encodedServerKey = base64_encode($this->serverKey . ':');
        $totalAmount = $baseAmount + $feeAmount;

        $payload = [
            'transaction_details' => [
                'order_id' => $externalId,
                'gross_amount' => (int) $totalAmount,
            ],
            'customer_details' => [
                'first_name' => $payerInfo['name'] ?? 'Siswa',
                'email' => $payerInfo['email'] ?? null,
                'phone' => $payerInfo['phone'] ?? null,
            ],
            'item_details' => [
                [
                    'id' => 'SPP',
                    'price' => (int) $baseAmount,
                    'quantity' => 1,
                    'name' => 'Pembayaran SPP'
                ]
            ],
            'custom_expiry' => [
                'expiry_duration' => max(5, $expiryDate->isFuture() ? (int) now()->diffInMinutes($expiryDate) : 5),
                'unit' => 'minute'
            ],
        ];

        if ($feeAmount > 0) {
            $payload['item_details'][] = [
                'id' => 'ADMIN_FEE',
                'price' => (int) $feeAmount,
                'quantity' => 1,
                'name' => 'Biaya Admin'
            ];
        }

        if ($paymentType === 'VA') {
            $bank = strtolower($bankCode);
            if ($bank === 'mandiri') {
                $payload['payment_type'] = 'echannel';
                $payload['echannel'] = [
                    'bill_info1' => 'Payment For:',
                    'bill_info2' => 'Tagihan SPP'
                ];
            } else {
                $payload['payment_type'] = 'bank_transfer';
                $payload['bank_transfer'] = [
                    'bank' => $bank
                ];
            }
        } elseif ($paymentType === 'QRIS') {
            $payload['payment_type'] = 'qris';
        } else {
            throw new \Exception("Tipe pembayaran tidak didukung oleh Midtrans Custom Checkout.");
        }

        $coreApiUrl = $this->isProduction 
            ? 'https://api.midtrans.com/v2/charge' 
            : 'https://api.sandbox.midtrans.com/v2/charge';

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedServerKey,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])
        ->connectTimeout(10)
        ->timeout(30)
        ->post($coreApiUrl, $payload);

        if ($response->successful()) {
            $data = $response->json();
            
            // Extract VA Number or QRIS String
            $vaNumber = null;
            $qrisString = null;

            if ($paymentType === 'VA') {
                if (strtolower($bankCode) === 'mandiri') {
                    // For Mandiri Bill Payment, VA number is combination of biller_code and bill_key
                    $vaNumber = ($data['biller_code'] ?? '') . ($data['bill_key'] ?? '');
                } elseif (isset($data['va_numbers']) && is_array($data['va_numbers'])) {
                    $vaNumber = $data['va_numbers'][0]['va_number'] ?? null;
                } elseif (isset($data['permata_va_number'])) {
                    $vaNumber = $data['permata_va_number'];
                }
            } elseif ($paymentType === 'QRIS') {
                // For QRIS, we extract from actions where name == 'generate-qr-code'
                if (isset($data['actions']) && is_array($data['actions'])) {
                    foreach ($data['actions'] as $action) {
                        if (($action['name'] ?? '') === 'generate-qr-code') {
                            $qrisString = $action['url'] ?? null;
                            break;
                        }
                    }
                }
                
                // Fallback for newer QRIS formats (like GoPay QRIS)
                if (!$qrisString && isset($data['qr_string'])) {
                    $qrisString = $data['qr_string'];
                }
            }

            return [
                'id' => $externalId, 
                'external_id' => $externalId,
                'payment_type' => $paymentType,
                'bank_code' => $bankCode,
                'amount' => $totalAmount,
                'status' => 'PENDING',
                'va_number' => $vaNumber,
                'qris_string' => $qrisString,
                'raw_response' => $data
            ];
        }

        Log::error('Midtrans Core API Creation Failed', [
            'external_id' => $externalId,
            'status' => $response->status(),
            'body' => $response->body(),
            'sent_payload' => $payload
        ]);
        
        return null;
    }
}
