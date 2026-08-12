<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PendingRegistration>
 */
class PendingRegistrationFactory extends Factory
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
            'nama_siswa' => fake()->name(),
            'tanggal_lahir' => fake()->date(),
            'id_kelas' => null, // will be overridden in test
            'nama_wali' => fake()->name(),
            'nomor_telepon_wali' => fake()->phoneNumber(),
            'email_wali' => fake()->unique()->safeEmail(),
            'legal_document_id' => null,
            'ip_address' => '127.0.0.1',
            'status' => 'pending',
            'amount' => 1500000,
            'admin_fee' => 5000,
            'total_amount' => 1505000,
            'kode_promo' => null,
            'xendit_external_id' => 'PREG-' . uniqid(),
            'xendit_invoice_id' => 'xnd_' . uniqid(),
            'xendit_payment_url' => 'https://xendit.com/url',
            'expires_at' => now()->addDays(1),
        ];
    }
}
