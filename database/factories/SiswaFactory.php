<?php

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Siswa>
 */
class SiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_siswa' => $this->faker->name(),
            'nis' => $this->faker->unique()->numerify('##########'),
            'tanggal_lahir' => $this->faker->date(),
            'status_siswa' => 'Aktif',
            'id_kelas' => Kelas::factory(),
            'id_user' => User::factory(),
            'nomor_telepon_wali' => $this->faker->phoneNumber(),
            'tanggal_bergabung' => now(),
        ];
    }
}
