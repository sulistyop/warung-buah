<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaksi;
use App\Models\Pembayaran;
use App\Models\Pelanggan;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class PiutangController extends Controller
{
    /**
     * List semua piutang (transaksi belum lunas)
     */
    #[OA\Get(
        path: '/piutang',
        summary: 'List piutang (transaksi belum lunas)',
        tags: ['Piutang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'pelanggan_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'nama_pelanggan', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'jatuh_tempo', in: 'query', description: 'Filter: overdue', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request)
    {
        $query = Transaksi::with(['user', 'pelanggan'])
            ->whereIn('status_bayar', ['tempo', 'cicil'])
            ->where('sisa_tagihan', '>', 0);

        if ($request->filled('pelanggan_id')) {
            $query->where('pelanggan_id', $request->pelanggan_id);
        }

        if ($request->filled('nama_pelanggan')) {
            $query->where('nama_pelanggan', 'like', '%' . $request->nama_pelanggan . '%');
        }

        if ($request->boolean('jatuh_tempo')) {
            $query->where('tanggal_jatuh_tempo', '<', now())
                  ->whereNotNull('tanggal_jatuh_tempo');
        }

        $data = $query->orderBy('created_at')->paginate($request->input('per_page', 20));

        $summary = [
            'total_piutang'       => Transaksi::whereIn('status_bayar', ['tempo', 'cicil'])->sum('sisa_tagihan'),
            'total_transaksi_open'=> Transaksi::whereIn('status_bayar', ['tempo', 'cicil'])->count(),
        ];

        return $this->success(['data' => $data, 'summary' => $summary]);
    }

    /**
     * Bayar piutang: satu pembayaran bisa melunasi beberapa transaksi (FIFO).
     * Contoh: input 15jt → lunasi transaksi 6jt, lanjut kurangi 3jt dari transaksi berikutnya, dst.
     */
    #[OA\Post(
        path: '/piutang/bayar',
        summary: 'Bayar piutang - satu input bisa bayar beberapa transaksi (FIFO)',
        description: 'Input jumlah bayar, sistem akan melunasi dari transaksi terlama terlebih dahulu.',
        tags: ['Piutang'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['jumlah'],
                properties: [
                    new OA\Property(property: 'pelanggan_id', type: 'integer', description: 'Jika pelanggan terdaftar'),
                    new OA\Property(property: 'nama_pelanggan', type: 'string', description: 'Jika pelanggan belum terdaftar (free text)'),
                    new OA\Property(property: 'jumlah', type: 'number', example: 15000000, description: 'Total uang yang dibayarkan'),
                    new OA\Property(property: 'metode', type: 'string', enum: ['tunai', 'transfer', 'qris', 'deposit'], example: 'tunai'),
                    new OA\Property(property: 'referensi', type: 'string'),
                    new OA\Property(property: 'catatan', type: 'string'),
                    new OA\Property(
                        property: 'transaksi_ids',
                        type: 'array',
                        description: 'Opsional: ID transaksi spesifik yang ingin dibayar. Jika kosong, FIFO.',
                        items: new OA\Items(type: 'integer')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Pembayaran berhasil'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function bayar(Request $request)
    {
        $validated = $request->validate([
            'pelanggan_id'    => 'nullable|exists:pelanggan,id',
            'nama_pelanggan'  => 'required_without:pelanggan_id|nullable|string',
            'jumlah'          => 'required|numeric|min:1',
            'metode'          => 'nullable|in:tunai,transfer,qris,deposit',
            'referensi'       => 'nullable|string',
            'catatan'         => 'nullable|string',
            'transaksi_ids'   => 'nullable|array',
            'transaksi_ids.*' => 'integer|exists:transaksi,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $sisaBayar = (float) $validated['jumlah'];
            $detailPembayaran = [];

            // Ambil transaksi yang akan dibayar
            $transaksiQuery = Transaksi::whereIn('status_bayar', ['tempo', 'cicil'])
                ->where('sisa_tagihan', '>', 0)
                ->orderBy('created_at'); // FIFO

            if (!empty($validated['pelanggan_id'])) {
                $transaksiQuery->where('pelanggan_id', $validated['pelanggan_id']);
            } elseif (!empty($validated['nama_pelanggan'])) {
                $transaksiQuery->where('nama_pelanggan', $validated['nama_pelanggan']);
            }

            if (!empty($validated['transaksi_ids'])) {
                $transaksiQuery->whereIn('id', $validated['transaksi_ids']);
            }

            $transaksiList = $transaksiQuery->get();

            if ($transaksiList->isEmpty()) {
                return $this->error('Tidak ada piutang ditemukan untuk pelanggan ini.', 422);
            }

            foreach ($transaksiList as $trx) {
                if ($sisaBayar <= 0) break;

                $bayarUntukIni = min($sisaBayar, $trx->sisa_tagihan);

                $pembayaran = Pembayaran::create([
                    'transaksi_id'    => $trx->id,
                    'kode_pembayaran' => Pembayaran::generateKode(),
                    'nominal'         => $bayarUntukIni,
                    'metode'          => $validated['metode'] ?? 'tunai',
                    'referensi'       => $validated['referensi'] ?? null,
                    'catatan'         => $validated['catatan'] ?? null,
                    'user_id'         => auth()->id(),
                ]);

                $trx->recalculate();
                $trx->refresh();

                $sisaBayar -= $bayarUntukIni;

                $detailPembayaran[] = [
                    'transaksi_id'   => $trx->id,
                    'kode_transaksi' => $trx->kode_transaksi,
                    'nama_pelanggan' => $trx->nama_pelanggan,
                    'dibayar'        => $bayarUntukIni,
                    'sisa_tagihan'   => $trx->sisa_tagihan,
                    'status_bayar'   => $trx->status_bayar,
                ];
            }

            $totalDibayar = $validated['jumlah'] - $sisaBayar;
            LogAktivitas::catat('piutang', 'bayar', "Bayar piutang Rp " . number_format($totalDibayar));

            return $this->success([
                'total_dibayar'     => $totalDibayar,
                'kembalian'         => $sisaBayar > 0 ? $sisaBayar : 0, // jika bayar lebih dari total piutang
                'detail_pembayaran' => $detailPembayaran,
            ], 'Pembayaran piutang berhasil.');
        });
    }

    /**
     * Rekap piutang per pelanggan
     */
    #[OA\Get(
        path: '/piutang/rekap-pelanggan',
        summary: 'Rekap total piutang per pelanggan',
        tags: ['Piutang'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function rekapPelanggan(Request $request)
    {
        $data = Transaksi::selectRaw('
                nama_pelanggan,
                pelanggan_id,
                COUNT(*) as jumlah_transaksi,
                SUM(total_tagihan) as total_tagihan,
                SUM(total_dibayar) as total_dibayar,
                SUM(sisa_tagihan) as total_sisa,
                MIN(tanggal_jatuh_tempo) as jatuh_tempo_terdekat
            ')
            ->whereIn('status_bayar', ['tempo', 'cicil'])
            ->where('sisa_tagihan', '>', 0)
            ->groupBy('nama_pelanggan', 'pelanggan_id')
            ->orderByDesc('total_sisa')
            ->get();

        return $this->success($data);
    }
}
