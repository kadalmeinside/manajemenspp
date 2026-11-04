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
        $dbServerKey = \App\Models\Setting::where('key', 'midtrans_server_key')->value('value');
        $this->serverKey = trim((string) (!empty($dbServerKey) ? $dbServerKey : config('midtrans.server_key')));

        $dbIsProd = \App\Models\Setting::where('key', 'midtrans_is_production')->value('value');
        if ($dbIsProd !== null && $dbIsProd !== '') {
            $this->isProduction = filter_var($dbIsProd, FILTER_VALIDATE_BOOLEAN);
        } else {
            $this->isProduction = filter_var(config('midtrans.is_production', false), FILTER_VALIDATE_BOOLEAN);
        }
        
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
        $baseInt = (int) round($baseAmount);
        $feeInt = (int) round($feeAmount);
        $totalInt = $baseInt + $feeInt;

        $invoiceDuration = $expiryDate->isFuture() ? (int) now()->diffInMinutes($expiryDate) : 0;
        $minDuration = 5; // 5 menit

        if ($invoiceDuration < $minDuration) {
            $invoiceDuration = $minDuration;
        }

        $itemName = mb_substr(trim($description) ?: 'Pembayaran Tagihan', 0, 50);

        $payload = [
            'transaction_details' => [
                'order_id' => $externalId,
                'gross_amount' => $totalInt,
            ],
            'customer_details' => [
                'first_name' => $payerInfo['name'] ?? 'Siswa',
                'email' => $payerInfo['email'] ?? null,
                'phone' => $payerInfo['phone'] ?? null,
            ],
            'item_details' => [
                [
                    'id' => 'MAIN_ITEM',
                    'price' => $baseInt,
                    'quantity' => 1,
                    'name' => $itemName,
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
        if ($feeInt > 0) {
            $payload['item_details'][] = [
                'id' => 'ADMIN_FEE',
                'price' => $feeInt,
                'quantity' => 1,
                'name' => 'Biaya Layanan / Admin'
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
            if (!empty($data['redirect_url']) && !empty($data['token'])) {
                return [
                    'id' => $externalId,
                    'external_id' => $externalId,
                    'invoice_url' => $data['redirect_url'],
                    'token' => $data['token']
                ];
            }
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
        // For Midtrans, you can cancel/expire a transaction using Core API
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

        $data = $response->json();
        $statusCode = (string) ($data['status_code'] ?? $response->status());

        if ($response->successful() && in_array($statusCode, ['200', '412'])) {
            return array_merge($data ?? [], ['status' => 'EXPIRED']);
        }

        // 404 pada Snap berarti user belum memilih metode pembayaran di halaman Snap (transaksi belum terbuat di Core API)
        if ($statusCode === '404') {
            Log::info('[Midtrans Service] Invoice belum diinisiasi di Midtrans (404), anggap expired.', [
                'order_id' => $invoiceId,
            ]);
            return ['status' => 'EXPIRED', 'status_code' => '404'];
        }

        Log::warning('[Midtrans Service] Failed to cancel invoice.', [
            'order_id' => $invoiceId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return null;
    }

    public function getPaymentMethods(): array
    {
        $settings = \App\Models\Setting::whereIn('key', [
            'enable_virtual_account',
            'enable_bank_bca',
            'enable_bank_bni',
            'enable_bank_bri',
            'enable_bank_mandiri',
            'enable_bank_permata',
            'enable_bank_cimb',
            'enable_payment_qris',
        ])->pluck('value', 'key');

        $methods = [];

        if (($settings['enable_payment_qris'] ?? '1') === '1') {
            $methods[] = 'gopay';
            $methods[] = 'qris';
        }

        $enableVa = ($settings['enable_virtual_account'] ?? '1') === '1';

        if ($enableVa) {
            if (($settings['enable_bank_bca'] ?? '1') === '1') $methods[] = 'bca_va';
            if (($settings['enable_bank_bni'] ?? '1') === '1') $methods[] = 'bni_va';
            if (($settings['enable_bank_bri'] ?? '1') === '1') $methods[] = 'bri_va';
            if (($settings['enable_bank_mandiri'] ?? '1') === '1') $methods[] = 'echannel';
            if (($settings['enable_bank_permata'] ?? '1') === '1') $methods[] = 'permata_va';
            if (($settings['enable_bank_cimb'] ?? '1') === '1') $methods[] = 'cimb_va';
        }

        if (empty($methods)) {
            $methods = ['qris'];
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
        $baseInt = (int) round($baseAmount);
        $feeInt = (int) round($feeAmount);
        $totalInt = $baseInt + $feeInt;

        $itemName = mb_substr(trim($description) ?: 'Pembayaran Tagihan', 0, 50);

        $payload = [
            'transaction_details' => [
                'order_id' => $externalId,
                'gross_amount' => $totalInt,
            ],
            'customer_details' => [
                'first_name' => $payerInfo['name'] ?? 'Siswa',
                'email' => $payerInfo['email'] ?? null,
                'phone' => $payerInfo['phone'] ?? null,
            ],
            'item_details' => [
                [
                    'id' => 'MAIN_ITEM',
                    'price' => $baseInt,
                    'quantity' => 1,
                    'name' => $itemName,
                ]
            ],
            'custom_expiry' => [
                'expiry_duration' => max(5, $expiryDate->isFuture() ? (int) now()->diffInMinutes($expiryDate) : 5),
                'unit' => 'minute'
            ],
        ];

        if ($feeInt > 0) {
            $feeLabel = $paymentType === 'QRIS'
                ? 'Biaya Layanan (QRIS)'
                : ($bankCode ? 'Biaya Layanan (VA ' . strtoupper($bankCode) . ')' : 'Biaya Layanan / Admin');

            $payload['item_details'][] = [
                'id' => 'ADMIN_FEE',
                'price' => $feeInt,
                'quantity' => 1,
                'name' => mb_substr($feeLabel, 0, 50),
            ];
        }

        if ($paymentType === 'VA') {
            $bank = strtolower($bankCode);
            if ($bank === 'mandiri') {
                $cleanInfo = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $itemName) ?: 'Pembayaran SPP';
                $payload['payment_type'] = 'echannel';
                $payload['echannel'] = [
                    'bill_info1' => 'Payment For',
                    'bill_info2' => mb_substr($cleanInfo, 0, 30),
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

        $data = $response->json();
        $statusCode = (string) ($data['status_code'] ?? '');

        if ($response->successful() && in_array($statusCode, ['200', '201'])) {
            // Extract VA Number or QRIS String
            $vaNumber = null;
            $billerCode = null;
            $billKey = null;
            $qrisString = null;
            $qrUrl = null;

            if ($paymentType === 'VA') {
                if (strtolower($bankCode) === 'mandiri') {
                    $billerCode = $data['biller_code'] ?? null;
                    $billKey = $data['bill_key'] ?? null;
                    // For Mandiri Bill Payment, VA number is combination of biller_code and bill_key
                    $vaNumber = ($billerCode ?? '') . ($billKey ?? '');
                } elseif (isset($data['va_numbers']) && is_array($data['va_numbers'])) {
                    $vaNumber = $data['va_numbers'][0]['va_number'] ?? null;
                } elseif (isset($data['permata_va_number'])) {
                    $vaNumber = $data['permata_va_number'];
                }
            } elseif ($paymentType === 'QRIS') {
                // Prioritaskan qr_string standar EMVCo agar bisa di-render dengan benar oleh QR generator
                if (!empty($data['qr_string'])) {
                    $qrisString = $data['qr_string'];
                }

                // Simpan juga URL image QRIS bawaan Midtrans dari actions
                if (isset($data['actions']) && is_array($data['actions'])) {
                    foreach ($data['actions'] as $action) {
                        if (($action['name'] ?? '') === 'generate-qr-code') {
                            $qrUrl = $action['url'] ?? null;
                            break;
                        }
                    }
                }

                if (!$qrisString && $qrUrl) {
                    $qrisString = $qrUrl;
                }
            }

            return [
                'id' => $externalId, 
                'transaction_id' => $data['transaction_id'] ?? null,
                'external_id' => $externalId,
                'payment_type' => $paymentType,
                'bank_code' => $bankCode,
                'amount' => $totalInt,
                'base_amount' => $baseInt,
                'fee_amount' => $feeInt,
                'status' => 'PENDING',
                'va_number' => $vaNumber,
                'biller_code' => $billerCode,
                'bill_key' => $billKey,
                'qris_string' => $qrisString,
                'qr_url' => $qrUrl,
                'raw_response' => $data
            ];
        }

        $statusMessage = $data['status_message'] ?? 'Unknown Midtrans Error';
        $validationMessages = isset($data['validation_messages']) && is_array($data['validation_messages'])
            ? ' (' . implode(', ', $data['validation_messages']) . ')'
            : '';

        Log::error('Midtrans Core API Creation Failed', [
            'external_id' => $externalId,
            'http_status' => $response->status(),
            'midtrans_status_code' => $statusCode,
            'status_message' => $statusMessage . $validationMessages,
            'body' => $response->body(),
        ]);

        if ($statusCode === '402' || str_contains(strtolower($statusMessage), 'channel is not activated')) {
            $channelName = $paymentType === 'QRIS' ? 'QRIS' : ('VA ' . strtoupper($bankCode));
            throw new \Exception("Metode pembayaran {$channelName} belum aktif untuk Core API di akun Midtrans Production Anda (Kode 402: {$statusMessage}). Silakan pilih metode pembayaran lain atau matikan channel ini di Pengaturan Aplikasi.");
        }

        if ($response->status() === 401 || $statusCode === '401') {
            throw new \Exception("Kredensial Midtrans Server Key tidak valid atau tidak sesuai dengan mode Production/Sandbox (Kode 401). Silakan periksa kembali Server Key di Pengaturan Aplikasi.");
        }
        
        throw new \Exception("Midtrans menolak pembuatan tagihan (Kode {$statusCode}: {$statusMessage}{$validationMessages}).");
    }
}
