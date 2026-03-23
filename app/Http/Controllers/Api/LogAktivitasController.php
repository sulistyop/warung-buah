<?php

namespace App\Http\Controllers\Api;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LogAktivitasController extends Controller
{
    #[OA\Get(
        path: '/log-aktivitas',
        summary: 'Log aktivitas sistem (admin only)',
        tags: ['Log Aktivitas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'modul', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'aksi', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal_dari', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return $this->error('Unauthorized', 403);
        }

        $query = LogAktivitas::with('user');

        if ($request->filled('modul')) {
            $query->where('modul', $request->modul);
        }
        if ($request->filled('aksi')) {
            $query->where('aksi', $request->aksi);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }

        $log = $query->orderByDesc('created_at')->paginate($request->input('per_page', 50));

        return $this->success($log);
    }
}
