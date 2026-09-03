<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PosController extends Controller
{
    /**
     * Tampilkan halaman Kasir (POS)
     */
    public function index()
    {
        // Ambil produk yang aktif beserta variannya
        // Jika produk adalah preorder, tampilkan semua varian (abaikan stok). Jika bukan preorder, hanya tampilkan yang stok > 0.
        $products = Product::with('variants')->where('is_active', true)->get()
            ->map(function ($product) {
                if (!$product->is_preorder) {
                    $product->setRelation('variants', $product->variants->where('stock', '>', 0)->values());
                }
                return $product;
            })
            ->filter(function ($product) {
                return $product->variants->count() > 0;
            })->values();

        // Ambil semua siswa beserta kelasnya (hanya siswa yang aktif)
        $siswas = \App\Models\Siswa::with('kelas:id_kelas,nama_kelas')
            ->select('id_siswa', 'nama_siswa', 'nis', 'id_kelas', 'id_user')
            ->where('status_siswa', 'Aktif')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id_siswa,
                    'user_id' => $s->id_user,
                    'name' => $s->nama_siswa,
                    'kelas' => $s->kelas ? $s->kelas->nama_kelas : 'Tanpa Kelas',
                    'nis' => $s->nis,
                ];
            });

        return Inertia::render('Admin/POS/Index', [
            'products' => $products,
            'siswas' => $siswas,
        ]);
    }

    /**
     * Proses Checkout Kasir
     */
    public function store(Request $request)
    {
        $request->validate([
            'siswa_id' => 'required|exists:siswa,id_siswa',
            'payment_method' => 'required|in:CASH,ONLINE',
            'uang_diterima' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Dapatkan user_id dari siswa yang dipilih
            $siswa = \App\Models\Siswa::findOrFail($request->siswa_id);
            if (!$siswa->id_user) {
                return back()->with('error', 'Siswa ini belum dihubungkan dengan akun Orang Tua. Transaksi tidak dapat dilanjutkan.');
            }
            $userId = $siswa->id_user;

            $totalAmount = 0;
            $orderItems = [];

            // 1. Verifikasi stok dan hitung total
            foreach ($request->items as $item) {
                $variant = ProductVariant::with('product')->lockForUpdate()->findOrFail($item['variant_id']);
                $product = $variant->product;

                if (!$product->is_preorder && $variant->stock < $item['quantity']) {
                    DB::rollBack();
                    return back()->with('error', "Stok tidak mencukupi untuk varian {$variant->name}. Tersedia: {$variant->stock}");
                }

                $subtotal = $variant->price * $item['quantity'];
                $totalAmount += $subtotal;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'price' => $variant->price,
                    'subtotal' => $subtotal,
                ];
            }

            // Tambah biaya layanan jika pembayaran via Xendit
            if ($request->payment_method === 'XENDIT') {
                $totalAmount += 4500; // Biaya admin xendit, sesuaikan dengan logic yang ada
            }

            $notes = 'Dibuat oleh Admin via POS';
            $kembalian = 0;
            
            if ($request->payment_method === 'CASH') {
                $uangDiterima = $request->uang_diterima ?? $totalAmount;
                if ($uangDiterima < $totalAmount) {
                    DB::rollBack();
                    return back()->with('error', 'Jumlah uang diterima kurang dari total belanja.');
                }
                $kembalian = $uangDiterima - $totalAmount;
                $notes .= "\nUang Diterima: Rp " . number_format($uangDiterima, 0, ',', '.') . "\nKembalian: Rp " . number_format($kembalian, 0, ',', '.');
            }

            $orderNumber = 'ORD-POS-' . date('Ymd') . '-' . strtoupper(Str::random(5));

            // 2. Buat Order
            $order = Order::create([
                'user_id' => $userId,
                'siswa_id' => $siswa->id_siswa,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'status' => $request->payment_method === 'CASH' ? 'PAID' : 'PENDING',
                'payment_method' => $request->payment_method,
                'notes' => $notes,
            ]);

            // 3. Simpan item pesanan dan potong stok
            foreach ($orderItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['subtotal'],
                ]);

                // Potong stok dan catat log mutasi
                $variant = ProductVariant::find($item['variant_id']);
                $product = Product::find($item['product_id']);

                if (!$product->is_preorder) {
                    $previousStock = $variant->stock;
                    $variant->decrement('stock', $item['quantity']);

                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'type' => 'sale',
                        'quantity' => -$item['quantity'],
                        'previous_stock' => $previousStock,
                        'new_stock' => $previousStock - $item['quantity'],
                        'reference_id' => $order->order_number,
                        'user_id' => auth()->id(), // Dicatat oleh Admin yang login
                    ]);
                }
            }

            // 4. Jika metode pembayaran ONLINE, buat invoice
            if ($request->payment_method === 'ONLINE') {
                $user = User::find($userId);
                $payerInfo = [
                    'email' => $user->email,
                    'name' => $user->name,
                    'phone' => '081234567890'
                ];
                $feeAmount = 4500; // Standar biaya admin

                $externalId = 'STORE_INV_' . $order->id;
                
                $gateway = \App\Services\PaymentGatewayFactory::make();
                $activeGateway = \App\Models\Setting::where('key', 'active_payment_gateway')->value('value') ?? 'xendit';

                $invoice = $gateway->createInvoice(
                    (float) $totalAmount,
                    (float) $feeAmount,
                    'Pembelian di Toko Sekolah: ' . $order->order_number,
                    $payerInfo,
                    $externalId,
                    route('admin.pos.index'),
                    route('admin.pos.index'),
                    now()->addDays(1)
                );

                if (!$invoice) {
                    throw new \Exception("Gagal membuat tagihan pembayaran.");
                }

                $order->update([
                    'external_id' => $externalId, // Using the custom ID instead of Xendit generated ID for consistency
                    'payment_url' => $invoice['invoice_url'],
                    'payment_method' => strtoupper($activeGateway),
                ]);

                DB::commit();
                return back()
                    ->with('success', 'Tagihan pembayaran berhasil dibuat.')
                    ->with('payment_url', $invoice['invoice_url']);
            }

            // Jika CASH, transaksi selesai.
            // Karena dibeli langsung, bisa jadi barangnya juga langsung diambil.
            // Biarkan admin yang memprosesnya ke COMPLETED di halaman Manajemen Pesanan, atau kita bisa set COMPLETED di sini.
            // Untuk sementara kita set PAID.
            
            DB::commit();
            return back()->with('success', 'Transaksi Kasir (Tunai) Berhasil Disimpan. Kembalian: Rp ' . number_format($kembalian, 0, ',', '.'));

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[POS Checkout Error] ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses transaksi: ' . $e->getMessage());
        }
    }
}
