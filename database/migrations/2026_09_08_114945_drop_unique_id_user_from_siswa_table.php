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
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropForeign(['id_user']);
            $table->dropUnique('siswa_id_user_unique');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropForeign(['id_user']);
            $table->unique('id_user', 'siswa_id_user_unique');
            $table->foreign('id_user')->references('id')->on('users')->onDelete('set null');
        });
    }
};
