<?php

namespace App\Contracts;

use Carbon\Carbon;

interface PaymentGatewayInterface
{
    /**
     * Membuat tagihan/invoice baru ke Payment Gateway.
     *
     * @param float $baseAmount Jumlah pokok tagihan.
     * @param float $feeAmount Biaya admin.
     * @param string $description Deskripsi tagihan.
     * @param array $payerInfo Informasi pembayar ['email', 'name', 'phone'].
     * @param string $externalId ID referensi unik.
     * @param string $successRedirectUrl URL setelah sukses bayar.
     * @param string $failureRedirectUrl URL jika gagal bayar.
     * @param Carbon $expiryDate Kapan tagihan expired.
     * @param array $notificationChannels Media pengiriman notif (jika didukung).
     * @return array|null Mengembalikan data tagihan dari Gateway (termasuk ID dan URL).
     */
    public function createInvoice(
        float $baseAmount,
        float $feeAmount,
        string $description,
        array $payerInfo,
        string $externalId,
        string $successRedirectUrl,
        string $failureRedirectUrl,
        Carbon $expiryDate,
        array $notificationChannels = ['email']
    );

    /**
     * Membuat tagihan/invoice dengan Custom Checkout (terutama untuk Gapura).
     *
     * @param float $baseAmount Jumlah pokok tagihan.
     * @param float $feeAmount Biaya admin.
     * @param string $description Deskripsi tagihan.
     * @param array $payerInfo Informasi pembayar ['email', 'name', 'phone'].
     * @param string $externalId ID referensi unik.
     * @param Carbon $expiryDate Kapan tagihan expired.
     * @param string $paymentType Tipe pembayaran (misal 'VA', 'QRIS').
     * @param string $bankCode Kode bank (jika VA, misal 'BCA').
     * @return array|null Mengembalikan data instruksi pembayaran (VA number / QR string).
     */
    public function createCustomPayment(
        float $baseAmount,
        float $feeAmount,
        string $description,
        array $payerInfo,
        string $externalId,
        Carbon $expiryDate,
        string $paymentType,
        string $bankCode = ''
    );

    /**
     * Membatalkan/expire invoice yang sedang aktif.
     *
     * @param string $invoiceId ID dari Payment Gateway (bukan ID lokal).
     * @return mixed
     */
    public function expireInvoice(string $invoiceId);

    /**
     * Mendapatkan daftar metode pembayaran yang diizinkan/tersedia.
     *
     * @return array
     */
    public function getPaymentMethods(): array;
}
