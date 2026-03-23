<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPeti extends Model
{
    protected $table = 'detail_peti';

    protected $fillable = [
        'item_transaksi_id',
        'no_peti',
        'berat_kotor',
        'berat_kemasan',
        'berat_bersih',
    ];

    protected $casts = [
        'berat_kotor'   => 'float',
        'berat_kemasan' => 'float',
        'berat_bersih'  => 'float',
    ];

    public function itemTransaksi(): BelongsTo
    {
        return $this->belongsTo(ItemTransaksi::class);
    }

    // Override save: hitung berat_bersih sebelum simpan
    protected static function booted(): void
    {
        static::saving(function (DetailPeti $peti) {
            $peti->berat_bersih = $peti->berat_kotor - $peti->berat_kemasan;
        });
    }
}
