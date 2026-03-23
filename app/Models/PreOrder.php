<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreOrder extends Model
{
    protected $table = 'pre_order';

    protected $fillable = [
        'kode_po',
        'pelanggan_id',
        'nama_pelanggan',
        'tanggal_po',
        'tanggal_kirim',
        'total',
        'status',
        'transaksi_id',
        'catatan',
        'user_id',
    ];

    protected $casts = [
        'tanggal_po'    => 'date',
        'tanggal_kirim' => 'date',
        'total'         => 'float',
    ];

    public static function generateKode(): string
    {
        $prefix = 'PO-' . date('Ymd') . '-';
        $last = self::where('kode_po', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_po, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailPreOrder::class, 'pre_order_id');
    }
}
