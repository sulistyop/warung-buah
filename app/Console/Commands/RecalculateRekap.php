<?php

namespace App\Console\Commands;

use App\Models\Rekap;
use Illuminate\Console\Command;

class RecalculateRekap extends Command
{
    protected $signature = 'rekap:recalculate
                            {--id= : Recalculate rekap tertentu saja}
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Recalculate pendapatan_bersih dan sisa untuk semua rekap';

    public function handle(): int
    {
        $query = Rekap::with(['details', 'komplain', 'pengurang']);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $rekaps = $query->get();
        $isDryRun = $this->option('dry-run');
        $updated = 0;

        foreach ($rekaps as $rekap) {
            $oldPendapatan = $rekap->pendapatan_bersih;
            $oldSisa = $rekap->sisa;

            if ($isDryRun) {
                $totalKotor = $rekap->details->sum('subtotal');
                $totalKomisi = $totalKotor * ($rekap->komisi_persen / 100);
                $totalKuli = $rekap->total_kuli;
                $totalBusuk = $rekap->komplain->sum('total');
                $totalPengurang = $rekap->pengurang->sum('jumlah');
                $newPendapatan = $totalKotor - $totalKomisi - $totalKuli - $rekap->total_ongkos - $totalPengurang;
                $newSisa = $newPendapatan - $totalBusuk;

                if ($oldPendapatan != $newPendapatan || $oldSisa != $newSisa) {
                    $this->info("{$rekap->kode_rekap}: pendapatan {$oldPendapatan} → {$newPendapatan}, sisa {$oldSisa} → {$newSisa}");
                    $updated++;
                }
            } else {
                $rekap->recalculate();
                $rekap->refresh();

                if ($oldPendapatan != $rekap->pendapatan_bersih || $oldSisa != $rekap->sisa) {
                    $this->info("{$rekap->kode_rekap}: pendapatan {$oldPendapatan} → {$rekap->pendapatan_bersih}, sisa {$oldSisa} → {$rekap->sisa}");
                    $updated++;
                }
            }
        }

        $this->info("Selesai. {$updated} rekap " . ($isDryRun ? 'perlu diupdate.' : 'diupdate.'));

        return self::SUCCESS;
    }
}
