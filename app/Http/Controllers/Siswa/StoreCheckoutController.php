<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Services\PaymentGatewayFactory;
use App\Contracts\PaymentGatewayInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreCheckoutController extends Controller
{
    protected PaymentGatewayInterface $gateway;

    public function __construct()
    {
        $this->gateway = PaymentGatewayFactory::make();
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id_siswa',
        ], [
            'siswa_id.required' => 'Silakan pilih siswa terlebih dahulu.',
        ]);

        $user = Auth::user();

        $force = $request->boolean('force');

        // Validasi Anti-Hoarding: Cek apakah user masih punya pesanan PENDING
        $pendingOrders = Order::with('items.variant')->where('user_id', $user->id)
            ->where('status', 'PENDING')
            ->get();

        if ($pendingOrders->isNotEmpty()) {
            if ($force) {
                // Batalkan semua pesanan PENDING dan kembalikan stok
                DB::transaction(function () use ($pendingOrders) {
                    foreach ($pendingOrders as $pendingOrder) {
                        foreach ($pendingOrder->items as $item) {
                            $variant = $item->variant;
                            if ($variant && !$item->product->is_preorder) {
                                $previousStock = $variant->stock;
                                $variant->increment('stock', $item->quantity);
                                
                                StockMovement::create([
                                    'product_variant_id' => $variant->id,
                                    'type' => 'returned',
                                    'quantity' => $item->quantity,
                                    'previous_stock' => $previousStock,
                                    'new_stock' => $previousStock + $item->quantity,
                                    'reference_id' => $pendingOrder->order_number,
                                    'user_id' => $pendingOrder->user_id,
                                    'notes' => 'Stok dikembalikan karena pesanan dibatalkan (Override by user).'
                                ]);
                            }
                        }
                        $pendingOrder->update(['status' => 'CANCELLED']);
                    }
                });
            } else {
                return back()->with([
                    'pending_order_conflict' => true,
                    'error' => 'Anda masih memiliki pesanan toko yang menunggu pembayaran. Harap selesaikan atau batalkan pesanan tersebut sebelum membuat pesanan baru.'
                ]);
            }
        }

        $cart = Cart::with(['items.product', 'items.variant'])->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->count() === 0) {
            return back()->with('error', 'Keranjang Anda kosong.');
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;
            $orderNumber = 'ORD-WEB-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            
            $activeGateway = \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';
            
            $order = Order::create([
                'user_id' => $user->id,
                'siswa_id' => $request->siswa_id,
                'order_number' => $orderNumber,
                'total_amount' => 0, // Akan diupdate nanti
                'status' => 'PENDING',
                'payment_method' => strtoupper($activeGateway),
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

            $invoice = $this->gateway->createInvoice(
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
                throw new \Exception("Gagal membuat tagihan pembayaran.");
            }

            $activeGateway = \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';

            $order->update([
                'external_id' => $externalId,
                'payment_url' => $invoice['invoice_url'],
                'payment_method' => strtoupper($activeGateway),
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
