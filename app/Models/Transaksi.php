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
        'pelanggan_id',
        'nama_pelanggan',
        'status_bayar',
        'tanggal_jatuh_tempo',
        'catatan',
        'komisi_persen',
        'total_kotor',
        'total_komisi',
        'total_biaya_operasional',
        'total_bersih',
        'total_tagihan',
        'total_dibayar',
        'sisa_tagihan',
        'uang_diterima',
        'kembalian',
        'status',
        'user_id',
    ];

    protected $casts = [
        'tanggal_jatuh_tempo'     => 'date',
        'komisi_persen'           => 'float',
        'total_kotor'             => 'float',
        'total_komisi'            => 'float',
        'total_biaya_operasional' => 'float',
        'total_bersih'            => 'float',
        'total_tagihan'           => 'float',
        'total_dibayar'           => 'float',
        'sisa_tagihan'            => 'float',
        'uang_diterima'           => 'float',
        'kembalian'               => 'float',
    ];

    public function itemTransaksi(): HasMany
    {
        return $this->hasMany(ItemTransaksi::class, 'transaksi_id');
    }

    public function biayaOperasional(): HasMany
    {
        return $this->hasMany(BiayaOperasional::class, 'transaksi_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'transaksi_id');
    }

    public function komplainTransaksi(): HasMany
    {
        return $this->hasMany(KomplainTransaksi::class, 'transaksi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
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

        // Kurangi total kotor dengan nilai busuk via FK item_transaksi_id
        foreach ($this->komplainTransaksi()->with('itemTransaksi')->get() as $k) {
            if ($k->itemTransaksi) {
                $totalKotor -= (float) $k->jumlah_bs * (float) $k->itemTransaksi->harga_per_kg;
            }
        }
        $totalKotor = max(0, $totalKotor);

        $totalBiaya = $this->biayaOperasional()->sum('nominal');
        $totalKomisi = $totalKotor * ($this->komisi_persen / 100);
        $totalBersih = $totalKotor + $totalBiaya;

        $totalTagihan = $totalBersih;
        $totalDibayar = $this->pembayaran()->sum('nominal');
        $sisaTagihan  = $totalTagihan - $totalDibayar;

        $this->update([
            'total_kotor'             => $totalKotor,
            'total_komisi'            => $totalKomisi,
            'total_biaya_operasional' => $totalBiaya,
            'total_bersih'            => $totalBersih,
            'total_tagihan'           => $totalTagihan,
            'total_dibayar'           => $totalDibayar,
            'sisa_tagihan'            => max(0, $sisaTagihan),
        ]);

        if ($sisaTagihan <= 0 && $totalTagihan > 0) {
            // Preserve 'transfer' status — don't overwrite with 'lunas'
            if ($this->status_bayar !== 'transfer') {
                $this->update(['status_bayar' => 'lunas']);
            }
        } elseif ($totalDibayar > 0 && $sisaTagihan > 0) {
            $this->update(['status_bayar' => 'cicil']);
        }
    }

    public function getStatusBayarLabelAttribute(): array
    {
        return match($this->status_bayar) {
            'lunas'    => ['label' => 'Lunas', 'color' => 'green'],
            'transfer' => ['label' => 'Transfer', 'color' => 'blue'],
            'tempo'    => ['label' => 'Tempo', 'color' => 'yellow'],
            'cicil'    => ['label' => 'Cicil', 'color' => 'orange'],
            default    => ['label' => '-', 'color' => 'gray'],
        };
    }

    public function isJatuhTempo(): bool
    {
        if (!$this->tanggal_jatuh_tempo) return false;
        return $this->tanggal_jatuh_tempo->isPast() && $this->sisa_tagihan > 0;
    }
}
