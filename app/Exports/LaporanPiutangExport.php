<?php

namespace App\Exports;

use App\Models\Transaksi;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanPiutangExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private ?string $tanggalDari = null,
        private ?string $tanggalSampai = null,
    ) {}

    public function collection()
    {
        $query = Transaksi::whereIn('status_bayar', ['tempo', 'cicil'])
            ->where('sisa_tagihan', '>', 0)
            ->orderBy('tanggal_jatuh_tempo');

        if ($this->tanggalDari) {
            $query->whereDate('created_at', '>=', $this->tanggalDari);
        }
        if ($this->tanggalSampai) {
            $query->whereDate('created_at', '<=', $this->tanggalSampai);
        }

        $today = Carbon::today();

        return $query->get()->map(function ($t, $i) use ($today) {
            $umur = $t->created_at ? $today->diffInDays($t->created_at->startOfDay(), false) : 0;
            $jatuhTempo = $t->tanggal_jatuh_tempo;
            $statusUmur = match(true) {
                $umur <= 30  => '0-30 hari',
                $umur <= 60  => '31-60 hari',
                $umur <= 90  => '61-90 hari',
                default      => '>90 hari',
            };
            $overdue = $jatuhTempo && $jatuhTempo->isPast() ? 'YA' : 'Tidak';

            return [
                'no'              => $i + 1,
                'tanggal_trx'     => $t->created_at->format('d/m/Y'),
                'kode'            => $t->kode_transaksi,
                'pelanggan'       => $t->nama_pelanggan,
                'status_bayar'    => strtoupper($t->status_bayar),
                'total_tagihan'   => $t->total_tagihan,
                'total_dibayar'   => $t->total_dibayar,
                'sisa_tagihan'    => $t->sisa_tagihan,
                'jatuh_tempo'     => $jatuhTempo ? $jatuhTempo->format('d/m/Y') : '-',
                'overdue'         => $overdue,
                'umur_piutang'    => $statusUmur,
            ];
        });
    }

    public function headings(): array
    {
        return ['No', 'Tgl Transaksi', 'Kode', 'Pelanggan', 'Status', 'Total Tagihan', 'Sudah Dibayar', 'Sisa Tagihan', 'Jatuh Tempo', 'Overdue?', 'Umur Piutang'];
    }

    public function title(): string
    {
        return 'Laporan Piutang';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B71C1C']]],
        ];
    }
}
