<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GapuraService implements PaymentGatewayInterface
{
    protected $baseUrl;
    protected $clientId;
    protected $clientSecret;
    protected $merchantId;
    protected $privateKey;

    public function __construct()
    {
        $this->baseUrl = config('gapura.base_url');
        $this->clientId = \App\Models\Setting::where('key', 'gapura_client_id')->value('value') ?? config('gapura.client_id');
        $this->clientSecret = \App\Models\Setting::where('key', 'gapura_client_secret')->value('value') ?? config('gapura.client_secret');
        $this->merchantId = \App\Models\Setting::where('key', 'gapura_merchant_id')->value('value') ?? config('gapura.merchant_id');
        $this->privateKey = \App\Models\Setting::where('key', 'gapura_private_key')->value('value') ?? config('gapura.private_key');
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
        throw new \Exception("GapuraService does not support Hosted Checkout (createInvoice) in this app.");
    }

    public function expireInvoice(string $invoiceId)
    {
        // Cancel virtual account / QRIS API call for DANA
        return null;
    }

    public function getPaymentMethods(): array
    {
        return ['BCA_VA', 'MANDIRI_VA', 'BNI_VA', 'QRIS'];
    }

    public function createCustomPayment(
        float $baseAmount,
        float $feeAmount,
        string $description,
        array $payerInfo,
        string $externalId,
        Carbon $expiryDate,
        string $paymentType,
        ?string $bankCode = null
    ) {
        $totalAmount = $baseAmount + $feeAmount;
        
        // Di Sandbox DANA, masa kedaluwarsa maksimal 30 menit.
        $isProduction = config('app.env') === 'production';
        if (!$isProduction) {
            $sandboxExpiry = now()->addMinutes(29); // Kita beri toleransi 1 menit
            if ($expiryDate->greaterThan($sandboxExpiry)) {
                $expiryDate = $sandboxExpiry;
            }
        }
        
        $timestamp = now()->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP');
        $relativeUrl = '/payment-gateway/v1.0/debit/payment-host-to-host.htm'; 
        
        $payOptionDetails = [];
        if ($paymentType === 'VA') {
            $payOptionDetails[] = [
                'payMethod' => 'VIRTUAL_ACCOUNT',
                'payOption' => 'VIRTUAL_ACCOUNT_' . strtoupper($bankCode ?? ''),
                'transAmount' => [
                    'value' => number_format($totalAmount, 2, '.', ''),
                    'currency' => 'IDR'
                ]
            ];
        } else if ($paymentType === 'QRIS') {
            $payOptionDetails[] = [
                'payMethod' => 'QRIS',
                'payOption' => 'QRIS', // Jika nanti salah, kita bisa periksa respons DANA
                'transAmount' => [
                    'value' => number_format($totalAmount, 2, '.', ''),
                    'currency' => 'IDR'
                ]
            ];
        }

        $payload = [
            'partnerReferenceNo' => $externalId,
            'merchantId' => $this->merchantId,
            'amount' => [
                'value' => number_format($totalAmount, 2, '.', ''),
                'currency' => 'IDR'
            ],
            'validUpTo' => $expiryDate->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i:sP'),
            'urlParams' => [
                [
                    'url' => route('tagihan.spp.success', ['siswa' => $payerInfo['siswa_id'] ?? '0']),
                    'type' => 'PAY_RETURN',
                    'isDeeplink' => 'N'
                ],
                [
                    'url' => url('/v1.0/debit/notify'), // Webhook URL (tanpa CSRF)
                    'type' => 'NOTIFICATION',
                    'isDeeplink' => 'N'
                ]
            ],
            'payOptionDetails' => $payOptionDetails,
            'additionalInfo' => [
                'order' => [
                    'orderTitle' => substr($description, 0, 64),
                    'scenario' => 'API',
                    'merchantTransType' => 'SPP',
                    'buyer' => [
                        'userId' => (string) ($payerInfo['siswa_id'] ?? 'unknown'),
                        'nickname' => substr($payerInfo['name'] ?? 'Siswa', 0, 255),
                    ],
                ]
            ]
        ];

        // --- 2. Buat X-SIGNATURE menggunakan Private Key ---
        $signature = '';
        if ($this->privateKey) {
            try {
                $signature = GapuraSignatureService::generateSignature(
                    'POST',
                    $relativeUrl,
                    $payload,
                    $timestamp,
                    $this->privateKey
                );
            } catch (\Exception $e) {
                Log::error('[Gapura] Gagal membuat signature', ['error' => $e->getMessage()]);
                throw new \Exception("Gagal menyusun keamanan request ke Gapura: " . $e->getMessage());
            }
        } else {
            throw new \Exception("Gapura Private Key belum dikonfigurasi di Pengaturan.");
        }

        // --- 3. Susun HTTP Headers standar SNAP ---
        $headers = [
            'Content-Type'  => 'application/json',
            'X-TIMESTAMP'   => $timestamp,
            'X-PARTNER-ID'  => $this->clientId,
            'X-EXTERNAL-ID' => $externalId,
            'X-SIGNATURE'   => $signature,
            'CHANNEL-ID'    => 'WEB',
            'ORIGIN'        => rtrim(config('app.url'), '/'),
        ];

        $baseUrl = $isProduction ? 'https://api.dana.id' : 'https://api.sandbox.dana.id';

        Log::info('[Gapura] Mengirim API Create Order ke DANA', [
            'url' => $baseUrl . $relativeUrl,
            'headers' => [
                'X-PARTNER-ID' => $headers['X-PARTNER-ID'],
                'ORIGIN' => $headers['ORIGIN'],
                'X-TIMESTAMP' => $headers['X-TIMESTAMP'],
                // JANGAN log signature atau private key secara utuh untuk keamanan, cukup potongannya saja
                'X-SIGNATURE' => substr($headers['X-SIGNATURE'], 0, 10) . '...'
            ],
            'payload' => $payload
        ]);
        
        $responseHttp = Http::withHeaders($headers)->post($baseUrl . $relativeUrl, $payload);
            
        if (!$responseHttp->successful()) {
            Log::error('[Gapura] API Call HTTP Failed', [
                'status' => $responseHttp->status(),
                'body' => $responseHttp->body()
            ]);
            throw new \Exception("Gagal terhubung ke API DANA Gapura. Error: " . $responseHttp->body());
        }

        $result = $responseHttp->json();
        
        Log::info('[Gapura] Response dari DANA', ['response' => $result]);

        // 2005400 adalah Response Code untuk "Success" berdasarkan dokumen
        if (!isset($result['responseCode']) || $result['responseCode'] !== '2005400') {
            Log::error('[Gapura] API Response Error', ['response' => $result]);
            throw new \Exception("DANA API menolak request: " . ($result['responseMessage'] ?? 'Unknown Error'));
        }

        // --- 4. Susun Respon ke Sistem Kita ---
        $response = [
            'id' => 'gapura-' . uniqid(),
            'external_id' => $externalId,
            'amount' => $totalAmount,
            'status' => 'PENDING',
            'payment_type' => $paymentType,
            'bank_code' => $bankCode,
            'expiry_date' => $expiryDate->toIso8601String(),
        ];

        // Ambil paymentCode dari balasan DANA (VA Number atau QRIS Text)
        if (isset($result['additionalInfo']['paymentCode'])) {
            $paymentCode = $result['additionalInfo']['paymentCode'];
            if ($paymentType === 'VA') {
                $response['va_number'] = $paymentCode;
            } else if ($paymentType === 'QRIS') {
                $response['qris_string'] = $paymentCode;
            }
        } else {
            Log::warning('[Gapura] additionalInfo.paymentCode tidak ditemukan dalam respon sukses DANA.', ['response' => $result]);
        }

        return $response;
    }
}
