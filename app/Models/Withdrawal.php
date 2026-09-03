<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Withdrawal extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'xendit_disbursement_id',
        'payment_gateway',
        'amount',
        'bank_code',
        'account_name',
        'account_number',
        'status',
        'completed_at',
        'payload'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'completed_at' => 'datetime',
        'payload' => 'array',
    ];
}
