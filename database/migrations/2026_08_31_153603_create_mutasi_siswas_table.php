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
        Schema::create('mutasi_siswas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('siswa_id')->constrained('siswa', 'id_siswa')->cascadeOnDelete();
            $table->foreignUuid('from_kelas_id')->nullable()->constrained('kelas', 'id_kelas')->nullOnDelete();
            $table->foreignUuid('to_kelas_id')->constrained('kelas', 'id_kelas')->cascadeOnDelete();
            
            $table->decimal('spp_baru', 10, 2)->nullable();
            $table->string('start_month', 7)->comment('Format: YYYY-MM');
            $table->enum('status', ['PENDING', 'APPROVED', 'CANCELLED', 'EXPIRED'])->default('PENDING');
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            
            $table->string('agreed_by')->nullable();
            $table->timestamp('agreed_at')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_siswas');
    }
};
