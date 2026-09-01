<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\LegalDocument;
use App\Models\Siswa;
use App\Models\User;
use App\Models\UserAgreement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationSuccess; 
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ReRegistrationController extends Controller
{
    /**
     * Menampilkan form pendaftaran ulang publik.
     */
    public function create()
    {
        $docId = \App\Models\Setting::where('key', 'legal_doc_re_registration')->value('value');
        $terms = $docId ? LegalDocument::find($docId) : null;

        return Inertia::render('Public/ReRegister', [
            'pageTitle'     => 'Formulir Daftar Ulang Siswa',
            'allKelas'      => Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas', 'kode_cabang']),
            'termsDocument' => $terms,
        ]);
    }

    public function createAcademy()
    {
        $academyClass = Kelas::where('nama_kelas', 'Persija Academy')->firstOrFail();
        
        $docId = \App\Models\Setting::where('key', 'legal_doc_registration_academy')->value('value');
        $terms = $docId ? LegalDocument::find($docId) : null;

        return Inertia::render('Public/ReRegisterAcademy', [
            'pageTitle'     => 'Formulir Daftar Ulang Siswa Academy',
            'academyClass'  => $academyClass,
            'termsDocument' => $terms,
        ]);
    }

    public function createSs()
    {
        $docId = \App\Models\Setting::where('key', 'legal_doc_registration_ss')->value('value');
        $terms = $docId ? LegalDocument::find($docId) : null;

        return Inertia::render('Public/ReRegisterSs', [
            'pageTitle'     => 'Formulir Daftar Ulang Siswa Soccer School',
            'allKelas'      => Kelas::where('deskripsi', 'Soccer School')
                                    ->orderBy('nama_kelas')
                                    ->get(['id_kelas', 'nama_kelas', 'kode_cabang']),
            'termsDocument' => $terms,
        ]);
    }
    
    

    /**
     * Menyimpan data siswa dari form pendaftaran ulang.
     */
    public function store(Request $request)
    {
        $messages = [
            'nama_siswa.required' => 'Nama lengkap siswa wajib diisi.',
            'nama_siswa.string' => 'Nama siswa harus berupa teks.',
            'nama_siswa.max' => 'Nama siswa maksimal 255 karakter.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'id_kelas.required' => 'Pilihan cabang atau kelas wajib diisi.',
            'id_kelas.exists' => 'Cabang atau kelas yang dipilih tidak valid.',
            'user_name.required' => 'Nama lengkap wali wajib diisi.',
            'user_name.string' => 'Nama wali harus berupa teks.',
            'user_name.max' => 'Nama wali maksimal 255 karakter.',
            'email_wali.required' => 'Alamat email wali wajib diisi.',
            'email_wali.email' => 'Format alamat email tidak valid.',
            'email_wali.unique' => 'Alamat email ini sudah terdaftar. Silakan gunakan email lain atau login.',
            'nomor_telepon_wali.required' => 'Nomor WhatsApp wali wajib diisi.',
            'user_password.required' => 'Password wajib diisi.',
            'user_password.confirmed' => 'Konfirmasi password tidak cocok.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan yang berlaku.',
            'legal_document_id.required' => 'Dokumen persetujuan wajib diisi.',
        ];

        $validated = $request->validate([
            'nama_siswa' => 'required|string|max:255',
            'tanggal_lahir' => 'required|date',
            'id_kelas' => 'required|uuid|exists:kelas,id_kelas',
            'user_name' => 'required|string|max:255',
            'email_wali' => 'required|string|email|max:255|unique:users,email',
            'nomor_telepon_wali' => 'required|string|max:20',
            'user_password' => ['required', 'confirmed', Password::defaults()],
            'legal_document_id' => 'required|uuid|exists:legal_documents,id',
            'terms' => 'accepted',
        ], $messages);

        // Format proper case
        $validated['nama_siswa'] = \Illuminate\Support\Str::title(strtolower(trim($validated['nama_siswa'])));
        $validated['user_name'] = \Illuminate\Support\Str::title(strtolower(trim($validated['user_name'])));

        $siswaRole = Role::where('name', 'siswa')->firstOrFail();
        $newNis = '';

        try {
            DB::transaction(function () use ($request, $validated, $siswaRole, &$newNis) {
                $kelas = Kelas::findOrFail($validated['id_kelas']);
                $tahun = now()->format('y');
                $kodeCabang = $kelas->kode_cabang ?? '99';
                $lastSiswa = Siswa::where('nis', 'LIKE', "{$tahun}{$kodeCabang}%")->lockForUpdate()->orderBy('nis', 'desc')->first();   
                $nomorUrut = $lastSiswa ? ((int) substr($lastSiswa->nis, -4)) + 1 : 1;
                $newNis = "{$tahun}{$kodeCabang}" . str_pad($nomorUrut, 4, '0', STR_PAD_LEFT);

                $user = User::create([
                    'name' => $validated['user_name'],
                    'email' => $validated['email_wali'],
                    'password' => Hash::make($validated['user_password']),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole($siswaRole);

                $siswa = Siswa::create([
                    'nis' => $newNis,
                    'nama_siswa' => $validated['nama_siswa'],
                    'tanggal_lahir' => $validated['tanggal_lahir'],
                    'status_siswa' => 'Aktif',
                    'id_kelas' => $validated['id_kelas'],
                    'id_user' => $user->id,
                    'email_wali' => $validated['email_wali'],
                    'nomor_telepon_wali' => $validated['nomor_telepon_wali'],
                    'tanggal_bergabung' => now(),
                ]);

                UserAgreement::create([
                    'user_id' => $user->id,
                    'id_siswa' => $siswa->id_siswa,
                    'legal_document_id' => $validated['legal_document_id'],
                    'agreed_at' => now(),
                    'ip_address' => $request->ip(),
                ]);

                $dataForEmail = [
                    'nis' => $newNis,
                    'nama_siswa' => $validated['nama_siswa'],
                    'nama_wali' => $validated['user_name'],
                    'email_wali' => $validated['email_wali'],
                ];
                try {
                    Mail::to($validated['email_wali'])->send(new RegistrationSuccess($dataForEmail));
                } catch (\Exception $mailEx) {
                    \Illuminate\Support\Facades\Log::warning('Gagal mengirim email pendaftaran ulang: ' . $mailEx->getMessage());
                }
            });
        } catch (\Exception $e) {
            return Redirect::back()->withErrors(['general' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }

        $completedData = [ 
            'nis' => $newNis,
            'nama_siswa' => $validated['nama_siswa'],
            'nama_wali' => $validated['user_name'],
            'email_wali' => $validated['email_wali'],
        ];

        return Redirect::back()->with([
            'success' => true,
            'message' => 'Pendaftaran ulang berhasil!',
            'completed_data' => $completedData,
        ]);
    }
}
