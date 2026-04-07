<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengurangRekap extends Model
{
    protected $table = 'pengurang_rekap';

    protected $fillable = [
        'rekap_id',
        'nama',
        'jumlah',
    ];

    protected $casts = [
        'jumlah' => 'float',
    ];

    public function rekap(): BelongsTo
    {
        return $this->belongsTo(Rekap::class, 'rekap_id');
    }
}
