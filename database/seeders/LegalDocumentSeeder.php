<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\LegalDocument;

class LegalDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        LegalDocument::updateOrCreate(
            ['name' => 'pindah-cabang-terms'],
            [
                'type' => 'terms_and_conditions',
                'version' => '1.0',
                'content' => '<p>Dengan menyetujui dokumen ini, Anda selaku orang tua/wali siswa mengonfirmasi bahwa:</p><ul><li>Anda mengajukan permohonan kepindahan cabang/kelas untuk putra/putri Anda.</li><li>Anda menyetujui penyesuaian nominal SPP (jika ada) sesuai dengan kebijakan cabang/kelas yang baru.</li><li>Pemindahan ini bersifat final setelah disetujui.</li></ul><p>Terima kasih atas kerja sama Anda.</p>',
                'published_at' => now(),
            ]
        );
    }
}
