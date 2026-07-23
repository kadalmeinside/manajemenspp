<?php

namespace App\Exports;

use App\Models\Invoice;
use App\Models\Siswa;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Support\Collection;

class LaporanSppExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected int $tahun;
    protected ?string $kelasId;
    protected ?string $search;

    public function __construct(int $tahun, ?string $kelasId = null, ?string $search = null)
    {
        $this->tahun   = $tahun;
        $this->kelasId = $kelasId;
        $this->search  = $search;
    }

    public function title(): string
    {
        return 'SPP ' . $this->tahun;
    }

    public function headings(): array
    {
        $monthNames = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthNames[] = Carbon::create(null, $i)->isoFormat('MMM');
        }
        return array_merge(['No', 'NIS', 'Nama Siswa', 'Kelas'], $monthNames, ['Total Bayar', 'Total Belum Bayar']);
    }

    public function collection(): Collection
    {
        $siswaQuery = Siswa::with('kelas')
            ->where('status_siswa', 'Aktif')
            ->orderBy('nama_siswa');

        if ($this->kelasId) {
            $siswaQuery->where('id_kelas', $this->kelasId);
        }

        if ($this->search) {
            $siswaQuery->where('nama_siswa', 'LIKE', "%{$this->search}%");
        }

        $siswas   = $siswaQuery->get();
        $siswaIds = $siswas->pluck('id_siswa');

        $invoices = Invoice::whereIn('id_siswa', $siswaIds)
            ->whereYear('periode_tagihan', $this->tahun)
            ->where('type', 'spp')
            ->get()
            ->keyBy(fn($item) => $item->id_siswa . '-' . Carbon::parse($item->periode_tagihan)->month);

        $rows = new Collection();
        $no   = 1;

        foreach ($siswas as $siswa) {
            $row = [
                $no++,
                $siswa->nis ?? '-',
                $siswa->nama_siswa,
                $siswa->kelas?->nama_kelas ?? '-',
            ];

            $paidCount   = 0;
            $unpaidCount = 0;

            for ($bulan = 1; $bulan <= 12; $bulan++) {
                $key    = $siswa->id_siswa . '-' . $bulan;
                $status = $invoices[$key]->status ?? 'N/A';
                $row[]  = $status;
                if ($status === 'PAID') {
                    $paidCount++;
                } elseif (in_array($status, ['PENDING', 'EXPIRED', 'FAILED'])) {
                    $unpaidCount++;
                }
            }

            $row[] = $paidCount;
            $row[] = $unpaidCount;
            $rows->push($row);
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }
}
