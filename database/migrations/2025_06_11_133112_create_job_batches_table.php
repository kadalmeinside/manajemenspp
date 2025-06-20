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
        Schema::create('job_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // Nama job, misal: "Generate Tagihan Massal"
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Admin yang memulai
            $table->string('status')->default('queued'); // queued, processing, finished, failed
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->text('output')->nullable(); // Untuk menyimpan pesan hasil/error
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_batches');
    }
};
