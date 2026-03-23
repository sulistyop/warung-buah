<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillDetailBarangDatangId extends Command
{
    protected $signature = 'backfill:detail-barang-datang-id
                            {--dry-run : Tampilkan hasil tanpa menyimpan}
                            {--limit=0 : Batasi jumlah item yang diproses (0 = semua)}';

    protected $description = 'Backfill detail_barang_datang_id pada item_transaksi berdasarkan matching supplier + nama produk + tanggal (FIFO)';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $limit    = (int) $this->option('limit');

        $this->info($isDryRun ? '[DRY RUN] Tidak ada data yang disimpan.' : 'Memulai backfill...');
        $this->newLine();

        // Ambil item yang belum punya detail_barang_datang_id dan punya supplier_id
        $query = DB::table('item_transaksi as it')
            ->join('transaksi as t', 't.id', '=', 'it.transaksi_id')
            ->whereNull('it.detail_barang_datang_id')
            ->whereNotNull('it.supplier_id')
            ->select(
                'it.id as item_id',
                'it.supplier_id',
                'it.jenis_buah',
                't.created_at as transaksi_created_at'
            )
            ->orderBy('t.created_at');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $items = $query->get();

        $this->info("Total item_transaksi yang perlu diproses: {$items->count()}");
        $this->newLine();

        $matched   = 0;
        $ambiguous = 0;
        $notFound  = 0;

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        foreach ($items as $item) {
            // Cari semua detail_barang_datang yang cocok (FIFO: tanggal paling awal dulu)
            $candidates = DB::table('detail_barang_datang as dbd')
                ->join('barang_datang as bd', 'bd.id', '=', 'dbd.barang_datang_id')
                ->where('bd.supplier_id', $item->supplier_id)
                ->where('bd.status', 'confirmed')
                ->where('dbd.nama_produk', $item->jenis_buah)
                ->whereDate('bd.tanggal', '<=', $item->transaksi_created_at)
                ->select('dbd.id as detail_id', 'bd.tanggal', 'bd.urutan_hari')
                ->orderBy('bd.tanggal')
                ->orderBy('bd.urutan_hari')
                ->get();

            if ($candidates->isEmpty()) {
                $notFound++;
                $bar->advance();
                continue;
            }

            $chosen = $candidates->first();

            if ($candidates->count() > 1) {
                $ambiguous++;
            } else {
                $matched++;
            }

            if (!$isDryRun) {
                DB::table('item_transaksi')
                    ->where('id', $item->item_id)
                    ->update(['detail_barang_datang_id' => $chosen->detail_id]);
            } else {
                $this->newLine();
                $this->line(sprintf(
                    '  item_id=%d supplier_id=%d jenis_buah="%s" → detail_id=%d (tanggal=%s) [%s]',
                    $item->item_id,
                    $item->supplier_id,
                    $item->jenis_buah,
                    $chosen->detail_id,
                    $chosen->tanggal,
                    $candidates->count() > 1 ? "AMBIGUOUS ({$candidates->count()} kandidat)" : 'ok'
                ));
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Matched (1 kandidat)', $matched],
                ['Matched FIFO (>1 kandidat, ambiguous)', $ambiguous],
                ['Tidak ditemukan', $notFound],
                ['Total diproses', $items->count()],
            ]
        );

        if ($notFound > 0) {
            $this->newLine();
            $this->warn("{$notFound} item tidak ditemukan pasangannya.");
            $this->warn('Kemungkinan penyebab:');
            $this->warn('  - Transaksi dibuat sebelum fitur barang_datang ada');
            $this->warn('  - supplier_id tidak cocok dengan barang_datang');
            $this->warn('  - nama jenis_buah berbeda dengan nama_produk di barang_datang');
        }

        if ($ambiguous > 0) {
            $this->newLine();
            $this->warn("{$ambiguous} item matched ke kandidat terlama (FIFO), tapi ada >1 kandidat.");
            $this->warn('Gunakan --dry-run untuk melihat detail dan koreksi manual jika perlu.');
        }

        if ($isDryRun) {
            $this->newLine();
            $this->info('Jalankan tanpa --dry-run untuk menyimpan perubahan.');
        } else {
            $this->info('Backfill selesai.');
        }

        return self::SUCCESS;
    }
}
