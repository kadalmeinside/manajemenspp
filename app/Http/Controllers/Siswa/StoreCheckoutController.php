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
            'siswa_id'     => 'required|exists:siswa,id_siswa',
            'payment_type' => 'nullable|string|in:VA,QRIS',
            'bank_code'    => 'nullable|string',
        ], [
            'siswa_id.required' => 'Silakan pilih siswa terlebih dahulu.',
        ]);

        $user = Auth::user();
        $force = $request->boolean('force');
        $paymentType = $request->input('payment_type') ?: 'VA';
        $bankCode = $request->input('bank_code') ?: 'BCA';

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

            $subtotalAmount = 0;
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

                $subtotal        = $variant->price * $item->quantity;
                $subtotalAmount += $subtotal;

                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $product->id,
                    'product_variant_id'=> $variant->id,
                    'quantity'          => $item->quantity,
                    'unit_price'        => $variant->price,
                    'subtotal'          => $subtotal,
                ]);
            }

            // Hitung biaya layanan toko berdasarkan konfigurasi MDR & metode pembayaran
            $paymentService = app(\App\Services\PaymentService::class);
            $feeAmount = $paymentService->calculateAdminFee(null, $subtotalAmount, $paymentType, $activeGateway, 'store');

            $grandTotal = $subtotalAmount + $feeAmount;
            $order->update(['total_amount' => $grandTotal]);

            // External ID menggunakan prefix STORE_INV_ yang konsisten dengan webhook
            $externalId = 'STORE_INV_' . strtoupper(Str::random(12));

            $payerInfo = [
                'email' => $user->email,
                'name'  => $user->name,
                'phone' => $siswa?->nomor_telepon_wali ?? '081234567890',
            ];

            $description = "Pembayaran Toko/Merchandise (Order: $orderNumber)";

            if (in_array($activeGateway, ['midtrans_custom', 'gapura']) && $paymentType) {
                $pgData = $this->gateway->createCustomPayment(
                    $subtotalAmount,
                    $feeAmount,
                    $description,
                    $payerInfo,
                    $externalId,
                    now()->addHours(24),
                    $paymentType,
                    $bankCode ?? ''
                );

                if (!$pgData) {
                    throw new \Exception("Gagal membuat tagihan custom {$activeGateway}.");
                }

                $safeCheckoutData = collect($pgData)->only([
                    'id',
                    'payment_type',
                    'bank_code',
                    'va_number',
                    'virtual_account_number',
                    'biller_code',
                    'bill_key',
                    'qris_string',
                    'qr_url',
                    'snap_redirect_url',
                    'amount',
                    'expiry_date',
                ])->toArray();

                $snapRedirectUrl = (!empty($pgData['snap_redirect_url']) && empty($pgData['va_number']) && empty($pgData['qris_string']) && empty($pgData['qr_url']))
                    ? $pgData['snap_redirect_url']
                    : null;

                $paymentUrl = $snapRedirectUrl ?: route('siswa.store.orders.custom_pay', ['order' => $order->id]);
                $methodLabel = $paymentType . ($paymentType === 'VA' && $bankCode ? ' ' . strtoupper($bankCode) : '');

                $order->update([
                    'external_id'    => $externalId,
                    'payment_url'    => $paymentUrl,
                    'payment_method' => $methodLabel,
                    'notes'          => json_encode([
                        'gateway'       => $activeGateway,
                        'fee_amount'    => $feeAmount,
                        'subtotal'      => $subtotalAmount,
                        'checkout_data' => $safeCheckoutData,
                    ]),
                ]);

                $cart->items()->delete();
                DB::commit();

                return \Inertia\Inertia::location($paymentUrl);
            }

            $invoice = $this->gateway->createInvoice(
                $subtotalAmount,
                $feeAmount,
                $description,
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
                'notes'          => json_encode([
                    'gateway'    => $activeGateway,
                    'fee_amount' => $feeAmount,
                    'subtotal'   => $subtotalAmount,
                ]),
            ]);

            // Kosongkan keranjang
            $cart->items()->delete();

            DB::commit();

            // Xendit / Midtrans Snap → langsung redirect ke payment URL
            return \Inertia\Inertia::location($paymentUrl);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses checkout: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan halaman instruksi pembayaran kustom (VA / QRIS) untuk Order Toko.
     */
    public function customPay(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $meta = json_decode($order->notes ?? '{}', true) ?: [];
        $checkoutData = $meta['checkout_data'] ?? null;

        if (empty($checkoutData)) {
            return redirect()->route('siswa.store.orders.show', $order->id);
        }

        $checkoutData = \App\Services\MidtransService::resolveCheckoutQris($checkoutData);

        if (!empty($checkoutData['snap_redirect_url']) && empty($checkoutData['va_number']) && empty($checkoutData['qris_string']) && empty($checkoutData['qr_url'])) {
            return \Inertia\Inertia::location($checkoutData['snap_redirect_url']);
        }

        $order->load('siswa.kelas');

        $dueDate = !empty($checkoutData['expiry_date'])
            ? \Carbon\Carbon::parse($checkoutData['expiry_date'])->toIso8601String()
            : $order->created_at->addHours(24)->toIso8601String();

        $invoiceData = [
            'id'                 => $order->id,
            'id_siswa'           => $order->siswa_id,
            'amount'             => (float) ($meta['subtotal'] ?? $order->total_amount),
            'admin_fee'          => (float) ($meta['fee_amount'] ?? 0),
            'total_amount'       => (float) $order->total_amount,
            'status'             => in_array($order->status, ['PAID', 'COMPLETED']) ? 'PAID' : (in_array($order->status, ['EXPIRED', 'CANCELLED', 'FAILED']) ? 'EXPIRED' : 'PENDING'),
            'due_date'           => $dueDate,
            'description'        => "Pembayaran Toko/Merchandise ({$order->order_number})",
            'external_id_xendit' => $order->external_id,
            'payment_method'     => $order->payment_method,
            'is_store_order'     => true,
            'siswa'              => $order->siswa ? [
                'id_siswa'   => $order->siswa->id_siswa,
                'nama_siswa' => $order->siswa->nama_siswa,
                'nis'        => $order->siswa->nis,
            ] : null,
        ];

        return \Inertia\Inertia::render('Public/CustomPay', [
            'invoice'      => $invoiceData,
            'checkoutData' => $checkoutData,
            'pageTitle'    => 'Pembayaran Merchandise',
        ]);
    }
}
