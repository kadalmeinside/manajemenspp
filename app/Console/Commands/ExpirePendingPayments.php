<?php

namespace App\Console\Commands;

use App\Models\Kelas;
use App\Models\Order;
use App\Models\PendingRegistration;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpirePendingPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:expire-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membatalkan PendingRegistration dan Order Toko yang sudah melewati batas waktu pembayaran serta mengembalikan kuota promo dan stok barang.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredRegCount = 0;
        $expiredOrderCount = 0;

        // 1. Bersihkan PendingRegistration yang sudah melewati expires_at
        $expiredRegs = PendingRegistration::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($expiredRegs as $pending) {
            DB::transaction(function () use ($pending, &$expiredRegCount) {
                $locked = PendingRegistration::where('id', $pending->id)
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if (!$locked) {
                    return;
                }

                if ($locked->kode_promo) {
                    $kelas = Kelas::find($locked->id_kelas);
                    if ($kelas) {
                        $appliedPromos = $kelas->getAppliedPromos($locked->kode_promo);
                        foreach ($appliedPromos as $promo) {
                            if ($promo->current_uses > 0) {
                                $promo->decrement('current_uses');
                            }
                        }
                    }
                }

                $locked->update(['status' => 'expired']);
                $expiredRegCount++;
            });
        }

        // 2. Bersihkan Order Toko (PENDING lebih dari 24 jam) & kembalikan stok
        $expiredOrders = Order::with(['items.variant', 'items.product'])
            ->where('status', 'PENDING')
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order, &$expiredOrderCount) {
                $lockedOrder = Order::with(['items.variant', 'items.product'])
                    ->where('id', $order->id)
                    ->where('status', 'PENDING')
                    ->lockForUpdate()
                    ->first();

                if (!$lockedOrder) {
                    return;
                }

                $lockedOrder->update(['status' => 'EXPIRED']);

                foreach ($lockedOrder->items as $item) {
                    $variant = $item->variant;
                    if ($variant && $item->product && !$item->product->is_preorder) {
                        $previousStock = $variant->stock;
                        $variant->increment('stock', $item->quantity);

                        StockMovement::create([
                            'product_variant_id' => $variant->id,
                            'type'               => 'returned',
                            'quantity'           => $item->quantity,
                            'previous_stock'     => $previousStock,
                            'new_stock'          => $previousStock + $item->quantity,
                            'reference_id'       => $lockedOrder->order_number,
                            'user_id'            => null,
                            'notes'              => 'Stok dikembalikan otomatis karena pesanan melewati batas waktu pembayaran.',
                        ]);
                    }
                }

                $expiredOrderCount++;
            });
        }

        if ($expiredRegCount > 0 || $expiredOrderCount > 0) {
            Log::info('[ExpirePendingPayments] Selesai membersihkan pembayaran kadaluarsa.', [
                'expired_registrations' => $expiredRegCount,
                'expired_orders'        => $expiredOrderCount,
            ]);
        }

        $this->info("Expired registrations: {$expiredRegCount}, Expired orders: {$expiredOrderCount}");

        return self::SUCCESS;
    }
}
