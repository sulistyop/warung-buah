<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'kode_produk',
        'nama_produk',
        'supplier_id',
        'ukuran',
        'kategori_id',
        'kategori',
        'satuan',
        'harga_beli',
        'harga_jual',
        'stok',
        'stok_minimum',
        'keterangan',
        'aktif',
    ];

    protected $casts = [
        'harga_beli' => 'float',
        'harga_jual' => 'float',
        'stok' => 'float',
        'stok_minimum' => 'float',
        'aktif' => 'boolean',
    ];

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

    public function scopeStokRendah($query)
    {
        return $query->whereRaw('stok <= stok_minimum');
    }

    // Relasi ke kategori
    public function kategoriRelasi(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    // Relasi ke supplier
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // Helper untuk mendapatkan nama kategori
    public function getNamaKategoriAttribute(): ?string
    {
        return $this->kategoriRelasi?->nama_kategori ?? $this->kategori;
    }

    // Helper untuk mendapatkan nama supplier
    public function getNamaSupplierAttribute(): ?string
    {
        return $this->supplier?->nama_supplier;
    }
}
