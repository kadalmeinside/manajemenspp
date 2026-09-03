<?php

namespace App\Services\Webhook;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

/**
 * Menangani logika webhook untuk pembayaran Legacy Bulk.
 * Digunakan oleh alur lama yang menyimpan relasi invoice via tabel invoice_relations (pivot).
 */
class LegacyBulkHandler
{
    public function handle(Invoice $parentInvoice, \Carbon\Carbon $paidTimestamp): void
    {
        $childInvoices = $parentInvoice->childInvoices;

        if ($childInvoices->isEmpty()) {
            Log::warning('[Webhook] Bulk payment tidak punya child invoices.', [
                'parent_invoice_id' => $parentInvoice->id,
            ]);
            return;
        }

        foreach ($childInvoices as $child) {
            if ($child->status !== 'PAID') {
                $child->update([
                    'status'            => 'PAID',
                    'paid_at'           => $paidTimestamp,
                    'parent_payment_id' => $parentInvoice->id,
                    'payment_method'    => $parentInvoice->payment_method,
                    'payment_gateway'   => $parentInvoice->payment_gateway,
                ]);
            }
        }
    }
}
