<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kelas;

class KelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kelasData = [
            [
                'nama_kelas' => 'SS Sawangan',
                'deskripsi' => 'Soccer School Sawangan.',
                'biaya_spp_default' => 500000.00
            ],
            [
                'nama_kelas' => 'SS Pulomas',
                'deskripsi' => 'Soccer School Pulomas.',
                'biaya_spp_default' => 500000.00
            ],
            [
                'nama_kelas' => 'SS Kingkong',
                'deskripsi' => 'Soccer School Kingkong.',
                'biaya_spp_default' => 500000.00
            ],
            [
                'nama_kelas' => 'SS Ciledug',
                'deskripsi' => 'Soccer School Ciledug.',
                'biaya_spp_default' => 500000.00
            ],
            [
                'nama_kelas' => 'SS Bekasi',
                'deskripsi' => 'Soccer School Bekasi.',
                'biaya_spp_default' => 500000.00
            ],
        ];

        foreach ($kelasData as $data) {
            Kelas::create($data);
        }

        $this->command->info(count($kelasData) . ' kelas berhasil dibuat.');
    }
}
