<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalDocument extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'type',
        'version',
        'content',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    /**
     * Relasi untuk melihat semua persetujuan yang terkait dengan dokumen ini.
     */
    public function agreements()
    {
        return $this->hasMany(UserAgreement::class);
    }
}
