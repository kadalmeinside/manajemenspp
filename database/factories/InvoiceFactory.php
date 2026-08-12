<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'id_siswa' => Siswa::factory(),
            'user_id' => User::factory(),
            'type' => 'spp',
            'description' => 'SPP Bulan Ini',
            'periode_tagihan' => now()->startOfMonth(),
            'amount' => 500000,
            'admin_fee' => 0,
            'total_amount' => 500000,
            'due_date' => now()->addDays(7),
            'status' => 'PENDING',
            'external_id_xendit' => 'INV-' . uniqid(),
            'xendit_invoice_id' => 'xendit_' . uniqid(),
            'xendit_payment_url' => 'https://checkout.xendit.co/web/' . uniqid(),
        ];
    }
}
