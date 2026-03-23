<?php

namespace App\Http\Controllers\Api;

use App\Models\KasLaci;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class PembayaranController extends Controller
{
    /**
     * Get all piutang (transaksi belum lunas)
     */
    #[OA\Get(
        path: '/pembayaran',
        summary: 'List piutang',
        description: 'Dapatkan daftar transaksi yang belum lunas (piutang)',
        operationId: 'getPiutang',
        tags: ['Pembayaran'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Jumlah per halaman', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'cari', in: 'query', description: 'Cari berdasarkan nama pelanggan/kode transaksi', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter status', schema: new OA\Schema(type: 'string', enum: ['jatuh_tempo'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Transaksi')),
                                new OA\Property(property: 'total', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = Transaksi::with('user')
            ->whereIn('status_bayar', ['tempo', 'cicil'])
            ->where('sisa_tagihan', '>', 0);

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_pelanggan', 'like', "%{$cari}%")
                  ->orWhere('kode_transaksi', 'like', "%{$cari}%");
            });
        }

        if ($request->status === 'jatuh_tempo') {
            $query->where('tanggal_jatuh_tempo', '<', now());
        }

        $perPage = $request->input('per_page', 20);
        $transaksi = $query->orderBy('tanggal_jatuh_tempo')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return $this->success($transaksi);
    }

    /**
     * Get pembayaran history for a transaksi
     */
    #[OA\Get(
        path: '/pembayaran/transaksi/{transaksi_id}',
        summary: 'Riwayat pembayaran transaksi',
        description: 'Dapatkan riwayat pembayaran untuk satu transaksi',
        operationId: 'getPembayaranTransaksi',
        tags: ['Pembayaran'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'transaksi_id', in: 'path', required: true, description: 'ID Transaksi', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'transaksi', ref: '#/components/schemas/Transaksi'),
                                new OA\Property(property: 'pembayaran', type: 'array', items: new OA\Items(ref: '#/components/schemas/Pembayaran')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Transaksi tidak ditemukan'),
        ]
    )]
    public function show(int $transaksi_id)
    {
        $transaksi = Transaksi::with(['pembayaran.user', 'itemTransaksi', 'user'])->find($transaksi_id);

        if (!$transaksi) {
            return $this->error('Transaksi tidak ditemukan', 404);
        }

        return $this->success([
            'transaksi' => $transaksi,
            'pembayaran' => $transaksi->pembayaran,
        ]);
    }

    /**
     * Get metode pembayaran options
     */
    #[OA\Get(
        path: '/pembayaran/metode-options',
        summary: 'Opsi metode pembayaran',
        description: 'Dapatkan daftar metode pembayaran yang tersedia',
        operationId: 'getMetodeOptions',
        tags: ['Pembayaran'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            example: ['tunai' => 'Tunai', 'transfer' => 'Transfer Bank', 'qris' => 'QRIS', 'lainnya' => 'Lainnya']
                        ),
                    ]
                )
            ),
        ]
    )]
    public function metodeOptions()
    {
        return $this->success(Pembayaran::getMetodeOptions());
    }

    /**
     * Create new pembayaran
     */
    #[OA\Post(
        path: '/pembayaran/transaksi/{transaksi_id}',
        summary: 'Catat pembayaran baru',
        description: 'Catat pembayaran baru untuk transaksi',
        operationId: 'createPembayaran',
        tags: ['Pembayaran'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'transaksi_id', in: 'path', required: true, description: 'ID Transaksi', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nominal', 'metode'],
                properties: [
                    new OA\Property(property: 'nominal', type: 'number', example: 500000),
                    new OA\Property(property: 'metode', type: 'string', enum: ['tunai', 'transfer', 'qris', 'lainnya'], example: 'transfer'),
                    new OA\Property(property: 'referensi', type: 'string', example: 'BCA-123456', nullable: true),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pembayaran berhasil dicatat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pembayaran berhasil dicatat'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Pembayaran'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Nominal melebihi sisa tagihan'),
            new OA\Response(response: 404, description: 'Transaksi tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request, int $transaksi_id)
    {
        $transaksi = Transaksi::find($transaksi_id);

        if (!$transaksi) {
            return $this->error('Transaksi tidak ditemukan', 404);
        }

        $request->validate([
            'nominal' => 'required|numeric|min:1',
            'metode' => 'required|in:tunai,transfer,qris,lainnya',
            'referensi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        if ($request->nominal > $transaksi->sisa_tagihan) {
            return $this->error('Nominal pembayaran melebihi sisa tagihan', 400);
        }

        DB::beginTransaction();
        try {
            $sisaSetelahBayar = $transaksi->sisa_tagihan - $request->nominal;

            $pembayaran = Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'kode_pembayaran' => Pembayaran::generateKode(),
                'nominal' => $request->nominal,
                'metode' => $request->metode,
                'referensi' => $request->referensi,
                'catatan' => $request->catatan,
                'sisa_tagihan' => max(0, $sisaSetelahBayar),
                'user_id' => auth()->id(),
            ]);

            if ($request->metode === 'tunai') {
                KasLaci::catatDariPembayaran($pembayaran);
            }

            // Recalculate transaksi
            $transaksi->recalculate();

            DB::commit();

            $pembayaran->load('user');

            return $this->success($pembayaran, 'Pembayaran berhasil dicatat', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal menyimpan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete pembayaran
     */
    #[OA\Delete(
        path: '/pembayaran/{id}',
        summary: 'Hapus pembayaran',
        description: 'Hapus catatan pembayaran',
        operationId: 'deletePembayaran',
        tags: ['Pembayaran'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Pembayaran', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pembayaran berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pembayaran berhasil dihapus'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Pembayaran tidak ditemukan'),
        ]
    )]
    public function destroy(int $id)
    {
        $pembayaran = Pembayaran::find($id);

        if (!$pembayaran) {
            return $this->error('Pembayaran tidak ditemukan', 404);
        }

        $transaksi = $pembayaran->transaksi;

        DB::beginTransaction();
        try {
            KasLaci::where('referensi_tipe', 'pembayaran')->where('referensi_id', $pembayaran->id)->delete();
            $pembayaran->delete();
            $transaksi->recalculate();

            // Update status bayar jika masih ada sisa
            if ($transaksi->sisa_tagihan > 0) {
                $newStatus = $transaksi->pembayaran()->count() > 0 ? 'cicil' : 'tempo';
                $transaksi->update(['status_bayar' => $newStatus]);
            }

            DB::commit();

            return $this->success(null, 'Pembayaran berhasil dihapus');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal menghapus: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get piutang summary/statistics
     */
    #[OA\Get(
        path: '/pembayaran/summary',
        summary: 'Ringkasan piutang',
        description: 'Dapatkan ringkasan total piutang',
        operationId: 'getPiutangSummary',
        tags: ['Pembayaran'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_piutang', type: 'number', example: 5000000),
                                new OA\Property(property: 'jumlah_transaksi', type: 'integer', example: 15),
                                new OA\Property(property: 'jatuh_tempo', type: 'integer', example: 5),
                                new OA\Property(property: 'belum_jatuh_tempo', type: 'integer', example: 10),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function summary(Request $request)
    {
        $query = Transaksi::whereIn('status_bayar', ['tempo', 'cicil'])
            ->where('sisa_tagihan', '>', 0);

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }

        $totalPiutang = (clone $query)->sum('sisa_tagihan');
        $jumlahTransaksi = (clone $query)->count();
        $jatuhTempo = (clone $query)->where('tanggal_jatuh_tempo', '<', now())->count();

        return $this->success([
            'total_piutang' => $totalPiutang,
            'jumlah_transaksi' => $jumlahTransaksi,
            'jatuh_tempo' => $jatuhTempo,
            'belum_jatuh_tempo' => $jumlahTransaksi - $jatuhTempo,
        ]);
    }
}
