<?php

namespace App\Imports;

use App\Models\Siswa;
use App\Models\User;
use App\Models\Kelas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Spatie\Permission\Models\Role;

class SiswaImport implements ToCollection, WithHeadingRow, WithValidation 
{
    private $kelasMap;
    private $siswaRole;

    public function __construct()
    {
        $this->kelasMap = Kelas::pluck('id_kelas', 'nama_kelas');
        $this->siswaRole = Role::where('name', 'siswa')->first();
    }

    public function collection(Collection $rows)
    {
        // Perhatikan: TIDAK ADA Validator::make() di sini
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // ... (logika create user, generate nis, create siswa tetap sama)
                $user = User::firstOrCreate(
                    ['email' => $row['email_wali']],
                    [
                        'name' => $row['nama_wali'],
                        'password' => Hash::make(Str::random(10)),
                        'email_verified_at' => now(),
                    ]
                );
                $user->assignRole($this->siswaRole);

                $kelas_id = $this->kelasMap[$row['nama_kelas']];
                $kelas = Kelas::find($kelas_id);
                
                $tahun = now()->format('Y');
                $kodeCabang = $kelas->kode_cabang ?? 'XXX';
                
                $lastSiswa = Siswa::where('nis', 'LIKE', "{$tahun}{$kodeCabang}%")->orderBy('nis', 'desc')->first();
                $nomorUrut = $lastSiswa ? ((int) substr($lastSiswa->nis, -4)) + 1 : 1;
                $newNis = "{$tahun}{$kodeCabang}" . str_pad($nomorUrut, 4, '0', STR_PAD_LEFT);

                Siswa::create([
                    'nis' => $newNis,
                    'nama_siswa' => $row['nama_siswa'],
                    'id_user' => $user->id,
                    'id_kelas' => $kelas_id,
                    'status_siswa' => 'Aktif',
                    'email_wali' => $row['email_wali'],
                    'nomor_telepon_wali' => $row['nomor_telepon_wali'] ?? null,
                    'tanggal_lahir' => isset($row['tanggal_lahir']) ? \Carbon\Carbon::parse($row['tanggal_lahir']) : null,
                    'tanggal_bergabung' => isset($row['tanggal_bergabung']) ? \Carbon\Carbon::parse($row['tanggal_bergabung']) : now(),
                ]);
            }
        });
    }

    // <-- Pastikan fungsi ini ada dan berisi rules yang benar
    public function rules(): array
    {
        return [
            '*.nama_siswa' => ['required', 'string', 'max:255'],
            '*.nama_wali' => ['required', 'string', 'max:255'],
            '*.email_wali' => ['required', 'email', 'distinct'],
            '*.nama_kelas' => ['required', 'string', 'exists:kelas,nama_kelas'],
            '*.tanggal_bergabung' => ['nullable', 'date'],
            '*.tanggal_lahir' => ['nullable', 'date'],
        ];
    }
}