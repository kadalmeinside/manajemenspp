<?php

use App\Models\Invoice;

// Find all paid SPP invoices that have a parent
$invoices = Invoice::where('type', 'spp')
    ->where('status', 'PAID')
    ->whereNotNull('parent_payment_id')
    ->get();

$count = 0;
foreach ($invoices as $invoice) {
    $parent = Invoice::find($invoice->parent_payment_id);
    if ($parent && $parent->payment_gateway) {
        $invoice->update([
            'payment_method' => $parent->payment_method,
            'payment_gateway' => $parent->payment_gateway,
        ]);
        $count++;
    }
}

echo "Fixed $count invoices.\n";
