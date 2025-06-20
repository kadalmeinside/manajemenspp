<?php

namespace App\Exports;

use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SiswaExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Siswa::with('kelas')->get();
    }

    /**
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID Siswa',
            'Nama Siswa',
            'Status',
            'Kelas',
            'Email Wali',
            'No. Telepon Wali',
            'Tanggal Bergabung',
        ];
    }

    /**
     *
     * @param mixed $siswa
     * @return array
     */
    public function map($siswa): array
    {
        return [
            $siswa->id_siswa,
            $siswa->nama_siswa,
            $siswa->status_siswa,
            $siswa->kelas ? $siswa->kelas->nama_kelas : 'N/A',
            $siswa->email_wali,
            $siswa->nomor_telepon_wali,
            $siswa->tanggal_bergabung,
        ];
    }
}