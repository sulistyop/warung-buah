<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailRekap extends Model
{
    protected $table = 'detail_rekap';

    protected $fillable = [
        'rekap_id',
        'nama_produk',
        'ukuran',
        'jumlah_peti',
        'total_berat_kotor',
        'total_berat_peti',
        'total_berat_bersih',
        'harga_per_kg',
        'subtotal',
    ];

    protected $casts = [
        'jumlah_peti'        => 'integer',
        'total_berat_kotor'  => 'float',
        'total_berat_peti'   => 'float',
        'total_berat_bersih' => 'float',
        'harga_per_kg'       => 'float',
        'subtotal'           => 'float',
    ];

    public function rekap(): BelongsTo
    {
        return $this->belongsTo(Rekap::class, 'rekap_id');
    }
}
