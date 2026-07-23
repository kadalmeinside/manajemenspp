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
        if ($request->has('invoice_id')) {
            $invoice = \App\Models\Invoice::find($request->invoice_id);
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
