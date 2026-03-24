<?php

namespace App\Http\Controllers\Api;

use App\Models\BarangDatang;
use App\Models\DetailBarangDatang;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class BarangDatangController extends Controller
{
    /**
     * List semua barang datang dengan filter
     */
    #[OA\Get(
        path: '/barang-datang',
        summary: 'List barang datang',
        description: 'Daftar semua penerimaan barang (stock in) dari supplier dengan filter',
        operationId: 'getBarangDatang',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'supplier_id', in: 'query', description: 'Filter by supplier', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal_dari', in: 'query', description: 'Filter tanggal mulai (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', description: 'Filter tanggal akhir (YYYY-MM-DD)', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'confirmed'])),
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
                                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BarangDatang')),
                                new OA\Property(property: 'total', type: 'integer', example: 20),
                                new OA\Property(property: 'last_page', type: 'integer', example: 2),
                                new OA\Property(property: 'per_page', type: 'integer', example: 20),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request)
    {
        $query = BarangDatang::with(['supplier', 'details.kategori'])
            ->withCount('details');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('tanggal_dari')) {
            $query->where('tanggal', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->where('tanggal', '<=', $request->tanggal_sampai);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 20);
        $data = $query->orderByDesc('tanggal')->orderByDesc('urutan_hari')->paginate($perPage);

        return $this->success($data);
    }

    /**
     * Detail satu barang datang
     */
    #[OA\Get(
        path: '/barang-datang/{id}',
        summary: 'Detail barang datang',
        operationId: 'getBarangDatangDetail',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BarangDatang'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang tidak ditemukan'),
                    ]
                )
            ),
        ]
    )]
    public function show(int $id)
    {
        $bd = BarangDatang::with(['supplier', 'details.kategori', 'dikonfirmasiOleh'])->find($id);

        if (!$bd) {
            return $this->error('Barang datang tidak ditemukan', 404);
        }

        return $this->success($bd);
    }

    /**
     * Buat barang datang baru beserta letter (produk baru) dari repeater
     */
    #[OA\Post(
        path: '/barang-datang',
        summary: 'Tambah barang datang baru',
        description: 'Input penerimaan barang dari supplier. Setiap letter diinput langsung (produk baru akan dibuat otomatis saat konfirmasi). Letter (nama_produk+ukuran) harus unik per hari per supplier.',
        operationId: 'createBarangDatang',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['supplier_id', 'tanggal', 'details'],
                properties: [
                    new OA\Property(property: 'supplier_id', type: 'integer', example: 1),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-03-07'),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true, example: 'Kiriman pagi'),
                    new OA\Property(
                        property: 'details',
                        type: 'array',
                        minItems: 1,
                        description: 'Daftar letter (produk baru) yang datang',
                        items: new OA\Items(
                            required: ['nama_produk', 'jumlah', 'harga_beli', 'satuan'],
                            properties: [
                                new OA\Property(property: 'nama_produk', type: 'string', example: 'Apel Fuji'),
                                new OA\Property(property: 'ukuran', type: 'string', example: 'A', nullable: true),
                                new OA\Property(property: 'kategori_id', type: 'integer', example: 1, nullable: true),
                                new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                new OA\Property(property: 'harga_beli', type: 'number', example: 15000),
                                new OA\Property(property: 'harga_jual', type: 'number', example: 20000, nullable: true),
                                new OA\Property(property: 'jumlah', type: 'number', example: 100),
                                new OA\Property(property: 'keterangan', type: 'string', nullable: true),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Berhasil dicatat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang berhasil dicatat'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BarangDatang'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error / letter duplikat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Letter "Apel Fuji (A)" sudah ada dalam kiriman hari ini dari supplier yang sama'),
                        new OA\Property(property: 'errors', type: 'object', nullable: true),
                    ]
                )
            ),
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id'               => 'required|exists:supplier,id',
            'tanggal'                   => 'required|date_format:Y-m-d',
            'catatan'                   => 'nullable|string',
            'details'                   => 'required|array|min:1',
            'details.*.nama_produk'     => 'required|string|max:255',
            'details.*.ukuran'          => 'nullable|string|max:50',
            'details.*.kategori_id'     => 'nullable|exists:kategori,id',
            'details.*.satuan'          => 'required|string|max:20',
            'details.*.harga_beli'      => 'required|numeric|min:0',
            'details.*.harga_jual'      => 'nullable|numeric|min:0',
            'details.*.jumlah'          => 'required|numeric|min:0.01',
            'details.*.keterangan'      => 'nullable|string',
        ]);

        $supplierId = $request->supplier_id;
        $tanggal    = $request->tanggal;

        // Validasi: tidak ada duplikat letter (nama_produk + ukuran) dalam request ini
        $letterError = $this->cekDuplikatDalamRequest($request->details);
        if ($letterError) {
            return $this->error($letterError, 422);
        }

        // Validasi: letter harus unik per supplier per hari (lintas kiriman)
        $konflikError = $this->cekKonflikHariIni($request->details, $supplierId, $tanggal);
        if ($konflikError) {
            return $this->error($konflikError, 422);
        }

        DB::beginTransaction();
        try {
            $urutan = BarangDatang::hitungUrutanHari($supplierId, $tanggal);
            $bd = BarangDatang::create([
                'kode_bd'     => BarangDatang::generateKode($tanggal),
                'supplier_id' => $supplierId,
                'tanggal'     => $tanggal,
                'urutan_hari' => $urutan,
                'catatan'     => $request->catatan,
                'status'      => 'draft',
            ]);

            foreach ($request->details as $item) {
                DetailBarangDatang::create([
                    'barang_datang_id' => $bd->id,
                    'nama_produk'      => $item['nama_produk'],
                    'ukuran'           => $item['ukuran'] ?? null,
                    'kategori_id'      => $item['kategori_id'] ?? null,
                    'satuan'           => $item['satuan'],
                    'harga_beli'       => $item['harga_beli'],
                    'harga_jual'       => $item['harga_jual'] ?? 0,
                    'jumlah'           => $item['jumlah'],
                    'keterangan'       => $item['keterangan'] ?? null,
                ]);
            }

            DB::commit();

            $bd->load(['supplier', 'details.kategori']);
            return $this->success($bd, 'Barang datang berhasil dicatat', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal menyimpan: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update barang datang (hanya status draft)
     */
    #[OA\Put(
        path: '/barang-datang/{id}',
        summary: 'Update barang datang',
        description: 'Hanya bisa diupdate jika status masih draft. Jika details dikirim, seluruh detail akan diganti.',
        operationId: 'updateBarangDatang',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'catatan', type: 'string', nullable: true),
                    new OA\Property(
                        property: 'details',
                        type: 'array',
                        description: 'Jika dikirim, semua detail lama akan diganti',
                        items: new OA\Items(
                            required: ['nama_produk', 'jumlah', 'harga_beli', 'satuan'],
                            properties: [
                                new OA\Property(property: 'nama_produk', type: 'string', example: 'Apel Fuji'),
                                new OA\Property(property: 'ukuran', type: 'string', example: 'A', nullable: true),
                                new OA\Property(property: 'kategori_id', type: 'integer', nullable: true),
                                new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
                                new OA\Property(property: 'harga_beli', type: 'number', example: 15000),
                                new OA\Property(property: 'harga_jual', type: 'number', example: 20000, nullable: true),
                                new OA\Property(property: 'jumlah', type: 'number', example: 100),
                                new OA\Property(property: 'keterangan', type: 'string', nullable: true),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil diupdate',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang berhasil diupdate'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BarangDatang'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Sudah dikonfirmasi, tidak bisa diubah',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang sudah dikonfirmasi, tidak dapat diubah'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang tidak ditemukan'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation error'),
                        new OA\Property(property: 'errors', type: 'object', nullable: true),
                    ]
                )
            ),
        ]
    )]
    public function update(Request $request, int $id)
    {
        $bd = BarangDatang::find($id);

        if (!$bd) {
            return $this->error('Barang datang tidak ditemukan', 404);
        }
        $request->validate([
            'catatan'                   => 'nullable|string',
            'details'                   => 'sometimes|array|min:1',
            'details.*.nama_produk'     => 'required|string|max:255',
            'details.*.ukuran'          => 'nullable|string|max:50',
            'details.*.kategori_id'     => 'nullable|exists:kategori,id',
            'details.*.satuan'          => 'required|string|max:20',
            'details.*.harga_beli'      => 'required|numeric|min:0',
            'details.*.harga_jual'      => 'nullable|numeric|min:0',
            'details.*.jumlah'          => 'required|numeric|min:0.01',
            'details.*.keterangan'      => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if ($request->has('catatan')) {
                $bd->update(['catatan' => $request->catatan]);
            }

            if ($request->has('details')) {
                $letterError = $this->cekDuplikatDalamRequest($request->details);
                if ($letterError) {
                    DB::rollBack();
                    return $this->error($letterError, 422);
                }

                // Cek konflik harian, kecualikan kiriman ini sendiri
                $konflikError = $this->cekKonflikHariIni(
                    $request->details,
                    $bd->supplier_id,
                    $bd->tanggal->format('Y-m-d'),
                    $bd->id
                );
                if ($konflikError) {
                    DB::rollBack();
                    return $this->error($konflikError, 422);
                }

                $bd->details()->delete();
                foreach ($request->details as $item) {
                    DetailBarangDatang::create([
                        'barang_datang_id' => $bd->id,
                        'nama_produk'      => $item['nama_produk'],
                        'ukuran'           => $item['ukuran'] ?? null,
                        'kategori_id'      => $item['kategori_id'] ?? null,
                        'satuan'           => $item['satuan'],
                        'harga_beli'       => $item['harga_beli'],
                        'harga_jual'       => $item['harga_jual'] ?? 0,
                        'jumlah'           => $item['jumlah'],
                        'keterangan'       => $item['keterangan'] ?? null,
                    ]);
                }
            }

            DB::commit();

            $bd->load(['supplier', 'details.kategori']);
            return $this->success($bd, 'Barang datang berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal update: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Hapus barang datang (hanya status draft)
     */
    #[OA\Delete(
        path: '/barang-datang/{id}',
        summary: 'Hapus barang datang',
        description: 'Hanya bisa dihapus jika status masih draft',
        operationId: 'deleteBarangDatang',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang berhasil dihapus'),
                        new OA\Property(property: 'data', type: 'null', nullable: true),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Sudah dikonfirmasi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang sudah dikonfirmasi, tidak dapat dihapus'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang tidak ditemukan'),
                    ]
                )
            ),
        ]
    )]
    public function destroy(int $id)
    {
        $bd = BarangDatang::find($id);

        if (!$bd) {
            return $this->error('Barang datang tidak ditemukan', 404);
        }
        if ($bd->isConfirmed()) {
            return $this->error('Barang datang sudah dikonfirmasi, tidak dapat dihapus', 403);
        }

        $bd->delete();
        return $this->success(null, 'Barang datang berhasil dihapus');
    }

    /**
     * Konfirmasi barang datang:
     * - Produk baru dibuat di master jika belum ada (cek nama+ukuran+supplier)
     * - Jika produk sudah ada → stok ditambah
     * - detail.produk_id diisi
     */
    #[OA\Post(
        path: '/barang-datang/{id}/confirm',
        summary: 'Konfirmasi barang datang',
        description: 'Konfirmasi penerimaan barang. Setiap letter akan dibuatkan produk baru di master (atau increment stok jika sudah ada). Stok otomatis masuk.',
        operationId: 'confirmBarangDatang',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Berhasil dikonfirmasi, produk & stok telah diupdate',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang dikonfirmasi. Produk dan stok telah diperbarui.'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/BarangDatang'),
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Sudah dikonfirmasi sebelumnya',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang sudah dikonfirmasi sebelumnya'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang tidak ditemukan'),
                    ]
                )
            ),
        ]
    )]
    public function confirm(int $id, Request $request)
    {
        $bd = BarangDatang::with('details')->find($id);

        if (!$bd) {
            return $this->error('Barang datang tidak ditemukan', 404);
        }
        if ($bd->isConfirmed()) {
            return $this->error('Barang datang sudah dikonfirmasi sebelumnya', 403);
        }
        if ($bd->details->isEmpty()) {
            return $this->error('Tidak ada letter dalam kiriman ini', 422);
        }

        DB::beginTransaction();
        try {
            foreach ($bd->details as $detail) {
                // Generate kode_produk unik untuk setiap letter jika belum punya
                if (!$detail->kode_produk) {
                    $detail->update([
                        'kode_produk' => DetailBarangDatang::generateKode(),
                        'stok_awal'   => $detail->jumlah,
                        'stok_sisa'   => $detail->jumlah,
                        'aktif'       => true,
                    ]);
                }
            }

            $bd->update([
                'status'            => 'confirmed',
                'dikonfirmasi_at'   => now(),
                'dikonfirmasi_oleh' => $request->user()?->id,
            ]);

            DB::commit();

            $bd->load(['supplier', 'details.kategori', 'dikonfirmasiOleh']);
            return $this->success($bd, 'Barang datang dikonfirmasi. Stok telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal konfirmasi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Ambil daftar detail barang datang yang tersedia untuk supplier (FIFO)
     */
    #[OA\Get(
        path: '/barang-datang/stok-tersedia',
        summary: 'Stok tersedia per supplier (FIFO)',
        description: 'Mengembalikan daftar DetailBarangDatang yang berasal dari kiriman (confirmed) untuk supplier tertentu, terurut FIFO, menyertakan data stok dan kode_bd.',
        operationId: 'getStokTersedia',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/DetailBarangDatang')),
                    ]
                )
            ),
        ]
    )]
    public function stokTersedia(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:supplier,id',
        ]);

        $supplierId = $request->supplier_id;

        $details = DetailBarangDatang::with(['pesanan.preOrder'])
            ->select('detail_barang_datang.*', 'barang_datang.kode_bd')
            ->join('barang_datang', 'detail_barang_datang.barang_datang_id', '=', 'barang_datang.id')
            ->where('barang_datang.supplier_id', $supplierId)
            ->where('barang_datang.status', 'confirmed')
            ->orderBy('barang_datang.tanggal', 'asc')
            ->orderBy('barang_datang.urutan_hari', 'asc')
            ->orderBy('detail_barang_datang.id', 'asc')
            ->get();

        return $this->success($details);
    }

    /**
     * Cek letter yang sudah digunakan supplier pada hari tertentu
     * Berguna untuk validasi realtime di frontend (disable letter yang sudah terpakai)
     */
    #[OA\Get(
        path: '/barang-datang/letter-terpakai',
        summary: 'Cek letter terpakai',
        description: 'Dapatkan daftar nama+ukuran letter yang sudah ada dalam kiriman hari ini dari supplier tertentu',
        operationId: 'getLetterTerpakai',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal', in: 'query', required: true, description: 'Format YYYY-MM-DD', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'exclude_bd_id', in: 'query', description: 'ID barang datang yang dikecualikan (untuk mode edit)', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar letter yang sudah terpakai hari ini dari supplier ini',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Success'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'terpakai',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/LetterTerpakaiItem')
                                ),
                                new OA\Property(
                                    property: 'jumlah_kiriman',
                                    type: 'integer',
                                    example: 1,
                                    description: 'Berapa kali supplier ini sudah kirim hari ini'
                                ),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function letterTerpakai(Request $request)
    {
        $request->validate([
            'supplier_id'   => 'required|exists:supplier,id',
            'tanggal'       => 'required|date_format:Y-m-d',
            'exclude_bd_id' => 'nullable|integer',
        ]);

        $query = DetailBarangDatang::whereHas('barangDatang', function ($q) use ($request) {
            $q->where('supplier_id', $request->supplier_id)
              ->where('tanggal', $request->tanggal);
            if ($request->filled('exclude_bd_id')) {
                $q->where('id', '!=', $request->exclude_bd_id);
            }
        });

        $details = $query->get(['nama_produk', 'ukuran']);

        $terpakai = $details->map(fn($d) => [
            'nama_produk' => $d->nama_produk,
            'ukuran'      => $d->ukuran,
            'key'         => strtolower(trim($d->nama_produk)) . '|' . strtolower(trim($d->ukuran ?? '')),
        ]);

        return $this->success([
            'terpakai'       => $terpakai,
            'jumlah_kiriman' => BarangDatang::where('supplier_id', $request->supplier_id)
                ->where('tanggal', $request->tanggal)
                ->count(),
        ]);
    }

    /**
     * Riwayat transaksi yang menjual stok dari kiriman (barang datang) tertentu
     */
    #[OA\Get(
        path: '/barang-datang/{id}/transaksi',
        summary: 'Riwayat transaksi per kiriman',
        description: 'Mengembalikan daftar transaksi yang menjual stok dari kiriman (barang datang) tertentu. Setiap transaksi hanya menyertakan item yang berasal dari kiriman ini (difilter berdasarkan detail_barang_datang_id).',
        operationId: 'getTransaksiByBarangDatang',
        tags: ['BarangDatang'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Barang Datang', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar transaksi yang menjual dari kiriman ini',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/TransaksiRiwayatKiriman')
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Barang datang tidak ditemukan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Barang datang tidak ditemukan'),
                    ]
                )
            ),
        ]
    )]
    public function transaksi(int $id)
    {
        $bd = BarangDatang::find($id);

        if (!$bd) {
            return $this->error('Barang datang tidak ditemukan', 404);
        }

        $detailIds = $bd->details()->pluck('id');

        $transaksis = Transaksi::whereHas('itemTransaksi', function ($q) use ($detailIds) {
                $q->whereIn('detail_barang_datang_id', $detailIds);
            })
            ->with(['itemTransaksi' => function ($q) use ($detailIds) {
                $q->whereIn('detail_barang_datang_id', $detailIds);
            }])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $transaksis,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Cek duplikat letter (nama_produk + ukuran) dalam satu request
     */
    private function cekDuplikatDalamRequest(array $details): ?string
    {
        $keys = array_map(fn($item) =>
            strtolower(trim($item['nama_produk'])) . '|' . strtolower(trim($item['ukuran'] ?? '')),
            $details
        );
        if (count($keys) !== count(array_unique($keys))) {
            return 'Terdapat letter duplikat (nama + ukuran sama) dalam satu kiriman';
        }
        return null;
    }

    /**
     * Cek konflik letter dengan kiriman lain pada hari yang sama dari supplier yang sama
     */
    private function cekKonflikHariIni(array $details, int $supplierId, string $tanggal, ?int $excludeBdId = null): ?string
    {
        $incomingKeys = array_map(fn($item) =>
            strtolower(trim($item['nama_produk'])) . '|' . strtolower(trim($item['ukuran'] ?? '')),
            $details
        );

        $queryExisting = DetailBarangDatang::whereHas('barangDatang', function ($q) use ($supplierId, $tanggal, $excludeBdId) {
            $q->where('supplier_id', $supplierId)->where('tanggal', $tanggal);
            if ($excludeBdId) {
                $q->where('id', '!=', $excludeBdId);
            }
        })->get(['nama_produk', 'ukuran']);

        foreach ($queryExisting as $existing) {
            $existingKey = strtolower(trim($existing->nama_produk)) . '|' . strtolower(trim($existing->ukuran ?? ''));
            if (in_array($existingKey, $incomingKeys)) {
                $label = $existing->ukuran ? "{$existing->nama_produk} ({$existing->ukuran})" : $existing->nama_produk;
                return "Letter \"{$label}\" sudah ada dalam kiriman hari ini dari supplier yang sama";
            }
        }

        return null;
    }
}
