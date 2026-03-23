<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'modul',
        'aksi',
        'model_type',
        'model_id',
        'deskripsi',
        'data_lama',
        'data_baru',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'data_lama'  => 'array',
        'data_baru'  => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(
        string $modul,
        string $aksi,
        string $deskripsi,
        ?Model $model = null,
        ?array $dataLama = null,
        ?array $dataBaru = null
    ): void {
        $request = app(\Illuminate\Http\Request::class);
        self::create([
            'user_id'    => auth()->id(),
            'modul'      => $modul,
            'aksi'       => $aksi,
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $model?->id,
            'deskripsi'  => $deskripsi,
            'data_lama'  => $dataLama,
            'data_baru'  => $dataBaru,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
