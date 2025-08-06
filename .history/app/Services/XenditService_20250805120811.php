<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

    /**
     * Membuat invoice di Xendit dengan rincian biaya yang transparan.
     *
     * @param float $baseAmount Jumlah pokok (misal: total SPP)
     * @param float $feeAmount Jumlah biaya admin
     * @param string $description Deskripsi tagihan
     * @param array $payerInfo Informasi pembayar ['email', 'name', 'phone']
     * @param string $externalId ID eksternal unik
     * @param string $successRedirectUrl URL redirect saat sukses
     * @param string $failureRedirectUrl URL redirect saat gagal
     * @param \Carbon\Carbon $expiryDate Tanggal kedaluwarsa
     * @param array $notificationChannels Saluran notifikasi
     * @return array|null
     */
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
        $encodedApiKey = base64_encode($this->apiKey . ':');
        $totalAmount = $baseAmount + $feeAmount;

        // Logika durasi yang disederhanakan
        $invoiceDuration = $expiryDate->isFuture() ? (int) now()->diffInSeconds($expiryDate) : 0;
        $minDuration = 300; // 5 menit

        if ($invoiceDuration < $minDuration) {
            Log::warning('[XenditService] Durasi invoice kurang dari minimum, disesuaikan.', ['original' => $invoiceDuration, 'adjusted' => $minDuration]);
            $invoiceDuration = $minDuration;
        }

        $payload = [
            'external_id' => $externalId,
            'amount' => $totalAmount,
            'description' => $description,
            'customer' => [
                'given_names' => $payerInfo['name'] ?? 'Siswa',
                'mobile_number' => $payerInfo['phone'] ?? null,
            ],
            'invoice_duration' => $invoiceDuration,
            'success_redirect_url' => $successRedirectUrl,
            'failure_redirect_url' => $failureRedirectUrl,
            'currency' => 'IDR',
            'payment_methods' => $this->getPaymentMethods(),
        ];

        // Hanya tambahkan email dan notifikasi jika email valid.
        if (!empty($payerInfo['email'])) {
            $payload['payer_email'] = $payerInfo['email'];
            $payload['customer']['email'] = $payerInfo['email'];
            $payload['notification_channels'] = $notificationChannels;
        } else {
            // Jika tidak ada email, jangan kirim notifikasi email.
            $payload['notification_channels'] = []; 
        }

        // Tambahkan rincian biaya jika ada.
        if ($feeAmount > 0) {
            $payload['fees'] = [
                ['type' => 'ADMIN', 'value' => $feeAmount]
            ];
        }

        Log::info('[XenditService] Creating invoice with final payload:', $payload);

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedApiKey,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/v2/invoices", $payload);

        if ($response->successful()) {
            Log::info('[XenditService] Invoice created successfully.', ['external_id' => $externalId]);
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
     * @deprecated Metode ini menggunakan model TagihanSpp yang lama. Gunakan createInvoice.
     * Membuat sesi pembayaran untuk Xendit Snap.
     */
    public function createSnapCheckoutSession(\App\Models\TagihanSpp $tagihan)
    {
        $encodedApiKey = base64_encode($this->apiKey . ':');

        $payload = [
            'reference_id' => $tagihan->external_id_xendit,
            'currency' => 'IDR',
            'amount' => (float) $tagihan->total_tagihan,
            'checkout_method' => 'ONE_TIME_PAYMENT',
            'channel_code' => 'ID_OVO',
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

    /**
     * Membatalkan (expire) sebuah invoice di Xendit.
     */
    public function expireInvoice(string $invoiceId)
    {
        $encodedApiKey = base64_encode($this->apiKey . ':');

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $encodedApiKey,
        ])->post("{$this->baseUrl}/invoices/{$invoiceId}/expire!");

        if ($response->successful()) {
            Log::info('[Xendit Service] Invoice expired successfully.', ['xendit_invoice_id' => $invoiceId]);
            return $response->json();
        }

        Log::error('[Xendit Service] Failed to expire invoice.', [
            'xendit_invoice_id' => $invoiceId,
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return null;
    }
}
