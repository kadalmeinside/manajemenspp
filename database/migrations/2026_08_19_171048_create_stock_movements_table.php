<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            
            // Types: 'restock' (barang masuk), 'sale' (penjualan), 'adjustment' (penyesuaian stok manual), 'returned' (pesanan dibatalkan/expired)
            $table->string('type', 20); 
            
            $table->integer('quantity'); // Bisa positif / negatif
            $table->integer('previous_stock');
            $table->integer('new_stock');
            
            $table->string('reference_id')->nullable(); // Misal: nomor pesanan (ORDER-123) atau catatan manual
            
            // Admin yang melakukan restock/adjustment, jika sale (dari checkout siswa) bisa null atau diisi ID siswa. Kita buat nullable.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
