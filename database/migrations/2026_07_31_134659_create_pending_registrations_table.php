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
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email_wali');
            $table->string('nama_wali');
            $table->string('nama_siswa');
            $table->date('tanggal_lahir');
            $table->string('nomor_telepon_wali');
            $table->uuid('id_kelas');
            $table->string('kode_promo')->nullable();
            $table->uuid('legal_document_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('admin_fee', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('xendit_external_id')->unique();
            $table->string('xendit_invoice_id')->nullable();
            $table->string('xendit_payment_url')->nullable();
            $table->enum('status', ['pending', 'paid', 'expired'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
