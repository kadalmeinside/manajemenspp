<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class PaidInvoicesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    protected $startDate;
    protected $endDate;

    public function __construct(string $startDate, string $endDate)
    {
        $this->startDate = Carbon::parse($startDate)->startOfDay();
        $this->endDate = Carbon::parse($endDate)->endOfDay();
    }

    public function query()
    {
        return Invoice::query()
            ->with(['siswa.kelas', 'paymentParent'])
            ->whereIn('status', ['PAID', 'SETTLED'])
            ->whereNotIn('type', ['pembayaran_spp_gabungan', 'pembayaran_gabungan'])
            ->whereBetween('paid_at', [$this->startDate, $this->endDate])
            ->orderBy('paid_at', 'asc');
    }

    public function headings(): array
    {
        return [
            'ID Tagihan',
            'Nama Siswa',
            'Kelas',
            'Deskripsi Tagihan',
            'Nominal (Rp)',
            'Tanggal Lunas',
            'Sistem Pembayaran',
            'Kanal Pembayaran',
            'Tipe',
            'Link Xendit / Referensi'
        ];
    }

    public function map($invoice): array
    {
        // Ambil dari parent jika tidak ada di child invoice (untuk pembayaran gabungan)
        $paymentMethod = $invoice->payment_method ?? $invoice->paymentParent?->payment_method ?? '';
        $xenditUrl = $invoice->xendit_payment_url ?? $invoice->paymentParent?->xendit_payment_url ?? '';
        $xenditId = $invoice->xendit_invoice_id ?? $invoice->paymentParent?->xendit_invoice_id ?? '-';

        $isManual = strtolower($paymentMethod) === 'manual';
        $sistemPembayaran = $isManual ? 'MANUAL' : 'XENDIT';
        
        $kanalPembayaran = '-';
        if ($isManual) {
            $kanalPembayaran = 'MANUAL (Diinput Admin)';
        } elseif (!empty($paymentMethod)) {
            $kanalPembayaran = strtoupper($paymentMethod);
        } else {
            $kanalPembayaran = 'XENDIT (Tidak spesifik)';
        }

        return [
            $invoice->id,
            $invoice->siswa->nama_siswa ?? '-',
            $invoice->siswa->kelas->nama_kelas ?? '-',
            $invoice->description,
            $invoice->total_amount,
            Carbon::parse($invoice->paid_at)->isoFormat('D MMMM YYYY, HH:mm'),
            $sistemPembayaran,
            $kanalPembayaran,
            strtoupper(str_replace('_', ' ', $invoice->type)),
            $xenditUrl ?: $xenditId,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}
