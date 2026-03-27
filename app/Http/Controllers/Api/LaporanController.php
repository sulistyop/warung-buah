<?php

namespace App\Http\Controllers\Api;

use App\Exports\LaporanPenjualanExport;
use App\Exports\LaporanPenjualanPerItemExport;
use App\Exports\LaporanRekapSupplierExport;
use App\Exports\LaporanPiutangExport;
use App\Exports\LaporanKasLaciExport;
use App\Exports\LaporanStokMasukExport;
use App\Exports\LaporanPelangganTerbaikExport;
use App\Models\ItemTransaksi;
use App\Models\Transaksi;
use App\Models\Rekap;
use App\Models\KasLaci;
use App\Models\BarangDatang;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

class LaporanController extends Controller
{
    // ─── 1. Laporan Penjualan ────────────────────────────────────────────────

    #[OA\Get(
        path: '/laporan/penjualan',
        summary: 'Laporan penjualan',
        description: 'Ringkasan penjualan: total transaksi, omset, status bayar, dan rincian per hari',
        operationId: 'getLaporanPenjualan',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-03-31')),
            new OA\Parameter(name: 'status_bayar', in: 'query', description: 'Filter status bayar', schema: new OA\Schema(type: 'string', enum: ['lunas', 'tempo', 'cicil'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'summary',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_transaksi', type: 'integer', example: 25),
                                        new OA\Property(property: 'total_omset', type: 'number', example: 50000000),
                                        new OA\Property(property: 'total_dibayar', type: 'number', example: 40000000),
                                        new OA\Property(property: 'total_piutang', type: 'number', example: 10000000),
                                        new OA\Property(property: 'jumlah_lunas', type: 'integer', example: 18),
                                        new OA\Property(property: 'jumlah_tempo', type: 'integer', example: 5),
                                        new OA\Property(property: 'jumlah_cicil', type: 'integer', example: 2),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'per_hari',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'tanggal', type: 'string', example: '2026-03-24'),
                                            new OA\Property(property: 'jumlah', type: 'integer', example: 5),
                                            new OA\Property(property: 'total_omset', type: 'number', example: 10000000),
                                            new OA\Property(property: 'total_dibayar', type: 'number', example: 8000000),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Transaksi')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation Error'),
        ]
    )]
    public function penjualan(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'status_bayar'   => 'nullable|in:lunas,tempo,cicil',
        ]);

        $query = Transaksi::with('user')->orderByDesc('created_at');
        $this->applyDateFilter($query, $request, 'created_at');
        if ($request->filled('status_bayar')) {
            $query->where('status_bayar', $request->status_bayar);
        }

        $transaksi = $query->get();

        $summary = [
            'total_transaksi'  => $transaksi->count(),
            'total_omset'      => $transaksi->sum('total_tagihan'),
            'total_dibayar'    => $transaksi->sum('total_dibayar'),
            'total_piutang'    => $transaksi->sum('sisa_tagihan'),
            'jumlah_lunas'     => $transaksi->where('status_bayar', 'lunas')->count(),
            'jumlah_tempo'     => $transaksi->where('status_bayar', 'tempo')->count(),
            'jumlah_cicil'     => $transaksi->where('status_bayar', 'cicil')->count(),
        ];

        // Grouping per tanggal
        $perHari = $transaksi->groupBy(fn($t) => $t->created_at->format('Y-m-d'))
            ->map(fn($group, $tgl) => [
                'tanggal'      => $tgl,
                'jumlah'       => $group->count(),
                'total_omset'  => $group->sum('total_tagihan'),
                'total_dibayar'=> $group->sum('total_dibayar'),
            ])->values();

        return $this->success([
            'summary'   => $summary,
            'per_hari'  => $perHari,
            'data'      => $transaksi,
        ]);
    }

    #[OA\Get(
        path: '/laporan/penjualan/export',
        summary: 'Export laporan penjualan ke Excel',
        description: 'Download file Excel laporan penjualan berdasarkan filter tanggal',
        operationId: 'exportLaporanPenjualan',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-03-31')),
            new OA\Parameter(name: 'status_bayar', in: 'query', description: 'Filter status bayar', schema: new OA\Schema(type: 'string', enum: ['lunas', 'tempo', 'cicil'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File Excel berhasil diunduh',
                headers: [
                    'Content-Disposition' => new OA\Header(header: 'Content-Disposition', description: 'attachment; filename="laporan-penjualan-YYYYMMDD-HHiiss.xlsx"', schema: new OA\Schema(type: 'string')),
                    'Content-Type' => new OA\Header(header: 'Content-Type', description: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string')),
                ],
                content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function exportPenjualan(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'status_bayar'   => 'nullable|in:lunas,tempo,cicil',
        ]);

        $filename = 'laporan-penjualan-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanPenjualanExport(
                $request->tanggal_dari,
                $request->tanggal_sampai,
                $request->status_bayar,
            ),
            $filename
        );
    }

    // ─── 2. Laporan Rekap Supplier ───────────────────────────────────────────

    #[OA\Get(
        path: '/laporan/rekap-supplier',
        summary: 'Laporan rekap supplier',
        description: 'Ringkasan rekap supplier: total rekap, peti, pendapatan bersih, sisa, per supplier',
        operationId: 'getLaporanRekapSupplier',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-03-31')),
            new OA\Parameter(name: 'supplier_id', in: 'query', description: 'Filter supplier tertentu', schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter status rekap', schema: new OA\Schema(type: 'string', enum: ['draft', 'final'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'summary',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_rekap', type: 'integer', example: 10),
                                        new OA\Property(property: 'total_peti', type: 'integer', example: 200),
                                        new OA\Property(property: 'total_kotor', type: 'number', example: 80000000),
                                        new OA\Property(property: 'total_komisi', type: 'number', example: 5600000),
                                        new OA\Property(property: 'total_kuli', type: 'number', example: 1000000),
                                        new OA\Property(property: 'total_ongkos', type: 'number', example: 500000),
                                        new OA\Property(property: 'total_busuk', type: 'number', example: 200000),
                                        new OA\Property(property: 'total_pendapatan_bersih', type: 'number', example: 72700000),
                                        new OA\Property(property: 'total_sisa', type: 'number', example: 7300000),
                                        new OA\Property(property: 'jumlah_draft', type: 'integer', example: 2),
                                        new OA\Property(property: 'jumlah_final', type: 'integer', example: 8),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'per_supplier',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'supplier', type: 'string', example: 'PT Buah Segar'),
                                            new OA\Property(property: 'jumlah_rekap', type: 'integer', example: 3),
                                            new OA\Property(property: 'total_peti', type: 'integer', example: 60),
                                            new OA\Property(property: 'total_sisa', type: 'number', example: 2500000),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', description: 'Data rekap supplier')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function rekapSupplier(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'supplier_id'    => 'nullable|integer',
            'status'         => 'nullable|in:draft,final',
        ]);

        $query = Rekap::with(['supplier', 'dibuatOleh'])->orderBy('tanggal');
        $this->applyDateFilter($query, $request, 'tanggal');
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rekap = $query->get();

        $summary = [
            'total_rekap'       => $rekap->count(),
            'total_peti'        => $rekap->sum('total_peti'),
            'total_kotor'       => $rekap->sum('total_kotor'),
            'total_komisi'      => $rekap->sum('total_komisi'),
            'total_kuli'        => $rekap->sum('total_kuli'),
            'total_ongkos'      => $rekap->sum('total_ongkos'),
            'total_busuk'       => $rekap->sum('total_busuk'),
            'total_pendapatan_bersih' => $rekap->sum('pendapatan_bersih'),
            'total_sisa'        => $rekap->sum('sisa'),
            'jumlah_draft'      => $rekap->where('status', 'draft')->count(),
            'jumlah_final'      => $rekap->where('status', 'final')->count(),
        ];

        // Group per supplier
        $perSupplier = $rekap->groupBy('supplier_id')
            ->map(fn($group) => [
                'supplier'          => $group->first()->supplier->nama_supplier ?? '-',
                'jumlah_rekap'      => $group->count(),
                'total_peti'        => $group->sum('total_peti'),
                'total_sisa'        => $group->sum('sisa'),
            ])->values();

        return $this->success([
            'summary'      => $summary,
            'per_supplier' => $perSupplier,
            'data'         => $rekap,
        ]);
    }

    #[OA\Get(
        path: '/laporan/rekap-supplier/export',
        summary: 'Export laporan rekap supplier ke Excel',
        description: 'Download file Excel laporan rekap supplier',
        operationId: 'exportLaporanRekapSupplier',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'supplier_id', in: 'query', description: 'Filter supplier tertentu', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter status rekap', schema: new OA\Schema(type: 'string', enum: ['draft', 'final'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File Excel berhasil diunduh',
                content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function exportRekapSupplier(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'supplier_id'    => 'nullable|integer',
            'status'         => 'nullable|in:draft,final',
        ]);

        $filename = 'laporan-rekap-supplier-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanRekapSupplierExport(
                $request->tanggal_dari,
                $request->tanggal_sampai,
                $request->supplier_id ? (int) $request->supplier_id : null,
                $request->status,
            ),
            $filename
        );
    }

    // ─── 3. Laporan Piutang ──────────────────────────────────────────────────

    #[OA\Get(
        path: '/laporan/piutang',
        summary: 'Laporan piutang',
        description: 'Ringkasan piutang aktif (tempo/cicil belum lunas) dengan analisis aging (0-30, 31-60, 61-90, >90 hari)',
        operationId: 'getLaporanPiutang',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal transaksi mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal transaksi akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-03-31')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'summary',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_piutang', type: 'number', example: 15000000),
                                        new OA\Property(property: 'jumlah_pelanggan', type: 'integer', example: 8),
                                        new OA\Property(property: 'jumlah_transaksi', type: 'integer', example: 12),
                                        new OA\Property(property: 'overdue', type: 'number', example: 3000000),
                                        new OA\Property(
                                            property: 'aging',
                                            type: 'object',
                                            properties: [
                                                new OA\Property(property: '0-30', type: 'number', example: 8000000),
                                                new OA\Property(property: '31-60', type: 'number', example: 4000000),
                                                new OA\Property(property: '61-90', type: 'number', example: 2000000),
                                                new OA\Property(property: '>90', type: 'number', example: 1000000),
                                            ]
                                        ),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        allOf: [
                                            new OA\Schema(ref: '#/components/schemas/Transaksi'),
                                            new OA\Schema(
                                                properties: [
                                                    new OA\Property(property: 'umur_hari', type: 'integer', example: 15),
                                                    new OA\Property(property: 'is_overdue', type: 'boolean', example: false),
                                                ]
                                            ),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function piutang(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $query = Transaksi::whereIn('status_bayar', ['tempo', 'cicil'])
            ->where('sisa_tagihan', '>', 0)
            ->orderBy('tanggal_jatuh_tempo');

        $this->applyDateFilter($query, $request, 'created_at');

        $today = Carbon::today();
        $transaksi = $query->get();

        $agingBrackets = ['0-30' => 0, '31-60' => 0, '61-90' => 0, '>90' => 0];
        foreach ($transaksi as $t) {
            $umur = $today->diffInDays($t->created_at->startOfDay(), false);
            if ($umur <= 30) {
                $agingBrackets['0-30'] += $t->sisa_tagihan;
            } elseif ($umur <= 60) {
                $agingBrackets['31-60'] += $t->sisa_tagihan;
            } elseif ($umur <= 90) {
                $agingBrackets['61-90'] += $t->sisa_tagihan;
            } else {
                $agingBrackets['>90'] += $t->sisa_tagihan;
            }
        }

        $summary = [
            'total_piutang'     => $transaksi->sum('sisa_tagihan'),
            'jumlah_pelanggan'  => $transaksi->pluck('nama_pelanggan')->unique()->count(),
            'jumlah_transaksi'  => $transaksi->count(),
            'overdue'           => $transaksi->filter(fn($t) => $t->isJatuhTempo())->sum('sisa_tagihan'),
            'aging'             => $agingBrackets,
        ];

        $data = $transaksi->map(function ($t) use ($today) {
            $umur = $today->diffInDays($t->created_at->startOfDay(), false);
            return array_merge($t->toArray(), [
                'umur_hari'   => $umur,
                'is_overdue'  => $t->isJatuhTempo(),
            ]);
        });

        return $this->success([
            'summary' => $summary,
            'data'    => $data,
        ]);
    }

    #[OA\Get(
        path: '/laporan/piutang/export',
        summary: 'Export laporan piutang ke Excel',
        description: 'Download file Excel laporan piutang aktif',
        operationId: 'exportLaporanPiutang',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File Excel berhasil diunduh',
                content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function exportPiutang(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $filename = 'laporan-piutang-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanPiutangExport($request->tanggal_dari, $request->tanggal_sampai),
            $filename
        );
    }

    // ─── 4. Laporan Kas Laci ─────────────────────────────────────────────────

    #[OA\Get(
        path: '/laporan/kas-laci',
        summary: 'Laporan kas laci',
        description: 'Ringkasan kas laci: total masuk, total keluar, saldo periode, rincian per hari',
        operationId: 'getLaporanKasLaci',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-03-31')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'summary',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_masuk', type: 'number', example: 30000000),
                                        new OA\Property(property: 'total_keluar', type: 'number', example: 10000000),
                                        new OA\Property(property: 'saldo_periode', type: 'number', example: 20000000),
                                        new OA\Property(property: 'total_entri', type: 'integer', example: 45),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'per_hari',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'tanggal', type: 'string', example: '2026-03-24'),
                                            new OA\Property(property: 'total_masuk', type: 'number', example: 2000000),
                                            new OA\Property(property: 'total_keluar', type: 'number', example: 500000),
                                        ]
                                    )
                                ),
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object', description: 'Data kas laci')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function kasLaci(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $query = KasLaci::with('dibuatOleh')->orderBy('tanggal')->orderBy('id');
        $this->applyDateFilter($query, $request, 'tanggal');

        $entries = $query->get();

        $summary = [
            'total_masuk'   => $entries->where('jenis', 'masuk')->sum('nominal'),
            'total_keluar'  => $entries->where('jenis', 'keluar')->sum('nominal'),
            'saldo_periode' => $entries->where('jenis', 'masuk')->sum('nominal') - $entries->where('jenis', 'keluar')->sum('nominal'),
            'total_entri'   => $entries->count(),
        ];

        // Per hari
        $perHari = $entries->groupBy(fn($k) => $k->tanggal)
            ->map(fn($group, $tgl) => [
                'tanggal'      => $tgl,
                'total_masuk'  => $group->where('jenis', 'masuk')->sum('nominal'),
                'total_keluar' => $group->where('jenis', 'keluar')->sum('nominal'),
            ])->values();

        return $this->success([
            'summary'  => $summary,
            'per_hari' => $perHari,
            'data'     => $entries,
        ]);
    }

    #[OA\Get(
        path: '/laporan/kas-laci/export',
        summary: 'Export laporan kas laci ke Excel',
        description: 'Download file Excel laporan kas laci',
        operationId: 'exportLaporanKasLaci',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File Excel berhasil diunduh',
                content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function exportKasLaci(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $filename = 'laporan-kas-laci-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanKasLaciExport($request->tanggal_dari, $request->tanggal_sampai),
            $filename
        );
    }

    // ─── 5. Laporan Stok Masuk ───────────────────────────────────────────────

    #[OA\Get(
        path: '/laporan/stok-masuk',
        summary: 'Laporan stok masuk',
        description: 'Ringkasan stok masuk dari barang datang: total pengiriman, produk, stok masuk, terjual, dan sisa',
        operationId: 'getLaporanStokMasuk',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-03-31')),
            new OA\Parameter(name: 'supplier_id', in: 'query', description: 'Filter supplier tertentu', schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'summary',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_bd', type: 'integer', example: 15, description: 'Total barang datang (pengiriman)'),
                                        new OA\Property(property: 'total_produk', type: 'integer', example: 45, description: 'Total item produk'),
                                        new OA\Property(property: 'total_stok_masuk', type: 'integer', example: 300, description: 'Total peti masuk'),
                                        new OA\Property(property: 'total_terjual', type: 'integer', example: 250, description: 'Total peti terjual'),
                                        new OA\Property(property: 'total_sisa', type: 'integer', example: 50, description: 'Total peti sisa'),
                                    ]
                                ),
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BarangDatang')),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function stokMasuk(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'supplier_id'    => 'nullable|integer',
        ]);

        $query = BarangDatang::with(['supplier', 'details'])->orderBy('tanggal');
        $this->applyDateFilter($query, $request, 'tanggal');
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $barangDatang = $query->get();

        $allDetails = $barangDatang->flatMap->details;
        $summary = [
            'total_bd'         => $barangDatang->count(),
            'total_produk'     => $allDetails->count(),
            'total_stok_masuk' => $allDetails->sum('jumlah'),
            'total_terjual'    => $allDetails->sum('stok_terjual'),
            'total_sisa'       => $allDetails->sum('stok_sisa'),
        ];

        return $this->success([
            'summary' => $summary,
            'data'    => $barangDatang,
        ]);
    }

    #[OA\Get(
        path: '/laporan/stok-masuk/export',
        summary: 'Export laporan stok masuk ke Excel',
        description: 'Download file Excel laporan stok masuk dari barang datang',
        operationId: 'exportLaporanStokMasuk',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'supplier_id', in: 'query', description: 'Filter supplier tertentu', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File Excel berhasil diunduh',
                content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function exportStokMasuk(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'supplier_id'    => 'nullable|integer',
        ]);

        $filename = 'laporan-stok-masuk-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanStokMasukExport(
                $request->tanggal_dari,
                $request->tanggal_sampai,
                $request->supplier_id ? (int) $request->supplier_id : null,
            ),
            $filename
        );
    }

    // ─── 6. Laporan Pelanggan Terbaik ────────────────────────────────────────

    #[OA\Get(
        path: '/laporan/pelanggan-terbaik',
        summary: 'Laporan pelanggan terbaik',
        description: 'Ranking pelanggan berdasarkan total omset dalam periode tertentu',
        operationId: 'getLaporanPelangganTerbaik',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-01-01')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date', example: '2026-03-31')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Jumlah pelanggan yang ditampilkan (5-100, default: 20)', schema: new OA\Schema(type: 'integer', minimum: 5, maximum: 100, default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'summary',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_pelanggan', type: 'integer', example: 20),
                                        new OA\Property(property: 'total_omset', type: 'number', example: 150000000),
                                        new OA\Property(property: 'top_pelanggan', type: 'string', example: 'Toko Buah Segar'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'ranking', type: 'integer', example: 1),
                                            new OA\Property(property: 'nama_pelanggan', type: 'string', example: 'Toko Buah Segar'),
                                            new OA\Property(property: 'total_transaksi', type: 'integer', example: 15),
                                            new OA\Property(property: 'total_omset', type: 'number', example: 25000000),
                                            new OA\Property(property: 'total_dibayar', type: 'number', example: 20000000),
                                            new OA\Property(property: 'total_piutang', type: 'number', example: 5000000),
                                            new OA\Property(property: 'transaksi_terakhir', type: 'string', format: 'date-time', example: '2026-03-25T10:00:00.000000Z'),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function pelangganTerbaik(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'limit'          => 'nullable|integer|min:5|max:100',
        ]);

        $limit = $request->input('limit', 20);

        $query = Transaksi::select(
            'nama_pelanggan',
            DB::raw('COUNT(*) as total_transaksi'),
            DB::raw('SUM(total_tagihan) as total_omset'),
            DB::raw('SUM(total_dibayar) as total_dibayar'),
            DB::raw('SUM(sisa_tagihan) as total_piutang'),
            DB::raw('MAX(created_at) as transaksi_terakhir')
        )
        ->groupBy('nama_pelanggan')
        ->orderByDesc('total_omset')
        ->limit($limit);

        $this->applyDateFilter($query, $request, 'created_at');

        $data = $query->get()->map(function ($row, $i) {
            return array_merge($row->toArray(), ['ranking' => $i + 1]);
        });

        $summary = [
            'total_pelanggan'  => $data->count(),
            'total_omset'      => $data->sum('total_omset'),
            'top_pelanggan'    => $data->first()?->nama_pelanggan ?? '-',
        ];

        return $this->success([
            'summary' => $summary,
            'data'    => $data,
        ]);
    }

    #[OA\Get(
        path: '/laporan/pelanggan-terbaik/export',
        summary: 'Export laporan pelanggan terbaik ke Excel',
        description: 'Download file Excel ranking pelanggan terbaik',
        operationId: 'exportLaporanPelangganTerbaik',
        tags: ['Laporan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File Excel berhasil diunduh',
                content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function exportPelangganTerbaik(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $filename = 'laporan-pelanggan-terbaik-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanPelangganTerbaikExport($request->tanggal_dari, $request->tanggal_sampai),
            $filename
        );
    }

    // ─── 7. Laporan Penjualan Per Item ───────────────────────────────────────

    public function penjualanPerItem(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'jenis_buah'     => 'nullable|string',
        ]);

        $query = ItemTransaksi::with([
            'transaksi' => function ($q) {
                $q->select('id', 'nama_pelanggan', 'created_at', 'status_bayar');
            },
            'detailBarangDatang:id,ukuran',
        ])->orderBy('created_at');

        // Filter tanggal dari created_at transaksi induk
        if ($request->filled('tanggal_dari')) {
            $query->whereHas('transaksi', fn($q) => $q->whereDate('created_at', '>=', $request->tanggal_dari));
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereHas('transaksi', fn($q) => $q->whereDate('created_at', '<=', $request->tanggal_sampai));
        }
        if ($request->filled('jenis_buah')) {
            $query->where('jenis_buah', 'like', '%' . $request->jenis_buah . '%');
        }

        $items = $query->get();

        $data = $items->map(fn($item) => [
            'tanggal'            => $item->transaksi?->created_at?->format('Y-m-d'),
            'nama_pelanggan'     => $item->transaksi?->nama_pelanggan ?? '-',
            'jenis_buah'         => $item->jenis_buah,
            'ukuran'             => $item->detailBarangDatang?->ukuran ?? '-',
            'jumlah_peti'        => $item->jumlah_peti,
            'total_berat_bersih' => $item->total_berat_bersih,
            'harga_per_kg'       => $item->harga_per_kg,
            'subtotal'           => $item->subtotal,
        ]);

        $summary = [
            'total_item'         => $data->count(),
            'total_peti'         => $data->sum('jumlah_peti'),
            'total_berat_bersih' => $data->sum('total_berat_bersih'),
            'total_omset'        => $data->sum('subtotal'),
        ];

        return $this->success([
            'summary' => $summary,
            'data'    => $data->values(),
        ]);
    }

    public function exportPenjualanPerItem(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'jenis_buah'     => 'nullable|string',
        ]);

        $filename = 'laporan-penjualan-per-item-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new LaporanPenjualanPerItemExport(
                $request->tanggal_dari,
                $request->tanggal_sampai,
                $request->jenis_buah,
            ),
            $filename
        );
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function applyDateFilter($query, Request $request, string $column): void
    {
        if ($request->filled('tanggal_dari')) {
            $query->whereDate($column, '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate($column, '<=', $request->tanggal_sampai);
        }
    }
}
