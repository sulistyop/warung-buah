<?php

namespace App\Exports;

use App\Models\KasLaci;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanKasLaciExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?string $tanggalDari = null,
        private ?string $tanggalSampai = null,
    ) {}

    public function collection()
    {
        $query = KasLaci::with('dibuatOleh')
            ->orderBy('tanggal')
            ->orderBy('id');

        if ($this->tanggalDari) {
            $query->whereDate('tanggal', '>=', $this->tanggalDari);
        }
        if ($this->tanggalSampai) {
            $query->whereDate('tanggal', '<=', $this->tanggalSampai);
        }

        $saldo = 0;
        // Hitung saldo awal jika ada filter tanggal
        if ($this->tanggalDari) {
            $masuk  = KasLaci::where('jenis', 'masuk')->whereDate('tanggal', '<', $this->tanggalDari)->sum('nominal');
            $keluar = KasLaci::where('jenis', 'keluar')->whereDate('tanggal', '<', $this->tanggalDari)->sum('nominal');
            $saldo  = $masuk - $keluar;
        }

        return $query->get()->map(function ($k, $i) use (&$saldo) {
            if ($k->jenis === 'masuk') {
                $saldo += $k->nominal;
            } else {
                $saldo -= $k->nominal;
            }

            return [
                'no'          => $i + 1,
                'tanggal'     => $k->tanggal,
                'kode'        => $k->kode_kas,
                'keterangan'  => $k->keterangan,
                'jenis'       => strtoupper($k->jenis),
                'masuk'       => $k->jenis === 'masuk' ? $k->nominal : 0,
                'keluar'      => $k->jenis === 'keluar' ? $k->nominal : 0,
                'saldo'       => $saldo,
                'metode'      => $k->metode_sumber,
                'tipe'        => $k->is_auto ? 'Otomatis' : 'Manual',
                'oleh'        => $k->dibuatOleh->name ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Tanggal', 'Kode', 'Keterangan', 'Jenis', 'Masuk', 'Keluar', 'Saldo', 'Metode', 'Tipe', 'Dibuat Oleh'];
    }

    public function title(): string
    {
        return 'Laporan Kas Laci';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1B5E20']]],
        ];
    }
}
