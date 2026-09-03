<?php

namespace App\Services\Webhook;

use App\Models\Invoice;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Menangani logika pembayaran SPP Gabungan (multi-bulan).
 *
 * LOGIKA KUNCI:
 * 1. Baca `selected_periods` dari invoice induk — daftar bulan yang dipilih siswa.
 * 2. Untuk setiap bulan: cari invoice SPP yang sudah ada.
 *    - Jika ADA → update status ke PAID saja, JANGAN ubah amount (menjaga nilai cuti!).
 *    - Jika TIDAK ADA → buat invoice SPP baru (untuk periode proyeksi).
 * 3. Fallback untuk invoice lama yang belum punya `selected_periods`.
 */
class SppGabunganHandler
{
    public function handle(Invoice $parentInvoice, Carbon $paidTimestamp): void
    {
        $siswa = $parentInvoice->siswa;
        if (!$siswa) {
            Log::error('[Webhook] Siswa tidak ditemukan untuk invoice induk.', [
                'parent_invoice_id' => $parentInvoice->id,
            ]);
            return;
        }

        $selectedPeriods = $parentInvoice->selected_periods ?? [];

        if (empty($selectedPeriods)) {
            // Fallback untuk invoice lama yang tidak punya selected_periods
            Log::warning('[Webhook] Invoice induk tidak memiliki selected_periods. Menggunakan fallback.', [
                'parent_invoice_id' => $parentInvoice->id,
            ]);
            $this->handleFallback($parentInvoice, $siswa, $paidTimestamp);
            return;
        }

        foreach ($selectedPeriods as $periodStr) {
            $period = Carbon::parse($periodStr)->startOfMonth();

            $sppInvoice = Invoice::where('id_siswa', $siswa->id_siswa)
                ->where('type', 'spp')
                ->whereDate('periode_tagihan', $period->toDateString())
                ->first();

            if ($sppInvoice) {
                // Invoice sudah ada — update status saja, JANGAN ubah amount (jaga nilai cuti!)
                $sppInvoice->update([
                    'status'            => 'PAID',
                    'paid_at'           => $paidTimestamp,
                    'parent_payment_id' => $parentInvoice->id,
                    'payment_method'    => $parentInvoice->payment_method,
                    'payment_gateway'   => $parentInvoice->payment_gateway,
                ]);
            } else {
                // Invoice belum ada — buat baru untuk periode proyeksi
                $monthlySpp = (float)($siswa->jumlah_spp_custom ?? 0);
                if ($monthlySpp <= 0) {
                    Log::error('[Webhook] jumlah_spp_custom siswa 0, tidak bisa buat invoice proyeksi.', [
                        'siswa_id' => $siswa->id_siswa,
                        'periode'  => $periodStr,
                    ]);
                    continue;
                }

                Carbon::setLocale('id');
                Invoice::create([
                    'id_siswa'          => $siswa->id_siswa,
                    'user_id'           => $parentInvoice->user_id,
                    'type'              => 'spp',
                    'description'       => 'SPP ' . $period->isoFormat('MMMM YYYY') . ' - ' . $siswa->nama_siswa . ' (NIS: ' . $siswa->nis . ')',
                    'periode_tagihan'   => $period,
                    'amount'            => $monthlySpp,
                    'admin_fee'         => 0,
                    'total_amount'      => $monthlySpp,
                    'due_date'          => $period->copy()->endOfMonth(),
                    'status'            => 'PAID',
                    'paid_at'           => $paidTimestamp,
                    'parent_payment_id' => $parentInvoice->id,
                    'payment_method'    => $parentInvoice->payment_method,
                    'payment_gateway'   => $parentInvoice->payment_gateway,
                ]);
            }
        }
    }

    /**
     * Fallback untuk invoice lama yang tidak punya `selected_periods`.
     * Menggunakan logika matematika dengan floor() untuk menghindari pembulatan yang salah.
     */
    private function handleFallback(Invoice $parentInvoice, Siswa $siswa, Carbon $paidTimestamp): void
    {
        $monthlySpp = (float)($siswa->jumlah_spp_custom ?? 0);
        if ($monthlySpp <= 0) {
            Log::error('[Webhook Fallback] jumlah_spp_custom siswa 0, tidak bisa proses fallback.', [
                'siswa_id' => $siswa->id_siswa,
            ]);
            return;
        }

        // Gunakan floor() bukan round() untuk hindari pembulatan ke atas yang salah
        // Hitung dari `amount` (hanya SPP, tanpa admin_fee) agar tidak terkontaminasi fee
        $numMonths = (int) floor($parentInvoice->amount / $monthlySpp);
        if ($numMonths <= 0) {
            Log::warning('[Webhook Fallback] numMonths = 0, skip.', ['parent_invoice_id' => $parentInvoice->id]);
            return;
        }

        $startPeriod = Carbon::parse($parentInvoice->periode_tagihan);
        Carbon::setLocale('id');

        for ($i = 0; $i < $numMonths; $i++) {
            $currentPeriod = $startPeriod->copy()->addMonths($i);

            $sppInvoice = Invoice::where('id_siswa', $siswa->id_siswa)
                ->where('type', 'spp')
                ->whereDate('periode_tagihan', $currentPeriod->toDateString())
                ->first();

            if ($sppInvoice) {
                $sppInvoice->update([
                    'status'            => 'PAID',
                    'paid_at'           => $paidTimestamp,
                    'parent_payment_id' => $parentInvoice->id,
                ]);
            } else {
                Invoice::create([
                    'id_siswa'          => $siswa->id_siswa,
                    'user_id'           => $parentInvoice->user_id,
                    'type'              => 'spp',
                    'description'       => 'SPP ' . $currentPeriod->isoFormat('MMMM YYYY') . ' - ' . $siswa->nama_siswa,
                    'periode_tagihan'   => $currentPeriod,
                    'amount'            => $monthlySpp,
                    'admin_fee'         => 0,
                    'total_amount'      => $monthlySpp,
                    'due_date'          => $currentPeriod->copy()->endOfMonth(),
                    'status'            => 'PAID',
                    'paid_at'           => $paidTimestamp,
                    'parent_payment_id' => $parentInvoice->id,
                ]);
            }
        }
    }
}
