<?php

namespace App\Console\Commands;

use App\Models\Transaksi;
use Illuminate\Console\Command;

class RecalculateTransaksi extends Command
{
    protected $signature = 'transaksi:recalculate
                            {--id= : Recalculate transaksi tertentu saja}
                            {--status= : Filter berdasarkan status_bayar (misal: tempo, cicil)}
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Recalculate total_tagihan dan sisa_tagihan untuk semua transaksi';

    public function handle(): int
    {
        $query = Transaksi::with(['itemTransaksi', 'biayaOperasional', 'pembayaran', 'komplainTransaksi.itemTransaksi']);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        if ($status = $this->option('status')) {
            $query->where('status_bayar', $status);
        }

        $transaksis = $query->get();
        $isDryRun = $this->option('dry-run');
        $updated = 0;

        foreach ($transaksis as $trx) {
            $oldTagihan = $trx->total_tagihan;
            $oldSisa    = $trx->sisa_tagihan;

            if ($isDryRun) {
                $totalKotor = $trx->itemTransaksi->sum('subtotal');
                foreach ($trx->komplainTransaksi as $k) {
                    if ($k->itemTransaksi) {
                        $totalKotor -= (float) $k->jumlah_bs * (float) $k->itemTransaksi->harga_per_kg;
                    }
                }
                $totalKotor  = max(0, $totalKotor);
                $totalBiaya  = $trx->biayaOperasional->sum('nominal');
                $totalBersih = $totalKotor + $totalBiaya;
                $totalDibayar = $trx->pembayaran->sum('nominal');
                $newTagihan  = $totalBersih;
                $newSisa     = max(0, $newTagihan - $totalDibayar);

                if ($oldTagihan != $newTagihan || $oldSisa != $newSisa) {
                    $this->info("{$trx->kode_transaksi}: tagihan {$oldTagihan} → {$newTagihan}, sisa {$oldSisa} → {$newSisa}");
                    $updated++;
                }
            } else {
                $trx->recalculate();
                $trx->refresh();

                if ($oldTagihan != $trx->total_tagihan || $oldSisa != $trx->sisa_tagihan) {
                    $this->info("{$trx->kode_transaksi}: tagihan {$oldTagihan} → {$trx->total_tagihan}, sisa {$oldSisa} → {$trx->sisa_tagihan}");
                    $updated++;
                }
            }
        }

        $this->info("Selesai. {$updated} transaksi " . ($isDryRun ? 'perlu diupdate.' : 'diupdate.'));

        return self::SUCCESS;
    }
}
