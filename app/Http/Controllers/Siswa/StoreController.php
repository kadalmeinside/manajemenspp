<?php

namespace App\Http\Controllers\Siswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)->with('variants');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        return Inertia::render('Siswa/Store/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['variants' => function($q) {
                $q->orderBy('price', 'asc');
            }])
            ->firstOrFail();

        return Inertia::render('Siswa/Store/Show', [
            'product' => $product,
        ]);
    }

    public function cart()
    {
        $cart = Cart::with(['items.product', 'items.variant'])
            ->firstOrCreate(['user_id' => Auth::id()]);

        $siswas = Auth::user()->siswas;

        return Inertia::render('Siswa/Store/Cart', [
            'cart' => $cart,
            'siswas' => $siswas,
        ]);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);
        $variant = ProductVariant::findOrFail($request->product_variant_id);

        if (!$product->is_active || !$variant->is_active) {
            return back()->with('error', 'Produk atau varian tidak tersedia.');
        }

        if (!$product->is_preorder && $variant->stock < $request->quantity) {
            return back()->with('error', 'Stok tidak mencukupi.');
        }

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_variant_id', $variant->id)
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;
            if (!$product->is_preorder && $variant->stock < $newQuantity) {
                return back()->with('error', 'Total kuantitas di keranjang melebihi stok yang ada.');
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $request->quantity,
            ]);
        }

        return back()->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function updateCartItem(Request $request, CartItem $cartItem)
    {
        if ($cartItem->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $product = $cartItem->product;
        $variant = $cartItem->variant;

        if (!$product->is_preorder && $variant->stock < $request->quantity) {
            return back()->with('error', 'Stok tidak mencukupi.');
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return back()->with('success', 'Keranjang diperbarui.');
    }

    public function removeCartItem(CartItem $cartItem)
    {
        if ($cartItem->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $cartItem->delete();

        return back()->with('success', 'Produk dihapus dari keranjang.');
    }
}
