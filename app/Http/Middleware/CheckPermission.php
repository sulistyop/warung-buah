<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Middleware: cek permission user terhadap modul & aksi.
     *
     * Penggunaan di route:
     *   ->middleware('permission:transaksi,create')
     *   ->middleware('permission:supplier,read')
     *
     * Admin selalu lolos. User lain dicek dari kolom permissions (JSON).
     */
    public function handle(Request $request, Closure $next, string $modul, string $aksi): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        if (!$user->hasPermission($modul, $aksi)) {
            return response()->json([
                'success' => false,
                'message' => "Akses ditolak. Anda tidak punya izin '{$aksi}' pada modul '{$modul}'.",
            ], 403);
        }

        return $next($request);
    }
}
