<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class XenditService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('xendit.api_key');
        $this->baseUrl = config('xendit.base_url');
    }

    public function createInvoice(
        float $amount,
        float $adminFee,
        string $description,
        array $payerInfo,
        string $externalId,
        string $successRedirectUrl,
        string $failureRedirectUrl,
        \Carbon\Carbon $expiryDateInput,
        array $notificationChannels = ['email']
    ) {
        $encodedApiKey = base64_encode($this->apiKey . ':');
        $now = now();

        $invoiceDurationInSeconds = 0;

        if (!($expiryDateInput instanceof Carbon)) {
            $expiryDateInput = Carbon::parse($expiryDateInput);
        }

        if ($expiryDateInput->isPast() || ($expiryDateInput->isToday() && $expiryDateInput->isPast())) {
            Log::warning('[XenditService] Expiry date input is in the past or too soon today. Adjusting to default future.', [
                'original_expiry_input' => $expiryDateInput->toIso8601String(),
                'now' => $now->toIso8601String(),
                'external_id' => $externalId
            ]);
            $expiryDateForCalc = $now->copy()->addHours(24);
        } else {
            $expiryDateForCalc = $expiryDateInput;
        }

        if ($expiryDateForCalc->getTimestamp() > $now->getTimestamp()) {
            $invoiceDurationInSeconds = $expiryDateForCalc->getTimestamp() - $now->getTimestamp();
        } else {
            $invoiceDurationInSeconds = 0;
        }


        Log::info('[XenditService] Duration Calculation Debug:', [
            'expiryDateInput_ISO' => $expiryDateInput->toIso8601String(),
            'expiryDateForCalc_ISO' => $expiryDateForCalc->toIso8601String(),
            'now_ISO' => $now->toIso8601String(),
            'calculated_duration_seconds_from_timestamp_diff' => $invoiceDurationInSeconds,
            'external_id' => $externalId
        ]);

        $minDurationAcceptedByXendit = 300;

        if ($invoiceDurationInSeconds < $minDurationAcceptedByXendit) {
            Log::warning('[XenditService] Calculated invoice_duration was less than Xendit minimum. Adjusting to minimum.', [
                'original_duration' => $invoiceDurationInSeconds,
                'adjusted_to' => $minDurationAcceptedByXendit,
                'external_id' => $externalId
            ]);
            $invoiceDurationInSeconds = $minDurationAcceptedByXendit;
        }

        $totalAmount = $amount + $adminFee;

        $payload = [
            'external_id' => $externalId,
            'amount' => $totalAmount,
            'description' => $description,
            'payer_email' => $payerInfo['email'] ?? null,
            'customer' => [
                'given_names' => $payerInfo['name'] ?? null,
                'email' => $payerInfo['email'] ?? null,
                'mobile_number' => $payerInfo['phone'] ?? null,
            ],
            'invoice_duration' => (int) $invoiceDurationInSeconds,
            'notification_channels' => $notificationChannels,
            'success_redirect_url' => $successRedirectUrl,
            'failure_redirect_url' => $failureRedirectUrl,
            'currency' => 'IDR',
            'payment_methods' => $this->getPaymentMethods(),
            'success_redirect_url' => route('payment.success'),
            'failure_redirect_url' => route('payment.failure'),
        ];

        if ($adminFee > 0) {
            $payload['fees'] = [
                [
                    'type' => 'ADMIN', // Tipe fee, bisa Anda namai apa saja
                    'value' => $adminFee
                ]
            ];
        }

        Log::info('[XenditService] Creating invoice with final payload:', $payload);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedApiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/v2/invoices", $payload);

        if ($response->successful()) {
            Log::info('[XenditService] Invoice created successfully via Xendit.', ['external_id' => $externalId, 'xendit_response' => $response->json()]);
            return $response->json();
        }

        logger()->error('Xendit Invoice Creation Failed (Service)', [
            'external_id' => $externalId,
            'status' => $response->status(),
            'body' => $response->body(),
            'sent_payload' => $payload
        ]);
        return null;
    }

    protected function getPaymentMethods()
    {
        return [
            'BCA', 'BNI', 'BRI', 'MANDIRI', 'PERMATA',
            'CREDIT_CARD',
            'OVO', 'DANA', 'LINKAJA', 'SHOPEEPAY',
            'QRIS'
        ];
    }

    /**
     * Membuat sesi pembayaran untuk Xendit Snap.
     *
     * @param TagihanSpp $tagihan
     * @return array|null Respons dari Xendit berisi token
     */
    public function createSnapCheckoutSession(\App\Models\TagihanSpp $tagihan)
    {
        $encodedApiKey = base64_encode($this->apiKey . ':');

        $payload = [
            'reference_id' => $tagihan->external_id_xendit, // Gunakan external_id yang sudah ada
            'currency' => 'IDR',
            'amount' => (float) $tagihan->total_tagihan,
            'checkout_method' => 'ONE_TIME_PAYMENT',
            'channel_code' => 'ID_OVO', // Ini bisa menjadi channel default atau dikosongkan
            'customer' => [
                'given_names' => $tagihan->siswa->nama_siswa,
                'email' => $tagihan->siswa->user->email,
                'mobile_number' => $tagihan->siswa->nomor_telepon_wali,
            ],
            'metadata' => [
                'tagihan_id' => $tagihan->id_tagihan,
                'periode' => $tagihan->periode_tagihan->isoFormat('MMMM YYYY'),
            ],
            'redirect_url' => route('payment.success')
        ];

        // API untuk membuat sesi checkout.
        // Pastikan Anda menggunakan endpoint yang benar dari dokumentasi Xendit terbaru.
        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedApiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/checkout_sessions", $payload);


        if ($response->successful()) {
            return $response->json();
        }

        logger()->error('Xendit Snap Session Creation Failed', [
            'reference_id' => $tagihan->external_id_xendit,
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        return null;
    }
}