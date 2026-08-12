<?php

namespace App\Services\Webhook;

use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\PendingRegistration;
use App\Models\Siswa;
use App\Models\User;
use App\Models\UserAgreement;
use App\Mail\RegistrationSuccess;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Menangani logika webhook untuk pembayaran dari PendingRegistration (Alur Baru Pendaftaran).
 * Saat dibayar: Buat User → Buat Siswa → Buat Invoice → Aktif.
 *
 * Termasuk juga handle invoice `pendaftaran` biasa (dari alur lama / via admin).
 */
class PendaftaranHandler
{
    /**
     * Tangani pembayaran PendingRegistration (alur pendaftaran baru / online).
     * Mengelola seluruh siklus: user, siswa, invoice, promo, dan email.
     */
    public function handlePendingRegistration(string $externalId, array $payload, string $payloadStatus): JsonResponse
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
                    // Release quota promo jika pembayaran kadaluarsa
                    $kelas = Kelas::find($pending->id_kelas);
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
                return response()->json(['message' => 'Non-PAID event recorded']);
            }

            // === PROSES PEMBUATAN AKUN & SISWA ===
            $paidTimestamp = Carbon::parse($payload['paid_at'] ?? now())->setTimezone(config('app.timezone'));
            $passwordPlain = Str::random(10);

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
                // Jika user sudah ada, password tidak diubah, hanya lampirkan siswa baru
                $passwordPlain = null;
            }

            $kelas = Kelas::find($pending->id_kelas);
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
                'external_id_xendit'      => $externalId,
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

            // Kirim email konfirmasi
            try {
                Mail::to($user->email)->queue(new RegistrationSuccess([
                    'nama_wali'  => $user->name,
                    'nama_siswa' => $siswa->nama_siswa,
                    'nis'        => $siswa->nis,
                    'email_wali' => $user->email,
                    'password'   => $passwordPlain,
                ]));
            } catch (\Exception $e) {
                Log::error('[Webhook PendingReg] Gagal kirim email.', ['error' => $e->getMessage()]);
            }

            DB::commit();
            return response()->json(['message' => 'Pending registration processed successfully']);

        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[Webhook PendingReg] Gagal memproses.', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Error processing webhook'], 500);
        }
    }

    /**
     * Tangani invoice pendaftaran PAID dari alur lama (via admin/non-PendingRegistration).
     * Aktifkan siswa dari status 'pending_payment' ke 'Aktif'.
     */
    public function handleInvoicePendaftaran(Invoice $invoice): void
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
        }
    }
}
