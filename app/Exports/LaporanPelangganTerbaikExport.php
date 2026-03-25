<?php

namespace App\Exports;

use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanPelangganTerbaikExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?string $tanggalDari = null,
        private ?string $tanggalSampai = null,
    ) {}

    public function collection()
    {
        $query = Transaksi::select(
            'nama_pelanggan',
            DB::raw('COUNT(*) as total_transaksi'),
            DB::raw('SUM(total_tagihan) as total_omset'),
            DB::raw('SUM(total_dibayar) as total_dibayar'),
            DB::raw('SUM(sisa_tagihan) as total_piutang'),
            DB::raw('MAX(created_at) as transaksi_terakhir')
        )
        ->groupBy('nama_pelanggan')
        ->orderByDesc('total_omset');

        if ($this->tanggalDari) {
            $query->whereDate('created_at', '>=', $this->tanggalDari);
        }
        if ($this->tanggalSampai) {
            $query->whereDate('created_at', '<=', $this->tanggalSampai);
        }

        return $query->get()->map(function ($row, $i) {
            return [
                'ranking'           => $i + 1,
                'pelanggan'         => $row->nama_pelanggan,
                'total_transaksi'   => $row->total_transaksi,
                'total_omset'       => $row->total_omset,
                'total_dibayar'     => $row->total_dibayar,
                'total_piutang'     => $row->total_piutang,
                'transaksi_terakhir'=> \Carbon\Carbon::parse($row->transaksi_terakhir)->format('d/m/Y'),
            ];
        });
    }

    public function headings(): array
    {
        return ['Ranking', 'Pelanggan', 'Jumlah Transaksi', 'Total Omset', 'Total Dibayar', 'Total Piutang', 'Transaksi Terakhir'];
    }

    public function title(): string
    {
        return 'Pelanggan Terbaik';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A148C']]],
        ];
    }
}
