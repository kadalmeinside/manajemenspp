<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Jersey Home Akademi 2026',
                'description' => 'Jersey utama edisi musim 2026 dengan bahan menyerap keringat.',
                'category' => 'jersey',
                'is_preorder' => false,
                'variants' => [
                    ['name' => 'Ukuran S', 'sku' => 'JRSY-H-26-S', 'price' => 150000, 'stock' => 10],
                    ['name' => 'Ukuran M', 'sku' => 'JRSY-H-26-M', 'price' => 150000, 'stock' => 15],
                    ['name' => 'Ukuran L', 'sku' => 'JRSY-H-26-L', 'price' => 150000, 'stock' => 20],
                    ['name' => 'Ukuran XL', 'sku' => 'JRSY-H-26-XL', 'price' => 160000, 'stock' => 10],
                    ['name' => 'Ukuran XXL', 'sku' => 'JRSY-H-26-XXL', 'price' => 170000, 'stock' => 5],
                ]
            ],
            [
                'name' => 'Jersey Away Akademi 2026',
                'description' => 'Jersey tandang edisi musim 2026. Pre-Order karena stok terbatas.',
                'category' => 'jersey',
                'is_preorder' => true,
                'variants' => [
                    ['name' => 'Ukuran S', 'sku' => 'JRSY-A-26-S', 'price' => 155000, 'stock' => 0],
                    ['name' => 'Ukuran M', 'sku' => 'JRSY-A-26-M', 'price' => 155000, 'stock' => 0],
                    ['name' => 'Ukuran L', 'sku' => 'JRSY-A-26-L', 'price' => 155000, 'stock' => 0],
                    ['name' => 'Ukuran XL', 'sku' => 'JRSY-A-26-XL', 'price' => 165000, 'stock' => 0],
                ]
            ],
            [
                'name' => 'Jaket Pelatih / Akademi',
                'description' => 'Jaket parasut untuk menemani sesi latihan saat musim hujan.',
                'category' => 'merchandise',
                'is_preorder' => false,
                'variants' => [
                    ['name' => 'Ukuran M', 'sku' => 'JKT-M', 'price' => 250000, 'stock' => 5],
                    ['name' => 'Ukuran L', 'sku' => 'JKT-L', 'price' => 250000, 'stock' => 8],
                    ['name' => 'Ukuran XL', 'sku' => 'JKT-XL', 'price' => 260000, 'stock' => 5],
                ]
            ],
            [
                'name' => 'Kaos Kaki Latihan (Anti-Slip)',
                'description' => 'Kaos kaki kualitas tinggi agar sepatu tidak licin saat bermain.',
                'category' => 'merchandise',
                'is_preorder' => false,
                'variants' => [
                    ['name' => 'Warna Hitam', 'sku' => 'KK-BLK', 'price' => 350000, 'stock' => 30],
                    ['name' => 'Warna Putih', 'sku' => 'KK-WHT', 'price' => 35000, 'stock' => 25],
                ]
            ],
            [
                'name' => 'Sepatu Bola Akademi Pro',
                'description' => 'Sepatu bola dengan stud yang nyaman untuk lapangan rumput asli dan sintetis.',
                'category' => 'equipment',
                'is_preorder' => false,
                'variants' => [
                    ['name' => 'Size 38', 'sku' => 'SPT-38', 'price' => 450000, 'stock' => 3],
                    ['name' => 'Size 39', 'sku' => 'SPT-39', 'price' => 450000, 'stock' => 4],
                    ['name' => 'Size 40', 'sku' => 'SPT-40', 'price' => 450000, 'stock' => 6],
                    ['name' => 'Size 41', 'sku' => 'SPT-41', 'price' => 450000, 'stock' => 8],
                    ['name' => 'Size 42', 'sku' => 'SPT-42', 'price' => 450000, 'stock' => 5],
                    ['name' => 'Size 43', 'sku' => 'SPT-43', 'price' => 450000, 'stock' => 2],
                ]
            ],
            [
                'name' => 'Bola Sepak Specs Size 5',
                'description' => 'Bola standar FIFA.',
                'category' => 'equipment',
                'is_preorder' => false,
                'variants' => [
                    ['name' => 'Standard', 'sku' => 'BL-SPCS-5', 'price' => 210000, 'stock' => 15],
                ]
            ],
        ];

        foreach ($products as $pData) {
            $variants = $pData['variants'];
            unset($pData['variants']);

            $pData['slug'] = Str::slug($pData['name']) . '-' . Str::random(5);

            $product = Product::create($pData);

            foreach ($variants as $variant) {
                $product->variants()->create($variant);
            }
        }
    }
}
