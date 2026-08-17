<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Kelas;
use App\Models\Promo;
use App\Models\LegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

class UserBugTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_bug()
    {
        // 1. Setup
        $kelas = Kelas::factory()->create([
            'biaya_pendaftaran' => 3000000,
        ]);

        $promo = Promo::create([
            'id_kelas' => $kelas->id_kelas,
            'kode_promo' => 'TESTBUG',
            'nama_promo' => 'Test Bug',
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

        // 2. Simulate validatePromoCode
        $responseValidate = $this->post(route('promo.validate'), [
            'id_kelas' => $kelas->id_kelas,
            'kode_promo' => 'TESTBUG',
        ]);
        $responseValidate->assertStatus(200);
        
        $this->assertEquals(2000000, $responseValidate->json('new_price'));

        // 3. Simulate pendaftaran.store
        $responseStore = $this->post(route('pendaftaran.store'), [
            'nama_siswa' => 'Test Siswa',
            'tanggal_lahir' => '2010-01-01',
            'id_kelas' => $kelas->id_kelas,
            'user_name' => 'Wali Test',
            'email_wali' => 'test@example.com',
            'nomor_telepon_wali' => '081234567890',
            'terms' => true,
            'legal_document_id' => $legal->id,
            'kode_promo' => 'TESTBUG',
        ]);
        
        $responseStore->assertStatus(302);
        
        $pending = \App\Models\PendingRegistration::first();
        $this->assertEquals(2000000, $pending->amount);
    }
}
