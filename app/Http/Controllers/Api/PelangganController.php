<?php

namespace App\Http\Controllers\Api;

use App\Models\Pelanggan;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PelangganController extends Controller
{
    #[OA\Get(
        path: '/pelanggan',
        summary: 'List pelanggan',
        tags: ['Pelanggan'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'cari', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'aktif', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request)
    {
        $query = Pelanggan::query();

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('toko', 'like', "%{$cari}%")
                  ->orWhere('telepon', 'like', "%{$cari}%")
                  ->orWhere('kode_pelanggan', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('aktif')) {
            $query->where('aktif', filter_var($request->aktif, FILTER_VALIDATE_BOOLEAN));
        }

        $pelanggan = $query->orderBy('nama')
            ->paginate($request->input('per_page', 50));

        $pelanggan->getCollection()->each->append(['total_piutang', 'total_deposit']);

        return $this->success($pelanggan);
    }

    #[OA\Get(
        path: '/pelanggan/all',
        summary: 'Semua pelanggan aktif (untuk dropdown)',
        tags: ['Pelanggan'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function all()
    {
        $pelanggan = Pelanggan::aktif()->orderBy('nama')->get(['id', 'kode_pelanggan', 'nama', 'toko', 'telepon']);
        return $this->success($pelanggan);
    }

    #[OA\Post(
        path: '/pelanggan',
        summary: 'Tambah pelanggan baru',
        tags: ['Pelanggan'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nama'],
                properties: [
                    new OA\Property(property: 'nama', type: 'string'),
                    new OA\Property(property: 'telepon', type: 'string'),
                    new OA\Property(property: 'toko', type: 'string'),
                    new OA\Property(property: 'alamat', type: 'string'),
                    new OA\Property(property: 'catatan', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'    => 'required|string|max:100',
            'telepon' => 'nullable|string|max:20',
            'toko'    => 'nullable|string|max:100',
            'alamat'  => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        $pelanggan = Pelanggan::create([
            ...$validated,
            'kode_pelanggan' => Pelanggan::generateKode(),
            'aktif'          => true,
        ]);

        LogAktivitas::catat('pelanggan', 'create', "Pelanggan {$pelanggan->nama} ditambahkan", $pelanggan);

        return $this->success($pelanggan, 'Pelanggan berhasil ditambahkan.', 201);
    }

    #[OA\Get(
        path: '/pelanggan/{id}',
        summary: 'Detail pelanggan + ringkasan piutang & deposit',
        tags: ['Pelanggan'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function show(int $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        $data = $pelanggan->toArray();
        $data['total_piutang'] = $pelanggan->total_piutang;
        $data['total_deposit'] = $pelanggan->total_deposit;

        // Transaksi yang belum lunas
        $data['piutang'] = $pelanggan->transaksi()
            ->whereIn('status_bayar', ['tempo', 'cicil'])
            ->orderBy('created_at')
            ->get(['id', 'kode_transaksi', 'total_tagihan', 'total_dibayar', 'sisa_tagihan', 'status_bayar', 'tanggal_jatuh_tempo', 'created_at']);

        // Deposit aktif
        $data['deposit_list'] = $pelanggan->deposit()
            ->where('sisa', '>', 0)
            ->orderByDesc('created_at')
            ->get(['id', 'kode_deposit', 'nominal', 'terpakai', 'sisa', 'metode', 'created_at']);

        return $this->success($data);
    }

    #[OA\Put(
        path: '/pelanggan/{id}',
        summary: 'Update pelanggan',
        tags: ['Pelanggan'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function update(Request $request, int $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        $validated = $request->validate([
            'nama'    => 'sometimes|string|max:100',
            'telepon' => 'nullable|string|max:20',
            'toko'    => 'nullable|string|max:100',
            'alamat'  => 'nullable|string',
            'catatan' => 'nullable|string',
            'aktif'   => 'sometimes|boolean',
        ]);

        $pelanggan->update($validated);
        LogAktivitas::catat('pelanggan', 'update', "Pelanggan {$pelanggan->nama} diupdate", $pelanggan);

        return $this->success($pelanggan, 'Pelanggan berhasil diupdate.');
    }

    #[OA\Delete(
        path: '/pelanggan/{id}',
        summary: 'Hapus pelanggan',
        tags: ['Pelanggan'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function destroy(int $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        // Jangan hapus jika masih ada piutang
        if ($pelanggan->total_piutang > 0) {
            return $this->error('Pelanggan masih memiliki piutang. Selesaikan dulu sebelum menghapus.', 422);
        }

        $pelanggan->delete();
        return $this->success(null, 'Pelanggan berhasil dihapus.');
    }
}
