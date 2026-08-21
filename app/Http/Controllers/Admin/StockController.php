<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StockController extends Controller
{
    /**
     * Tampilkan halaman kelola stok untuk sebuah produk
     */
    public function index(Product $product)
    {
        $product->load('variants.stockMovements.user');

        // Untuk halaman history, kita ambil log dari semua varian produk ini
        $movements = StockMovement::with(['variant', 'user'])
            ->whereHas('variant', function ($q) use ($product) {
                $q->where('product_id', $product->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Products/Stock', [
            'product' => $product,
            'movements' => $movements
        ]);
    }

    /**
     * Simpan perubahan stok (Restock atau Adjustment)
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'type' => 'required|in:restock,adjustment',
            'quantity' => 'required|integer|not_in:0', // Tidak boleh 0
            'reference_id' => 'nullable|string|max:255'
        ]);

        $variant = ProductVariant::findOrFail($request->variant_id);

        // Pastikan varian milik produk ini
        if ($variant->product_id !== $product->id) {
            abort(403, 'Varian tidak cocok dengan produk.');
        }

        $previousStock = $variant->stock;
        
        // Update stok
        $variant->stock += $request->quantity;
        
        if ($variant->stock < 0) {
            return back()->with('error', 'Stok tidak boleh menjadi negatif.');
        }
        
        $variant->save();

        // Catat mutasi
        StockMovement::create([
            'product_variant_id' => $variant->id,
            'type' => $request->type,
            'quantity' => $request->quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $variant->stock,
            'reference_id' => $request->reference_id,
            'user_id' => auth()->id()
        ]);

        return back()->with('success', 'Stok berhasil diperbarui.');
    }
}
