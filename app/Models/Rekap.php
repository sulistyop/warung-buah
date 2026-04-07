<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rekap extends Model
{
    protected $table = 'rekap';

    protected $fillable = [
        'kode_rekap',
        'supplier_id',
        'tanggal',
        'komisi_persen',
        'kuli_per_peti',
        'total_peti',
        'total_kotor',
        'total_komisi',
        'total_kuli',
        'total_ongkos',
        'keterangan_ongkos',
        'total_pengurang',
        'total_busuk',
        'pendapatan_bersih',
        'sisa',
        'status',
        'dibuat_oleh',
        'final_at',
    ];

    protected $casts = [
        'tanggal'           => 'date',
        'final_at'          => 'datetime',
        'komisi_persen'     => 'float',
        'kuli_per_peti'     => 'float',
        'total_peti'        => 'integer',
        'total_kotor'       => 'float',
        'total_komisi'      => 'float',
        'total_kuli'        => 'float',
        'total_ongkos'      => 'float',
        'total_pengurang'   => 'float',
        'total_busuk'       => 'float',
        'pendapatan_bersih' => 'float',
        'sisa'              => 'float',
    ];

    public static function generateKode(string $tanggal): string
    {
        $prefix = 'RKP-' . str_replace('-', '', $tanggal) . '-';
        $last = self::where('kode_rekap', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_rekap, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailRekap::class, 'rekap_id');
    }

    public function komplain(): HasMany
    {
        return $this->hasMany(KomplainRekap::class, 'rekap_id');
    }

    public function pengurang(): HasMany
    {
        return $this->hasMany(PengurangRekap::class, 'rekap_id');
    }

    /**
     * Hitung ulang semua total dari detail dan komplain.
     */
    public function recalculate(): void
    {
        $details = $this->details;

        $totalPeti        = $details->sum('jumlah_peti');
        $totalKotor       = $details->sum('subtotal');
        $totalKomisi      = $totalKotor * ($this->komisi_persen / 100);
        $totalKuli        = $this->total_kuli; // manual input, tidak dihitung per-peti
        $totalBusuk       = $this->komplain->sum('total');
        $totalPengurang   = $this->pengurang->sum('jumlah');
        $pendapatanBersih = $totalKotor - $totalKomisi - $totalKuli - $this->total_ongkos - $totalPengurang;
        $sisa             = $pendapatanBersih - $totalBusuk;

        $this->update([
            'total_peti'        => $totalPeti,
            'total_kotor'       => $totalKotor,
            'total_komisi'      => $totalKomisi,
            'total_busuk'       => $totalBusuk,
            'total_pengurang'   => $totalPengurang,
            'pendapatan_bersih' => $pendapatanBersih,
            'sisa'              => $sisa,
        ]);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
