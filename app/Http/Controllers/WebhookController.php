<?php

namespace App\Http\Controllers;

use App\Models\TagihanSpp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WebhookController extends Controller
{
    /**
     * Menangani callback/webhook invoice dari Xendit.
     */
    public function handleInvoiceCallback(Request $request)
    {
        Log::info('[Xendit Webhook] Received:', $request->all());

        $xenditCallbackToken = $request->header('x-callback-token');
        $storedCallbackToken = config('xendit.callback_verification_token'); 

        Log::info('[Xendit Webhook] Token Verification Details:', [
            'received_token_from_header' => $xenditCallbackToken,
            'stored_token_from_config' => $storedCallbackToken,
            'are_they_equal' => ($xenditCallbackToken === $storedCallbackToken)
        ]);

        if (!$storedCallbackToken || $xenditCallbackToken !== $storedCallbackToken) {
            Log::warning('[Xendit Webhook] Invalid callback token.', [
                'received_token' => $xenditCallbackToken,
            ]);
            return response()->json(['message' => 'Invalid callback token'], 403);
        }

        Log::info('[Xendit Webhook] Token verified successfully. Processing payload...');
        $payload = $request->json()->all(); 

        if (!isset($payload['external_id']) || !isset($payload['status'])) {
            Log.error('[Xendit Webhook] Missing external_id or status in payload.', ['payload' => $payload]);
            return response()->json(['message' => 'Missing required data'], 400);
        }

        $externalId = $payload['external_id'];
        $statusDariXendit = $payload['status']; 

        $tagihan = TagihanSpp::where('external_id_xendit', $externalId)->first();

        if (!$tagihan) {
            Log.warning('[Xendit Webhook] TagihanSpp not found for external_id.', ['external_id' => $externalId]);
            return response()->json(['message' => 'Tagihan not found, but webhook acknowledged.'], 200);
        }

        if ($tagihan->status_pembayaran_xendit !== $statusDariXendit) {
            $tagihan->status_pembayaran_xendit = $statusDariXendit;

            if ($statusDariXendit === 'PAID') {
                $tagihan->tanggal_bayar = isset($payload['paid_at']) ? Carbon::parse($payload['paid_at']) : now();
                $tagihan->metode_pembayaran = $payload['payment_channel'] ?? null;
                Log::info('[Xendit Webhook] Tagihan PAID.', ['external_id' => $externalId, 'tagihan_id' => $tagihan->id_tagihan]);
            } elseif ($statusDariXendit === 'EXPIRED') {
                Log::info('[Xendit Webhook] Tagihan EXPIRED.', ['external_id' => $externalId, 'tagihan_id' => $tagihan->id_tagihan]);
                $tagihan->tanggal_bayar = null;
            } elseif ($statusDariXendit === 'FAILED') {
                Log::info('[Xendit Webhook] Tagihan FAILED.', ['external_id' => $externalId, 'tagihan_id' => $tagihan->id_tagihan]);
                $tagihan->tanggal_bayar = null;
            }

            $tagihan->save();
        } else {
            Log::info('[Xendit Webhook] Status tagihan tidak berubah, tidak ada update.', ['external_id' => $externalId, 'status' => $statusDariXendit]);
        }
        
        return response()->json(['message' => 'Webhook received successfully'], 200);
    }
}