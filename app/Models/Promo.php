<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id_kelas',
        'nama_promo',
        'kode_promo', // <-- Tambahkan di fillable
        'tipe_diskon',
        'nilai_diskon',
        'tanggal_mulai',
        'tanggal_berakhir',
        'is_active',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berakhir' => 'date',
        'nilai_diskon' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_promo', 'promo_id', 'invoice_id');
    }
}
