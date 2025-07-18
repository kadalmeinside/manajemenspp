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
        $xenditCallbackToken = $request->header('x-callback-token');
        $storedCallbackToken = config('xendit.callback_verification_token');

        if (!$storedCallbackToken || $xenditCallbackToken !== $storedCallbackToken) {
            Log::warning('[Xendit Webhook] VERIFICATION FAILED.');
            return response()->json(['message' => 'Invalid callback token'], 403);
        }

        $payload = $request->all();
        Log::info('[Xendit Webhook] Payload received:', $payload);

        $parentInvoice = Invoice::with('siswa')->where('external_id_xendit', $payload['external_id'])->first();

        if (!$parentInvoice) {
            Log::warning('[Xendit Webhook] Parent Invoice not found.', ['external_id' => $payload['external_id']]);
            return response()->json(['message' => 'Invoice not found']);
        }

        // Hanya proses jika status PAID dan tipe-nya adalah pembayaran gabungan SPP
        if (strtoupper($payload['status']) === 'PAID' && $parentInvoice->type === 'pembayaran_spp_gabungan') {
            
            DB::beginTransaction();
            try {
                $paidTimestamp = Carbon::parse($payload['paid_at']);

                // Update Invoice Induk
                $parentInvoice->update([
                    'status' => 'PAID',
                    'paid_at' => $paidTimestamp,
                    'xendit_callback_payload' => $payload
                ]);

                $siswa = $parentInvoice->siswa;
                $monthlySpp = $siswa->jumlah_spp_custom;

                if ($monthlySpp > 0) {
                    // Hitung berapa bulan yang dibayar
                    $numMonths = round($parentInvoice->total_amount / $monthlySpp);
                    // Ambil periode awal dari invoice induk
                    $startPeriod = Carbon::parse($parentInvoice->periode_tagihan);

                    for ($i = 0; $i < $numMonths; $i++) {
                        $currentPeriod = $startPeriod->copy()->addMonths($i);

                        // LOGIKA AJAIB: Update jika ada, atau Buat jika belum ada.
                        Invoice::updateOrCreate(
                            [
                                'id_siswa' => $siswa->id_siswa,
                                'type' => 'spp',
                                'periode_tagihan' => $currentPeriod->format('Y-m-d'),
                            ],
                            [
                                'user_id' => $parentInvoice->user_id,
                                'description' => "Pembayaran SPP Bulan " . $currentPeriod->isoFormat('MMMM YYYY'),
                                'amount' => $monthlySpp,
                                'total_amount' => $monthlySpp, // Asumsi admin fee custom ditangani terpisah/tidak ada
                                'due_date' => $currentPeriod->copy()->endOfMonth(),
                                'status' => 'PAID',
                                'paid_at' => $paidTimestamp,
                            ]
                        );
                    }
                    Log::info("[Xendit Webhook] Successfully processed {$numMonths} SPP invoices for Parent Invoice ID: {$parentInvoice->id}");
                }
                
                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('[Xendit Webhook] FAILED to process unified SPP payment.', [
                    'parent_invoice_id' => $parentInvoice->id, 
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json(['message' => 'Error processing webhook'], 500);
            }
        }

        return response()->json(['message' => 'Webhook processed']);
    }
}