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
        $terms = LegalDocument::where('id', '0197d482-c41e-7390-a573-1e24cc504ea0')
                              ->latest('published_at')
                              ->first();

        return Inertia::render('Public/ReRegister', [
            'pageTitle' => 'Formulir Daftar Ulang Siswa',
            'allKelas' => Kelas::orderBy('nama_kelas')->get(['id_kelas', 'nama_kelas', 'kode_cabang']),
            'termsDocument' => $terms,
        ]);
    }

    public function createAcademy()
    {
        $academyClass = Kelas::where('nama_kelas', 'Persija Academy')->firstOrFail();
        $terms = LegalDocument::where('id', '0197d482-c41e-7390-a573-1e24cc504ea0')
                              ->latest('published_at')
                              ->first();

        return Inertia::render('Public/ReRegisterAcademy', [
            'pageTitle' => 'Formulir Daftar Ulang Siswa Academy',
            'academyClass' => $academyClass,
            'termsDocument' => $terms,
        ]);
    }

    public function createSs()
    {
        $terms = LegalDocument::where('id', '0197d482-c41e-7390-a573-1e24cc504ea0')
                              ->latest('published_at')
                              ->first();

        return Inertia::render('Public/ReRegisterSs', [
            'pageTitle' => 'Formulir Daftar Ulang Siswa Soccer School',
            'allKelas' => Kelas::where('deskripsi', 'Soccer School')
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
        ]);

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

                Siswa::create([
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
                Mail::to($validated['email_wali'])->send(new RegistrationSuccess($dataForEmail));
                
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
