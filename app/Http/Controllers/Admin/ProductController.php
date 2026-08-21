<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('variants');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/CreateEdit');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_preorder' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'variants' => 'required|string', // Frontend sends as JSON string due to FormData
        ]);

        $variants = json_decode($validated['variants'], true);

        // Validate decoded variants
        $request->merge(['decoded_variants' => $variants]);
        $request->validate([
            'decoded_variants' => 'required|array|min:1',
            'decoded_variants.*.name' => 'required|string|max:255',
            'decoded_variants.*.sku' => 'nullable|string|max:255',
            'decoded_variants.*.price' => 'required|numeric|min:0',
            'decoded_variants.*.stock' => 'required|integer|min:0',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($validated, $variants, $imagePath) {
            $product = Product::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                'description' => $validated['description'],
                'category' => $validated['category'],
                'is_active' => $validated['is_active'] ?? true,
                'is_preorder' => $validated['is_preorder'] ?? false,
                'image_path' => $imagePath,
            ]);

            foreach ($variants as $variantData) {
                $product->variants()->create($variantData);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');
        // Add full url for image preview if exists
        $product->image_url = $product->image_path ? asset('storage/' . $product->image_path) : null;
        
        return Inertia::render('Admin/Products/CreateEdit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_preorder' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'variants' => 'required|string', // Frontend sends as JSON string
        ]);

        $variants = json_decode($validated['variants'], true);

        // Validate decoded variants
        $request->merge(['decoded_variants' => $variants]);
        $request->validate([
            'decoded_variants' => 'required|array|min:1',
            'decoded_variants.*.id' => 'nullable|uuid',
            'decoded_variants.*.name' => 'required|string|max:255',
            'decoded_variants.*.sku' => 'nullable|string|max:255',
            'decoded_variants.*.price' => 'required|numeric|min:0',
            'decoded_variants.*.stock' => 'required|integer|min:0',
        ]);

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($imagePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($validated, $variants, $product, $imagePath) {
            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'is_active' => $validated['is_active'] ?? true,
                'is_preorder' => $validated['is_preorder'] ?? false,
                'image_path' => $imagePath,
            ]);

            $existingVariantIds = $product->variants()->pluck('id')->toArray();
            $incomingVariantIds = array_filter(array_column($variants, 'id'));

            // Delete variants that are not in the incoming list
            $variantsToDelete = array_diff($existingVariantIds, $incomingVariantIds);
            ProductVariant::whereIn('id', $variantsToDelete)->delete();

            // Update or Create variants
            foreach ($variants as $variantData) {
                if (isset($variantData['id']) && in_array($variantData['id'], $existingVariantIds)) {
                    $product->variants()->where('id', $variantData['id'])->update([
                        'name' => $variantData['name'],
                        'sku' => $variantData['sku'],
                        'price' => $variantData['price'],
                        'stock' => $variantData['stock'],
                    ]);
                } else {
                    $product->variants()->create([
                        'name' => $variantData['name'],
                        'sku' => $variantData['sku'],
                        'price' => $variantData['price'],
                        'stock' => $variantData['stock'],
                    ]);
                }
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
