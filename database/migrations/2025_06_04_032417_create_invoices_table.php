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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            $table->foreignUuid('id_siswa')->constrained('siswa', 'id_siswa')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type')->index();
            $table->string('description');
            $table->date('periode_tagihan')->nullable();
            $table->decimal('amount', 10, 2);
            $table->decimal('admin_fee', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->date('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('status')->default('PENDING')->index();
            $table->string('xendit_va_number_tagihan', 50)->nullable();
            $table->string('xendit_invoice_id')->nullable()->unique();
            $table->text('xendit_payment_url')->nullable();
            $table->string('external_id_xendit')->nullable()->unique();
            $table->json('xendit_callback_payload')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
