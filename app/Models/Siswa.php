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

    /**
     * Generate NIS otomatis untuk siswa baru.
     *
     * Format NIS: {YYYY}{kode_cabang}{NNNN}
     * Contoh: 2025JKT0001
     *
     * Perbaikan dari versi sebelumnya:
     * - Format seragam dengan SiswaController::store() — konsisten di semua flow
     * - Menggunakan lockForUpdate() dalam DB transaction untuk mencegah race condition
     *   (dua siswa mendaftar bersamaan tidak akan mendapat NIS yang sama)
     */
    public function generateNis(): void
    {
        // Jika siswa sudah punya NIS, jangan buat lagi.
        if ($this->nis) {
            return;
        }

        // Pastikan kelas sudah di-load untuk mendapatkan kode_cabang
        $kelas = $this->kelas ?? Kelas::find($this->id_kelas);
        $kodeCabang = $kelas?->kode_cabang ?? 'XXX';
        $tahun = Carbon::now()->format('Y');
        $prefix = "{$tahun}{$kodeCabang}";

        // Gunakan transaksi dengan lockForUpdate() untuk mencegah race condition
        // Tanpa ini, dua siswa yang mendaftar bersamaan bisa mendapat NIS yang sama!
        \Illuminate\Support\Facades\DB::transaction(function () use ($prefix) {
            // lockForUpdate() memblokir row lain dari baca/tulis sampai transaksi selesai
            $lastSiswa = self::where('nis', 'LIKE', $prefix . '%')
                             ->lockForUpdate()
                             ->orderBy('nis', 'desc')
                             ->first();

            $nextSequence = 1;
            if ($lastSiswa && $lastSiswa->nis) {
                // Ambil 4 digit terakhir dari NIS
                $lastSequence = (int) substr($lastSiswa->nis, -4);
                $nextSequence = $lastSequence + 1;
            }

            // Format nomor urut menjadi 4 digit dengan angka 0 di depan
            $sequencePadded = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

            // Gabungkan dan simpan
            $this->nis = $prefix . $sequencePadded;
            $this->saveQuietly(); // saveQuietly agar tidak trigger event/log duplikat
        });
    }
}
