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
        'max_uses',
        'current_uses',
        'max_uses_per_user',
        'max_discount',
        'bukti_sk',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_berakhir' => 'date',
        'nilai_diskon' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'is_active' => 'boolean',
        'max_uses' => 'integer',
        'current_uses' => 'integer',
        'max_uses_per_user' => 'integer',
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_promo', 'promo_id', 'invoice_id');
    }

    public function scopeValidForKelas($query, $idKelas)
    {
        return $query->where('is_active', true)
            ->where(function ($q) use ($idKelas) {
                $q->where('id_kelas', $idKelas)
                  ->orWhereNull('id_kelas');
            })
            ->where('tanggal_mulai', '<=', now())
            ->where(function ($q) {
                $q->where('tanggal_berakhir', '>=', now())
                  ->orWhereNull('tanggal_berakhir');
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                  ->orWhereColumn('current_uses', '<', 'max_uses');
            });
    }

    public function calculateDiscount($originalPrice)
    {
        $discountAmount = 0;
        if ($this->tipe_diskon === 'persen') {
            $discountAmount = $originalPrice * ($this->nilai_diskon / 100);
            if ($this->max_discount && $discountAmount > $this->max_discount) {
                $discountAmount = (float) $this->max_discount;
            }
        } else {
            $discountAmount = (float) $this->nilai_diskon;
        }
        return $discountAmount;
    }
}
