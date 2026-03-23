<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\DetailBarangDatang;

class ItemTransaksi extends Model
{
    protected $table = 'item_transaksi';

    protected $fillable = [
        'transaksi_id',
        'supplier_id',
        'detail_barang_datang_id',
        'nama_supplier',
        'jenis_buah',
       // 'ukuran',
        'harga_per_kg',
        'jumlah_peti',
        'total_berat_bersih',
        'subtotal',
    ];

    protected $casts = [
        'harga_per_kg'       => 'float',
        'total_berat_bersih' => 'float',
        'subtotal'           => 'float',
    ];

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function detailPeti(): HasMany
    {
        return $this->hasMany(DetailPeti::class, 'item_transaksi_id');
    }

    public function detailBarangDatang(): BelongsTo
    {
        return $this->belongsTo(DetailBarangDatang::class, 'detail_barang_datang_id');
    }

    /**
     * Hitung ulang jumlah peti, total berat bersih, subtotal
     */
    public function recalculate(): void
    {
        $peti = $this->detailPeti;
        $totalBerat = $peti->sum('berat_bersih');
        $subtotal   = $totalBerat * $this->harga_per_kg;

        $this->update([
            'jumlah_peti'        => $peti->count(),
            'total_berat_bersih' => $totalBerat,
            'subtotal'           => $subtotal,
        ]);
    }
}
