<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanPenjualanExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?string $tanggalDari = null,
        private ?string $tanggalSampai = null,
        private ?string $statusBayar = null,
    ) {}

    public function collection()
    {
        $query = Transaksi::with('user')->orderBy('created_at');

        if ($this->tanggalDari) {
            $query->whereDate('created_at', '>=', $this->tanggalDari);
        }
        if ($this->tanggalSampai) {
            $query->whereDate('created_at', '<=', $this->tanggalSampai);
        }
        if ($this->statusBayar) {
            $query->where('status_bayar', $this->statusBayar);
        }

        return $query->get()->map(function ($t, $i) {
            return [
                'no'             => $i + 1,
                'tanggal'        => $t->created_at->format('d/m/Y H:i'),
                'kode'           => $t->kode_transaksi,
                'pelanggan'      => $t->nama_pelanggan,
                'status_bayar'   => strtoupper($t->status_bayar),
                'total_tagihan'  => $t->total_tagihan,
                'total_dibayar'  => $t->total_dibayar,
                'sisa_tagihan'   => $t->sisa_tagihan,
                'kasir'          => $t->user->name ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Kode Transaksi', 'Pelanggan', 'Status Bayar', 'Total Tagihan', 'Total Dibayar', 'Sisa Tagihan', 'Kasir'];
    }

    public function title(): string
    {
        return 'Laporan Penjualan';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']], 'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']]],
        ];
    }
}
