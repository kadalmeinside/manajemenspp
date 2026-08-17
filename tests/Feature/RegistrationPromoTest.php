<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Kelas;
use App\Models\Promo;
use App\Models\LegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class RegistrationPromoTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_with_promo()
    {
        $kelas = Kelas::factory()->create([
            'biaya_pendaftaran' => 3000000,
        ]);

        $promo = Promo::create([
            'id_kelas' => $kelas->id_kelas,
            'kode_promo' => 'DISKON1M',
            'nama_promo' => 'Diskon 1M',
            'tipe_diskon' => 'nominal',
            'nilai_diskon' => 1000000,
            'is_active' => true,
            'tanggal_mulai' => now()->subDay(),
            'tanggal_berakhir' => now()->addDays(30),
            'max_uses' => 100,
            'current_uses' => 0,
        ]);

        $legal = LegalDocument::create([
            'name' => 'Terms',
            'type' => 'registration',
            'content' => 'Content',
            'version' => '1.0'
        ]);

        Http::fake([
            '*' => Http::response(['id' => 'inv_123', 'invoice_url' => 'https://checkout.xendit.co/web/inv_123'], 200)
        ]);

        $response = $this->post(route('pendaftaran.store'), [
            'nama_siswa' => 'Test Siswa',
            'tanggal_lahir' => '2010-01-01',
            'id_kelas' => $kelas->id_kelas,
            'user_name' => 'Wali Test',
            'email_wali' => 'test@example.com',
            'nomor_telepon_wali' => '081234567890',
            'terms' => true,
            'legal_document_id' => $legal->id,
            'kode_promo' => 'DISKON1M',
        ]);
        
        $pending = \App\Models\PendingRegistration::first();
        $this->assertEquals(2000000, $pending->amount);
        $this->assertEquals('DISKON1M', $pending->kode_promo);
    }
}
