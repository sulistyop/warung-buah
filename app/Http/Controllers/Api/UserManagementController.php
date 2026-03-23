<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    private array $validModuls = [
        'transaksi', 'pembayaran', 'pelanggan', 'supplier', 'kategori',
        'barang_datang', 'rekap', 'pre_order', 'deposit', 'piutang',
        'kas_laci',
    ];

    private array $validAksis = ['create', 'read', 'update', 'delete'];

    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        $users = User::when($request->filled('cari'), function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->cari . '%')
              ->orWhere('email', 'like', '%' . $request->cari . '%');
        })
        ->orderBy('name')
        ->get(['id', 'name', 'email', 'role', 'aktif', 'permissions', 'last_login_at', 'created_at']);

        return $this->success($users);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8',
            'role'        => 'required|in:admin,kasir,operator',
            'permissions' => 'nullable|array',
        ]);

        $permissions = null;
        if ($validated['role'] !== 'admin' && !empty($validated['permissions'])) {
            $permissions = $this->sanitizePermissions($validated['permissions']);
        }

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'role'        => $validated['role'],
            'aktif'       => true,
            'permissions' => $permissions,
        ]);

        LogAktivitas::catat('user', 'create', "User {$user->name} ({$user->role}) dibuat");

        return $this->success(
            $this->formatUser($user),
            'User berhasil dibuat.',
            201
        );
    }

    public function update(Request $request, int $id)
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:100',
            'email'       => 'sometimes|email|unique:users,email,' . $id,
            'password'    => 'nullable|string|min:8',
            'role'        => 'sometimes|in:admin,kasir,operator',
            'aktif'       => 'sometimes|boolean',
            'permissions' => 'nullable|array',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Jika role admin, hapus permissions (admin = all access)
        $role = $validated['role'] ?? $user->role;
        if ($role === 'admin') {
            $validated['permissions'] = null;
        } elseif (array_key_exists('permissions', $validated)) {
            $validated['permissions'] = $validated['permissions']
                ? $this->sanitizePermissions($validated['permissions'])
                : null;
        }

        $user->update($validated);
        LogAktivitas::catat('user', 'update', "User {$user->name} diupdate");

        return $this->success($this->formatUser($user), 'User berhasil diupdate.');
    }

    public function destroy(int $id)
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        if (auth()->id() === $id) {
            return $this->error('Tidak bisa menonaktifkan akun sendiri.', 422);
        }

        $user = User::findOrFail($id);
        $user->update(['aktif' => false]);

        LogAktivitas::catat('user', 'deactivate', "User {$user->name} dinonaktifkan");

        return $this->success(null, 'User berhasil dinonaktifkan.');
    }

    /**
     * Bersihkan permissions: hanya izinkan modul & aksi yang valid.
     * Format input: {"transaksi":["create","read"], "supplier":["read"]}
     */
    private function sanitizePermissions(array $raw): array
    {
        $clean = [];
        foreach ($raw as $modul => $aksis) {
            if (!in_array($modul, $this->validModuls)) continue;
            if (!is_array($aksis)) continue;
            $filtered = array_values(array_intersect($aksis, $this->validAksis));
            if (!empty($filtered)) {
                $clean[$modul] = $filtered;
            }
        }
        return $clean;
    }

    private function formatUser(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->role,
            'aktif'       => $user->aktif,
            'permissions' => $user->isAdmin() ? null : ($user->permissions ?? []),
            'last_login_at' => $user->last_login_at,
            'created_at'  => $user->created_at,
        ];
    }
}
