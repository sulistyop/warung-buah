<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';

    protected $fillable = [
        'kode_pelanggan',
        'nama',
        'telepon',
        'toko',
        'alamat',
        'catatan',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    public static function generateKode(): string
    {
        $prefix = 'PLG-';
        $last = self::where('kode_pelanggan', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_pelanggan, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'pelanggan_id');
    }

    public function deposit(): HasMany
    {
        return $this->hasMany(Deposit::class, 'pelanggan_id');
    }

    /**
     * Total sisa piutang pelanggan (sum sisa_tagihan dari semua transaksi belum lunas)
     */
    public function getTotalPiutangAttribute(): float
    {
        return (float) $this->transaksi()
            ->whereIn('status_bayar', ['tempo', 'cicil'])
            ->sum('sisa_tagihan');
    }

    /**
     * Total deposit aktif yang belum terpakai
     */
    public function getTotalDepositAttribute(): float
    {
        return (float) $this->deposit()->sum('sisa');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
