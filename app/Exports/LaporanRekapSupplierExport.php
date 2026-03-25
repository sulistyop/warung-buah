<?php

namespace App\Exports;

use App\Models\Rekap;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanRekapSupplierExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?string $tanggalDari = null,
        private ?string $tanggalSampai = null,
        private ?int $supplierId = null,
        private ?string $status = null,
    ) {}

    public function collection()
    {
        $query = Rekap::with(['supplier', 'dibuatOleh'])->orderBy('tanggal');

        if ($this->tanggalDari) {
            $query->where('tanggal', '>=', $this->tanggalDari);
        }
        if ($this->tanggalSampai) {
            $query->where('tanggal', '<=', $this->tanggalSampai);
        }
        if ($this->supplierId) {
            $query->where('supplier_id', $this->supplierId);
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }

        return $query->get()->map(function ($r, $i) {
            return [
                'no'                => $i + 1,
                'tanggal'           => $r->tanggal->format('d/m/Y'),
                'kode'              => $r->kode_rekap,
                'supplier'          => $r->supplier->nama_supplier ?? '-',
                'status'            => strtoupper($r->status),
                'total_peti'        => $r->total_peti,
                'total_kotor'       => $r->total_kotor,
                'total_komisi'      => $r->total_komisi,
                'total_kuli'        => $r->total_kuli,
                'total_ongkos'      => $r->total_ongkos,
                'total_busuk'       => $r->total_busuk,
                'pendapatan_bersih' => $r->pendapatan_bersih,
                'sisa'              => $r->sisa,
                'dibuat_oleh'       => $r->dibuatOleh->name ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Kode Rekap', 'Supplier', 'Status', 'Total Peti', 'Total Kotor', 'Komisi', 'Kuli', 'Ongkos', 'Busuk', 'Pendapatan Bersih', 'Sisa (ke Supplier)', 'Dibuat Oleh'];
    }

    public function title(): string
    {
        return 'Rekap Supplier';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']]],
        ];
    }
}
