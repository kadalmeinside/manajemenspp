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
        Schema::create('siswa', function (Blueprint $table) {
            $table->uuid('id_siswa')->primary();
            $table->string('nama_siswa');
            $table->enum('status_siswa', ['Aktif', 'Non-Aktif', 'Lulus', 'Cuti', 'pending_payment'])->default('Aktif');
            $table->decimal('jumlah_spp_custom', 10, 2)->nullable();
            $table->decimal('admin_fee_custom', 10, 2)->nullable();
            $table->string('email_wali')->unique()->nullable();
            $table->string('nomor_telepon_wali', 20)->nullable();
            $table->string('xendit_fixed_va_id')->unique()->nullable();
            $table->string('nomor_va_fixed', 50)->unique()->nullable();
            $table->date('tanggal_bergabung');

            // Foreign Keys
            $table->foreignUuid('id_kelas')->nullable()->constrained('kelas', 'id_kelas')->onDelete('set null');
            $table->unsignedBigInteger('id_user')->nullable()->unique();
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};
