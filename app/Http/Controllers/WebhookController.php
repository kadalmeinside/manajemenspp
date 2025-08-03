<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

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

        if (strtoupper($payload['status']) === 'PAID' && $parentInvoice->type === 'pembayaran_spp_gabungan') {
            
            DB::beginTransaction();
            try {
                $paidTimestamp = Carbon::parse($payload['paid_at']);

                $parentInvoice->update([
                    'status' => 'PAID',
                    'paid_at' => $paidTimestamp,
                    'xendit_callback_payload' => $payload
                ]);

                $siswa = $parentInvoice->siswa;
                $monthlySpp = (float)($siswa->jumlah_spp_custom ?? 0);
                
                if ($parentInvoice->amount > 0 && $monthlySpp > 0) {
                    $numMonths = round($parentInvoice->amount / $monthlySpp);
                    $startPeriod = Carbon::parse($parentInvoice->periode_tagihan);

                    for ($i = 0; $i < $numMonths; $i++) {
                        $currentPeriod = $startPeriod->copy()->addMonths($i);

                        // ### PERBAIKAN ###
                        // Mengganti updateOrCreate dengan logika yang lebih robust (cari, lalu update/create)
                        // untuk menangani perbedaan format tanggal.

                        // 1. Cari invoice yang ada menggunakan whereDate untuk mengabaikan waktu.
                        $invoice = Invoice::where('id_siswa', $siswa->id_siswa)
                            ->where('type', 'spp')
                            ->whereDate('periode_tagihan', $currentPeriod)
                            ->first();

                        // 2. Siapkan data yang akan disimpan.
                        $data = [
                            'user_id' => $parentInvoice->user_id,
                            'description' => "Pembayaran SPP Bulan " . $currentPeriod->isoFormat('MMMM YYYY'),
                            'amount' => $monthlySpp,
                            'admin_fee' => 0,
                            'total_amount' => $monthlySpp,
                            'due_date' => $currentPeriod->copy()->endOfMonth(),
                            'status' => 'PAID',
                            'paid_at' => $paidTimestamp,
                            'parent_payment_id' => $parentInvoice->id 
                        ];

                        if ($invoice) {
                            // 3a. Jika invoice ditemukan, UPDATE.
                            $invoice->update($data);
                        } else {
                            // 3b. Jika tidak ditemukan, CREATE baru.
                            Invoice::create(array_merge([
                                'id_siswa' => $siswa->id_siswa,
                                'type' => 'spp',
                                'periode_tagihan' => $currentPeriod,
                            ], $data));
                        }
                    }
                    Log::info("[Xendit Webhook] Successfully processed {$numMonths} SPP invoices for Parent Invoice ID: {$parentInvoice->id}");
                }
                
                DB::commit();

            } catch (Throwable $e) {
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