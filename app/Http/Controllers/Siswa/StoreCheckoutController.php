<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockMovement;
use App\Models\Siswa;
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
        $pendingOrders = Order::with('items.variant')
            ->where('user_id', $user->id)
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
                                    'type'               => 'returned',
                                    'quantity'           => $item->quantity,
                                    'previous_stock'     => $previousStock,
                                    'new_stock'          => $previousStock + $item->quantity,
                                    'reference_id'       => $pendingOrder->order_number,
                                    'user_id'            => $pendingOrder->user_id,
                                    'notes'              => 'Stok dikembalikan karena pesanan dibatalkan (Override by user).',
                                ]);
                            }
                        }
                        $pendingOrder->update(['status' => 'CANCELLED']);
                    }
                });
            } else {
                return back()->with([
                    'pending_order_conflict' => true,
                    'error'                  => 'Anda masih memiliki pesanan toko yang menunggu pembayaran. Harap selesaikan atau batalkan pesanan tersebut sebelum membuat pesanan baru.',
                ]);
            }
        }

        $cart = Cart::with(['items.product', 'items.variant'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart || $cart->items->count() === 0) {
            return back()->with('error', 'Keranjang Anda kosong.');
        }

        try {
            DB::beginTransaction();

            // Ambil gateway & siswa sekali saja di awal
            $activeGateway = \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';
            $siswa         = Siswa::find($request->siswa_id);

            $totalAmount = 0;
            $orderNumber = 'ORD-WEB-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            $order = Order::create([
                'user_id'        => $user->id,
                'siswa_id'       => $request->siswa_id,
                'order_number'   => $orderNumber,
                'total_amount'   => 0, // Akan diupdate nanti
                'status'         => 'PENDING',
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
                    $variant->decrement('stock', $item->quantity);

                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'type'               => 'sale',
                        'quantity'           => -$item->quantity,
                        'previous_stock'     => $previousStock,
                        'new_stock'          => $previousStock - $item->quantity,
                        'reference_id'       => $orderNumber,
                        'user_id'            => $user->id,
                    ]);
                }

                $subtotal     = $variant->price * $item->quantity;
                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $product->id,
                    'product_variant_id'=> $variant->id,
                    'quantity'          => $item->quantity,
                    'unit_price'        => $variant->price,
                    'subtotal'          => $subtotal,
                ]);
            }

            $order->update(['total_amount' => $totalAmount]);

            // Ambil admin fee dari pengaturan siswa atau default global
            $feeAmount = $siswa ? (float) ($siswa->admin_fee_custom ?? 0) : 0;
            if ($feeAmount === 0.0) {
                $feeAmount = (float) (\App\Models\Setting::where('key', 'default_admin_fee')->value('value') ?? 0);
            }

            // External ID menggunakan prefix UNIF- yang konsisten dengan webhook
            $externalId = 'UNIF-' . substr($siswa->id_siswa ?? $order->id, 0, 8) . '-' . strtoupper(Str::random(8));

            $payerInfo = [
                'email' => $user->email,
                'name'  => $user->name,
                'phone' => $siswa?->nomor_telepon_wali ?? '081234567890',
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

            $paymentUrl = $invoice['invoice_url'] ?? null;

            $order->update([
                'external_id'    => $externalId,
                'payment_url'    => $paymentUrl,
                'payment_method' => strtoupper($activeGateway),
            ]);

            // Kosongkan keranjang
            $cart->items()->delete();

            DB::commit();

            // Redirect berdasarkan gateway yang aktif
            // Midtrans Custom / Gapura → halaman custom checkout
            if (in_array($activeGateway, ['midtrans_custom', 'gapura']) && !empty($invoice['checkout_data'])) {
                return \Inertia\Inertia::location(route('tagihan.spp.custom_pay', ['invoice' => $order->id]));
            }

            // Xendit / Midtrans Snap → langsung redirect ke payment URL
            return \Inertia\Inertia::location($paymentUrl);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses checkout: ' . $e->getMessage());
        }
    }
}
