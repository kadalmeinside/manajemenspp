<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentController extends Controller
{
    /**
     * Menampilkan halaman konfirmasi pembayaran sukses.
     */
    public function success(Request $request)
    {
        $invoice = null;
        
        // Handle standard invoice_id param
        if ($request->has('invoice_id')) {
            $invoice = \App\Models\Invoice::find($request->invoice_id);
        } 
        // Handle Midtrans order_id param
        elseif ($request->has('order_id')) {
            $invoice = \App\Models\Invoice::where('external_id_xendit', $request->order_id)->first();
        }

        // If invoice is found but not PAID, don't show generic success page blindly
        if ($invoice && $invoice->status !== 'PAID') {
            // If this is a custom checkout invoice, redirect back to the custom checkout page
            if (in_array($invoice->payment_gateway, ['gapura', 'midtrans_custom']) && !empty($invoice->checkout_data)) {
                return redirect()->route('tagihan.spp.custom_pay', ['invoice' => $invoice->id]);
            }
            // Otherwise, just fall through and let Payment/Success view handle the PENDING state display
        }

        return Inertia::render('Payment/Success', [
            'pageTitle' => 'Pembayaran Berhasil',
            'invoice' => $invoice,
        ]);
    }

    /**
     * Menampilkan halaman notifikasi pembayaran gagal.
     */
    public function failure(Request $request)
    {
        return Inertia::render('Payment/Failure', [
            'pageTitle' => 'Pembayaran Gagal',
        ]);
    }
}
