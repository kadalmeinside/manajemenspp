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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_gateway')->default('xendit')->after('id');
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            $table->string('payment_gateway')->default('xendit')->after('id');
        });

        // Update existing records to explicitly have 'xendit' (just in case defaults don't apply to existing rows in some DB engines)
        DB::table('invoices')->update(['payment_gateway' => 'xendit']);
        DB::table('withdrawals')->update(['payment_gateway' => 'xendit']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('withdrawals', function (Blueprint $table) {
            $table->dropColumn('payment_gateway');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_gateway');
        });
    }
};
