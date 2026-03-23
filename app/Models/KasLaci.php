<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasLaci extends Model
{
    protected $table = 'kas_laci';

    protected $fillable = [
        'kode_kas',
        'tanggal',
        'keterangan',
        'jenis',
        'nominal',
        'metode_sumber',
        'referensi_tipe',
        'referensi_id',
        'is_auto',
        'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'nominal' => 'float',
        'is_auto' => 'boolean',
    ];

    public static function generateKode(): string
    {
        $prefix = 'KAS-' . date('Ymd') . '-';
        $last = self::where('kode_kas', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_kas, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function dibuatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public static function catatDariTransaksi(Transaksi $transaksi): self
    {
        return self::create([
            'kode_kas'       => self::generateKode(),
            'tanggal'        => today(),
            'keterangan'     => "Penjualan tunai {$transaksi->kode_transaksi} - {$transaksi->nama_pelanggan}",
            'jenis'          => 'masuk',
            'nominal'        => $transaksi->total_dibayar,
            'metode_sumber'  => 'tunai',
            'referensi_tipe' => 'transaksi',
            'referensi_id'   => $transaksi->id,
            'is_auto'        => true,
            'dibuat_oleh'    => auth()->id(),
        ]);
    }

    public static function catatDariPembayaran(Pembayaran $pembayaran): self
    {
        return self::create([
            'kode_kas'       => self::generateKode(),
            'tanggal'        => today(),
            'keterangan'     => "Pembayaran {$pembayaran->kode_pembayaran} - {$pembayaran->transaksi->nama_pelanggan}",
            'jenis'          => 'masuk',
            'nominal'        => $pembayaran->nominal,
            'metode_sumber'  => 'tunai',
            'referensi_tipe' => 'pembayaran',
            'referensi_id'   => $pembayaran->id,
            'is_auto'        => true,
            'dibuat_oleh'    => auth()->id(),
        ]);
    }
}
