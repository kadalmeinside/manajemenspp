<?php

namespace App\Http\Controllers;

use App\Models\Invoice; 
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
        // Log semua header yang masuk untuk debugging
        Log::info('[Xendit Webhook] Received request with headers:', $request->headers->all());
        
        $xenditCallbackToken = $request->header('x-callback-token');
        $storedCallbackToken = config('xendit.callback_verification_token');

        Log::info('[Xendit Webhook] Token Verification:', [
            'received_token' => $xenditCallbackToken,
            'expected_token' => $storedCallbackToken
        ]);

        if (!$storedCallbackToken || $xenditCallbackToken !== $storedCallbackToken) {
            Log::warning('[Xendit Webhook] VERIFICATION FAILED - Invalid callback token.');
            return response()->json(['message' => 'Invalid callback token'], 403);
        }

        $payload = $request->all();
        Log::info('[Xendit Webhook] Payload received:', $payload);

        // Cari invoice berdasarkan external_id
        $invoice = Invoice::where('external_id_xendit', $payload['external_id'])->first();

        if ($invoice) {
            // Update status invoice
            $invoice->status = strtoupper($payload['status']);
            
            // Simpan seluruh payload webhook untuk audit
            $invoice->xendit_callback_payload = $payload;

            if ($invoice->status === 'PAID') {
                $invoice->paid_at = Carbon::parse($payload['paid_at']);
                
                // Aktifkan siswa jika ini adalah invoice pendaftaran
                if ($invoice->type === 'pendaftaran') {
                    $siswa = $invoice->siswa;
                    if ($siswa && $siswa->status_siswa === 'pending_payment') {
                        $siswa->update(['status_siswa' => 'Aktif', 'tanggal_bergabung' => now()]);
                        Log::info('[Xendit Webhook] Siswa activated.', ['siswa_id' => $siswa->id_siswa]);
                    }
                }
            }
            
            $invoice->save();
            Log::info('[Xendit Webhook] Invoice status updated successfully.', ['invoice_id' => $invoice->id, 'new_status' => $invoice->status]);
        } else {
            Log::warning('[Xendit Webhook] Invoice not found for external_id.', ['external_id' => $payload['external_id']]);
        }

        return response()->json(['message' => 'Webhook processed']);
    }
}