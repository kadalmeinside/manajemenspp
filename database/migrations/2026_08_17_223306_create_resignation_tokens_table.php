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
        Schema::create('resignation_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('siswa_id'); // Using UUID since id_siswa is a uuid
            $table->string('token', 100)->unique();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('siswa_id')->references('id_siswa')->on('siswa')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resignation_tokens');
    }
};
