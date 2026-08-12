<?php

namespace Tests\Feature\Webhook;

use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SppGabunganHandlerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['xendit.callback_verification_token' => 'test-token']);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    }

    public function test_processes_spp_gabungan_modern_selected_periods()
    {
        $kelas = Kelas::factory()->create();
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['id_kelas' => $kelas->id_kelas, 'id_user' => $user->id]);

        $child1 = Invoice::factory()->create(['id_siswa' => $siswa->id_siswa, 'status' => 'PENDING', 'periode_tagihan' => '2023-01-01']);
        $child2 = Invoice::factory()->create(['id_siswa' => $siswa->id_siswa, 'status' => 'PENDING', 'periode_tagihan' => '2023-02-01']);

        $parentInvoice = Invoice::factory()->create([
            'id_siswa' => $siswa->id_siswa,
            'type' => 'pembayaran_spp_gabungan',
            'external_id_xendit' => 'GAB-123',
            'status' => 'PENDING',
            'selected_periods' => [
                $child1->periode_tagihan->format('Y-m-d'),
                $child2->periode_tagihan->format('Y-m-d')
            ]
        ]);

        $response = $this->postJson('/webhooks/xendit/invoice', [
            'external_id' => 'GAB-123',
            'status' => 'PAID',
        ], [
            'x-callback-token' => 'test-token'
        ]);

        $response->assertStatus(200);

        // Parent should be paid
        $this->assertDatabaseHas('invoices', [
            'id' => $parentInvoice->id,
            'status' => 'PAID'
        ]);

        // Children should be paid and linked
        $this->assertDatabaseHas('invoices', [
            'id' => $child1->id,
            'status' => 'PAID',
            'parent_payment_id' => $parentInvoice->id
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $child2->id,
            'status' => 'PAID',
            'parent_payment_id' => $parentInvoice->id
        ]);
    }

    public function test_processes_legacy_bulk_pivot_fallback()
    {
        $kelas = Kelas::factory()->create();
        $user = User::factory()->create();
        $siswa = Siswa::factory()->create(['id_kelas' => $kelas->id_kelas, 'id_user' => $user->id]);

        $child1 = Invoice::factory()->create(['id_siswa' => $siswa->id_siswa, 'status' => 'PENDING']);
        
        $parentInvoice = Invoice::factory()->create([
            'id_siswa' => $siswa->id_siswa,
            'type' => 'pembayaran_gabungan', // Legacy type
            'external_id_xendit' => 'LEGACY-123',
            'status' => 'PENDING',
            'selected_periods' => null // No periods array
        ]);

        // Create pivot relation
        $parentInvoice->childInvoices()->attach($child1->id);

        $response = $this->postJson('/webhooks/xendit/invoice', [
            'external_id' => 'LEGACY-123',
            'status' => 'PAID',
        ], [
            'x-callback-token' => 'test-token'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('invoices', [
            'id' => $parentInvoice->id,
            'status' => 'PAID'
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $child1->id,
            'status' => 'PAID',
            'parent_payment_id' => $parentInvoice->id // Fallback legacy should also set this now based on our new code
        ]);
    }
}
