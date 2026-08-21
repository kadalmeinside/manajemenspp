<?php

namespace App\Services\Webhook;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreOrderHandler
{
    /**
     * Menangani webhook dari Xendit khusus untuk tagihan Toko (Order)
     */
    public function handleStoreOrder(string $externalId, array $payload, string $payloadStatus)
    {
        $orderId = str_replace('STORE_INV_', '', $externalId);
        
        DB::beginTransaction();
        try {
            $order = Order::with('items.variant')->lockForUpdate()->find($orderId);

            if (!$order) {
                DB::rollBack();
                Log::warning('[Xendit Webhook] Order Toko tidak ditemukan.', ['external_id' => $externalId]);
                return response()->json(['message' => 'Order not found, skipping']);
            }

            // === IDEMPOTENCY CHECK ===
            if ($order->status === 'PAID' || $order->status === 'COMPLETED') {
                DB::rollBack();
                Log::warning('[Xendit Webhook] Order sudah PAID/COMPLETED sebelumnya, abaikan.', [
                    'order_id'    => $order->id,
                    'external_id' => $externalId,
                ]);
                return response()->json(['message' => 'Already processed, skipping']);
            }

            // === EXPIRED / FAILED ===
            // Jika tagihan kadaluarsa, pesanan dibatalkan dan STOK DIKEMBALIKAN.
            if (in_array($payloadStatus, ['EXPIRED', 'FAILED'])) {
                if ($order->status !== 'EXPIRED' && $order->status !== 'FAILED') {
                    $order->update(['status' => $payloadStatus]);
                    
                    // Kembalikan stok
                    foreach ($order->items as $item) {
                        $variant = $item->variant;
                        if ($variant && !$item->product->is_preorder) {
                            $previousStock = $variant->stock;
                            $variant->increment('stock', $item->quantity);
                            
                            // Catat log pengembalian stok
                            StockMovement::create([
                                'product_variant_id' => $variant->id,
                                'type' => 'returned',
                                'quantity' => $item->quantity,
                                'previous_stock' => $previousStock,
                                'new_stock' => $previousStock + $item->quantity,
                                'reference_id' => $order->order_number,
                                'user_id' => null, // sistem
                            ]);
                        }
                    }
                }
                
                DB::commit();
                return response()->json(['message' => 'Non-PAID event recorded. Stock returned.']);
            }

            // === PAID ===
            if ($payloadStatus === 'PAID') {
                $order->update(['status' => 'PAID']);
                DB::commit();
                return response()->json(['message' => 'Order successfully marked as PAID']);
            }

            DB::commit();
            return response()->json(['message' => 'Webhook handled']);
            
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Xendit Webhook] GAGAL memproses Order Toko.', [
                'external_id' => $externalId,
                'error'       => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Error processing store order webhook'], 500);
        }
    }
}
