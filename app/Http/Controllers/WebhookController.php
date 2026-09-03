<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\NotificationService;
use App\Services\Webhook\LegacyBulkHandler;
use App\Services\Webhook\PendaftaranHandler;
use App\Services\Webhook\SppGabunganHandler;
use App\Services\Webhook\StoreOrderHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Controller untuk menerima dan mendistribusikan Webhook dari Xendit.
 *
 * Tanggung jawab controller ini hanya:
 * 1. Verifikasi token keamanan.
 * 2. Identifikasi tipe invoice/pembayaran.
 * 3. Mendelegasikan ke Service Handler yang tepat.
 * 4. Mengirimkan notifikasi setelah proses berhasil.
 *
 * Logika bisnis ada di: app/Services/Webhook/
 */
class WebhookController extends Controller
{
    public function __construct(
        private readonly SppGabunganHandler $sppGabunganHandler,
        private readonly PendaftaranHandler $pendaftaranHandler,
        private readonly LegacyBulkHandler  $legacyBulkHandler,
        private readonly StoreOrderHandler    $storeOrderHandler,
    ) {}

    /**
     * Menangani callback/webhook invoice dari Xendit.
     *
     * Desain:
     * - IDEMPOTENT: Jika invoice sudah PAID, webhook diabaikan dengan aman.
     * - COMPLETE: Handle semua type invoice (spp, pendaftaran, pembayaran_spp_gabungan, pembayaran_gabungan).
     * - ACCURATE: Menggunakan `selected_periods` yang tersimpan di invoice induk.
     * - SAFE: Tidak menimpa amount/total_amount invoice anak — menjaga nilai cuti.
     */
    public function handleInvoiceCallback(Request $request)
    {
        // === 1. VERIFIKASI TOKEN ===
        $xenditCallbackToken = $request->header('x-callback-token');
        $storedCallbackToken = config('xendit.callback_verification_token');

        if (!$storedCallbackToken || $xenditCallbackToken !== $storedCallbackToken) {
            Log::warning('[Xendit Webhook] TOKEN VERIFICATION FAILED.', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid callback token'], 403);
        }

        $payload       = $request->all();
        $externalId    = $payload['external_id'] ?? null;
        $payloadStatus = strtoupper($payload['status'] ?? '');

        // === 2. VALIDASI EXTERNAL ID ===
        if (!$externalId) {
            Log::warning('[Xendit Webhook] Payload tidak memiliki external_id.');
            return response()->json(['message' => 'Missing external_id'], 400);
        }

        // === 3. DETEKSI DUAL-MODE: PREG- (PendingRegistration), STORE_INV (Order), vs invoice biasa ===
        if (str_starts_with($externalId, 'PREG-')) {
            return $this->pendaftaranHandler->handlePendingRegistration($externalId, $payload, $payloadStatus);
        }
        if (str_starts_with($externalId, 'STORE_INV_')) {
            return $this->storeOrderHandler->handleStoreOrder($externalId, $payload, $payloadStatus);
        }

        // === 4. PROSES INVOICE BIASA (SPP, Pendaftaran, Gabungan) ===
        DB::beginTransaction();
        try {
            $invoice = Invoice::with(['siswa', 'childInvoices'])
                ->where('external_id_xendit', $externalId)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                DB::rollBack();
                Log::warning('[Xendit Webhook] Invoice tidak ditemukan.', ['external_id' => $externalId]);
                // Return 200 agar Xendit tidak retry untuk data yang memang tidak ada
                return response()->json(['message' => 'Invoice not found, skipping']);
            }

            if ($invoice->payment_gateway !== 'xendit') {
                DB::rollBack();
                Log::warning('[Xendit Webhook] Invoice bukan dari Xendit.', ['external_id' => $externalId, 'gateway' => $invoice->payment_gateway]);
                return response()->json(['message' => 'Not a Xendit invoice, skipping']);
            }

            // === 5. IDEMPOTENCY CHECK ===
            if ($invoice->status === 'PAID') {
                DB::rollBack();
                Log::warning('[Xendit Webhook] Invoice sudah PAID sebelumnya, abaikan.', [
                    'invoice_id'  => $invoice->id,
                    'external_id' => $externalId,
                ]);
                return response()->json(['message' => 'Already processed, skipping']);
            }

            // === 6. HANYA PROSES EVENT PAID ===
            if ($payloadStatus !== 'PAID') {
                if (in_array($payloadStatus, ['EXPIRED', 'FAILED'])) {
                    $invoice->update([
                        'status'                  => $payloadStatus,
                        'xendit_callback_payload' => $payload,
                    ]);
                }
                DB::commit();
                return response()->json(['message' => 'Non-PAID event recorded']);
            }

            // === 7. UPDATE INVOICE UTAMA ===
            $paidTimestamp = Carbon::parse($payload['paid_at'] ?? now())->setTimezone(config('app.timezone'));
            $paymentMethod = $payload['payment_channel'] ?? $payload['payment_method'] ?? null;

            $invoice->update([
                'status'                  => 'PAID',
                'paid_at'                 => $paidTimestamp,
                'payment_method'          => $paymentMethod,
                'xendit_callback_payload' => $payload,
            ]);

            // === 8. DELEGASI KE HANDLER YANG TEPAT ===
            match ($invoice->type) {
                'pembayaran_spp_gabungan' => $this->sppGabunganHandler->handle($invoice, $paidTimestamp),
                'pendaftaran'             => $this->pendaftaranHandler->handleInvoicePendaftaran($invoice),
                'pembayaran_gabungan'     => $this->legacyBulkHandler->handle($invoice, $paidTimestamp),
                'spp'                     => null, // Invoice SPP individual — sudah diupdate di atas, selesai
                default => Log::warning('[Xendit Webhook] Unhandled invoice type: ' . $invoice->type, [
                    'invoice_id' => $invoice->id,
                ]),
            };

            DB::commit();

            // === 9. KIRIM NOTIFIKASI REAL-TIME KE ADMIN ===
            $this->sendPaymentNotification($invoice);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[Xendit Webhook] GAGAL memproses.', [
                'invoice_id' => $invoice->id ?? null,
                'type'       => $invoice->type ?? null,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            // Return 500 agar Xendit tahu ada error dan bisa retry
            return response()->json(['message' => 'Error processing webhook'], 500);
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }

    /**
     * Kirim notifikasi real-time ke admin berdasarkan tipe invoice yang berhasil dibayar.
     */
    private function sendPaymentNotification(Invoice $invoice): void
    {
        $siswa   = $invoice->siswa;
        $title   = 'Pembayaran Berhasil';
        $message = '';
        $url     = '/admin/invoices';

        if ($invoice->type === 'pendaftaran') {
            $title   = 'Pendaftaran & Pembayaran Berhasil';
            $message = "Siswa baru {$siswa->nama_siswa} telah mendaftar dan membayar.";
            $url     = '/admin/siswa/pendaftar-lunas';
        } elseif (in_array($invoice->type, ['spp', 'pembayaran_spp_gabungan'])) {
            $title   = 'Pembayaran SPP';
            $message = "SPP untuk {$siswa->nama_siswa} telah dibayar (Xendit).";
        }

        app(NotificationService::class)->sendToAdmins([
            'title'   => $title,
            'message' => $message,
            'type'    => 'payment_success',
            'url'     => $url,
        ], $siswa->id_kelas ?? null);
    }

    /**
     * Menangani callback/webhook dari Midtrans.
     */
    public function handleMidtransCallback(Request $request)
    {
        $payload = $request->all();
        $serverKey = config('midtrans.server_key') ?? \App\Models\Setting::where('key', 'midtrans_server_key')->value('value');
        
        $orderId = $payload['order_id'] ?? null;
        $statusCode = $payload['status_code'] ?? null;
        $grossAmount = $payload['gross_amount'] ?? null;
        $signatureKey = $payload['signature_key'] ?? null;
        
        // Verifikasi Signature Key Midtrans
        $calculatedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        
        if ($signatureKey !== $calculatedSignature) {
            Log::warning('[Midtrans Webhook] TOKEN VERIFICATION FAILED.', [
                'ip' => $request->ip(),
                'expected' => $calculatedSignature,
                'received' => $signatureKey
            ]);
            return response()->json(['message' => 'Invalid signature key'], 403);
        }

        $transactionStatus = $payload['transaction_status'] ?? '';
        $payloadStatus = in_array($transactionStatus, ['settlement', 'capture']) ? 'PAID' : strtoupper($transactionStatus);

        if (!$orderId) {
            return response()->json(['message' => 'Missing order_id'], 400);
        }

        // Delegasi Handler seperti Xendit
        if (str_starts_with($orderId, 'PREG-')) {
            return $this->pendaftaranHandler->handlePendingRegistration($orderId, $payload, $payloadStatus);
        }
        if (str_starts_with($orderId, 'STORE_INV_')) {
            return $this->storeOrderHandler->handleStoreOrder($orderId, $payload, $payloadStatus);
        }

        // PROSES INVOICE BIASA
        DB::beginTransaction();
        try {
            // Note: external_id_xendit refers to our order_id logically. (Bisa direname nanti)
            $invoice = Invoice::with(['siswa', 'childInvoices'])
                ->where('external_id_xendit', $orderId)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                DB::rollBack();
                Log::warning('[Midtrans Webhook] Invoice tidak ditemukan.', ['order_id' => $orderId]);
                return response()->json(['message' => 'Invoice not found, skipping']);
            }

            if ($invoice->payment_gateway !== 'midtrans') {
                DB::rollBack();
                Log::warning('[Midtrans Webhook] Invoice bukan dari Midtrans.', ['order_id' => $orderId, 'gateway' => $invoice->payment_gateway]);
                return response()->json(['message' => 'Not a Midtrans invoice, skipping']);
            }

            if ($invoice->status === 'PAID') {
                DB::rollBack();
                return response()->json(['message' => 'Already processed, skipping']);
            }

            // Jika belum lunas dan status dari webhook adalah PAID
            if ($payloadStatus === 'PAID') {
                $now = Carbon::now();

                // LUNASKAN INVOICE INDUK
                $invoice->status = 'PAID';
                $invoice->paid_at = $now;
                // Kita simpan metode pembayaran jika ada
                $invoice->payment_method = strtoupper($payload['payment_type'] ?? 'MIDTRANS'); 
                $invoice->save();

                // Delegasi khusus SPP Gabungan
                if ($invoice->type === 'pembayaran_spp_gabungan') {
                    $this->sppGabunganHandler->processPaidGabungan($invoice, $now);
                } 
                else if ($invoice->type === 'pembayaran_gabungan') {
                    $this->legacyBulkHandler->processPaidGabungan($invoice, $now);
                }
                else {
                    // Update childs secara generik
                    foreach ($invoice->childInvoices as $child) {
                        if ($child->status !== 'PAID') {
                            $child->status = 'PAID';
                            $child->paid_at = $now;
                            $child->save();
                        }
                    }
                }

                DB::commit();

                // Kiri Notifikasi
                try {
                    NotificationService::sendPaymentSuccessNotification($invoice);
                } catch (Throwable $e) {
                    Log::error('[Midtrans Webhook] Gagal mengirim notifikasi.', ['error' => $e->getMessage()]);
                }

                return response()->json(['message' => 'Midtrans Webhook processed successfully (PAID)']);
            }

            DB::commit();
            return response()->json(['message' => 'Midtrans Webhook processed, status: ' . $payloadStatus]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[Midtrans Webhook] GAGAL memproses.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }
}