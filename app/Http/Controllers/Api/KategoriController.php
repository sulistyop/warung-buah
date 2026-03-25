<?php

namespace App\Http\Controllers\Api;

use App\Models\Kategori;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class KategoriController extends Controller
{
    /**
     * Get all kategori with filters
     */
    #[OA\Get(
        path: '/kategori',
        summary: 'List semua kategori',
        description: 'Dapatkan daftar kategori dengan filter dan pagination',
        operationId: 'getKategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Jumlah per halaman', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'cari', in: 'query', description: 'Cari berdasarkan kode/nama', schema: new OA\Schema(type: 'string')),
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
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Kategori')),
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
        $query = Kategori::query();

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('kode_kategori', 'like', "%{$cari}%")
                  ->orWhere('nama_kategori', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('aktif', $request->status === 'aktif');
        }

        $perPage = $request->input('per_page', 20);
        $kategori = $query->withCount('detailBarangDatang as produk_count')->orderBy('nama_kategori')->paginate($perPage);

        return $this->success($kategori);
    }

    /**
     * Get all kategori aktif (tanpa pagination)
     */
    #[OA\Get(
        path: '/kategori/all',
        summary: 'List semua kategori aktif',
        description: 'Dapatkan semua kategori aktif tanpa pagination (untuk dropdown)',
        operationId: 'getAllKategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Kategori')),
                    ]
                )
            ),
        ]
    )]
    public function all()
    {
        $kategori = Kategori::aktif()->orderBy('nama_kategori')->get();
        return $this->success($kategori);
    }

    /**
     * Get single kategori
     */
    #[OA\Get(
        path: '/kategori/{id}',
        summary: 'Detail kategori',
        description: 'Dapatkan detail kategori berdasarkan ID',
        operationId: 'getKategoriDetail',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Kategori', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Kategori'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan'),
        ]
    )]
    public function show(int $id)
    {
        $kategori = Kategori::withCount('detailBarangDatang as produk_count')->find($id);

        if (!$kategori) {
            return $this->error('Kategori tidak ditemukan', 404);
        }

        return $this->success($kategori);
    }

    /**
     * Get warna options
     */
    #[OA\Get(
        path: '/kategori/warna-options',
        summary: 'Opsi warna kategori',
        description: 'Dapatkan daftar opsi warna untuk kategori',
        operationId: 'getKategoriWarnaOptions',
        tags: ['Kategori'],
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
                            example: ['#4CAF50' => 'Hijau', '#2196F3' => 'Biru', '#FF9800' => 'Orange']
                        ),
                    ]
                )
            ),
        ]
    )]
    public function warnaOptions()
    {
        return $this->success(Kategori::getWarnaOptions());
    }

    /**
     * Create new kategori
     */
    #[OA\Post(
        path: '/kategori',
        summary: 'Tambah kategori baru',
        description: 'Tambahkan kategori baru ke sistem',
        operationId: 'createKategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama_kategori', 'warna'],
                properties: [
                    new OA\Property(property: 'nama_kategori', type: 'string', example: 'Buah Lokal'),
                    new OA\Property(property: 'deskripsi', type: 'string', example: 'Buah-buahan lokal Indonesia', nullable: true),
                    new OA\Property(property: 'warna', type: 'string', example: '#4CAF50'),
                    new OA\Property(property: 'aktif', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kategori berhasil ditambahkan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil ditambahkan'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Kategori'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'deskripsi' => 'nullable|string|max:255',
            'warna' => 'required|string|max:20',
            'aktif' => 'nullable|boolean',
        ]);

        $kategori = Kategori::create([
            'kode_kategori' => Kategori::generateKode(),
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'warna' => $request->warna,
            'aktif' => $request->aktif ?? true,
        ]);

        return $this->success($kategori, 'Kategori berhasil ditambahkan', 201);
    }

    /**
     * Update kategori
     */
    #[OA\Put(
        path: '/kategori/{id}',
        summary: 'Update kategori',
        description: 'Update data kategori yang sudah ada',
        operationId: 'updateKategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Kategori', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'nama_kategori', type: 'string', example: 'Buah Lokal Updated'),
                    new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
                    new OA\Property(property: 'warna', type: 'string', example: '#2196F3'),
                    new OA\Property(property: 'aktif', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Kategori berhasil diupdate',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil diupdate'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/Kategori'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, int $id)
    {
        $kategori = Kategori::find($id);

        if (!$kategori) {
            return $this->error('Kategori tidak ditemukan', 404);
        }

        $request->validate([
            'nama_kategori' => 'sometimes|required|string|max:100',
            'deskripsi' => 'nullable|string|max:255',
            'warna' => 'sometimes|required|string|max:20',
            'aktif' => 'sometimes|boolean',
        ]);

        $kategori->update($request->only(['nama_kategori', 'deskripsi', 'warna', 'aktif']));

        return $this->success($kategori, 'Kategori berhasil diupdate');
    }

    /**
     * Delete kategori
     */
    #[OA\Delete(
        path: '/kategori/{id}',
        summary: 'Hapus kategori',
        description: 'Hapus kategori dari sistem',
        operationId: 'deleteKategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'ID Kategori', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Kategori berhasil dihapus',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Kategori berhasil dihapus'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Kategori tidak ditemukan'),
            new OA\Response(response: 400, description: 'Kategori tidak bisa dihapus'),
        ]
    )]
    public function destroy(int $id)
    {
        $kategori = Kategori::find($id);

        if (!$kategori) {
            return $this->error('Kategori tidak ditemukan', 404);
        }

        if ($kategori->detailBarangDatang()->count() > 0) {
            return $this->error('Kategori tidak bisa dihapus karena masih ada produk terkait', 400);
        }

        $kategori->delete();

        return $this->success(null, 'Kategori berhasil dihapus');
    }

    /**
     * Search kategori (for autocomplete)
     */
    #[OA\Get(
        path: '/kategori/search',
        summary: 'Search kategori',
        description: 'Cari kategori untuk autocomplete',
        operationId: 'searchKategori',
        tags: ['Kategori'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Kata kunci pencarian', schema: new OA\Schema(type: 'string')),
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
                                    new OA\Property(property: 'kode_kategori', type: 'string'),
                                    new OA\Property(property: 'nama_kategori', type: 'string'),
                                    new OA\Property(property: 'warna', type: 'string'),
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
        $query = $request->get('q', '');
        $limit = $request->get('limit', 10);

        $kategori = Kategori::aktif()
            ->where(function ($q) use ($query) {
                $q->where('nama_kategori', 'like', "%{$query}%")
                  ->orWhere('kode_kategori', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get(['id', 'kode_kategori', 'nama_kategori', 'warna']);

        return $this->success($kategori);
    }
}
