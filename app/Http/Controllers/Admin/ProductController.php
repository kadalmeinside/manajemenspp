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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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

        DB::transaction(function () use ($validated, $variants, $request) {
            $product = Product::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(5),
                'description' => $validated['description'],
                'category' => $validated['category'],
                'is_active' => $validated['is_active'] ?? true,
                'is_preorder' => $validated['is_preorder'] ?? false,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => $index === 0, // First image is primary
                        'sort_order' => $index,
                    ]);
                }
            }

            foreach ($variants as $variantData) {
                $product->variants()->create($variantData);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $product->load(['variants', 'images']);
        // Add full url for image preview if exists (legacy compatibility)
        $product->image_url = $product->image_path ? asset('storage/' . $product->image_path) : null;
        
        $product->images->transform(function ($img) {
            $img->url = asset('storage/' . $img->image_path);
            return $img;
        });
        
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
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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

        DB::transaction(function () use ($validated, $variants, $product, $request) {
            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'is_active' => $validated['is_active'] ?? true,
                'is_preorder' => $validated['is_preorder'] ?? false,
            ]);

            if ($request->hasFile('images')) {
                $maxOrder = $product->images()->max('sort_order') ?? -1;
                $hasPrimary = $product->images()->where('is_primary', true)->exists();

                foreach ($request->file('images') as $index => $file) {
                    $path = $file->store('products', 'public');
                    $maxOrder++;
                    
                    $product->images()->create([
                        'image_path' => $path,
                        'is_primary' => (!$hasPrimary && $index === 0),
                        'sort_order' => $maxOrder,
                    ]);
                    $hasPrimary = true;
                }
            }

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

    public function deleteImage(Product $product, $imageId)
    {
        $image = $product->images()->findOrFail($imageId);
        
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($image->image_path)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($image->image_path);
        }
        
        $wasPrimary = $image->is_primary;
        $image->delete();

        if ($wasPrimary) {
            $firstImage = $product->images()->orderBy('sort_order')->first();
            if ($firstImage) {
                $firstImage->update(['is_primary' => true]);
            }
        }

        return back()->with('success', 'Gambar berhasil dihapus.');
    }
}
