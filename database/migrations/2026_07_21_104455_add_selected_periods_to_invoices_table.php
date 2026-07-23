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
        Schema::table('invoices', function (Blueprint $table) {
            // Menyimpan daftar periode yang dipilih siswa saat pembayaran gabungan.
            // Format: ["2025-01-01", "2025-02-01", "2025-03-01"]
            // Kolom ini krusial agar webhook bisa tahu bulan mana yang harus di-update
            // tanpa mengandalkan pembagian matematika yang tidak akurat.
            $table->json('selected_periods')->nullable()->after('periode_tagihan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('selected_periods');
        });
    }
};
