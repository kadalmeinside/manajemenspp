<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE siswa MODIFY COLUMN status_siswa ENUM('Aktif', 'Non-Aktif', 'Lulus', 'Cuti', 'pending_payment', 'Keluar') DEFAULT 'Aktif'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE siswa MODIFY COLUMN status_siswa ENUM('Aktif', 'Non-Aktif', 'Lulus', 'Cuti', 'pending_payment') DEFAULT 'Aktif'");
        }
    }
};
