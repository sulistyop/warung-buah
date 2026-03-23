<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KomplainRekap extends Model
{
    protected $table = 'komplain_rekap';

    protected $fillable = [
        'rekap_id',
        'nama_produk',
        'jumlah_bs',
        'harga_ganti',
        'total',
        'keterangan',
    ];

    protected $casts = [
        'jumlah_bs'   => 'integer',
        'harga_ganti' => 'float',
        'total'       => 'float',
    ];

    public function rekap(): BelongsTo
    {
        return $this->belongsTo(Rekap::class, 'rekap_id');
    }
}
