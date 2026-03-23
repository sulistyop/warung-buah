<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangDatang extends Model
{
    protected $table = 'barang_datang';

    protected $fillable = [
        'kode_bd',
        'supplier_id',
        'tanggal',
        'urutan_hari',
        'catatan',
        'status',
        'dikonfirmasi_at',
        'dikonfirmasi_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'dikonfirmasi_at' => 'datetime',
        'urutan_hari' => 'integer',
    ];

    /**
     * Generate kode barang datang: BD-YYYYMMDD-NNNN (running sequence)
     */
    public static function generateKode(string $tanggal): string
    {
        $prefix = 'BD-' . str_replace('-', '', $tanggal) . '-';
        $last = self::where('kode_bd', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_bd, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung urutan kiriman hari ini dari supplier ini
     */
    public static function hitungUrutanHari(int $supplierId, string $tanggal): int
    {
        return self::where('supplier_id', $supplierId)
            ->where('tanggal', $tanggal)
            ->count() + 1;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function dikonfirmasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonfirmasi_oleh');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailBarangDatang::class, 'barang_datang_id');
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed';
    }
}
