<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\XenditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreCheckoutController extends Controller
{
    protected $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id_siswa',
        ], [
            'siswa_id.required' => 'Silakan pilih siswa terlebih dahulu.',
        ]);

        $user = Auth::user();
        $cart = Cart::with(['items.product', 'items.variant'])->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->count() === 0) {
            return back()->with('error', 'Keranjang Anda kosong.');
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $orderNumber = 'ORD-WEB-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            
            $order = Order::create([
                'user_id' => $user->id,
                'siswa_id' => $request->siswa_id,
                'order_number' => $orderNumber,
                'total_amount' => 0, // Akan diupdate nanti
                'status' => 'PENDING',
                'payment_method' => 'XENDIT',
            ]);

            foreach ($cart->items as $item) {
                $product = $item->product;
                $variant = $item->variant;

                // Cek stok (jika bukan preorder)
                if (!$product->is_preorder) {
                    if ($variant->stock < $item->quantity) {
                        throw new \Exception("Stok tidak mencukupi untuk " . $product->name . " varian " . $variant->name);
                    }
                    
                    $previousStock = $variant->stock;
                    // Kurangi stok
                    $variant->decrement('stock', $item->quantity);
                    
                    \App\Models\StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'type' => 'sale',
                        'quantity' => -$item->quantity,
                        'previous_stock' => $previousStock,
                        'new_stock' => $previousStock - $item->quantity,
                        'reference_id' => $orderNumber,
                        'user_id' => $user->id,
                    ]);
                }

                $subtotal = $variant->price * $item->quantity;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $item->quantity,
                    'unit_price' => $variant->price,
                    'subtotal' => $subtotal,
                ]);
            }

            // Tambahkan biaya admin Xendit (misal Rp 4.500 jika dibebankan ke user, atau 0 jika sekolah menanggung)
            // Untuk amannya, kita anggap biaya 4500 (atau sesuai logic SPP).
            $feeAmount = 4500;
            $grandTotal = $totalAmount + $feeAmount;

            $order->update(['total_amount' => $totalAmount]); // Harga barang saja

            $externalId = 'STORE_INV_' . $order->id;
            
            $payerInfo = [
                'email' => $user->email,
                'name' => $user->name,
                'phone' => '081234567890' // Optional
            ];

            $invoice = $this->xenditService->createInvoice(
                $totalAmount,
                $feeAmount,
                "Pembayaran Toko/Merchandise (Order: $orderNumber)",
                $payerInfo,
                $externalId,
                route('siswa.store.orders.index'),
                route('siswa.store.orders.index'),
                now()->addDays(1)
            );

            if (!$invoice) {
                throw new \Exception("Gagal membuat tagihan Xendit.");
            }

            $order->update([
                'external_id' => $externalId,
                'payment_url' => $invoice['invoice_url'],
            ]);

            // Kosongkan keranjang
            $cart->items()->delete();

            DB::commit();

            return \Inertia\Inertia::location($invoice['invoice_url']);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses checkout: ' . $e->getMessage());
        }
    }
}
