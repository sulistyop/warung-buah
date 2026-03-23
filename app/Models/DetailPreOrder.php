<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPreOrder extends Model
{
    protected $table = 'detail_pre_order';

    protected $fillable = [
        'pre_order_id',
        'detail_barang_datang_id',
        'supplier_id',
        'nama_supplier',
        'nama_produk',
        'ukuran',
        'jumlah_peti',
        'harga_per_kg',
        'estimasi_berat_bersih',
        'subtotal',
    ];

    protected $casts = [
        'supplier_id'           => 'integer',
        'jumlah_peti'           => 'integer',
        'harga_per_kg'          => 'float',
        'estimasi_berat_bersih' => 'float',
        'subtotal'              => 'float',
    ];

    public function preOrder(): BelongsTo
    {
        return $this->belongsTo(PreOrder::class, 'pre_order_id');
    }

    public function stokBarang(): BelongsTo
    {
        return $this->belongsTo(DetailBarangDatang::class, 'detail_barang_datang_id');
    }
}
