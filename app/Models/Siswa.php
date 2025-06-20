<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions; 

class Siswa extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $table = 'siswa';
    protected $primaryKey = 'id_siswa';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nama_siswa',
        'tanggal_lahir',
        'status_siswa',
        'jumlah_spp_custom',
        'admin_fee_custom',
        'email_wali',
        'nomor_telepon_wali',
        'xendit_fixed_va_id',
        'nomor_va_fixed',
        'tanggal_bergabung',
        'id_kelas',
        'id_user',
    ];

    protected $casts = [
        'tanggal_bergabung' => 'date:Y-m-d',
        'jumlah_spp_custom' => 'decimal:2',
        'admin_fee_custom' => 'decimal:2',
        'tanggal_lahir' => 'date:Y-m-d',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_siswa', 'status_siswa', 'id_kelas']) // Hanya log perubahan pada kolom ini
            ->logOnlyDirty() // Hanya log jika ada perubahan (saat update)
            ->setDescriptionForEvent(fn(string $eventName) => "Data siswa telah di-{$eventName}")
            ->useLogName('Siswa'); // Nama log untuk kategori
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'id_siswa', 'id_siswa');
    }
}
