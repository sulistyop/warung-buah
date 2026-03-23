<?php

namespace App\Http\Controllers\Api;

use App\Models\Deposit;
use App\Models\KasLaci;
use App\Models\Pelanggan;
use App\Models\Transaksi;
use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DepositController extends Controller
{
    #[OA\Get(
        path: '/deposit',
        summary: 'List deposit pelanggan',
        tags: ['Deposit'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'pelanggan_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request)
    {
        $query = Deposit::with('pelanggan');

        if ($request->filled('pelanggan_id')) {
            $query->where('pelanggan_id', $request->pelanggan_id);
        }

        $deposit = $query->orderByDesc('created_at')->paginate($request->input('per_page', 20));
        return $this->success($deposit);
    }

    /**
     * Tambah deposit / titip uang pelanggan
     */
    #[OA\Post(
        path: '/deposit',
        summary: 'Tambah deposit (titip uang) pelanggan',
        tags: ['Deposit'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pelanggan_id', 'nominal'],
                properties: [
                    new OA\Property(property: 'pelanggan_id', type: 'integer'),
                    new OA\Property(property: 'nominal', type: 'number', example: 5000000),
                    new OA\Property(property: 'metode', type: 'string', enum: ['tunai', 'transfer', 'qris'], example: 'tunai'),
                    new OA\Property(property: 'referensi', type: 'string'),
                    new OA\Property(property: 'catatan', type: 'string'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Deposit berhasil ditambahkan')]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pelanggan_id' => 'required|exists:pelanggan,id',
            'nominal'      => 'required|numeric|min:1',
            'metode'       => 'nullable|in:tunai,transfer,qris',
            'referensi'    => 'nullable|string',
            'catatan'      => 'nullable|string',
        ]);

        $deposit = Deposit::create([
            'kode_deposit' => Deposit::generateKode(),
            'pelanggan_id' => $validated['pelanggan_id'],
            'nominal'      => $validated['nominal'],
            'terpakai'     => 0,
            'sisa'         => $validated['nominal'],
            'metode'       => $validated['metode'] ?? 'tunai',
            'referensi'    => $validated['referensi'] ?? null,
            'catatan'      => $validated['catatan'] ?? null,
            'user_id'      => auth()->id(),
        ]);

        $deposit->load('pelanggan');
        LogAktivitas::catat('deposit', 'create', "Deposit {$deposit->kode_deposit} Rp " . number_format($deposit->nominal) . " untuk {$deposit->pelanggan->nama}", $deposit);

        if (($validated['metode'] ?? 'tunai') === 'tunai') {
            KasLaci::create([
                'kode_kas'       => KasLaci::generateKode(),
                'tanggal'        => today(),
                'keterangan'     => "Deposit {$deposit->kode_deposit} - {$deposit->pelanggan->nama}",
                'jenis'          => 'masuk',
                'nominal'        => $deposit->nominal,
                'metode_sumber'  => 'tunai',
                'referensi_tipe' => 'deposit',
                'referensi_id'   => $deposit->id,
                'is_auto'        => true,
                'dibuat_oleh'    => auth()->id(),
            ]);
        }

        return $this->success($deposit, 'Deposit berhasil ditambahkan.', 201);
    }

    /**
     * Gunakan deposit untuk melunasi/mencicil piutang pelanggan.
     * Satu kali input bisa bayar beberapa transaksi sekaligus (FIFO).
     */
    #[OA\Post(
        path: '/deposit/bayar-piutang',
        summary: 'Gunakan deposit untuk bayar piutang (FIFO: lunasi dari yang terlama)',
        tags: ['Deposit'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pelanggan_id', 'jumlah'],
                properties: [
                    new OA\Property(property: 'pelanggan_id', type: 'integer'),
                    new OA\Property(
                        property: 'jumlah',
                        type: 'number',
                        description: 'Jumlah yang akan dibayarkan dari deposit',
                        example: 15000000
                    ),
                    new OA\Property(
                        property: 'transaksi_ids',
                        type: 'array',
                        description: 'Opsional: spesifik transaksi yang mau dibayar. Jika kosong, FIFO dari terlama.',
                        items: new OA\Items(type: 'integer')
                    ),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function bayarPiutang(Request $request)
    {
        $validated = $request->validate([
            'pelanggan_id'    => 'required|exists:pelanggan,id',
            'jumlah'          => 'required|numeric|min:1',
            'transaksi_ids'   => 'nullable|array',
            'transaksi_ids.*' => 'integer|exists:transaksi,id',
        ]);

        $pelanggan = Pelanggan::findOrFail($validated['pelanggan_id']);
        $totalDeposit = $pelanggan->total_deposit;

        if ($totalDeposit < $validated['jumlah']) {
            return $this->error(
                "Saldo deposit tidak cukup. Saldo: Rp " . number_format($totalDeposit) . ", Diperlukan: Rp " . number_format($validated['jumlah']),
                422
            );
        }

        return DB::transaction(function () use ($validated, $pelanggan) {
            $sisaBayar = $validated['jumlah'];
            $detailPembayaran = [];

            // Ambil transaksi yang akan dibayar
            $transaksiQuery = $pelanggan->transaksi()
                ->whereIn('status_bayar', ['tempo', 'cicil'])
                ->where('sisa_tagihan', '>', 0)
                ->orderBy('created_at'); // FIFO

            if (!empty($validated['transaksi_ids'])) {
                $transaksiQuery->whereIn('id', $validated['transaksi_ids']);
            }

            $transaksiList = $transaksiQuery->get();

            foreach ($transaksiList as $trx) {
                if ($sisaBayar <= 0) break;

                $bayarUntukIni = min($sisaBayar, $trx->sisa_tagihan);

                // Buat record pembayaran
                $bayar = \App\Models\Pembayaran::create([
                    'transaksi_id'    => $trx->id,
                    'kode_pembayaran' => \App\Models\Pembayaran::generateKode(),
                    'nominal'         => $bayarUntukIni,
                    'metode'          => 'deposit',
                    'catatan'         => 'Bayar via deposit ' . $pelanggan->nama,
                    'user_id'         => auth()->id(),
                ]);

                $trx->recalculate();
                $sisaBayar -= $bayarUntukIni;

                $detailPembayaran[] = [
                    'transaksi_id'   => $trx->id,
                    'kode_transaksi' => $trx->kode_transaksi,
                    'dibayar'        => $bayarUntukIni,
                    'sisa_tagihan'   => $trx->fresh()->sisa_tagihan,
                    'status_bayar'   => $trx->fresh()->status_bayar,
                ];
            }

            // Kurangi deposit (FIFO dari deposit yang paling lama)
            $sudahDikurangi = $validated['jumlah'] - $sisaBayar; // total yang berhasil dibayarkan
            $depositList = $pelanggan->deposit()->where('sisa', '>', 0)->orderBy('created_at')->get();
            $sisaKurang = $sudahDikurangi;
            foreach ($depositList as $dep) {
                if ($sisaKurang <= 0) break;
                $dipakai = $dep->gunakan($sisaKurang);
                $sisaKurang -= $dipakai;
            }

            LogAktivitas::catat('deposit', 'bayar_piutang', "Bayar piutang {$pelanggan->nama} Rp " . number_format($sudahDikurangi));

            return $this->success([
                'total_dibayar'     => $sudahDikurangi,
                'sisa_deposit'      => $pelanggan->fresh()->total_deposit,
                'detail_pembayaran' => $detailPembayaran,
            ], 'Pembayaran via deposit berhasil.');
        });
    }
}
