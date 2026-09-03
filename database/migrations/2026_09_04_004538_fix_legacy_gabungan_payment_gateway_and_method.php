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
        // DB raw update to fix child invoices inherited from parent
        \Illuminate\Support\Facades\DB::statement("
            UPDATE invoices child
            JOIN invoices parent ON child.parent_payment_id = parent.id
            SET 
                child.payment_gateway = parent.payment_gateway,
                child.payment_method = parent.payment_method
            WHERE child.type = 'spp' 
              AND child.status = 'PAID' 
              AND child.parent_payment_id IS NOT NULL
              AND parent.payment_gateway IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed
    }
};
