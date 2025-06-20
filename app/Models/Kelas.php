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
        'nama_kelas',
        'deskripsi',
        'biaya_spp_default',
    ];

    protected $casts = [
        'biaya_spp_default' => 'decimal:2',
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
}
