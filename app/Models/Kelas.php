<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Kelas extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $table = 'kelas'; 
    protected $primaryKey = 'id_kelas'; 
    public $incrementing = false; 
    protected $keyType = 'string'; 

    protected $fillable = [
        'id_kelas',
        'nama_kelas',
        'deskripsi',
        'kode_cabang',
        'biaya_spp_default',
        'biaya_pendaftaran',
    ];

    protected $casts = [
        'biaya_spp_default' => 'decimal:2',
        'biaya_pendaftaran' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_kelas', 'deskripsi', 'biaya_spp_default'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Data Kelas '{$this->nama_kelas}' telah di-{$eventName}")
            ->useLogName('Kelas'); 
    }

    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'id_kelas', 'id_kelas');
    }

    public function managers()
    {
        return $this->belongsToMany(User::class, 'kelas_user', 'kelas_id', 'user_id');
    }

    public function promos()
    {
        return $this->hasMany(Promo::class, 'id_kelas', 'id_kelas');
    }
    

    /**
     * Fungsi pintar untuk menghitung biaya pendaftaran aktual
     * setelah mempertimbangkan promo yang aktif (otomatis atau dengan kode).
     */
    public function getBiayaPendaftaranSaatIni(string $kodePromo = null): float
    {
        $harga = (float) $this->biaya_pendaftaran;

        // 1. Terapkan promo otomatis terlebih dahulu
        $promoOtomatis = Promo::validForKelas($this->id_kelas)
            ->whereNull('kode_promo')
            ->first();

        if ($promoOtomatis) {
            $harga -= $promoOtomatis->calculateDiscount($harga);
        }

        // 2. Jika ada kode promo, terapkan di atas harga yang SUDAH terdiskon
        if ($kodePromo) {
            $promoKode = Promo::validForKelas($this->id_kelas)
                ->where('kode_promo', $kodePromo)
                ->first();

            if ($promoKode) {
                $harga -= $promoKode->calculateDiscount($harga);
            }
        }

        return max(0, $harga); // Pastikan harga tidak minus
    }

    /**
     * Mendapatkan daftar model Promo yang sah dan diterapkan (otomatis maupun kode).
     * Digunakan untuk mencatat relasi di tabel pivot saat invoice dibuat.
     */
    public function getAppliedPromos(string $kodePromo = null)
    {
        $promos = collect();

        // 1. Promo Otomatis
        $promoOtomatis = Promo::validForKelas($this->id_kelas)
            ->whereNull('kode_promo')
            ->first();

        if ($promoOtomatis) {
            $promos->push($promoOtomatis);
        }

        // 2. Promo Kode
        if ($kodePromo) {
            $promoKode = Promo::validForKelas($this->id_kelas)
                ->where('kode_promo', $kodePromo)
                ->first();

            if ($promoKode) {
                $promos->push($promoKode);
            }
        }

        return $promos;
    }
}
