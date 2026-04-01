<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KomplainTransaksi extends Model
{
    protected $table = 'komplain_transaksi';

    protected $fillable = [
        'transaksi_id',
        'item_transaksi_id',
        'nama_produk',
        'jumlah_bs',
        'harga_ganti',
        'total',
        'keterangan',
    ];

    protected $casts = [
        'item_transaksi_id' => 'integer',
        'jumlah_bs'         => 'float',
        'harga_ganti'       => 'float',
        'total'             => 'float',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function itemTransaksi(): BelongsTo
    {
        return $this->belongsTo(ItemTransaksi::class);
    }
}
