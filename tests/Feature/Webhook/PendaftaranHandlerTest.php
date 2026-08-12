<?php

namespace Tests\Feature\Webhook;

use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\PendingRegistration;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationSuccess;

class PendaftaranHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xendit.callback_verification_token' => 'test-token']);
        
        // Ensure roles exist for testing
        Role::firstOrCreate(['name' => 'siswa']);
        Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_processes_pending_registration_successfully()
    {
        Mail::fake();

        $kelas = Kelas::factory()->create([
            'biaya_spp_default' => 500000,
        ]);

        $legalDoc = \App\Models\LegalDocument::create([
            'name' => 'Terms of Service',
            'type' => 'terms',
            'version' => '1.0',
            'content' => 'Test content',
            'published_at' => now(),
        ]);

        $pending = PendingRegistration::factory()->create([
            'xendit_external_id' => 'PREG-12345',
            'status' => 'pending',
            'id_kelas' => $kelas->id_kelas,
            'email_wali' => 'test_wali@example.com',
            'nama_siswa' => 'Budi Santoso',
            'legal_document_id' => $legalDoc->id,
        ]);

        $response = $this->postJson('/webhooks/xendit/invoice', [
            'external_id' => 'PREG-12345',
            'status' => 'PAID',
            'paid_at' => now()->toIso8601String(),
            'payment_channel' => 'BCA',
        ], [
            'x-callback-token' => 'test-token'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Pending registration processed successfully']);

        // Assert Pending Registration status updated
        $this->assertDatabaseHas('pending_registrations', [
            'xendit_external_id' => 'PREG-12345',
            'status' => 'paid'
        ]);

        // Assert User Created
        $user = User::where('email', 'test_wali@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('siswa'));

        // Assert Siswa Created
        $siswa = Siswa::where('id_user', $user->id)->first();
        $this->assertNotNull($siswa);
        $this->assertEquals('Budi Santoso', $siswa->nama_siswa);
        $this->assertEquals('Aktif', $siswa->status_siswa);

        // Assert Invoice Created
        $this->assertDatabaseHas('invoices', [
            'id_siswa' => $siswa->id_siswa,
            'user_id' => $user->id,
            'type' => 'pendaftaran',
            'status' => 'PAID',
            'external_id_xendit' => 'PREG-12345',
            'payment_method' => 'BCA',
        ]);

        // Assert Mail Queued
        Mail::assertQueued(RegistrationSuccess::class, function ($mail) {
            return $mail->hasTo('test_wali@example.com');
        });
    }

    public function test_links_to_existing_user_if_email_exists()
    {
        Mail::fake();
        $kelas = Kelas::factory()->create();
        
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com'
        ]);
        $existingUser->assignRole('siswa');

        $legalDoc = \App\Models\LegalDocument::create([
            'name' => 'Terms of Service',
            'type' => 'terms',
            'version' => '1.0',
            'content' => 'Test content',
            'published_at' => now(),
        ]);

        $pending = PendingRegistration::factory()->create([
            'xendit_external_id' => 'PREG-999',
            'status' => 'pending',
            'id_kelas' => $kelas->id_kelas,
            'email_wali' => 'existing@example.com',
            'nama_siswa' => 'Adik Budi',
            'legal_document_id' => $legalDoc->id,
        ]);

        $response = $this->postJson('/webhooks/xendit/invoice', [
            'external_id' => 'PREG-999',
            'status' => 'PAID',
        ], [
            'x-callback-token' => 'test-token'
        ]);

        $response->assertStatus(200);

        // Should not create a new user, but attach new siswa to existing user
        $usersCount = User::where('email', 'existing@example.com')->count();
        $this->assertEquals(1, $usersCount);

        $this->assertDatabaseHas('siswa', [
            'id_user' => $existingUser->id,
            'nama_siswa' => 'Adik Budi'
        ]);
    }
}
