<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Log;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xendit.callback_verification_token' => 'test-token']);
    }

    public function test_rejects_invalid_callback_token()
    {
        $response = $this->postJson('/webhooks/xendit/invoice', [], [
            'x-callback-token' => 'invalid-token'
        ]);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Invalid callback token']);
    }

    public function test_ignores_if_external_id_missing()
    {
        $response = $this->postJson('/webhooks/xendit/invoice', [
            'status' => 'PAID'
        ], [
            'x-callback-token' => 'test-token'
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Missing external_id']);
    }

    public function test_returns_200_if_invoice_not_found()
    {
        // Xendit expects 200 so it doesn't retry on our end for something that genuinely doesn't exist.
        $response = $this->postJson('/webhooks/xendit/invoice', [
            'external_id' => 'INV-DOESNOTEXIST',
            'status' => 'PAID'
        ], [
            'x-callback-token' => 'test-token'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Invoice not found, skipping']);
    }

    public function test_idempotent_if_already_paid()
    {
        $kelas = Kelas::factory()->create();
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['id_kelas' => $kelas->id_kelas, 'id_user' => $user->id]);
        
        $invoice = Invoice::factory()->create([
            'id_siswa' => $siswa->id_siswa,
            'external_id_xendit' => 'INV-123',
            'status' => 'PAID'
        ]);

        $response = $this->postJson('/webhooks/xendit/invoice', [
            'external_id' => 'INV-123',
            'status' => 'PAID'
        ], [
            'x-callback-token' => 'test-token'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Already processed, skipping']);
    }

    public function test_records_non_paid_events()
    {
        $kelas = Kelas::factory()->create();
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['id_kelas' => $kelas->id_kelas, 'id_user' => $user->id]);
        
        $invoice = Invoice::factory()->create([
            'id_siswa' => $siswa->id_siswa,
            'external_id_xendit' => 'INV-123',
            'status' => 'PENDING'
        ]);

        $response = $this->postJson('/webhooks/xendit/invoice', [
            'external_id' => 'INV-123',
            'status' => 'EXPIRED',
            'foo' => 'bar'
        ], [
            'x-callback-token' => 'test-token'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Non-PAID event recorded']);

        $invoice->refresh();
        $this->assertEquals('EXPIRED', $invoice->status);
        $this->assertEquals('bar', $invoice->xendit_callback_payload['foo']);
    }
}
