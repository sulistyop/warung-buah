<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'aktif', 'permissions'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password'    => 'hashed',
        'aktif'       => 'boolean',
        'permissions' => 'array',
    ];

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Cek apakah user punya akses ke modul + aksi tertentu.
     * Admin selalu true. User lain cek dari kolom permissions.
     *
     * @param string $modul   e.g. 'transaksi', 'supplier'
     * @param string $aksi    e.g. 'create', 'read', 'update', 'delete'
     */
    public function hasPermission(string $modul, string $aksi): bool
    {
        if ($this->isAdmin()) return true;

        $permissions = $this->permissions ?? [];

        return in_array($aksi, $permissions[$modul] ?? []);
    }

    /**
     * Ambil semua permissions user dalam format array.
     * Admin dapat semua modul + semua aksi.
     */
    public function allPermissions(): array
    {
        if ($this->isAdmin()) return [];  // null = all access di sisi Flutter

        return $this->permissions ?? [];
    }
}
