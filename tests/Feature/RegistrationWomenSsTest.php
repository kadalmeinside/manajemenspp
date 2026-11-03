<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\LegalDocument;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationWomenSsTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_womenss_screen_can_be_rendered_with_correct_classes_and_page_title(): void
    {
        // Buat kelas Women Soccer School
        $womenClass1 = Kelas::factory()->create([
            'nama_kelas' => 'Women Soccer School U-15',
            'deskripsi' => 'Women Soccer School',
            'biaya_pendaftaran' => 1500000,
        ]);

        $womenClass2 = Kelas::factory()->create([
            'nama_kelas' => 'Women Soccer School U-17',
            'deskripsi' => 'Women Soccer School',
            'biaya_pendaftaran' => 1700000,
        ]);

        // Buat kelas lain (Soccer School & Academy) yang TIDAK boleh muncul
        Kelas::factory()->create([
            'nama_kelas' => 'Soccer School Ragunan',
            'deskripsi' => 'Soccer School',
            'biaya_pendaftaran' => 1000000,
        ]);

        Kelas::factory()->create([
            'nama_kelas' => 'Persija Academy',
            'deskripsi' => 'Academy',
            'biaya_pendaftaran' => 2000000,
        ]);

        $legal = LegalDocument::create([
            'name' => 'Syarat Ketentuan Women SS',
            'type' => 'registration',
            'content' => 'Ketentuan...',
            'version' => '1.0',
            'published_at' => now(),
        ]);

        Setting::updateOrCreate(
            ['key' => 'legal_doc_registration_womenss'],
            ['value' => $legal->id]
        );

        $response = $this->get(route('register-womenss.create'));

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Public/RegisterSs')
            ->where('pageTitle', 'Formulir Pendaftaran Women Soccer School')
            ->has('allKelas', 2)
            ->where('allKelas.0.nama_kelas', $womenClass1->nama_kelas)
            ->where('allKelas.1.nama_kelas', $womenClass2->nama_kelas)
        );
    }

    public function test_route_daftar_womenss_is_accessible_via_url(): void
    {
        $response = $this->get('/daftar-womenss');
        $response->assertStatus(200);
    }
}

