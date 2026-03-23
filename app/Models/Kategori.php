<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    protected $table = 'kategori';

    protected $fillable = [
        'kode_kategori',
        'nama_kategori',
        'deskripsi',
        'warna',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
    ];

    // Generate kode otomatis
    public static function generateKode(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $number = $last ? ((int) substr($last->kode_kategori, 3)) + 1 : 1;
        return 'KAT' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    // Scope aktif
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    // Get warna options
    public static function getWarnaOptions(): array
    {
        return [
            '#4CAF50' => 'Hijau',
            '#2196F3' => 'Biru',
            '#FF9800' => 'Orange',
            '#F44336' => 'Merah',
            '#9C27B0' => 'Ungu',
            '#00BCD4' => 'Cyan',
            '#795548' => 'Coklat',
            '#607D8B' => 'Abu-abu',
        ];
    }
}
