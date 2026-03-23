<?php

namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SupplierController extends Controller
{
    /**
     * Get all suppliers with filters
     */
    #[OA\Get(
        path: '/supplier',
        summary: 'List semua supplier',
        description: 'Dapatkan daftar supplier dengan filter dan pagination',
        operationId: 'getSupplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Jumlah per halaman', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'cari', in: 'query', description: 'Cari berdasarkan nama/kode/telepon/kota', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter status', schema: new OA\Schema(type: 'string', enum: ['aktif', 'nonaktif'])),
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
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Supplier')),
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
        $query = Supplier::query();

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_supplier', 'like', "%{$cari}%")
                  ->orWhere('kode_supplier', 'like', "%{$cari}%")
                  ->orWhere('telepon', 'like', "%{$cari}%")
                  ->orWhere('kota', 'like', "%{$cari}%");
            });
        }

        if ($request->status === 'aktif') {
            $query->where('aktif', true);
        } elseif ($request->status === 'nonaktif') {
            $query->where('aktif', false);
        }

        $perPage = $request->input('per_page', 20);
        $supplier = $query->orderBy('nama_supplier')->paginate($perPage);

        return $this->success($supplier);
    }

    /**
     * Get single supplier
     */
    #[OA\Get(
        path: '/supplier/{id}',
        summary: 'Detail supplier',
        description: 'Dapatkan detail supplier berdasarkan ID',
        operationId: 'getSupplierDetail',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Supplier', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Supplier'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Supplier tidak ditemukan'),
        ]
    )]
    public function show(int $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->error('Supplier tidak ditemukan', 404);
        }

        return $this->success($supplier);
    }

    /**
     * Create new supplier
     */
    #[OA\Post(
        path: '/supplier',
        summary: 'Tambah supplier baru',
        description: 'Tambahkan supplier baru ke sistem',
        operationId: 'createSupplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_supplier'],
                properties: [
                    new OA\Property(property: 'nama_supplier', type: 'string', example: 'PT Buah Segar'),
                    new OA\Property(property: 'telepon', type: 'string', example: '08123456789', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'supplier@example.com', nullable: true),
                    new OA\Property(property: 'alamat', type: 'string', example: 'Jl. Pasar Buah No. 123', nullable: true),
                    new OA\Property(property: 'kota', type: 'string', example: 'Jakarta', nullable: true),
                    new OA\Property(property: 'kontak_person', type: 'string', example: 'Budi', nullable: true),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Supplier berhasil ditambahkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Supplier berhasil ditambahkan'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Supplier'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'telepon'       => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'alamat'        => 'nullable|string',
            'kota'          => 'nullable|string|max:100',
            'kontak_person' => 'nullable|string|max:255',
            'catatan'       => 'nullable|string',
            'komisi_persen' => 'nullable|numeric|min:0|max:100',
            'kuli_per_peti' => 'nullable|numeric|min:0',
        ]);

        $supplier = Supplier::create([
            'kode_supplier' => Supplier::generateKode(),
            'nama_supplier' => $request->nama_supplier,
            'telepon'       => $request->telepon,
            'email'         => $request->email,
            'alamat'        => $request->alamat,
            'kota'          => $request->kota,
            'kontak_person' => $request->kontak_person,
            'catatan'       => $request->catatan,
            'komisi_persen' => $request->input('komisi_persen', 0),
            'kuli_per_peti' => $request->input('kuli_per_peti', 0),
            'aktif'         => true,
        ]);

        return $this->success($supplier, 'Supplier berhasil ditambahkan', 201);
    }

    /**
     * Update supplier
     */
    #[OA\Put(
        path: '/supplier/{id}',
        summary: 'Update supplier',
        description: 'Update data supplier yang sudah ada',
        operationId: 'updateSupplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Supplier', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nama_supplier', type: 'string', example: 'PT Buah Segar Updated'),
                    new OA\Property(property: 'telepon', type: 'string', nullable: true),
                    new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                    new OA\Property(property: 'alamat', type: 'string', nullable: true),
                    new OA\Property(property: 'kota', type: 'string', nullable: true),
                    new OA\Property(property: 'kontak_person', type: 'string', nullable: true),
                    new OA\Property(property: 'catatan', type: 'string', nullable: true),
                    new OA\Property(property: 'aktif', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Supplier berhasil diupdate',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Supplier berhasil diupdate'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Supplier'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Supplier tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, int $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->error('Supplier tidak ditemukan', 404);
        }

        $request->validate([
            'nama_supplier' => 'sometimes|required|string|max:255',
            'telepon'       => 'nullable|string|max:20',
            'email'         => 'nullable|email|max:255',
            'alamat'        => 'nullable|string',
            'kota'          => 'nullable|string|max:100',
            'kontak_person' => 'nullable|string|max:255',
            'catatan'       => 'nullable|string',
            'komisi_persen' => 'nullable|numeric|min:0|max:100',
            'kuli_per_peti' => 'nullable|numeric|min:0',
            'aktif'         => 'sometimes|boolean',
        ]);

        $supplier->update($request->only([
            'nama_supplier', 'telepon', 'email', 'alamat',
            'kota', 'kontak_person', 'catatan',
            'komisi_persen', 'kuli_per_peti', 'aktif',
        ]));

        return $this->success($supplier, 'Supplier berhasil diupdate');
    }

    /**
     * Delete supplier
     */
    #[OA\Delete(
        path: '/supplier/{id}',
        summary: 'Hapus supplier',
        description: 'Hapus supplier dari sistem',
        operationId: 'deleteSupplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Supplier', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Supplier berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Supplier berhasil dihapus'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Supplier tidak ditemukan'),
        ]
    )]
    public function destroy(int $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return $this->error('Supplier tidak ditemukan', 404);
        }

        $blockers = [];

        if ($supplier->itemTransaksi()->exists()) {
            $blockers[] = 'transaksi';
        }

        if ($supplier->barangDatang()->exists()) {
            $blockers[] = 'barang datang';
        }

        if ($supplier->rekap()->exists()) {
            $blockers[] = 'rekap';
        }

        if (!empty($blockers)) {
            $supplier->update(['aktif' => false]);
            $msg = 'Supplier dinonaktifkan karena terkait: ' . implode(', ', $blockers);
            return $this->success($supplier, $msg);
        }

        $supplier->delete();

        return $this->success(null, 'Supplier berhasil dihapus');
    }

    /**
     * Search supplier (for autocomplete)
     */
    #[OA\Get(
        path: '/supplier/search',
        summary: 'Search supplier',
        description: 'Cari supplier untuk autocomplete',
        operationId: 'searchSupplier',
        tags: ['Supplier'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'term', in: 'query', description: 'Kata kunci pencarian', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Limit hasil', schema: new OA\Schema(type: 'integer', default: 10)),
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
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer'),
                                    new OA\Property(property: 'kode_supplier', type: 'string'),
                                    new OA\Property(property: 'nama_supplier', type: 'string'),
                                    new OA\Property(property: 'telepon', type: 'string'),
                                    new OA\Property(property: 'kota', type: 'string'),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function search(Request $request)
    {
        $term = $request->input('term', '');
        $limit = $request->input('limit', 10);

        $supplier = Supplier::aktif()
            ->where(function ($q) use ($term) {
                $q->where('nama_supplier', 'like', "%{$term}%")
                  ->orWhere('kode_supplier', 'like', "%{$term}%");
            })
            ->limit($limit)
            ->get(['id', 'kode_supplier', 'nama_supplier', 'telepon', 'kota']);

        return $this->success($supplier);
    }
}
