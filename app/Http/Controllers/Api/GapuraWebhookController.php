<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Setting;
use App\Services\GapuraSignatureService;
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

class GapuraWebhookController extends Controller
{
    public function __construct(
        private readonly SppGabunganHandler $sppGabunganHandler,
        private readonly PendaftaranHandler $pendaftaranHandler,
        private readonly LegacyBulkHandler  $legacyBulkHandler,
        private readonly StoreOrderHandler  $storeOrderHandler,
    ) {}

    public function handleFinishNotify(Request $request)
    {
        Log::info('[Gapura Webhook] Menerima request dari DANA', $request->all());

        $danaPublicKey = Setting::where('key', 'gapura_dana_public_key')->value('value');
        if (!$danaPublicKey) {
            Log::error('[Gapura Webhook] Public Key DANA belum disetel.');
            return response()->json(['message' => 'Internal server error'], 500);
        }

        $signature = $request->header('X-SIGNATURE');
        $timestamp = $request->header('X-TIMESTAMP');

        if (!$signature || !$timestamp) {
            Log::warning('[Gapura Webhook] Header X-SIGNATURE atau X-TIMESTAMP hilang.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            $isValid = GapuraSignatureService::verifySignature(
                $request->method(),
                $request->getPathInfo(), 
                $request->getContent(),
                $timestamp,
                $signature,
                $danaPublicKey
            );

            if (!$isValid) {
                Log::warning('[Gapura Webhook] Signature tidak valid.');
                return response()->json(['message' => 'Invalid signature'], 401);
            }
            
            Log::info('[Gapura Webhook] Signature verified successfully.');

            $payload = $request->json()->all();
            $externalId = $payload['originalPartnerReferenceNo'] ?? null;

            if (!$externalId) {
                Log::error('[Gapura Webhook] Payload tidak memiliki originalPartnerReferenceNo', $payload);
                return response()->json(['message' => 'Invalid payload'], 400);
            }

            $latestTransactionStatus = $payload['latestTransactionStatus'] ?? '00';
            $payloadStatus = ($latestTransactionStatus === '00' || strtoupper($latestTransactionStatus) === 'SUCCESS' || strtoupper($latestTransactionStatus) === 'PAID')
                ? 'PAID'
                : (in_array(strtoupper($latestTransactionStatus), ['05', 'CANCELLED', 'EXPIRED']) ? 'EXPIRED' : 'PAID');

            // 1. Cek Pendaftaran Baru (PREG-)
            if (str_starts_with($externalId, 'PREG-')) {
                return $this->pendaftaranHandler->handlePendingRegistration($externalId, $payload, $payloadStatus);
            }

            // 2. Cek Order Toko
            if (str_starts_with($externalId, 'STORE_INV_') || Order::where('external_id', $externalId)->exists()) {
                return $this->storeOrderHandler->handleStoreOrder($externalId, $payload, $payloadStatus);
            }

            // 3. Proses Invoice Biasa / SPP Gabungan
            DB::beginTransaction();
            $invoice = Invoice::with(['siswa', 'childInvoices'])
                ->where('external_id_xendit', $externalId)
                ->lockForUpdate()
                ->first();
            
            if (!$invoice) {
                DB::rollBack();
                Log::warning('[Gapura Webhook] Invoice tidak ditemukan untuk ID: ' . $externalId);
                return response()->json(['message' => 'Invoice not found'], 404);
            }

            if ($invoice->status === 'PAID') {
                DB::rollBack();
                return response()->json(['message' => 'Already processed, skipping'], 200);
            }

            if ($payloadStatus === 'PAID') {
                $paidAmount = $payload['amount']['value'] ?? null;
                if ($paidAmount !== null && is_numeric($paidAmount)) {
                    $expectedAmount = (int) round((float) $invoice->total_amount);
                    $receivedAmount = (int) round((float) $paidAmount);
                    if ($receivedAmount < $expectedAmount) {
                        DB::rollBack();
                        Log::error('[Gapura Webhook] AMOUNT MISMATCH!', [
                            'external_id'     => $externalId,
                            'expected_amount' => $expectedAmount,
                            'received_amount' => $receivedAmount,
                        ]);
                        return response()->json(['message' => 'Amount mismatch'], 400);
                    }
                }

                $now = Carbon::now();
                $invoice->update([
                    'status'                  => 'PAID',
                    'paid_at'                 => $now,
                    'xendit_callback_payload' => $payload,
                ]);

                if ($invoice->type === 'pembayaran_spp_gabungan') {
                    $this->sppGabunganHandler->handle($invoice, $now);
                } elseif ($invoice->type === 'pembayaran_gabungan') {
                    $this->legacyBulkHandler->handle($invoice, $now);
                } elseif ($invoice->type === 'pendaftaran') {
                    $this->pendaftaranHandler->handleInvoicePendaftaran($invoice);
                } else {
                    foreach ($invoice->childInvoices as $child) {
                        if ($child->status !== 'PAID') {
                            $child->update([
                                'status'          => 'PAID',
                                'paid_at'         => $now,
                                'payment_method'  => $invoice->payment_method,
                                'payment_gateway' => $invoice->payment_gateway,
                            ]);
                        }
                    }
                }

                DB::commit();
                Log::info('[Gapura Webhook] Invoice ' . $externalId . ' berhasil dilunasi.');

                try {
                    if ($invoice->siswa) {
                        $title = $invoice->type === 'pendaftaran' ? 'Pendaftaran & Pembayaran Berhasil' : 'Pembayaran SPP';
                        $message = $invoice->type === 'pendaftaran'
                            ? "Siswa baru {$invoice->siswa->nama_siswa} telah mendaftar dan membayar (Gapura)."
                            : "SPP untuk {$invoice->siswa->nama_siswa} telah dibayar (Gapura).";
                        $url = $invoice->type === 'pendaftaran' ? '/admin/siswa/pendaftar-lunas' : '/admin/invoices';

                        app(NotificationService::class)->sendToAdmins([
                            'title'   => $title,
                            'message' => $message,
                            'type'    => 'payment_success',
                            'url'     => $url,
                        ], $invoice->siswa->id_kelas ?? null);
                    }
                } catch (Throwable $e) {
                    Log::error('[Gapura Webhook] Gagal mengirim notifikasi.', ['error' => $e->getMessage()]);
                }
            } else {
                $invoice->update([
                    'status'                  => $payloadStatus,
                    'xendit_callback_payload' => $payload,
                ]);
                DB::commit();
            }

            return response()->json(['message' => 'Webhook received and verified.'], 200);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('[Gapura Webhook] Error saat memproses webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }
}
