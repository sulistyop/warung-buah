<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaksi extends Model
{
    protected $table = 'transaksi';

    protected $fillable = [
        'kode_transaksi',
        'nama_pelanggan',
        'status_bayar',
        'tanggal_jatuh_tempo',
        'catatan',
        'komisi_persen',
        'total_kotor',
        'total_komisi',
        'total_biaya_operasional',
        'total_bersih',
        'status',
        'user_id',
    ];

    protected $casts = [
        'tanggal_jatuh_tempo'    => 'date',
        'komisi_persen'          => 'float',
        'total_kotor'            => 'float',
        'total_komisi'           => 'float',
        'total_biaya_operasional'=> 'float',
        'total_bersih'           => 'float',
    ];

    public function itemTransaksi(): HasMany
    {
        return $this->hasMany(ItemTransaksi::class, 'transaksi_id');
    }

    public function biayaOperasional(): HasMany
    {
        return $this->hasMany(BiayaOperasional::class, 'transaksi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateKode(): string
    {
        $prefix = 'TRX-' . date('Ymd') . '-';
        $last = self::where('kode_transaksi', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_transaksi, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung ulang semua total dan simpan
     */
    public function recalculate(): void
    {
        $totalKotor = $this->itemTransaksi()->sum('subtotal');
        $totalBiaya = $this->biayaOperasional()->sum('nominal');
        $totalKomisi = $totalKotor * ($this->komisi_persen / 100);
        $totalBersih = $totalKotor - $totalKomisi - $totalBiaya;

        $this->update([
            'total_kotor'             => $totalKotor,
            'total_komisi'            => $totalKomisi,
            'total_biaya_operasional' => $totalBiaya,
            'total_bersih'            => $totalBersih,
        ]);
    }
}
