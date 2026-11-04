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
     * Ambil konfigurasi MDR dan metode pembayaran untuk dikirim ke Frontend.
     */
    public static function getMdrConfig(): array
    {
        $settings = \App\Models\Setting::whereIn('key', [
            'enable_mdr_fee',
            'enable_mdr_pendaftaran',
            'enable_mdr_spp',
            'enable_mdr_store',
            'mdr_va_flat',
            'mdr_qris_percent',
            'enable_virtual_account',
        ])->pluck('value', 'key');

        $isExplicitlyDisabled = isset($settings['enable_mdr_fee']) && $settings['enable_mdr_fee'] === '0';

        return [
            'enabled'            => !$isExplicitlyDisabled,
            'enable_pendaftaran' => ($settings['enable_mdr_pendaftaran'] ?? '0') === '1',
            'enable_spp'         => ($settings['enable_mdr_spp'] ?? '0') === '1',
            'enable_store'       => ($settings['enable_mdr_store'] ?? '1') === '1',
            'va_flat'            => (float) (isset($settings['mdr_va_flat']) && $settings['mdr_va_flat'] !== '' ? $settings['mdr_va_flat'] : 4440),
            'qris_percent'       => (float) (isset($settings['mdr_qris_percent']) && $settings['mdr_qris_percent'] !== '' ? $settings['mdr_qris_percent'] : 0.7),
            'enable_va'          => ($settings['enable_virtual_account'] ?? '1') === '1',
        ];
    }

    /**
     * Hitung biaya admin / MDR berdasarkan pengaturan aktif dan metode pembayaran yang dipilih.
     */
    public function calculateAdminFee(
        ?Siswa $siswa,
        float $baseAmount,
        ?string $paymentType = null,
        ?string $activeGateway = null,
        string $module = 'spp'
    ): float {
        $activeGateway = $activeGateway ?? config('payment.active_gateway') ?? \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';
        $mdrConfig = self::getMdrConfig();

        if ($mdrConfig['enabled'] && in_array($activeGateway, ['midtrans', 'midtrans_custom', 'gapura'])) {
            $isModuleEnabled = match ($module) {
                'pendaftaran' => $mdrConfig['enable_pendaftaran'],
                'store', 'pembelian' => $mdrConfig['enable_store'],
                'spp' => $mdrConfig['enable_spp'],
                default => false,
            };

            if ($isModuleEnabled) {
                $type = strtoupper($paymentType ?: ($mdrConfig['enable_va'] ? 'VA' : 'QRIS'));
                if ($type === 'QRIS') {
                    return (float) ceil($baseAmount * ($mdrConfig['qris_percent'] / 100));
                }
                if ($type === 'VA') {
                    return (float) round($mdrConfig['va_flat']);
                }
            } else {
                return 0.0;
            }
        }

        return (float) ($siswa?->admin_fee_custom ?? 0);
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
            $activeGateway = config('payment.active_gateway') ?? \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';

            // 1. Hitung total SPP (existing invoices + projected periods) terlebih dahulu
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

            // 2. Hitung admin fee / MDR sesuai metode pembayaran yang dipilih
            $adminFee = $this->calculateAdminFee($siswa, $totalSpp, $paymentType, $activeGateway);
            $totalAmount = $totalSpp + $adminFee;

            if ($totalAmount <= 0) {
                throw new \Exception("Total tagihan tidak valid (Rp 0).");
            }

            // 3. Cek reuse invoice (Pencegahan Spam API Xendit/Midtrans)
            $oldParentInvoices = Invoice::where('id_siswa', $siswa->id_siswa)
                ->where('type', 'pembayaran_spp_gabungan')
                ->where('status', 'PENDING')
                ->get();

            foreach ($oldParentInvoices as $oldParent) {
                $savedPeriods = collect($oldParent->selected_periods)->sort()->values();
                $isNotExpired = !$oldParent->due_date || Carbon::parse($oldParent->due_date)->isFuture();
                $sameAmount   = abs((float) $oldParent->total_amount - $totalAmount) < 1;

                // Jika periode, gateway, dan nominal sama persis serta belum kadaluarsa, gunakan kembali
                if ($isNotExpired &&
                    $sameAmount &&
                    $savedPeriods->diff($periods)->isEmpty() && 
                    $periods->diff($savedPeriods)->isEmpty() && 
                    $oldParent->payment_gateway === $activeGateway) {
                    
                    if (in_array($activeGateway, ['gapura', 'midtrans_custom']) && $paymentType) {
                        $oldCheckoutData = $oldParent->checkout_data ?? [];
                        if (($oldCheckoutData['payment_type'] ?? '') === $paymentType && 
                            ($oldCheckoutData['bank_code'] ?? '') === ($bankCode ?? '')) {
                            return $oldParent;
                        }
                    } else if ($oldParent->xendit_payment_url) {
                        return $oldParent;
                    }
                }

                // Jika beda atau sudah kadaluarsa, expire-kan yang lama sesuai gateway asalnya
                if ($oldParent->xendit_invoice_id) {
                    try {
                        $oldGateway = PaymentGatewayFactory::make($oldParent->payment_gateway);
                        $oldGateway->expireInvoice($oldParent->xendit_invoice_id);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('[PaymentService] Gagal expire invoice lama: ' . $e->getMessage());
                    }
                }
                $oldParent->delete();
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
            
            if (in_array($activeGateway, ['gapura', 'midtrans_custom']) && $paymentType) {
                $pgInvoiceData = $this->gateway->createCustomPayment(
                    $totalSpp,
                    $adminFee,
                    $parentInvoice->description, 
                    $payerInfo,
                    $parentInvoice->external_id_xendit, 
                    now()->addDay(),
                    $paymentType,
                    $bankCode ?? ''
                );

                if (!$pgInvoiceData) {
                    throw new \Exception("Gagal membuat tagihan {$activeGateway} DANA/Midtrans.");
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
