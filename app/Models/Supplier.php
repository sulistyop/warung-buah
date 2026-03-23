<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $table = 'supplier';

    protected $fillable = [
        'kode_supplier',
        'nama_supplier',
        'telepon',
        'email',
        'alamat',
        'kota',
        'kontak_person',
        'catatan',
        'komisi_persen',
        'kuli_per_peti',
        'aktif',
    ];

    protected $casts = [
        'aktif'         => 'boolean',
        'komisi_persen' => 'float',
        'kuli_per_peti' => 'float',
    ];

    public static function generateKode(): string
    {
        $prefix = 'SUP-';
        $last = self::where('kode_supplier', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_supplier, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function itemTransaksi(): HasMany
    {
        return $this->hasMany(ItemTransaksi::class, 'supplier_id');
    }

    public function barangDatang(): HasMany
    {
        return $this->hasMany(BarangDatang::class, 'supplier_id');
    }

    public function rekap(): HasMany
    {
        return $this->hasMany(Rekap::class, 'supplier_id');
    }

    public function produk(): HasMany
    {
        return $this->hasMany(Produk::class, 'supplier_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
