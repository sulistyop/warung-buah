<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetailBarangDatang extends Model
{
    protected $table = 'detail_barang_datang';

    protected $fillable = [
        'barang_datang_id',
        'kode_produk',
        'nama_produk',
        'ukuran',
        'kategori_id',
        'satuan',
        'harga_beli',
        'harga_jual',
        'jumlah',
        'stok_awal',
        'stok_terjual',
        'stok_sisa',
        'status_stok',
        'keterangan',
        'aktif',
    ];

    protected $casts = [
        'jumlah'       => 'float',
        'harga_beli'   => 'float',
        'harga_jual'   => 'float',
        'stok_awal'    => 'float',
        'stok_terjual' => 'float',
        'stok_sisa'    => 'float',
        'aktif'        => 'boolean',
    ];

    protected $appends = ['stok_pesanan', 'stok_sisa_real'];

    /**
     * Generate kode produk unik: PRD-NNNN
     */
    public static function generateKode(): string
    {
        $prefix = 'PRD-';
        $last = self::where('kode_produk', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_produk, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    public function barangDatang(): BelongsTo
    {
        return $this->belongsTo(BarangDatang::class, 'barang_datang_id');
    }

    public function itemTransaksi(): HasMany
    {
        return $this->hasMany(ItemTransaksi::class, 'detail_barang_datang_id');
    }

    public function pesanan(): HasMany
    {
        return $this->hasMany(DetailPreOrder::class, 'detail_barang_datang_id');
    }

    public function getStokPesananAttribute(): float
    {
        return (float) $this->pesanan()
            ->whereHas('preOrder', fn($q) => $q->whereIn('status', ['pending', 'diproses']))
            ->sum('jumlah_peti');
    }

    public function getStokSisaRealAttribute(): float
    {
        return max(0, $this->stok_sisa - $this->stok_pesanan);
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    /**
     * Kurangi stok saat ada penjualan. Return false jika stok tidak cukup.
     */
    public function kurangiStok(float $jumlahPeti): bool
    {
        if ($this->stok_sisa < $jumlahPeti) {
            return false;
        }

        $terjual = $this->stok_terjual + $jumlahPeti;
        $sisa    = $this->stok_awal - $terjual;

        $this->update([
            'stok_terjual' => $terjual,
            'stok_sisa'    => max(0, $sisa),
            'status_stok'  => $sisa <= 0 ? 'habis' : 'available',
        ]);

        return true;
    }

    /**
     * Tambah kembali stok (misal saat transaksi dibatalkan).
     */
    public function kembalikanStok(float $jumlahPeti): void
    {
        $terjual = max(0, $this->stok_terjual - $jumlahPeti);
        $sisa    = $this->stok_awal - $terjual;

        $this->update([
            'stok_terjual' => $terjual,
            'stok_sisa'    => $sisa,
            'status_stok'  => $sisa <= 0 ? 'habis' : 'available',
        ]);
    }

    public function isHabis(): bool
    {
        return $this->status_stok === 'habis';
    }
}
