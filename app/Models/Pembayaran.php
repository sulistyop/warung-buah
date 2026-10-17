<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use DateTimeInterface;

class Pembayaran extends Model
{
    protected $table = 'pembayaran';

    protected $fillable = [
        'transaksi_id',
        'kode_pembayaran',
        'nominal',
        'metode',
        'referensi',
        'catatan',
        'sisa_tagihan',
        'user_id',
    ];

    protected $casts = [
        'nominal' => 'float',
        'sisa_tagihan' => 'float',
    ];

    // Serialisasi tanggal ke WIB plain (tanpa Z) agar jam tampil benar di app,
    // konsisten dengan model Transaksi.
    protected function serializeDate(DateTimeInterface $date): string
    {
        return Carbon::instance($date)
            ->setTimezone(config('app.timezone', 'Asia/Jakarta'))
            ->format('Y-m-d H:i:s');
    }

    public static function generateKode(): string
    {
        $prefix = 'PAY-' . now()->format('Ymd') . '-';
        $last = self::where('kode_pembayaran', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        $seq = $last ? ((int) substr($last->kode_pembayaran, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getMetodeOptions(): array
    {
        return [
            'tunai' => 'Tunai',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'lainnya' => 'Lainnya',
        ];
    }
}
