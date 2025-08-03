<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Invoice extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    protected $table = 'invoices';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'id_siswa',
        'user_id',
        'type',
        'description',
        'periode_tagihan',
        'amount',
        'admin_fee',
        'total_amount',
        'due_date',
        'paid_at',
        'status',
        'xendit_invoice_id',
        'xendit_payment_url',
        'external_id_xendit',
        'xendit_callback_payload',
        'recreated_from_id',
        'parent_payment_id',
    ];

    protected $casts = [
        'periode_tagihan' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function childInvoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_relations', 'parent_invoice_id', 'child_invoice_id');
    }

    public function parentInvoice()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_relations', 'child_invoice_id', 'parent_invoice_id');
    }

    public function paymentParent()
    {
        return $this->belongsTo(Invoice::class, 'parent_payment_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Lacak perubahan pada kolom-kolom penting invoice
            ->logOnly(['status', 'paid_at', 'total_amount'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Invoice #{$this->id} untuk '{$this->siswa->nama_siswa}' telah di-{$eventName}")
            ->useLogName('Invoice');
    }

    public function originalInvoice()
    {
        return $this->belongsTo(Invoice::class, 'recreated_from_id');
    }
    

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
