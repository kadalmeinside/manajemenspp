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
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('id_kelas')->constrained('kelas', 'id_kelas')->cascadeOnDelete();
            $table->string('nama_promo');
            $table->string('kode_promo')->nullable()->unique(); // <-- Kolom baru untuk kode promo
            $table->enum('tipe_diskon', ['persen', 'tetap'])->default('tetap');
            $table->decimal('nilai_diskon', 10, 2);
            $table->date('tanggal_mulai');
            $table->date('tanggal_berakhir')->nullable(); // <-- Dibuat nullable untuk promo selamanya
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};
