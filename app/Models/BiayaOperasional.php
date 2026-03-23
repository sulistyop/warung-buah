<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiayaOperasional extends Model
{
    protected $table = 'biaya_operasional';

    protected $fillable = [
        'transaksi_id',
        'nama_biaya',
        'nominal',
    ];

    protected $casts = [
        'nominal' => 'float',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }
}
