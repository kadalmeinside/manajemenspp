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
        $promoOtomatis = $this->promos()
            ->where('is_active', true)
            ->whereNull('kode_promo')
            ->where('tanggal_mulai', '<=', now())
            ->where(fn($q) => $q->where('tanggal_berakhir', '>=', now())->orWhereNull('tanggal_berakhir'))
            ->first();

        if ($promoOtomatis) {
            if ($promoOtomatis->tipe_diskon === 'persen') {
                $harga -= $harga * ($promoOtomatis->nilai_diskon / 100);
            } else {
                $harga -= (float) $promoOtomatis->nilai_diskon;
            }
        }

        // 2. Jika ada kode promo, terapkan di atas harga yang SUDAH terdiskon
        if ($kodePromo) {
            $promoKode = $this->promos()
                ->where('is_active', true)
                ->where('kode_promo', $kodePromo)
                ->where('tanggal_mulai', '<=', now())
                ->where(fn($q) => $q->where('tanggal_berakhir', '>=', now())->orWhereNull('tanggal_berakhir'))
                ->first();

            if ($promoKode) {
                if ($promoKode->tipe_diskon === 'persen') {
                    // Diskon persen dihitung dari harga setelah diskon otomatis
                    $harga -= $harga * ($promoKode->nilai_diskon / 100);
                } else {
                    $harga -= (float) $promoKode->nilai_diskon;
                }
            }
        }

        return max(0, $harga); // Pastikan harga tidak minus
    }
}
