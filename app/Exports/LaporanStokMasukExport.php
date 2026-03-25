<?php

namespace App\Exports;

use App\Models\BarangDatang;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanStokMasukExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?string $tanggalDari = null,
        private ?string $tanggalSampai = null,
        private ?int $supplierId = null,
    ) {}

    public function collection()
    {
        $query = BarangDatang::with(['supplier', 'details'])
            ->orderBy('tanggal');

        if ($this->tanggalDari) {
            $query->where('tanggal', '>=', $this->tanggalDari);
        }
        if ($this->tanggalSampai) {
            $query->where('tanggal', '<=', $this->tanggalSampai);
        }
        if ($this->supplierId) {
            $query->where('supplier_id', $this->supplierId);
        }

        $rows = collect();
        $no = 1;
        foreach ($query->get() as $bd) {
            foreach ($bd->details as $detail) {
                $rows->push([
                    'no'           => $no++,
                    'tanggal'      => $bd->tanggal->format('d/m/Y'),
                    'kode_bd'      => $bd->kode_bd,
                    'supplier'     => $bd->supplier->nama_supplier ?? '-',
                    'status_bd'    => strtoupper($bd->status),
                    'nama_produk'  => $detail->nama_produk,
                    'ukuran'       => $detail->ukuran ?? '-',
                    'satuan'       => $detail->satuan,
                    'jumlah'       => $detail->jumlah,
                    'stok_terjual' => $detail->stok_terjual,
                    'stok_sisa'    => $detail->stok_sisa,
                    'status_stok'  => strtoupper($detail->status_stok),
                    'harga_beli'   => $detail->harga_beli,
                    'harga_jual'   => $detail->harga_jual,
                ]);
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Kode BD', 'Supplier', 'Status BD', 'Nama Produk', 'Ukuran', 'Satuan', 'Jumlah', 'Terjual', 'Sisa', 'Status Stok', 'Harga Beli', 'Harga Jual'];
    }

    public function title(): string
    {
        return 'Laporan Stok Masuk';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D47A1']]],
        ];
    }
}
