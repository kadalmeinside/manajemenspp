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
        throw new \Exception("MidtransService does not support createCustomPayment yet.");
    }
}
