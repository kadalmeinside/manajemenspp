<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\GapuraSignatureService;
use App\Models\Setting;
use App\Models\Invoice;

class GapuraWebhookController extends Controller
{
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
            // Karena DANA memverifikasi path yang utuh, kita berikan path yang DANA tuju
            // e.g. /v1.0/debit/notify
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
            
            // Berdasarkan dokumentasi PDF, ID transaksi kita berada di originalPartnerReferenceNo
            $externalId = $payload['originalPartnerReferenceNo'] ?? null;

            if (!$externalId) {
                Log::error('[Gapura Webhook] Payload tidak memiliki originalPartnerReferenceNo', $payload);
                return response()->json(['message' => 'Invalid payload'], 400);
            }

            // Cari Invoice
            $invoice = Invoice::where('external_id_xendit', $externalId)->first();
            
            if (!$invoice) {
                Log::warning('[Gapura Webhook] Invoice tidak ditemukan untuk ID: ' . $externalId);
                return response()->json(['message' => 'Invoice not found'], 404);
            }

            if ($invoice->status !== 'PAID') {
                $invoice->update([
                    'status' => 'PAID',
                    'paid_at' => now(),
                ]);
                Log::info('[Gapura Webhook] Invoice ' . $externalId . ' berhasil dilunasi.');
            }

            return response()->json(['message' => 'Webhook received and verified.'], 200);

        } catch (\Exception $e) {
            Log::error('[Gapura Webhook] Error saat memproses webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }
}
