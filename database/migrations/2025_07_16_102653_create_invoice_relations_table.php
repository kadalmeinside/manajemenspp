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
        Schema::create('invoice_relations', function (Blueprint $table) {
            $table->primary(['parent_invoice_id', 'child_invoice_id']);
            $table->foreignUuid('parent_invoice_id')->references('id')->on('invoices') ->onDelete('cascade');
            $table->foreignUuid('child_invoice_id')->references('id')->on('invoices')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_relations');
    }
};
