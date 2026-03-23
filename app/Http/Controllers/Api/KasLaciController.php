<?php

namespace App\Http\Controllers\Api;

use App\Models\KasLaci;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class KasLaciController extends Controller
{
    #[OA\Get(
        path: '/kas-laci',
        summary: 'List entri kas laci',
        tags: ['Kas Laci'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'jenis', in: 'query', schema: new OA\Schema(type: 'string', enum: ['masuk', 'keluar'])),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
            'jenis'          => 'nullable|in:masuk,keluar',
        ]);

        $query = KasLaci::with('dibuatOleh')
            ->orderBy('tanggal')
            ->orderBy('id');

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
        }
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        $entries = $query->get();

        // Hitung saldo awal dari semua entri sebelum tanggal_dari
        $saldoAwal = 0;
        if ($request->filled('tanggal_dari')) {
            $masuk  = KasLaci::where('jenis', 'masuk')->whereDate('tanggal', '<', $request->tanggal_dari)->sum('nominal');
            $keluar = KasLaci::where('jenis', 'keluar')->whereDate('tanggal', '<', $request->tanggal_dari)->sum('nominal');
            $saldoAwal = $masuk - $keluar;
        }

        // Tambahkan running saldo ke setiap baris
        $runningSaldo = $saldoAwal;
        $result = $entries->map(function ($entry) use (&$runningSaldo) {
            if ($entry->jenis === 'masuk') {
                $runningSaldo += $entry->nominal;
            } else {
                $runningSaldo -= $entry->nominal;
            }
            $entry->saldo = $runningSaldo;
            return $entry;
        });

        return $this->success([
            'saldo_awal' => $saldoAwal,
            'data'       => $result,
        ]);
    }

    #[OA\Post(
        path: '/kas-laci',
        summary: 'Input manual kas laci (masuk atau keluar)',
        tags: ['Kas Laci'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['tanggal', 'keterangan', 'jenis', 'nominal'],
                properties: [
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
                    new OA\Property(property: 'keterangan', type: 'string'),
                    new OA\Property(property: 'jenis', type: 'string', enum: ['masuk', 'keluar']),
                    new OA\Property(property: 'nominal', type: 'number'),
                    new OA\Property(property: 'metode_sumber', type: 'string', enum: ['tunai', 'transfer', 'qris', 'lainnya']),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'tanggal'       => 'required|date',
            'keterangan'    => 'required|string|max:500',
            'jenis'         => 'required|in:masuk,keluar',
            'nominal'       => 'required|numeric|min:1',
            'metode_sumber' => 'nullable|in:tunai,transfer,qris,lainnya',
        ]);

        $kas = KasLaci::create([
            'kode_kas'       => KasLaci::generateKode(),
            'tanggal'        => $request->tanggal,
            'keterangan'     => $request->keterangan,
            'jenis'          => $request->jenis,
            'nominal'        => $request->nominal,
            'metode_sumber'  => $request->input('metode_sumber', 'tunai'),
            'referensi_tipe' => 'manual',
            'referensi_id'   => null,
            'is_auto'        => false,
            'dibuat_oleh'    => auth()->id(),
        ]);

        LogAktivitas::catat(
            'kas_laci',
            'create',
            "Manual kas laci {$kas->kode_kas}: {$kas->jenis} Rp " . number_format($kas->nominal) . " - {$kas->keterangan}",
            $kas
        );

        $kas->load('dibuatOleh');

        return $this->success($kas, 'Kas laci berhasil dicatat', 201);
    }

    #[OA\Get(
        path: '/kas-laci/summary',
        summary: 'Ringkasan kas laci (total masuk, keluar, saldo)',
        tags: ['Kas Laci'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tanggal_dari', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function summary(Request $request)
    {
        $request->validate([
            'tanggal_dari'   => 'nullable|date',
            'tanggal_sampai' => 'nullable|date',
        ]);

        $query = KasLaci::query();

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
        }

        $totalMasuk  = (clone $query)->where('jenis', 'masuk')->sum('nominal');
        $totalKeluar = (clone $query)->where('jenis', 'keluar')->sum('nominal');

        $saldoKas = KasLaci::where('jenis', 'masuk')->sum('nominal')
                  - KasLaci::where('jenis', 'keluar')->sum('nominal');

        return $this->success([
            'total_masuk'   => $totalMasuk,
            'total_keluar'  => $totalKeluar,
            'saldo_periode' => $totalMasuk - $totalKeluar,
            'saldo_kas'     => $saldoKas,
        ]);
    }

    #[OA\Delete(
        path: '/kas-laci/{id}',
        summary: 'Hapus entri kas laci manual',
        tags: ['Kas Laci'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 403, description: 'Entri otomatis tidak dapat dihapus'),
            new OA\Response(response: 404, description: 'Not Found'),
        ]
    )]
    public function destroy(int $id)
    {
        $kas = KasLaci::find($id);

        if (!$kas) {
            return $this->error('Entri kas laci tidak ditemukan', 404);
        }

        if ($kas->is_auto) {
            return $this->error(
                'Entri otomatis tidak dapat dihapus secara manual. Hapus transaksi atau pembayaran sumbernya.',
                403
            );
        }

        DB::beginTransaction();
        try {
            LogAktivitas::catat(
                'kas_laci',
                'delete',
                "Hapus kas laci {$kas->kode_kas}: {$kas->jenis} Rp " . number_format($kas->nominal),
                $kas
            );
            $kas->delete();
            DB::commit();

            return $this->success(null, 'Entri kas laci berhasil dihapus');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Gagal menghapus: ' . $e->getMessage(), 500);
        }
    }
}
