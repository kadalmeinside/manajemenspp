<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAgreement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'legal_document_id',
        'agreed_at',
        'ip_address',
    ];

    protected $casts = [
        'agreed_at' => 'datetime',
    ];

    /**
     * Relasi untuk melihat user yang menyetujui.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi untuk melihat dokumen yang disetujui.
     */
    public function document()
    {
        return $this->belongsTo(LegalDocument::class, 'legal_document_id');
    }
}
