<?php

namespace App\Http\Controllers\Api;

use App\Models\PreOrder;
use App\Models\DetailPreOrder;
use App\Models\DetailBarangDatang;
use App\Models\Transaksi;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class PreOrderController extends Controller
{
    #[OA\Get(
        path: '/pre-order',
        summary: 'List Pre Order',
        tags: ['Pre Order'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter status PO', schema: new OA\Schema(type: 'string', enum: ['pending', 'diproses', 'selesai', 'dibatalkan'])),
            new OA\Parameter(name: 'cari', in: 'query', description: 'Cari berdasarkan nama pelanggan atau kode PO', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request)
    {
        $query = PreOrder::with(['pelanggan', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_pelanggan', 'like', "%{$cari}%")
                  ->orWhere('kode_po', 'like', "%{$cari}%");
            });
        }

        $po = $query->orderByDesc('tanggal_po')->paginate($request->input('per_page', 20));
        return $this->success($po);
    }

    #[OA\Post(
        path: '/pre-order',
        summary: 'Buat Pre Order baru',
        description: 'Membuat PO dari pilihan supplier & produk dengan harga freetext.',
        tags: ['Pre Order'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_pelanggan', 'tanggal_po', 'details'],
                properties: [
                    new OA\Property(property: 'pelanggan_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'nama_pelanggan', type: 'string', example: 'Toko Maju'),
                    new OA\Property(property: 'tanggal_po', type: 'string', format: 'date', example: '2026-03-14'),
                    new OA\Property(property: 'tanggal_kirim', type: 'string', format: 'date', nullable: true),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'details',
                        type: 'array',
                        items: new OA\Items(
                            required: ['nama_produk', 'jumlah_peti', 'harga_per_kg'],
                            properties: [
                                new OA\Property(property: 'supplier_id', type: 'integer', nullable: true, description: 'ID supplier'),
                                new OA\Property(property: 'nama_supplier', type: 'string', nullable: true, example: 'PT Buah Segar'),
                                new OA\Property(property: 'detail_barang_datang_id', type: 'integer', nullable: true, description: 'ID stok spesifik (opsional)'),
                                new OA\Property(property: 'nama_produk', type: 'string', example: 'Apel Fuji'),
                                new OA\Property(property: 'ukuran', type: 'string', nullable: true, example: 'A'),
                                new OA\Property(property: 'jumlah_peti', type: 'integer', example: 5),
                                new OA\Property(property: 'harga_per_kg', type: 'number', example: 25000),
                                new OA\Property(property: 'estimasi_berat_bersih', type: 'number', nullable: true, example: 100),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'PO berhasil dibuat'),
            new OA\Response(response: 422, description: 'Validasi gagal atau stok tidak cukup'),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pelanggan_id'                      => 'nullable|exists:pelanggan,id',
            'nama_pelanggan'                    => 'required|string',
            'tanggal_po'                        => 'required|date',
            'tanggal_kirim'                     => 'nullable|date|after_or_equal:tanggal_po',
            'catatan'                           => 'nullable|string',
            'details'                           => 'required|array|min:1',
            'details.*.supplier_id'             => 'nullable|exists:supplier,id',
            'details.*.nama_supplier'           => 'nullable|string',
            'details.*.detail_barang_datang_id' => 'nullable|exists:detail_barang_datang,id',
            'details.*.nama_produk'             => 'required|string',
            'details.*.ukuran'                  => 'nullable|string',
            'details.*.jumlah_peti'             => 'required|integer|min:1',
            'details.*.harga_per_kg'            => 'required|numeric|min:0',
            'details.*.estimasi_berat_bersih'   => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            // Cek ketersediaan stok jika ada detail_barang_datang_id
            foreach ($validated['details'] as $d) {
                if (!empty($d['detail_barang_datang_id'])) {
                    $stok = DetailBarangDatang::findOrFail($d['detail_barang_datang_id']);
                    if ($stok->stok_sisa_real < $d['jumlah_peti']) {
                        return $this->error(
                            "Stok {$stok->nama_produk} tidak cukup. Tersedia: {$stok->stok_sisa_real} peti (sudah termasuk pesanan aktif), Diminta: {$d['jumlah_peti']} peti.",
                            422
                        );
                    }
                }
            }

            $po = PreOrder::create([
                'kode_po'        => PreOrder::generateKode(),
                'pelanggan_id'   => $validated['pelanggan_id'] ?? null,
                'nama_pelanggan' => $validated['nama_pelanggan'],
                'tanggal_po'     => $validated['tanggal_po'],
                'tanggal_kirim'  => $validated['tanggal_kirim'] ?? null,
                'total'          => 0,
                'status'         => 'pending',
                'catatan'        => $validated['catatan'] ?? null,
                'user_id'        => auth()->id(),
            ]);

            $totalPO = 0;
            foreach ($validated['details'] as $d) {
                $subtotal = ($d['estimasi_berat_bersih'] ?? 0) * $d['harga_per_kg'];
                DetailPreOrder::create([
                    'pre_order_id'            => $po->id,
                    'supplier_id'             => $d['supplier_id'] ?? null,
                    'nama_supplier'           => $d['nama_supplier'] ?? null,
                    'detail_barang_datang_id' => $d['detail_barang_datang_id'] ?? null,
                    'nama_produk'             => $d['nama_produk'],
                    'ukuran'                  => $d['ukuran'] ?? null,
                    'jumlah_peti'             => $d['jumlah_peti'],
                    'harga_per_kg'            => $d['harga_per_kg'],
                    'estimasi_berat_bersih'   => $d['estimasi_berat_bersih'] ?? 0,
                    'subtotal'                => $subtotal,
                ]);
                $totalPO += $subtotal;
            }

            $po->update(['total' => $totalPO]);
            $po->load(['pelanggan', 'details', 'user']);

            LogAktivitas::catat('pre_order', 'create', "PO {$po->kode_po} untuk {$po->nama_pelanggan}", $po);

            return $this->success($po, 'Pre Order berhasil dibuat.', 201);
        });
    }

    #[OA\Get(
        path: '/pre-order/{id}',
        summary: 'Detail Pre Order',
        tags: ['Pre Order'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'PO tidak ditemukan'),
        ]
    )]
    public function show(int $id)
    {
        $po = PreOrder::with(['pelanggan', 'details.stokBarang', 'user', 'transaksi'])->findOrFail($id);
        return $this->success($po);
    }

    #[OA\Post(
        path: '/pre-order/{id}/proses',
        summary: 'Proses PO — hubungkan ke Transaksi',
        description: 'Menghubungkan Pre Order yang sudah dikonversi ke Transaksi oleh Flutter. Status PO berubah menjadi "diproses".',
        tags: ['Pre Order'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['transaksi_id'],
                properties: [
                    new OA\Property(property: 'transaksi_id', type: 'integer', description: 'ID transaksi yang baru dibuat dari PO ini'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'PO berhasil diproses'),
            new OA\Response(response: 422, description: 'PO tidak bisa diproses'),
        ]
    )]
    public function proses(Request $request, int $id)
    {
        $request->validate([
            'transaksi_id' => 'required|exists:transaksi,id',
        ]);

        $po = PreOrder::findOrFail($id);

        if ($po->status !== 'pending') {
            return $this->error("PO tidak bisa diproses dengan status {$po->status}.", 422);
        }

        $po->update([
            'status'       => 'diproses',
            'transaksi_id' => $request->transaksi_id,
        ]);

        $po->load(['pelanggan', 'details', 'user', 'transaksi']);

        LogAktivitas::catat('pre_order', 'proses', "PO {$po->kode_po} diproses ke transaksi #{$request->transaksi_id}", $po);

        return $this->success($po, 'Pre Order berhasil diproses.');
    }

    #[OA\Get(
        path: '/pre-order/{id}/form-transaksi',
        summary: 'Suggestion data PO untuk form Transaksi',
        description: 'Mengembalikan data PO yang sudah diformat siap pakai untuk pre-fill form Create Transaksi di Flutter.',
        tags: ['Pre Order'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 422, description: 'PO tidak berstatus pending'),
        ]
    )]
    public function formTransaksi(int $id)
    {
        $po = PreOrder::with(['pelanggan', 'details'])->findOrFail($id);

        if ($po->status !== 'pending') {
            return $this->error("PO berstatus {$po->status}, tidak bisa dikonversi.", 422);
        }

        $items = $po->details->map(fn ($d) => [
            'supplier_id'   => $d->supplier_id,
            'nama_supplier' => $d->nama_supplier,
            'jenis_buah'    => $d->nama_produk,
            'ukuran'        => $d->ukuran,
            'harga_per_kg'  => $d->harga_per_kg,
            'jumlah_peti_po' => $d->jumlah_peti,
            'peti'          => [],   // diisi user saat timbang
        ]);

        return $this->success([
            'po_id'          => $po->id,
            'kode_po'        => $po->kode_po,
            'pelanggan_id'   => $po->pelanggan_id,
            'nama_pelanggan' => $po->nama_pelanggan,
            'catatan'        => $po->catatan,
            'items'          => $items,
        ]);
    }

    #[OA\Post(
        path: '/pre-order/{id}/batal',
        summary: 'Batalkan Pre Order',
        tags: ['Pre Order'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'PO berhasil dibatalkan'),
            new OA\Response(response: 422, description: 'PO tidak bisa dibatalkan'),
        ]
    )]
    public function batal(int $id)
    {
        $po = PreOrder::findOrFail($id);

        if (in_array($po->status, ['selesai', 'dibatalkan'])) {
            return $this->error('PO tidak bisa dibatalkan dengan status ' . $po->status, 422);
        }

        $po->update(['status' => 'dibatalkan']);
        LogAktivitas::catat('pre_order', 'batal', "PO {$po->kode_po} dibatalkan", $po);

        return $this->success($po, 'Pre Order berhasil dibatalkan.');
    }
}
