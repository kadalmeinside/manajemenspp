<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Siswa;
use App\Models\Kelas;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Faker\Factory as Faker;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');
        $siswaRole = Role::where('name', 'siswa')->first();
        $daftarKelas = Kelas::all();

        if (!$siswaRole) {
            $this->command->error('Role "siswa" tidak ditemukan. Jalankan RoleAndPermissionSeeder terlebih dahulu.');
            return;
        }

        if ($daftarKelas->isEmpty()) {
            $this->command->error('Tidak ada data kelas. Jalankan KelasSeeder terlebih dahulu atau buat data kelas manual.');
            return;
        }

        $jumlahSiswa = 10; 
        $this->command->info("Membuat {$jumlahSiswa} data siswa...");

        for ($i = 0; $i < $jumlahSiswa; $i++) {
            $namaSiswa = $faker->firstName . ' ' . $faker->lastName;
            $emailWali = $faker->unique()->safeEmail;

            // Buat User untuk siswa
            $userSiswa = User::create([
                'name' => $namaSiswa, 
                'username' => strtolower(str_replace(' ', '', $namaSiswa)) . $faker->randomNumber(3),
                'email' => $emailWali,
                'password' => Hash::make('password123'), 
                'email_verified_at' => now(),
            ]);
            $userSiswa->assignRole($siswaRole);

            // Buat Data Siswa
            Siswa::create([
                'nis' => now()->year . str_pad(Siswa::count() + 1, 4, '0', STR_PAD_LEFT),
                'nama_siswa' => $namaSiswa,
                'tanggal_lahir' => $faker->dateTimeBetween('-15 years', '-5 years')->format('Y-m-d'),
                'status_siswa' => 'Aktif',
                'jumlah_spp_custom' => null, 
                'admin_fee_custom' => null,
                'email_wali' => $emailWali,
                'nomor_telepon_wali' => $faker->phoneNumber,
                'tanggal_bergabung' => $faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
                'id_kelas' => $daftarKelas->random()->id_kelas,
                'id_user' => $userSiswa->id,
            ]);
        }
        $this->command->info("{$jumlahSiswa} data siswa beserta user siswa/wali berhasil dibuat.");
    }
}
