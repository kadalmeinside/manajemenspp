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
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('xendit_payment_url');
            $table->string('payment_method')->nullable()->after('payment_gateway');
            $table->json('checkout_data')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'payment_method', 'checkout_data']);
        });
    }
};

