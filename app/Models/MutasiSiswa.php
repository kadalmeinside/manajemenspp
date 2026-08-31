<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MutasiSiswa extends Model
{
    use HasUuids;

    protected $fillable = [
        'siswa_id',
        'from_kelas_id',
        'to_kelas_id',
        'spp_baru',
        'start_month',
        'status',
        'token',
        'expires_at',
        'agreed_by',
        'agreed_at',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'agreed_at' => 'datetime',
        'spp_baru' => 'decimal:2',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id', 'id_siswa');
    }

    public function fromKelas()
    {
        return $this->belongsTo(Kelas::class, 'from_kelas_id', 'id_kelas');
    }

    public function toKelas()
    {
        return $this->belongsTo(Kelas::class, 'to_kelas_id', 'id_kelas');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
