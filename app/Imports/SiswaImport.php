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
use Spatie\Permission\Models\Role;

class SiswaImport implements ToCollection, WithHeadingRow
{
    private $kelas;
    private $siswaRole;

    public function __construct()
    {
        $this->kelas = Kelas::all()->keyBy('nama_kelas');
        $this->siswaRole = Role::where('name', 'siswa')->first();
    }

    public function collection(Collection $rows)
    {
        Validator::make($rows->toArray(), [
             '*.nama_siswa' => 'required|string|max:255',
             '*.email_wali' => 'required|email|max:255|unique:users,email',
             '*.kelas' => 'required|string|exists:kelas,nama_kelas',
        ])->validate();

        foreach ($rows as $row)
        {
            $user = User::create([
                'name'     => $row['nama_siswa'],
                'email'    => $row['email_wali'],
                'password' => Hash::make(Str::random(10)),
                'email_verified_at' => now(),
            ]);

            if ($this->siswaRole) {
                $user->assignRole($this->siswaRole);
            }

            Siswa::create([
                'nama_siswa' => $row['nama_siswa'],
                'id_user' => $user->id,
                'id_kelas' => $this->kelas[$row['kelas']]->id_kelas, 
                'status_siswa' => 'Aktif',
                'email_wali' => $row['email_wali'],
                'nomor_telepon_wali' => $row['no_telepon_wali'] ?? null,
                'tanggal_bergabung' => $row['tanggal_bergabung'] ? \Carbon\Carbon::parse($row['tanggal_bergabung']) : now(),
            ]);
        }
    }
}