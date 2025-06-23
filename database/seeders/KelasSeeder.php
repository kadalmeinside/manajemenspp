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
                'kode_cabang' => '01',
                'biaya_spp_default' => 500000.00
            ],
            [
                'nama_kelas' => 'SS Pulomas',
                'deskripsi' => 'Soccer School Pulomas.',
                'kode_cabang' => '02',
                'biaya_spp_default' => 500000.00
            ],
            [
                'nama_kelas' => 'SS Kingkong',
                'deskripsi' => 'Soccer School Kingkong.',
                'kode_cabang' => '03',
                'biaya_spp_default' => 500000.00
            ],
            [
                'nama_kelas' => 'SS Ciledug',
                'deskripsi' => 'Soccer School Ciledug.',
                'kode_cabang' => '04',
                'biaya_spp_default' => 500000.00
            ],
            [
                'nama_kelas' => 'SS Bekasi',
                'deskripsi' => 'Soccer School Bekasi.',
                'kode_cabang' => '05',
                'biaya_spp_default' => 500000.00
            ],
        ];

        foreach ($kelasData as $data) {
            Kelas::create($data);
        }

        $this->command->info(count($kelasData) . ' kelas berhasil dibuat.');
    }
}
