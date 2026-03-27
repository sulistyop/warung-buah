<?php

namespace App\Exports;

use App\Models\ItemTransaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanPenjualanPerItemExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?string $tanggalDari = null,
        private ?string $tanggalSampai = null,
        private ?string $jenisBuah = null,
    ) {}

    public function collection()
    {
        $query = ItemTransaksi::with([
            'transaksi' => function ($q) {
                $q->select('id', 'nama_pelanggan', 'created_at', 'status_bayar');
            },
            'detailBarangDatang:id,ukuran',
        ])->orderBy('created_at');

        if ($this->tanggalDari) {
            $query->whereHas('transaksi', fn($q) => $q->whereDate('created_at', '>=', $this->tanggalDari));
        }
        if ($this->tanggalSampai) {
            $query->whereHas('transaksi', fn($q) => $q->whereDate('created_at', '<=', $this->tanggalSampai));
        }
        if ($this->jenisBuah) {
            $query->where('jenis_buah', 'like', '%' . $this->jenisBuah . '%');
        }

        return $query->get()->map(function ($item, $i) {
            return [
                'no'                 => $i + 1,
                'tanggal'            => $item->transaksi?->created_at?->format('d/m/Y') ?? '-',
                'nama_pelanggan'     => $item->transaksi?->nama_pelanggan ?? '-',
                'jenis_buah'         => $item->jenis_buah,
                'ukuran'             => $item->detailBarangDatang?->ukuran ?? '-',
                'jumlah_peti'        => $item->jumlah_peti,
                'total_berat_bersih' => $item->total_berat_bersih,
                'harga_per_kg'       => $item->harga_per_kg,
                'subtotal'           => $item->subtotal,
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Nama Pelanggan', 'Jenis Buah', 'Leter', 'Peti', 'Netto (kg)', 'Harga/kg', 'Subtotal'];
    }

    public function title(): string
    {
        return 'Laporan Penjualan Per Item';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']],
            ],
        ];
    }
}
