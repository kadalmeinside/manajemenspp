<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Siswa;
use App\Exceptions\InsufficientSppDataException;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Contracts\PaymentGatewayInterface;

class PaymentService
{
    protected PaymentGatewayInterface $gateway;

    public function __construct()
    {
        // Secara dinamis memanggil gateway yang sedang aktif
        $this->gateway = PaymentGatewayFactory::make();
    }

    /**
     * Create a unified payment invoice (Pembayaran Gabungan) for multiple SPP periods.
     *
     * @param Siswa $siswa
     * @param Collection $periods
     * @param int|null $userId
     * @return Invoice
     * @throws \Exception
     */
    public function createUnifiedPayment(
        Siswa $siswa, 
        Collection $periods, 
        ?int $userId = null,
        ?string $paymentType = null,
        ?string $bankCode = ''
    ): Invoice
    {
        return DB::transaction(function () use ($periods, $siswa, $userId, $paymentType, $bankCode) {
            $periods = $periods->sort()->values();

            // 1. Cek reuse invoice (Pencegahan Spam API Xendit/Midtrans)
            $oldParentInvoices = Invoice::where('id_siswa', $siswa->id_siswa)
                ->where('type', 'pembayaran_spp_gabungan')
                ->where('status', 'PENDING')
                ->get();

            $activeGateway = \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';

            foreach ($oldParentInvoices as $oldParent) {
                $savedPeriods = collect($oldParent->selected_periods)->sort()->values();
                // Jika periode sama persis, dan gateway-nya sama, gunakan kembali
                // Catatan: Jika gapura (Custom Checkout), kita abaikan xendit_payment_url karena url tidak ada
                if ($savedPeriods->diff($periods)->isEmpty() && 
                    $periods->diff($savedPeriods)->isEmpty() && 
                    $oldParent->payment_gateway === $activeGateway) {
                    
                    if ($activeGateway === 'gapura') {
                        // Untuk gapura, pastikan paymentType-nya sama, jika ya return lama. Jika tidak, hapus dan buat baru.
                        $oldCheckoutData = $oldParent->checkout_data ?? [];
                        if (($oldCheckoutData['payment_type'] ?? '') === $paymentType && 
                            ($oldCheckoutData['bank_code'] ?? '') === $bankCode) {
                            return $oldParent;
                        }
                    } else if ($oldParent->xendit_payment_url) {
                        return $oldParent;
                    }
                }

                // Jika beda, expire-kan yang lama
                if ($oldParent->xendit_invoice_id) {
                    $this->gateway->expireInvoice($oldParent->xendit_invoice_id);
                }
                $oldParent->delete();
            }

            // 2. Hitung total SPP (existing invoices + projected periods)
            $totalSpp = 0;
            $existingInvoices = $siswa->invoices()
                ->whereIn('periode_tagihan', $periods->toArray())
                ->where('type', 'spp')
                ->lockForUpdate()
                ->get();

            // Validasi Double Billing
            if ($existingInvoices->where('status', 'PAID')->isNotEmpty()) {
                throw new \Exception('Satu atau lebih bulan yang Anda pilih sudah lunas dibayar.');
            }

            // Validasi Berurutan (Sequential Enforcement)
            $firstUnpaidPeriod = $siswa->invoices()
                ->where('type', 'spp')
                ->where('status', '!=', 'PAID')
                ->orderBy('periode_tagihan', 'asc')
                ->value('periode_tagihan');

            if ($firstUnpaidPeriod && Carbon::parse($periods->first())->startOfMonth()->notEqualTo(Carbon::parse($firstUnpaidPeriod)->startOfMonth())) {
                throw new \Exception('Pembayaran SPP harus dilakukan secara berurutan dimulai dari tagihan tertua (' . Carbon::parse($firstUnpaidPeriod)->isoFormat('MMMM YYYY') . ') yang belum lunas.');
            }
                
            $totalSpp += $existingInvoices->sum('total_amount');
            
            $existingPeriods = $existingInvoices->pluck('periode_tagihan')->map(fn($p) => $p->format('Y-m-d'));
            $projectedPeriods = $periods->diff($existingPeriods);

            if ($projectedPeriods->isNotEmpty()) {
                $sppPerBulan = (float)($siswa->jumlah_spp_custom ?? 0);
                if ($sppPerBulan <= 0) {
                    throw new InsufficientSppDataException('Data nominal SPP Anda belum diatur untuk membuat tagihan baru.');
                }
                $totalSpp += $projectedPeriods->count() * $sppPerBulan;
            }

            // 3. Tambahkan admin fee satu kali
            $adminFee = (float)($siswa->admin_fee_custom ?? 0);
            $totalAmount = $totalSpp + $adminFee;

            if ($totalAmount <= 0) {
                throw new \Exception("Total tagihan tidak valid (Rp 0).");
            }

            // 4. Buat deskripsi yang sesuai (1 bulan vs Gabungan)
            Carbon::setLocale('id');
            $startPeriod = Carbon::parse($periods->first());
            
            if ($periods->count() == 1) {
                $description = "SPP {$startPeriod->isoFormat('MMMM YYYY')} - {$siswa->nama_siswa} (NIS: {$siswa->nis})";
            } else {
                $endPeriod = Carbon::parse($periods->last());
                $description = "SPP Gabungan ({$periods->count()} Bulan: {$startPeriod->isoFormat('MMMM YYYY')} - {$endPeriod->isoFormat('MMMM YYYY')}) - {$siswa->nama_siswa} (NIS: {$siswa->nis})";
            }

            // 5. Buat invoice induk yang baru
            $parentInvoice = Invoice::create([
                'id_siswa'         => $siswa->id_siswa,
                'user_id'          => $userId ?? $siswa->user?->id,
                'type'             => 'pembayaran_spp_gabungan',
                'description'      => $description,
                'periode_tagihan'  => $startPeriod,
                'selected_periods' => $periods->toArray(),
                'amount'           => $totalSpp,
                'admin_fee'        => $adminFee,
                'total_amount'     => $totalAmount,
                'due_date'         => now()->addDay(),
                'status'           => 'PENDING',
                'external_id_xendit' => 'UNIF-'.substr($siswa->id_siswa, 0, 8).'-'.strtoupper(Str::random(8)),
                'payment_gateway'    => $activeGateway,
            ]);

            // 6. Buat invoice di Gateway Aktif
            $payerInfo = [
                'email' => $siswa->user?->email, 
                'name' => $siswa->nama_siswa, 
                'phone' => $siswa->nomor_telepon_wali
            ];
            
            if ($activeGateway === 'gapura' && $paymentType) {
                $pgInvoiceData = $this->gateway->createCustomPayment(
                    $totalSpp,
                    $adminFee,
                    $parentInvoice->description, 
                    $payerInfo,
                    $parentInvoice->external_id_xendit, 
                    now()->addDay(),
                    $paymentType,
                    $bankCode
                );

                if (!$pgInvoiceData) {
                    throw new \Exception('Gagal membuat tagihan Gapura DANA.');
                }
                
                $parentInvoice->update([
                    'xendit_invoice_id' => $pgInvoiceData['id'],
                    'checkout_data' => $pgInvoiceData,
                ]);
            } else {
                $pgInvoiceData = $this->gateway->createInvoice(
                    $totalSpp,
                    $adminFee,
                    $parentInvoice->description, 
                    $payerInfo,
                    $parentInvoice->external_id_xendit, 
                    route('payment.success'), 
                    route('payment.failure'), 
                    now()->addDay()
                );

                if (!$pgInvoiceData || !isset($pgInvoiceData['invoice_url'])) {
                    throw new \Exception('Gagal membuat link pembayaran di Payment Gateway.');
                }
                
                $parentInvoice->update([
                    'xendit_invoice_id' => $pgInvoiceData['id'],
                    'xendit_payment_url' => $pgInvoiceData['invoice_url'],
                ]);
            }

            return $parentInvoice;
        });
    }
}
