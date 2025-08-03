<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicPaymentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Menjalankan setup awal sebelum setiap test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Menjalankan seeder untuk memastikan semua role dan permission tersedia
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    /**
     * Test untuk menyimulasikan alur kerja pembayaran SPP lengkap
     * dari halaman cek tagihan publik.
     *
     * @return void
     */
    public function test_full_spp_payment_workflow_for_public_user()
    {
        // ===================================================================
        // TAHAP 1: PERSIAPAN DATA (ARRANGE)
        // ===================================================================

        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');

        $kelas = Kelas::factory()->create();
        $siswa = Siswa::factory()->for($kelas)->create([
            'nomor_telepon_wali' => '081234567890',
            'tanggal_lahir' => '2010-05-15',
            'jumlah_spp_custom' => 500000,
            'admin_fee_custom' => 5000,
        ]);

        $currentDate = Carbon::create(2025, 7, 15);
        Carbon::setTestNow($currentDate);

        // ===================================================================
        // TAHAP 2: AKSI ADMIN - MEMBUAT INVOICE AWAL
        // ===================================================================
        
        $response = $this->actingAs($adminUser)->post(route('admin.invoices.store'), [
            'id_siswa' => $siswa->id_siswa,
            'periode_tagihan_bulan' => 7, // Juli
            'periode_tagihan_tahun' => 2025,
            'jumlah_spp_ditagih' => 500000,
            'tanggal_jatuh_tempo' => now()->addDays(10)->format('Y-m-d'),
            'send_notification' => false,
        ]);

        $response->assertRedirect(route('admin.invoices.index'));

        $this->assertDatabaseHas('invoices', [
            'id_siswa' => $siswa->id_siswa,
            'type' => 'spp',
            'periode_tagihan' => '2025-07-01 00:00:00', 
            'status' => 'PENDING',
            'xendit_payment_url' => null,
        ]);
        
        // ### PERBAIKAN ###: Gunakan query yang lebih spesifik dan firstOrFail()
        $invoiceJuli = Invoice::where('id_siswa', $siswa->id_siswa)
            ->where('type', 'spp')
            ->whereDate('periode_tagihan', '2025-07-01')
            ->firstOrFail();

        // ===================================================================
        // TAHAP 3: AKSI SISWA/WALI - CEK TAGIHAN & MEMBAYAR
        // ===================================================================

        $response = $this->post(route('tagihan.check_status'), [
            'nomor_telepon_wali' => '081234567890',
            'tanggal_lahir' => '2010-05-15',
        ]);
        $response->assertRedirect(route('tagihan.check_result'));
        $response->assertSessionHas('checked_siswa_id', $siswa->id_siswa);

        $this->mock(XenditService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createInvoice')->once()->andReturn([
                'id' => 'inv_mock_'.Str::uuid(),
                'invoice_url' => 'https://checkout.xendit.co/web/mock_url',
            ]);
            $mock->shouldReceive('expireInvoice')->zeroOrMoreTimes(); 
        });

        $response = $this->withSession(['checked_siswa_id' => $siswa->id_siswa])
                         ->post(route('tagihan.check_pay'), [
            'periods' => [
                '2025-07-01',
                '2025-08-01',
            ],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('invoices', [
            'id_siswa' => $siswa->id_siswa,
            'type' => 'pembayaran_spp_gabungan',
            'status' => 'PENDING',
            'amount' => 1000000,
            'admin_fee' => 5000,
            'total_amount' => 1005000,
        ]);
        
        // ### PERBAIKAN ###: Gunakan query yang lebih spesifik dan firstOrFail()
        $parentInvoice = Invoice::where('id_siswa', $siswa->id_siswa)
            ->where('type', 'pembayaran_spp_gabungan')
            ->firstOrFail();

        // ===================================================================
        // TAHAP 4: SIMULASI WEBHOOK DARI XENDIT
        // ===================================================================

        $webhookPayload = [
            'id' => $parentInvoice->xendit_invoice_id,
            'external_id' => $parentInvoice->external_id_xendit,
            'status' => 'PAID',
            'paid_at' => now()->toIso8601String(),
        ];

        $response = $this->withHeaders(['x-callback-token' => config('xendit.callback_verification_token')])
                         ->post(route('webhooks.xendit.invoice'), $webhookPayload);
        
        $response->assertSuccessful();
        $response->assertJson(['message' => 'Webhook processed']);
        
        // ===================================================================
        // TAHAP 5: VERIFIKASI AKHIR (ASSERT)
        // ===================================================================

        $this->assertDatabaseHas('invoices', [
            'id' => $parentInvoice->id,
            'status' => 'PAID',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceJuli->id,
            'status' => 'PAID',
            'parent_payment_id' => $parentInvoice->id,
        ]);

        $this->assertDatabaseHas('invoices', [
            'id_siswa' => $siswa->id_siswa,
            'type' => 'spp',
            'periode_tagihan' => '2025-08-01 00:00:00',
            'status' => 'PAID',
            'parent_payment_id' => $parentInvoice->id,
        ]);
        
        $paidSppCount = $siswa->invoices()->where('type', 'spp')->where('status', 'PAID')->count();
        $this->assertEquals(2, $paidSppCount);
    }
}
