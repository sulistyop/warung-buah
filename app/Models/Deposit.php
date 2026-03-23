<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    protected $table = 'deposit';

    protected $fillable = [
        'kode_deposit',
        'pelanggan_id',
        'nominal',
        'terpakai',
        'sisa',
        'metode',
        'referensi',
        'catatan',
        'user_id',
    ];

    protected $casts = [
        'nominal'  => 'float',
        'terpakai' => 'float',
        'sisa'     => 'float',
    ];

    public static function generateKode(): string
    {
        $prefix = 'DEP-' . date('Ymd') . '-';
        $last = self::where('kode_deposit', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_deposit, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Gunakan deposit untuk bayar sebesar $jumlah.
     * Return jumlah yang berhasil dipakai.
     */
    public function gunakan(float $jumlah): float
    {
        $dipakai = min($this->sisa, $jumlah);
        $this->update([
            'terpakai' => $this->terpakai + $dipakai,
            'sisa'     => $this->sisa - $dipakai,
        ]);
        return $dipakai;
    }
}
