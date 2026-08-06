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
        Schema::table('promos', function (Blueprint $table) {
            $table->dropForeign(['id_kelas']);
        });

        Schema::table('promos', function (Blueprint $table) {
            $table->uuid('id_kelas')->nullable()->change();
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
            
            $table->integer('max_uses')->nullable()->after('is_active');
            $table->integer('current_uses')->default(0)->after('max_uses');
            $table->integer('max_uses_per_user')->nullable()->after('current_uses');
            $table->decimal('max_discount', 12, 2)->nullable()->after('max_uses_per_user');
            $table->string('bukti_sk')->nullable()->after('max_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropForeign(['id_kelas']);
        });

        Schema::table('promos', function (Blueprint $table) {
            $table->uuid('id_kelas')->nullable(false)->change();
            $table->foreign('id_kelas')->references('id_kelas')->on('kelas')->cascadeOnDelete();
            
            $table->dropColumn([
                'max_uses',
                'current_uses',
                'max_uses_per_user',
                'max_discount',
                'bukti_sk'
            ]);
        });
    }
};
