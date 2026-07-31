<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PendingRegistration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'pending_registrations';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'email_wali',
        'nama_wali',
        'nama_siswa',
        'tanggal_lahir',
        'nomor_telepon_wali',
        'id_kelas',
        'kode_promo',
        'legal_document_id',
        'ip_address',
        'amount',
        'admin_fee',
        'total_amount',
        'xendit_external_id',
        'xendit_invoice_id',
        'xendit_payment_url',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];
}
