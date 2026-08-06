<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Siswa;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Models\UserAgreement;
use App\Mail\RegistrationSuccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class WebhookController extends Controller
{
    /**
     * Menangani callback/webhook invoice dari Xendit.
     *
     * Desain:
     * - IDEMPOTENT: Jika invoice sudah PAID, webhook diabaikan dengan aman.
     * - COMPLETE: Handle semua type invoice (spp, pendaftaran, pembayaran_spp_gabungan, pembayaran_gabungan).
     * - ACCURATE: Menggunakan `selected_periods` yang tersimpan di invoice induk,
     *             bukan perhitungan matematika pembagian yang bisa salah.
     * - SAFE: Tidak menimpa amount/total_amount invoice anak — menjaga nilai cuti.
     */
    public function handleInvoiceCallback(Request $request)
    {
        // === 1. VERIFIKASI TOKEN ===
        $xenditCallbackToken = $request->header('x-callback-token');
        $storedCallbackToken = config('xendit.callback_verification_token');

        if (!$storedCallbackToken || $xenditCallbackToken !== $storedCallbackToken) {
            Log::warning('[Xendit Webhook] TOKEN VERIFICATION FAILED.', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid callback token'], 403);
        }

        $payload = $request->all();
        $externalId = $payload['external_id'] ?? null;
        $payloadStatus = strtoupper($payload['status'] ?? '');

        // Log::info('[Xendit Webhook] Payload diterima.', [
        //     'external_id' => $externalId,
        //     'status'      => $payloadStatus,
        // ]);

        // === 2. DETEKSI DUAL-MODE (PREG- vs REG-/SPP-) ===
        if (!$externalId) {
            Log::warning('[Xendit Webhook] Payload tidak memiliki external_id.');
            return response()->json(['message' => 'Missing external_id'], 400);
        }

        if (str_starts_with($externalId, 'PREG-')) {
            return $this->handlePendingRegistrationPayment($externalId, $payload, $payloadStatus);
        }

        DB::beginTransaction();
        try {
            $invoice = Invoice::with(['siswa', 'childInvoices'])
                ->where('external_id_xendit', $externalId)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                DB::rollBack();
                Log::warning('[Xendit Webhook] Invoice tidak ditemukan.', ['external_id' => $externalId]);
                // Return 200 agar Xendit tidak terus retry untuk data yang memang tidak ada
                return response()->json(['message' => 'Invoice not found, skipping']);
            }

            // === 3. IDEMPOTENCY CHECK — Jangan proses dua kali! ===
            if ($invoice->status === 'PAID') {
                DB::rollBack();
                Log::warning('[Xendit Webhook] Invoice sudah PAID sebelumnya, abaikan.', [
                    'invoice_id'  => $invoice->id,
                    'external_id' => $externalId,
                ]);
                return response()->json(['message' => 'Already processed, skipping']);
            }

            // === 4. HANYA PROSES EVENT PAID ===
            if ($payloadStatus !== 'PAID') {
                // Log::info('[Xendit Webhook] Status bukan PAID, abaikan.', [
                //     'external_id' => $externalId,
                //     'status'      => $payloadStatus,
                // ]);
                // Update status ke EXPIRED/FAILED jika relevan
                if (in_array($payloadStatus, ['EXPIRED', 'FAILED'])) {
                    $invoice->update([
                        'status'                  => $payloadStatus,
                        'xendit_callback_payload' => $payload,
                    ]);
                }
                DB::commit();
                return response()->json(['message' => 'Non-PAID event recorded']);
            }

            // === 5. PROSES PEMBAYARAN PAID ===
            $paidTimestamp = Carbon::parse($payload['paid_at'] ?? now())->setTimezone(config('app.timezone'));
            $paymentMethod = $payload['payment_channel'] ?? $payload['payment_method'] ?? null;

            // Update invoice utama terlebih dahulu
            $invoice->update([
                'status'                  => 'PAID',
                'paid_at'                 => $paidTimestamp,
                'payment_method'          => $paymentMethod,
                'xendit_callback_payload' => $payload,
            ]);

            // Dispatch ke handler yang tepat berdasarkan type invoice
            match ($invoice->type) {
                'pembayaran_spp_gabungan' => $this->handleGabunganSppPayment($invoice, $paidTimestamp),
                'pendaftaran'             => $this->handlePendaftaranPayment($invoice),
                'pembayaran_gabungan'     => $this->handleLegacyBulkPayment($invoice, $paidTimestamp),
                'spp'                     => null, // Invoice SPP individual — sudah diupdate di atas, selesai
                default => Log::warning('[Xendit Webhook] Unhandled invoice type: ' . $invoice->type, [
                    'invoice_id' => $invoice->id,
                ]),
            };

            DB::commit();

            // Log::info('[Xendit Webhook] Berhasil diproses.', [
            //     'invoice_id'   => $invoice->id,
            //     'type'         => $invoice->type,
            //     'paid_at'      => $paidTimestamp,
            // ]);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[Xendit Webhook] GAGAL memproses.', [
                'invoice_id' => $invoice->id ?? null,
                'type'       => $invoice->type ?? null,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);
            // Return 500 agar Xendit tahu ada error dan bisa retry
            return response()->json(['message' => 'Error processing webhook'], 500);
        }

        return response()->json(['message' => 'Webhook processed successfully']);
    }

    /**
     * Handle: Pembayaran SPP Gabungan (multi-bulan dari siswa login atau publik).
     *
     * LOGIKA KUNCI:
     * 1. Baca `selected_periods` dari invoice induk — daftar bulan yang dipilih siswa.
     * 2. Untuk setiap bulan: cari invoice SPP yang sudah ada.
     *    - Jika ADA → update status ke PAID saja, JANGAN ubah amount (menjaga nilai cuti!).
     *    - Jika TIDAK ADA → buat invoice SPP baru (untuk periode proyeksi).
     * 3. Fallback untuk invoice lama yang belum punya `selected_periods`.
     */
    private function handleGabunganSppPayment(Invoice $parentInvoice, Carbon $paidTimestamp): void
    {
        $siswa = $parentInvoice->siswa;
        if (!$siswa) {
            Log::error('[Webhook] Siswa tidak ditemukan untuk invoice induk.', [
                'parent_invoice_id' => $parentInvoice->id,
            ]);
            return;
        }

        // === AMBIL DAFTAR PERIODE YANG DIPILIH ===
        $selectedPeriods = $parentInvoice->selected_periods ?? [];

        if (empty($selectedPeriods)) {
            // === FALLBACK untuk invoice lama yang belum punya selected_periods ===
            // Gunakan logika lama tapi dengan perbaikan: pakai integer division, bukan round()
            Log::warning('[Webhook] Invoice induk tidak memiliki selected_periods. Menggunakan fallback.', [
                'parent_invoice_id' => $parentInvoice->id,
            ]);
            $this->handleGabunganSppFallback($parentInvoice, $siswa, $paidTimestamp);
            return;
        }

        // === PROSES SETIAP PERIODE YANG DIPILIH ===
        $processedCount = 0;
        foreach ($selectedPeriods as $periodStr) {
            $period = Carbon::parse($periodStr)->startOfMonth();

            // Cari invoice SPP yang sudah ada untuk bulan ini
            $sppInvoice = Invoice::where('id_siswa', $siswa->id_siswa)
                ->where('type', 'spp')
                ->whereDate('periode_tagihan', $period->toDateString())
                ->first();

            if ($sppInvoice) {
                // INVOICE SUDAH ADA — Update status saja, JANGAN ubah amount!
                // Ini penting untuk menjaga nilai cuti (250rb) agar tidak ditimpa nilai normal (500rb)
                $sppInvoice->update([
                    'status'            => 'PAID',
                    'paid_at'           => $paidTimestamp,
                    'parent_payment_id' => $parentInvoice->id,
                ]);
            } else {
                // INVOICE BELUM ADA — Buat baru (untuk periode proyeksi yang belum dibuat admin)
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
                ]);
            }

            $processedCount++;
        }

        // Log::info('[Webhook] Berhasil proses pembayaran_spp_gabungan.', [
        //     'parent_invoice_id' => $parentInvoice->id,
        //     'total_periods'     => count($selectedPeriods),
        //     'processed'         => $processedCount,
        // ]);
    }

    /**
     * Fallback untuk invoice lama yang tidak punya `selected_periods`.
     * Menggunakan logika matematika tapi dengan perbaikan dari versi lama.
     */
    private function handleGabunganSppFallback(Invoice $parentInvoice, Siswa $siswa, Carbon $paidTimestamp): void
    {
        $monthlySpp = (float)($siswa->jumlah_spp_custom ?? 0);
        if ($monthlySpp <= 0) {
            Log::error('[Webhook Fallback] jumlah_spp_custom siswa 0, tidak bisa proses fallback.', [
                'siswa_id' => $siswa->id_siswa,
            ]);
            return;
        }

        // Perbaikan dari versi lama:
        // - Gunakan (int) floor() bukan round() untuk hindari pembulatan ke atas yang salah
        // - Hitung dari `amount` (hanya SPP, tanpa admin_fee) agar tidak terkontaminasi fee
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
                // Update status saja — JANGAN ubah amount (jaga nilai cuti)
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

        // Log::info('[Webhook Fallback] Selesai proses ' . $numMonths . ' bulan.', [
        //     'parent_invoice_id' => $parentInvoice->id,
        // ]);
    }

    /**
     * Handle: Invoice pendaftaran PAID → aktifkan siswa dari 'pending_payment' ke 'Aktif'.
     */
    private function handlePendaftaranPayment(Invoice $invoice): void
    {
        $siswa = $invoice->siswa;
        if (!$siswa) {
            Log::warning('[Webhook] Siswa tidak ditemukan untuk invoice pendaftaran.', [
                'invoice_id' => $invoice->id,
            ]);
            return;
        }

        if ($siswa->status_siswa === 'pending_payment') {
            $siswa->update(['status_siswa' => 'Aktif']);
            // Log::info('[Webhook] Siswa berhasil diaktifkan setelah bayar pendaftaran.', [
            //     'siswa_id'   => $siswa->id_siswa,
            //     'nama_siswa' => $siswa->nama_siswa,
            // ]);
        } else {
            // Log::info('[Webhook] Siswa sudah aktif, tidak perlu update status.', [
            //     'siswa_id'      => $siswa->id_siswa,
            //     'status_siswa'  => $siswa->status_siswa,
            // ]);
        }
    }

    /**
     * Handle: Legacy Bulk Payment (menggunakan tabel invoice_relations / pivot).
     * Dipakai oleh createBulkPayment() yang lama.
     */
    private function handleLegacyBulkPayment(Invoice $parentInvoice, Carbon $paidTimestamp): void
    {
        $childInvoices = $parentInvoice->childInvoices;

        if ($childInvoices->isEmpty()) {
            Log::warning('[Webhook] Bulk payment tidak punya child invoices.', [
                'parent_invoice_id' => $parentInvoice->id,
            ]);
            return;
        }

        $updatedCount = 0;
        foreach ($childInvoices as $child) {
            if ($child->status !== 'PAID') {
                $child->update([
                    'status'            => 'PAID',
                    'paid_at'           => $paidTimestamp,
                    'parent_payment_id' => $parentInvoice->id,
                ]);
                $updatedCount++;
            }
        }

        // Log::info('[Webhook] Berhasil update child invoice (bulk).', [
        //     'parent_invoice_id' => $parentInvoice->id,
        //     'total_children'    => $childInvoices->count(),
        //     'updated'           => $updatedCount,
        // ]);
    }

    /**
     * Handle pembayaran dari PendingRegistration (Alur Baru Pendaftaran).
     * Saat dibayar: Buat User -> Buat Siswa -> Buat Invoice -> Aktif.
     */
    private function handlePendingRegistrationPayment(string $externalId, array $payload, string $payloadStatus)
    {
        DB::beginTransaction();
        try {
            $pending = PendingRegistration::where('xendit_external_id', $externalId)
                ->lockForUpdate()
                ->first();

            if (!$pending) {
                DB::rollBack();
                Log::warning('[Webhook PendingReg] Data tidak ditemukan.', ['external_id' => $externalId]);
                return response()->json(['message' => 'Pending registration not found, skipping']);
            }

            if ($pending->status === 'paid') {
                DB::rollBack();
                Log::warning('[Webhook PendingReg] Sudah paid sebelumnya, abaikan.', ['external_id' => $externalId]);
                return response()->json(['message' => 'Already processed, skipping']);
            }

            if ($payloadStatus !== 'PAID') {
                if (in_array($payloadStatus, ['EXPIRED', 'FAILED'])) {
                    // Release Quota Promo
                    $kelas = \App\Models\Kelas::find($pending->id_kelas);
                    if ($kelas) {
                        $appliedPromos = $kelas->getAppliedPromos($pending->kode_promo);
                        foreach ($appliedPromos as $promo) {
                            if ($promo->current_uses > 0) {
                                $promo->decrement('current_uses');
                            }
                        }
                    }
                    $pending->update(['status' => 'expired']);
                }
                DB::rollBack();
                // Log::info('[Webhook PendingReg] Status bukan PAID.', ['status' => $payloadStatus]);
                return response()->json(['message' => 'Non-PAID event recorded']);
            }

            // === PROSES PEMBUATAN AKUN & SISWA ===
            $paidTimestamp = Carbon::parse($payload['paid_at'] ?? now())->setTimezone(config('app.timezone'));
            $passwordPlain = Str::random(10); // Generate acak

            // Cek apakah user sudah ada (berdasarkan email)
            $user = User::where('email', $pending->email_wali)->first();
            if (!$user) {
                $user = User::create([
                    'name'     => $pending->nama_wali,
                    'email'    => $pending->email_wali,
                    'password' => Hash::make($passwordPlain),
                ]);
                $user->assignRole('siswa');
            } else {
                // Jika user sudah ada, password tidak diubah, kita hanya lampirkan siswa baru
                $passwordPlain = null; 
            }

            $kelas = \App\Models\Kelas::find($pending->id_kelas);
            $biayaSppDefault = $kelas ? $kelas->biaya_spp_default : 0;

            $siswa = Siswa::create([
                'nama_siswa'         => $pending->nama_siswa,
                'tanggal_lahir'      => $pending->tanggal_lahir,
                'id_kelas'           => $pending->id_kelas,
                'id_user'            => $user->id,
                'nomor_telepon_wali' => $pending->nomor_telepon_wali,
                'status_siswa'       => 'Aktif', // Langsung aktif karena sudah bayar!
                'tanggal_bergabung'  => now(),
                'jumlah_spp_custom'  => $biayaSppDefault,
                'admin_fee_custom'   => $pending->admin_fee,
            ]);
            $siswa->generateNis();

            UserAgreement::create([
                'user_id'           => $user->id,
                'id_siswa'          => $siswa->id_siswa,
                'legal_document_id' => $pending->legal_document_id,
                'agreed_at'         => now(),
                'ip_address'        => $pending->ip_address,
            ]);

            $deskripsi = "Biaya Pendaftaran - {$siswa->nama_siswa} (NIS: {$siswa->nis})";
            $invoice = Invoice::create([
                'id_siswa'                => $siswa->id_siswa,
                'user_id'                 => $user->id,
                'type'                    => 'pendaftaran',
                'description'             => $deskripsi,
                'amount'                  => $pending->amount,
                'admin_fee'               => $pending->admin_fee,
                'total_amount'            => $pending->total_amount,
                'due_date'                => $pending->expires_at,
                'status'                  => 'PAID',
                'paid_at'                 => $paidTimestamp,
                'payment_method'          => $payload['payment_channel'] ?? $payload['payment_method'] ?? null,
                'external_id_xendit'      => $externalId, // reuse ID ini
                'xendit_invoice_id'       => $pending->xendit_invoice_id,
                'xendit_payment_url'      => $pending->xendit_payment_url,
                'xendit_callback_payload' => $payload,
            ]);

            // Terapkan relasi promo ke invoice yang baru dibuat
            if ($kelas) {
                $appliedPromos = $kelas->getAppliedPromos($pending->kode_promo);
                if ($appliedPromos->isNotEmpty()) {
                    $invoice->promos()->attach($appliedPromos->pluck('id')->toArray());
                }
            }

            $pending->update(['status' => 'paid']);

            // Kirim email
            try {
                Mail::to($user->email)->queue(new RegistrationSuccess([
                    'nama_wali'  => $user->name,
                    'nama_siswa' => $siswa->nama_siswa,
                    'nis'        => $siswa->nis,
                    'email_wali' => $user->email,
                    'password'   => $passwordPlain, // akan dikirim ke view
                ]));
            } catch (\Exception $e) {
                Log::error('[Webhook PendingReg] Gagal kirim email.', ['error' => $e->getMessage()]);
            }

            DB::commit();
            // Log::info('[Webhook PendingReg] Berhasil membuat akun dan siswa.', ['siswa_id' => $siswa->id_siswa]);
            return response()->json(['message' => 'Pending registration processed successfully']);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[Webhook PendingReg] Gagal memproses.', [
                'external_id' => $externalId,
                'error'       => $e->getMessage()
            ]);
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }
}