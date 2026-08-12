<?php

namespace Tests\Feature\Jobs;

use App\Jobs\GenerateMassInvoices;
use App\Models\Invoice;
use App\Models\JobBatch;
use App\Models\Kelas;
use App\Models\Setting;
use App\Models\Siswa;
use App\Models\StudentLeave;
use App\Models\User;
use App\Services\XenditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class GenerateMassInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_invoices_only_for_eligible_students()
    {
        Event::fake();
        Carbon::setTestNow('2023-09-10');

        $kelas = Kelas::factory()->create(['biaya_spp_default' => 500000]);
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // 1. Siswa Aktif, mulai_spp = Aug 2023 (ELIGIBLE for Sept)
        $siswaEligible = Siswa::factory()->create([
            'id_kelas' => $kelas->id_kelas,
            'id_user' => $user1->id,
            'status_siswa' => 'Aktif',
            'mulai_spp_date' => '2023-08-15',
        ]);

        // 2. Siswa Aktif, mulai_spp = Oct 2023 (NOT ELIGIBLE for Sept)
        $siswaNotEligible = Siswa::factory()->create([
            'id_kelas' => $kelas->id_kelas,
            'id_user' => $user2->id,
            'status_siswa' => 'Aktif',
            'mulai_spp_date' => '2023-10-01',
        ]);

        // 3. Siswa Aktif, cuti approved di Sept (ELIGIBLE tapi potongan cuti)
        $siswaCuti = Siswa::factory()->create([
            'id_kelas' => $kelas->id_kelas,
            'id_user' => $user3->id,
            'status_siswa' => 'Aktif',
            'mulai_spp_date' => '2023-01-01',
        ]);
        StudentLeave::create([
            'id_siswa' => $siswaCuti->id_siswa,
            'month' => 9,
            'year' => 2023,
            'status' => 'approved',
            'reason' => 'Sakit'
        ]);
        Setting::create(['key' => 'spp_cuti_amount', 'value' => '200000']);

        // Mock XenditService to prevent actual API calls
        $mockXendit = Mockery::mock(XenditService::class);
        $mockXendit->shouldReceive('createInvoice')->andReturn([
            'id' => 'xnd_mock_' . uniqid(),
            'external_id' => 'INV-MOCK',
            'invoice_url' => 'http://mock.url',
        ]);

        $batch = JobBatch::create(['name' => 'Test Batch', 'user_id' => $user1->id, 'status' => 'pending']);

        $job = new GenerateMassInvoices($batch, [
            'tahun' => 2023,
            'bulan' => 9,
            'jatuh_tempo' => '2023-09-20',
            'jenis_jumlah_spp' => 'default',
            'id_kelas' => ''
        ]);

        // Run the job
        $job->handle($mockXendit);

        // Assertions
        $this->assertDatabaseCount('invoices', 2);

        // Siswa Eligible gets 500k invoice
        $this->assertDatabaseHas('invoices', [
            'id_siswa' => $siswaEligible->id_siswa,
            'total_amount' => 500000,
            'periode_tagihan' => '2023-09-01 00:00:00',
        ]);

        // Siswa Not Eligible gets NO invoice
        $this->assertDatabaseMissing('invoices', [
            'id_siswa' => $siswaNotEligible->id_siswa,
        ]);

        // Siswa Cuti gets 200k invoice
        $this->assertDatabaseHas('invoices', [
            'id_siswa' => $siswaCuti->id_siswa,
            'total_amount' => 200000,
            'periode_tagihan' => '2023-09-01 00:00:00',
        ]);
    }
}
