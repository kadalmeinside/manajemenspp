<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions; 
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Siswa extends Model
{
    use HasFactory, HasUuids, LogsActivity, SoftDeletes;

    protected $table = 'siswa';
    protected $primaryKey = 'id_siswa';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nis',
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
            ->logOnly(['nama_siswa', 'status_siswa', 'id_kelas']) 
            ->logOnlyDirty() 
            ->setDescriptionForEvent(fn(string $eventName) => "Data siswa telah di-{$eventName}")
            ->useLogName('Siswa'); 
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

    protected static function booted(): void
    {
        static::deleting(function (Siswa $siswa) {
            if ($siswa->user) {
                $siswa->user->delete();
            }
        });
    }

    public function generateNis()
    {
        // Jika siswa sudah punya NIS, jangan buat lagi.
        if ($this->nis) {
            return;
        }

        $yearMonth = Carbon::now()->format('Ym');

        // 1. Cari siswa terakhir yang dibuat di bulan yang sama untuk mendapatkan nomor urut
        $lastSiswa = self::where('nis', 'LIKE', $yearMonth . '%')
                         ->orderBy('nis', 'desc')
                         ->first();

        $nextSequence = 1;
        if ($lastSiswa && $lastSiswa->nis) {
            // Ambil 4 digit terakhir dari NIS, ubah ke angka, lalu tambah 1
            $lastSequence = (int) substr($lastSiswa->nis, -4);
            $nextSequence = $lastSequence + 1;
        }

        // 2. Format nomor urut menjadi 4 digit dengan angka 0 di depan
        $sequencePadded = Str::padLeft($nextSequence, 4, '0');

        // 3. Gabungkan menjadi NIS baru dan simpan
        $this->nis = $yearMonth . $sequencePadded;
        $this->save();
    }
}
