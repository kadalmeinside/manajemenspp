<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use App\Models\Setting;
use App\Models\Invoice;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Validation\Rules\Password;

class RegistrationController extends Controller
{
    /**
     * Menampilkan form pendaftaran.
     */
    public function create()
    {
        $registrationFee = Setting::where('key', 'registration_fee')->value('value') ?? 2500000;

        return Inertia::render('Public/Registration', [
            'pageTitle' => 'Formulir Pendaftaran Siswa Baru',
            'allKelas' => Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas']),
            'registrationFee' => (float) $registrationFee,
        ]);
    }

    /**
     * Menyimpan data pendaftaran, membuat user & siswa pending, dan membuat invoice.
     */
    public function store(Request $request, XenditService $xenditService)
    {
        $validated = $request->validate([
            'nama_siswa' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'user_name' => 'required|string|max:255', // Nama wali
            'email_wali' => 'required|string|email|max:255|unique:users,email',
            'nomor_telepon_wali' => 'required|string|max:20',
            'user_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $registrationFee = Setting::where('key', 'registration_fee')->value('value') ?? 2500000;
        $siswaRole = Role::where('name', 'siswa')->firstOrFail();

        // sleep(5);
        // dd($registrationFee);

        try {
            $invoice = DB::transaction(function () use ($validated, $siswaRole, $registrationFee) {
                $user = User::create([
                    'name' => $validated['user_name'],
                    'email' => $validated['email_wali'],
                    'password' => Hash::make($validated['user_password']),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole($siswaRole);

                $siswa = Siswa::create([
                    'nama_siswa' => $validated['nama_siswa'],
                    'tanggal_lahir' => $validated['tanggal_lahir'],
                    'status_siswa' => 'pending_payment',
                    'id_kelas' => $validated['id_kelas'],
                    'id_user' => $user->id,
                    'email_wali' => $validated['email_wali'],
                    'nomor_telepon_wali' => $validated['nomor_telepon_wali'],
                    'tanggal_bergabung' => now(),
                ]);

                return Invoice::create([
                    'id_siswa' => $siswa->id_siswa,
                    'user_id' => null,
                    'type' => 'pendaftaran',
                    'description' => 'Biaya Pendaftaran - ' . $siswa->nama_siswa,
                    'periode_tagihan' => null,
                    'amount' => $registrationFee,
                    'admin_fee' => 0,
                    'total_amount' => $registrationFee,
                    'due_date' => now()->addDay(),
                    'status' => 'PENDING',
                    'external_id_xendit' => 'REG-' . $siswa->id_siswa . '-' . strtoupper(Str::random(8)),
                ]);
            });

            $payerInfo = [
                'email' => $validated['email_wali'],
                'name' => $validated['user_name'],
                'phone' => $validated['nomor_telepon_wali'],
            ];
            
            $xenditInvoiceData = $xenditService->createInvoice(
                (float) $invoice->total_amount, 0,
                $invoice->description, $payerInfo,
                $invoice->external_id_xendit,
                route('payment.success'),
                route('payment.failure'),
                Carbon::parse($invoice->due_date),
                ['email', 'whatsapp']
            );

            if ($xenditInvoiceData && isset($xenditInvoiceData['invoice_url'])) {
                $invoice->update([
                    'xendit_invoice_id' => $xenditInvoiceData['id'],
                    'xendit_payment_url' => $xenditInvoiceData['invoice_url'],
                ]);
                return Inertia::location($xenditInvoiceData['invoice_url']);
            } else {
                throw new \Exception('Gagal membuat invoice pembayaran Xendit setelah data tersimpan.');
            }

        } catch (\Exception $e) {
            Log::error('Kesalahan saat pendaftaran siswa baru: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['general' => 'Terjadi kesalahan saat pendaftaran. Silakan hubungi admin untuk bantuan.']);
        }
    }
}
