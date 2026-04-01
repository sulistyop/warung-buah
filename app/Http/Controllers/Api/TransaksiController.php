<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Models\DetailPeti;
use App\Models\BiayaOperasional;
use App\Models\KasLaci;
use App\Models\Pembayaran;
use App\Models\Setting;
use App\Models\DetailBarangDatang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class TransaksiController extends Controller
{
    /**
     * Get all transaksi with filters
     */
    #[OA\Get(
        path: '/transaksi',
        summary: 'List semua transaksi',
        description: 'Dapatkan daftar transaksi dengan filter dan pagination',
        operationId: 'getTransaksi',
        tags: ['Transaksi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Jumlah per halaman', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'cari', in: 'query', description: 'Cari berdasarkan nama pelanggan atau kode transaksi', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status_bayar', in: 'query', description: 'Filter status bayar', schema: new OA\Schema(type: 'string', enum: ['lunas', 'tempo', 'cicil'])),
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal dari (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal sampai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
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
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request)
    {
        $query = Transaksi::with('user');

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_pelanggan', 'like', "%{$cari}%")
                  ->orWhere('kode_transaksi', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('status_bayar')) {
            $query->where('status_bayar', $request->status_bayar);
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }

        $perPage = $request->input('per_page', 20);
        $transaksi = $query->orderByDesc('created_at')->paginate($perPage);

        return $this->success($transaksi);
    }

    /**
     * Get single transaksi detail
     */
    #[OA\Get(
        path: '/transaksi/{id}',
        summary: 'Detail transaksi',
        description: 'Dapatkan detail transaksi beserta item, peti, biaya, dan pembayaran',
        operationId: 'getTransaksiDetail',
        tags: ['Transaksi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Transaksi', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TransaksiDetail'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Transaksi tidak ditemukan'),
        ]
    )]
    public function show(int $id)
    {
        Log::info("Mencari transaksi dengan ID: {$id}");
        $transaksi = Transaksi::with([
            'itemTransaksi.detailPeti',
            'biayaOperasional',
            'pembayaran.user',
            'user',
        ])->find($id);

        if (!$transaksi) {
            return $this->error('Transaksi tidak ditemukan', 404);
        }

        return $this->success($transaksi);
    }

    /**
     * Get form data for creating transaksi
     */
    #[OA\Get(
        path: '/transaksi/form-data',
        summary: 'Data untuk form transaksi',
        description: 'Dapatkan data yang dibutuhkan untuk form transaksi (komisi default, supplier, produk)',
        operationId: 'getTransaksiFormData',
        tags: ['Transaksi'],
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
                                new OA\Property(property: 'komisi_default', type: 'number', example: 10),
                                new OA\Property(property: 'suppliers', type: 'array', items: new OA\Items(ref: '#/components/schemas/Supplier')),
                                new OA\Property(property: 'stok_tersedia', type: 'array', items: new OA\Items(ref: '#/components/schemas/DetailBarangDatang')),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function formData()
    {
        $komisiDefault = Setting::get('komisi_persen', 0);
        $suppliers = \App\Models\Supplier::aktif()->orderBy('nama_supplier')->get();

        // Stok tersedia dari detail_barang_datang yang sudah confirmed, FIFO
        $stokTersedia = DetailBarangDatang::select('detail_barang_datang.*', 'barang_datang.kode_bd', 'barang_datang.tanggal', 'barang_datang.supplier_id')
            ->join('barang_datang', 'detail_barang_datang.barang_datang_id', '=', 'barang_datang.id')
            ->where('barang_datang.status', 'confirmed')
            ->where('detail_barang_datang.status_stok', 'available')
            ->where('detail_barang_datang.aktif', true)
            ->orderBy('barang_datang.tanggal', 'asc')
            ->orderBy('barang_datang.urutan_hari', 'asc')
            ->orderBy('detail_barang_datang.id', 'asc')
            ->with('kategori')
            ->get();

        return $this->success([
            'komisi_default' => $komisiDefault,
            'suppliers'      => $suppliers,
            'stok_tersedia'  => $stokTersedia,
        ]);
    }

    /**
     * Create new transaksi
     */
    #[OA\Post(
        path: '/transaksi',
        summary: 'Buat transaksi baru',
        description: 'Buat transaksi baru dengan item buah, detail peti, dan biaya operasional',
        operationId: 'createTransaksi',
        tags: ['Transaksi'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_pelanggan', 'status_bayar', 'komisi_persen', 'items'],
                properties: [
                    new OA\Property(property: 'nama_pelanggan', type: 'string', example: 'Toko Buah Segar'),
                    new OA\Property(property: 'status_bayar', type: 'string', enum: ['lunas', 'tempo', 'cicil'], example: 'tempo'),
                    new OA\Property(property: 'tanggal_jatuh_tempo', type: 'string', format: 'date', example: '2026-04-01', nullable: true),
                    new OA\Property(property: 'komisi_persen', type: 'number', example: 10),
                    new OA\Property(property: 'catatan', type: 'string', example: 'Kirim pagi hari', nullable: true),
                    new OA\Property(property: 'uang_diterima', type: 'number', example: 500000, nullable: true),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['nama_supplier', 'jenis_buah', 'harga_per_kg', 'peti'],
                            properties: [
                                new OA\Property(property: 'supplier_id', type: 'integer', example: 1, nullable: true),
                                new OA\Property(property: 'nama_supplier', type: 'string', example: 'Supplier ABC'),
                                new OA\Property(property: 'jenis_buah', type: 'string', example: 'Apel Fuji'),
                                new OA\Property(property: 'harga_per_kg', type: 'number', example: 25000),
                                new OA\Property(
                                    property: 'peti',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        required: ['berat_kotor', 'berat_kemasan'],
                                        properties: [
                                            new OA\Property(property: 'berat_kotor', type: 'number', example: 25.5),
                                            new OA\Property(property: 'berat_kemasan', type: 'number', example: 2.5),
                                        ]
                                    )
                                ),
                            ]
                        )
                    ),
                    new OA\Property(
                        property: 'biaya',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'nama_biaya', type: 'string', example: 'Ongkos kirim'),
                                new OA\Property(property: 'nominal', type: 'number', example: 50000),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Transaksi berhasil dibuat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Transaksi berhasil disimpan'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TransaksiDetail'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'nama_pelanggan'       => 'required|string|max:255',
            'status_bayar'         => 'required|in:lunas,transfer,tempo,cicil',
            'tanggal_jatuh_tempo'  => 'nullable|date',
            'komisi_persen'        => 'required|numeric|min:0|max:100',
            'catatan'              => 'nullable|string',
            'uang_diterima'        => 'nullable|numeric|min:0',
            'items'                => 'required|array|min:1',
            'items.*.supplier_id'  => 'nullable|integer',
            'items.*.detail_barang_datang_id' => 'nullable|exists:detail_barang_datang,id',
            'items.*.nama_supplier'=> 'required|string',
            'items.*.jenis_buah'   => 'required|string',
            'items.*.harga_per_kg' => 'required|numeric|min:0',
            'items.*.peti'         => 'required|array|min:1',
            'items.*.peti.*.berat_kotor'   => 'required|numeric|min:0',
            'items.*.peti.*.berat_kemasan' => 'required|numeric|min:0',
            'biaya'                => 'nullable|array',
            'biaya.*.nama_biaya'   => 'required_with:biaya|string',
            'biaya.*.nominal'      => 'required_with:biaya|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $transaksi = Transaksi::create([
                'kode_transaksi'   => Transaksi::generateKode(),
                'nama_pelanggan'   => $request->nama_pelanggan,
                'status_bayar'     => $request->status_bayar,
                'tanggal_jatuh_tempo' => $request->tanggal_jatuh_tempo,
                'catatan'          => $request->catatan,
                'komisi_persen'    => 0,
                'uang_diterima'    => $request->uang_diterima ?? 0,
                'status'           => 'selesai',
                'user_id'          => auth()->id(),
            ]);

            // Simpan item buah
            foreach ($request->items as $itemData) {
                $supplierId = $itemData['supplier_id'] ?? null;

                $item = ItemTransaksi::create([
                    'transaksi_id'  => $transaksi->id,
                    'supplier_id'   => $supplierId,
                    'detail_barang_datang_id' => $itemData['detail_barang_datang_id'] ?? null,
                    'nama_supplier' => $itemData['nama_supplier'],
                    'jenis_buah'    => $itemData['jenis_buah'],
                    'harga_per_kg'  => $itemData['harga_per_kg'],
                ]);

                // Validasi jumlah peti tidak melebihi stok tersedia
                if (!empty($itemData['detail_barang_datang_id'])) {
                    $stok = DetailBarangDatang::find($itemData['detail_barang_datang_id']);
                    if ($stok) {
                        $jumlahPetiDiminta = count($itemData['peti']);
                        if ($jumlahPetiDiminta > $stok->stok_sisa) {
                            DB::rollBack();
                            return $this->error(
                                "Jumlah peti ({$jumlahPetiDiminta}) melebihi stok tersedia ({$stok->stok_sisa} peti) untuk {$stok->nama_produk}",
                                422
                            );
                        }
                    }
                }

                // Simpan peti-peti
                foreach ($itemData['peti'] as $idx => $petiData) {
                    DetailPeti::create([
                        'item_transaksi_id' => $item->id,
                        'no_peti'           => $idx + 1,
                        'berat_kotor'       => $petiData['berat_kotor'],
                        'berat_kemasan'     => $petiData['berat_kemasan'],
                    ]);
                }

                $item->recalculate();

                // Kurangi stok berdasarkan jumlah peti yang dipakai
                if (!empty($itemData['detail_barang_datang_id']) && isset($stok)) {
                    if (!$stok->kurangiStok($item->jumlah_peti)) {
                        DB::rollBack();
                        return $this->error('Stok tidak cukup pada detail barang datang yang dipilih', 422);
                    }
                }
            }

            // Simpan biaya operasional
            if ($request->biaya) {
                foreach ($request->biaya as $biaya) {
                    if (!empty($biaya['nama_biaya']) && isset($biaya['nominal'])) {
                        BiayaOperasional::create([
                            'transaksi_id' => $transaksi->id,
                            'nama_biaya'   => $biaya['nama_biaya'],
                            'nominal'      => $biaya['nominal'],
                        ]);
                    }
                }
            }

            $transaksi->recalculate();

            // Hitung kembalian dan catat pembayaran jika lunas
            $totalTagihan = $transaksi->fresh()->total_tagihan;
            $uangDiterima = $request->uang_diterima ?? 0;
            $kembalian = max(0, $uangDiterima - $totalTagihan);

            $transaksi->update([
                'uang_diterima' => $uangDiterima,
                'kembalian' => $kembalian,
            ]);

            // Jika status lunas dan ada uang diterima, catat sebagai pembayaran tunai + kas laci
            if ($request->status_bayar === 'lunas' && $uangDiterima > 0) {
                Pembayaran::create([
                    'transaksi_id' => $transaksi->id,
                    'kode_pembayaran' => Pembayaran::generateKode(),
                    'nominal' => min($uangDiterima, $totalTagihan),
                    'metode' => 'tunai',
                    'catatan' => 'Pembayaran saat transaksi',
                    'sisa_tagihan' => 0,
                    'user_id' => auth()->id(),
                ]);
                $transaksi->recalculate();
                KasLaci::catatDariTransaksi($transaksi->fresh());
            }

            // Jika status transfer, catat sebagai pembayaran transfer (tidak masuk kas laci)
            if ($request->status_bayar === 'transfer') {
                Pembayaran::create([
                    'transaksi_id' => $transaksi->id,
                    'kode_pembayaran' => Pembayaran::generateKode(),
                    'nominal' => $totalTagihan,
                    'metode' => 'transfer',
                    'catatan' => 'Pembayaran transfer saat transaksi',
                    'sisa_tagihan' => 0,
                    'user_id' => auth()->id(),
                ]);
                $transaksi->recalculate();
            }

            DB::commit();

            // Load relations untuk response
            $transaksi->load([
                'itemTransaksi.detailPeti',
                'biayaOperasional',
                'pembayaran.user',
                'user',
            ]);

            return $this->success($transaksi, 'Transaksi berhasil disimpan', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal menyimpan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update transaksi
     */
    public function update(Request $request, int $id)
    {
        $transaksi = Transaksi::find($id);
        if (!$transaksi) {
            return $this->error('Transaksi tidak ditemukan', 404);
        }

        $request->validate([
            'nama_pelanggan'              => 'required|string|max:255',
            'status_bayar'                => 'required|in:lunas,transfer,tempo,cicil',
            'tanggal_jatuh_tempo'         => 'nullable|date',
            'komisi_persen'               => 'required|numeric|min:0|max:100',
            'catatan'                     => 'nullable|string',
            'items'                       => 'nullable|array',
            'items.*.id'                  => 'required|integer|exists:item_transaksi,id',
            'items.*.harga_per_kg'        => 'required|numeric|min:0',
            'items.*.peti'                => 'nullable|array',
            'items.*.peti.*.id'           => 'required|integer|exists:detail_peti,id',
            'items.*.peti.*.berat_kotor'  => 'required|numeric|min:0',
            'items.*.peti.*.berat_kemasan'=> 'required|numeric|min:0',
            'biaya'                       => 'nullable|array',
            'biaya.*.nama_biaya'          => 'required_with:biaya|string',
            'biaya.*.nominal'             => 'required_with:biaya|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Update header transaksi
            $transaksi->update([
                'nama_pelanggan'       => $request->nama_pelanggan,
                'status_bayar'         => $request->status_bayar,
                'tanggal_jatuh_tempo'  => $request->tanggal_jatuh_tempo,
                'komisi_persen'        => $request->komisi_persen,
                'catatan'              => $request->catatan,
            ]);

            // Update item harga + berat peti
            foreach ($request->items ?? [] as $itemData) {
                $item = ItemTransaksi::find($itemData['id']);
                if (!$item || $item->transaksi_id !== $transaksi->id) {
                    continue;
                }

                $item->update(['harga_per_kg' => $itemData['harga_per_kg']]);

                foreach ($itemData['peti'] ?? [] as $petiData) {
                    $peti = DetailPeti::find($petiData['id']);
                    if (!$peti || $peti->item_transaksi_id !== $item->id) {
                        continue;
                    }
                    $peti->update([
                        'berat_kotor'   => $petiData['berat_kotor'],
                        'berat_kemasan' => $petiData['berat_kemasan'],
                        // berat_bersih adalah generated column, otomatis dihitung DB
                    ]);
                }

                $item->recalculate();
            }

            // Ganti seluruh biaya operasional
            $transaksi->biayaOperasional()->delete();
            foreach ($request->biaya ?? [] as $biaya) {
                if (!empty($biaya['nama_biaya']) && isset($biaya['nominal'])) {
                    BiayaOperasional::create([
                        'transaksi_id' => $transaksi->id,
                        'nama_biaya'   => $biaya['nama_biaya'],
                        'nominal'      => $biaya['nominal'],
                    ]);
                }
            }

            $transaksi->recalculate();

            DB::commit();

            $transaksi->load([
                'itemTransaksi.detailPeti',
                'biayaOperasional',
                'pembayaran.user',
                'user',
            ]);

            return $this->success($transaksi, 'Transaksi berhasil diperbarui');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal memperbarui: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete transaksi
     */
    #[OA\Delete(
        path: '/transaksi/{id}',
        summary: 'Hapus transaksi',
        description: 'Hapus transaksi beserta semua data terkait',
        operationId: 'deleteTransaksi',
        tags: ['Transaksi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Transaksi', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transaksi berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Transaksi berhasil dihapus'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Transaksi tidak ditemukan'),
        ]
    )]
    public function destroy(int $id)
    {
        $transaksi = Transaksi::find($id);

        if (!$transaksi) {
            return $this->error('Transaksi tidak ditemukan', 404);
        }

        KasLaci::where('referensi_tipe', 'transaksi')->where('referensi_id', $transaksi->id)->delete();
        $transaksi->delete();

        return $this->success(null, 'Transaksi berhasil dihapus');
    }

    /**
     * Get transaksi statistics/summary
     */
    #[OA\Get(
        path: '/transaksi/statistics',
        summary: 'Statistik transaksi',
        description: 'Dapatkan ringkasan statistik transaksi',
        operationId: 'getTransaksiStatistics',
        tags: ['Transaksi'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
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
                                new OA\Property(property: 'total_transaksi', type: 'integer', example: 150),
                                new OA\Property(property: 'total_pendapatan', type: 'number', example: 15000000),
                                new OA\Property(property: 'total_piutang', type: 'number', example: 5000000),
                                new OA\Property(property: 'transaksi_lunas', type: 'integer', example: 100),
                                new OA\Property(property: 'transaksi_tempo', type: 'integer', example: 30),
                                new OA\Property(property: 'transaksi_cicil', type: 'integer', example: 20),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function statistics(Request $request)
    {
        $query = Transaksi::query();

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->tanggal_dari);
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_sampai);
        }

        $stats = [
            'total_transaksi' => (clone $query)->count(),
            'total_pendapatan' => (clone $query)->sum('total_tagihan'),
            'total_piutang' => (clone $query)->whereIn('status_bayar', ['tempo', 'cicil'])->sum('sisa_tagihan'),
            'transaksi_lunas' => (clone $query)->where('status_bayar', 'lunas')->count(),
            'transaksi_tempo' => (clone $query)->where('status_bayar', 'tempo')->count(),
            'transaksi_cicil' => (clone $query)->where('status_bayar', 'cicil')->count(),
        ];

        return $this->success($stats);
    }
}
