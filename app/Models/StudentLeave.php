<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentLeave extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'id_siswa',
        'month',
        'year',
        'reason',
        'status',
        'approved_by',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
