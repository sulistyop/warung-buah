<?php

namespace App\Http\Controllers\Api;

use App\Models\BarangDatang;
use App\Models\DetailBarangDatang;
use App\Models\Rekap;
use App\Models\DetailRekap;
use App\Models\KomplainRekap;
use App\Models\PengurangRekap;
use App\Models\LogAktivitas;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class RekapController extends Controller
{
    /**
     * List semua rekap
     */
    #[OA\Get(
        path: '/rekap',
        summary: 'List rekap harian supplier',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal_dari', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'tanggal_sampai', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'final'])),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function index(Request $request)
    {
        $query = Rekap::with(['supplier', 'dibuatOleh']);

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

        $rekap = $query->orderByDesc('tanggal')->orderByDesc('id')
            ->paginate($request->input('per_page', 20));

        return $this->success($rekap);
    }

    /**
     * Cek apakah supplier+tanggal sudah bisa direkap (semua produk habis)
     */
    #[OA\Get(
        path: '/rekap/cek-siap/{supplier_id}/{tanggal}',
        summary: 'Cek apakah rekap bisa dibuat (semua stok habis)',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function cekSiapRekap(int $supplierId, string $tanggal)
    {
        $barangDatangList = BarangDatang::where('supplier_id', $supplierId)
            ->where('tanggal', $tanggal)
            ->where('status', 'confirmed')
            ->with('details')
            ->get();

        if ($barangDatangList->isEmpty()) {
            return $this->error('Tidak ada barang datang confirmed untuk supplier dan tanggal ini.', 422);
        }

        $belumHabis = [];
        foreach ($barangDatangList as $bd) {
            foreach ($bd->details as $detail) {
                if ($detail->status_stok !== 'habis') {
                    $belumHabis[] = [
                        'kode_bd'     => $bd->kode_bd,
                        'nama_produk' => $detail->nama_produk,
                        'ukuran'      => $detail->ukuran,
                        'stok_sisa'   => $detail->stok_sisa,
                    ];
                }
            }
        }

        $siap = empty($belumHabis);

        return $this->success([
            'siap'        => $siap,
            'belum_habis' => $belumHabis,
            'pesan'       => $siap
                ? 'Semua produk sudah habis. Rekap bisa dibuat.'
                : 'Ada ' . count($belumHabis) . ' produk yang belum habis. Rekap belum bisa dibuat.',
        ]);
    }

    /**
     * List semua supplier+tanggal yang sudah siap direkap (semua stok habis, belum ada rekap)
     * Query params: supplier_id (optional), tanggal (optional)
     */
    #[OA\Get(
        path: '/rekap/siap-direkap',
        summary: 'Cari rekap yang tersedia — supplier+tanggal siap direkap',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function siapDirekap(Request $request)
    {
        // Ambil semua barang datang confirmed yang semua detailnya sudah habis
        // dan belum ada rekap untuk supplier+tanggal tersebut
        $query = BarangDatang::where('status', 'confirmed')
            ->with(['supplier', 'details']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('tanggal')) {
            $query->where('tanggal', $request->tanggal);
        }

        $barangDatangList = $query->get();

        // Group by supplier_id + tanggal
        $grouped = [];
        foreach ($barangDatangList as $bd) {
            $key = $bd->supplier_id . '||' . $bd->tanggal->toDateString();
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'supplier_id'    => $bd->supplier_id,
                    'supplier_nama'  => $bd->supplier->nama_supplier ?? '-',
                    'tanggal'        => $bd->tanggal->toDateString(),
                    'semua_habis'    => true,
                    'total_letter'   => 0,
                    'kode_bd_list'   => [],
                ];
            }
            $grouped[$key]['kode_bd_list'][] = $bd->kode_bd;

            foreach ($bd->details as $detail) {
                $grouped[$key]['total_letter']++;
                if ($detail->status_stok !== 'habis') {
                    $grouped[$key]['semua_habis'] = false;
                }
            }
        }

        // Filter: hanya yang semua_habis = true
        $siap = array_filter($grouped, fn($g) => $g['semua_habis']);

        // Filter: belum ada rekap untuk supplier+tanggal tersebut
        $result = [];
        foreach ($siap as $item) {
            $sudahAda = Rekap::where('supplier_id', $item['supplier_id'])
                ->where('tanggal', $item['tanggal'])
                ->exists();

            if (!$sudahAda) {
                $result[] = [
                    'supplier_id'   => $item['supplier_id'],
                    'supplier_nama' => $item['supplier_nama'],
                    'tanggal'       => $item['tanggal'],
                    'total_letter'  => $item['total_letter'],
                    'kode_bd_list'  => $item['kode_bd_list'],
                ];
            }
        }

        // Sort by tanggal desc
        usort($result, fn($a, $b) => strcmp($b['tanggal'], $a['tanggal']));

        return $this->success(array_values($result));
    }

    /**
     * Suggestion detail rekap dari data barang datang (untuk pre-fill form Flutter)
     */
    #[OA\Get(
        path: '/rekap/suggestion/{supplier_id}/{tanggal}',
        summary: 'Suggestion detail rekap dari barang datang (pre-fill form)',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'supplier_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tanggal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function suggestionRekap(int $supplierId, string $tanggal)
    {
        $barangDatangList = BarangDatang::where('supplier_id', $supplierId)
            ->where('tanggal', $tanggal)
            ->where('status', 'confirmed')
            ->with(['details.itemTransaksi.detailPeti', 'details.itemTransaksi.transaksi.komplainTransaksi'])
            ->get();

        if ($barangDatangList->isEmpty()) {
            return $this->error('Tidak ada barang datang confirmed untuk supplier dan tanggal ini.', 422);
        }

        // Satu baris per item_transaksi — sesuai format nota rekap (bisa beda harga per batch)
        $rows = [];
        foreach ($barangDatangList as $bd) {
            foreach ($bd->details as $detail) {
                foreach ($detail->itemTransaksi as $item) {
                    $beratKotor  = 0.0;
                    $beratKemasan = 0.0;

                    foreach ($item->detailPeti as $peti) {
                        $beratKotor   += $peti->berat_kotor;
                        $beratKemasan += $peti->berat_kemasan;
                    }

                    // Kurangi berat bersih dengan kg busuk via FK item_transaksi_id
                    $totalBusuk = $item->transaksi
                        ? $item->transaksi->komplainTransaksi
                            ->where('item_transaksi_id', $item->id)
                            ->sum('jumlah_bs')
                        : 0;

                    $beratBersih = round(max(0, (float) $item->total_berat_bersih - $totalBusuk), 3);
                    $harga       = (float) ($item->harga_per_kg ?: $detail->harga_beli);
                    $subtotal    = round($beratBersih * $harga, 2);

                    $rows[] = [
                        'nama_produk'        => $detail->nama_produk,
                        'ukuran'             => $detail->ukuran,
                        'harga_per_kg'       => $harga,
                        'jumlah_peti'        => (int) $item->jumlah_peti,
                        'total_berat_kotor'  => round($beratKotor, 3),
                        'total_berat_peti'   => round($beratKemasan, 3),
                        'total_berat_bersih' => $beratBersih,
                        'subtotal'           => $subtotal,
                        '_kode_bd'           => $bd->kode_bd,
                        '_item_transaksi_id' => $item->id,
                    ];
                }
            }
        }

        // Urutkan: nama_produk → ukuran → item_transaksi_id (urutan masuk)
        usort($rows, fn($a, $b) =>
            $a['nama_produk'] <=> $b['nama_produk']
            ?: ($a['ukuran'] ?? '') <=> ($b['ukuran'] ?? '')
            ?: $a['_item_transaksi_id'] <=> $b['_item_transaksi_id']
        );

        return $this->success([
            'suggestions'  => $rows,
            'total_letter' => count($rows),
        ]);
    }

    /**
     * Buat rekap baru (manual atau otomatis dari barang datang yang sudah habis)
     */
    #[OA\Post(
        path: '/rekap',
        summary: 'Buat rekap harian supplier',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['supplier_id', 'tanggal'],
                properties: [
                    new OA\Property(property: 'supplier_id', type: 'integer'),
                    new OA\Property(property: 'tanggal', type: 'string', format: 'date'),
                    new OA\Property(property: 'total_ongkos', type: 'number', example: 760000),
                    new OA\Property(property: 'keterangan_ongkos', type: 'string', example: 'Ongkos angkut'),
                    new OA\Property(
                        property: 'details',
                        type: 'array',
                        description: 'Detail per letter/produk. Jika kosong, digenerate dari barang datang.',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'nama_produk', type: 'string'),
                                new OA\Property(property: 'ukuran', type: 'string'),
                                new OA\Property(property: 'jumlah_peti', type: 'integer'),
                                new OA\Property(property: 'total_berat_kotor', type: 'number'),
                                new OA\Property(property: 'total_berat_peti', type: 'number'),
                                new OA\Property(property: 'total_berat_bersih', type: 'number'),
                                new OA\Property(property: 'harga_per_kg', type: 'number'),
                            ]
                        )
                    ),
                    new OA\Property(
                        property: 'komplain',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'nama_produk', type: 'string'),
                                new OA\Property(property: 'jumlah_bs', type: 'integer'),
                                new OA\Property(property: 'harga_ganti', type: 'number'),
                                new OA\Property(property: 'keterangan', type: 'string'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Rekap berhasil dibuat'),
            new OA\Response(response: 422, description: 'Ada produk belum habis'),
        ]
    )]
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id'        => 'required|exists:supplier,id',
            'tanggal'            => 'required|date',
            'total_kuli'         => 'nullable|numeric|min:0',
            'total_ongkos'       => 'nullable|numeric|min:0',
            'keterangan_ongkos'  => 'nullable|string',
            'details'            => 'nullable|array',
            'details.*.nama_produk'        => 'required_with:details|string',
            'details.*.ukuran'             => 'nullable|string',
            'details.*.jumlah_peti'        => 'required_with:details|integer|min:1',
            'details.*.total_berat_kotor'  => 'required_with:details|numeric|min:0',
            'details.*.total_berat_peti'   => 'required_with:details|numeric|min:0',
            'details.*.total_berat_bersih' => 'required_with:details|numeric|min:0',
            'details.*.harga_per_kg'       => 'required_with:details|numeric|min:0',
            'komplain'                     => 'nullable|array',
            'komplain.*.nama_produk'       => 'required_with:komplain|string',
            'komplain.*.jumlah_bs'         => 'required_with:komplain|integer|min:1',
            'komplain.*.harga_ganti'       => 'required_with:komplain|numeric|min:0',
            'komplain.*.keterangan'        => 'nullable|string',
            'pengurang'                    => 'nullable|array',
            'pengurang.*.nama'             => 'required_with:pengurang|string',
            'pengurang.*.jumlah'           => 'required_with:pengurang|numeric|min:0',
        ]);

        // Cek apakah rekap untuk supplier+tanggal ini sudah ada
        $existing = Rekap::where('supplier_id', $validated['supplier_id'])
            ->where('tanggal', $validated['tanggal'])
            ->first();

        if ($existing) {
            return $this->error('Rekap untuk supplier dan tanggal ini sudah ada (ID: ' . $existing->id . ').', 422);
        }

        // Cek semua produk sudah habis
        $barangDatangList = BarangDatang::where('supplier_id', $validated['supplier_id'])
            ->where('tanggal', $validated['tanggal'])
            ->where('status', 'confirmed')
            ->with('details')
            ->get();

        if ($barangDatangList->isEmpty()) {
            return $this->error('Tidak ada barang datang confirmed untuk supplier dan tanggal ini.', 422);
        }

        $belumHabis = [];
        foreach ($barangDatangList as $bd) {
            foreach ($bd->details as $detail) {
                if ($detail->status_stok !== 'habis') {
                    $belumHabis[] = $detail->nama_produk . ' (' . $detail->ukuran . ') sisa ' . $detail->stok_sisa;
                }
            }
        }

        if (!empty($belumHabis)) {
            return $this->error(
                'Rekap belum bisa dibuat. Produk berikut belum habis: ' . implode(', ', $belumHabis),
                422,
                ['belum_habis' => $belumHabis]
            );
        }

        return DB::transaction(function () use ($validated, $barangDatangList, $request) {
            $supplier = \App\Models\Supplier::find($validated['supplier_id']);

            $rekap = Rekap::create([
                'kode_rekap'        => Rekap::generateKode($validated['tanggal']),
                'supplier_id'       => $validated['supplier_id'],
                'tanggal'           => $validated['tanggal'],
                'komisi_persen'     => $supplier->komisi_persen,
                'kuli_per_peti'     => 0,
                'total_kuli'        => $validated['total_kuli'] ?? 0,
                'total_ongkos'      => $validated['total_ongkos'] ?? 0,
                'keterangan_ongkos' => $validated['keterangan_ongkos'] ?? null,
                'status'            => 'draft',
                'dibuat_oleh'       => auth()->id(),
            ]);

            // Simpan detail — jika kosong, generate otomatis dari barang datang
            $detailsToSave = $validated['details'] ?? [];

            if (empty($detailsToSave)) {
                // Auto-generate: aggregate per (nama_produk, ukuran) dari semua kiriman hari itu
                $grouped = [];
                foreach ($barangDatangList as $bd) {
                    foreach ($bd->details as $detail) {
                        $key = $detail->nama_produk . '||' . ($detail->ukuran ?? '');
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'nama_produk'  => $detail->nama_produk,
                                'ukuran'       => $detail->ukuran,
                                'harga_per_kg' => $detail->harga_beli,
                                'stok_total'   => 0,
                            ];
                        }
                        $grouped[$key]['stok_total'] += $detail->stok_awal ?? $detail->jumlah;
                    }
                }
                foreach ($grouped as $item) {
                    // Tanpa berat fisik: berat bersih = stok_total (jumlah kg), peti = 0
                    $beratBersih = $item['stok_total'];
                    $subtotal    = $beratBersih * $item['harga_per_kg'];
                    DetailRekap::create([
                        'rekap_id'           => $rekap->id,
                        'nama_produk'        => $item['nama_produk'],
                        'ukuran'             => $item['ukuran'],
                        'jumlah_peti'        => 0,
                        'total_berat_kotor'  => $beratBersih,
                        'total_berat_peti'   => 0,
                        'total_berat_bersih' => $beratBersih,
                        'harga_per_kg'       => $item['harga_per_kg'],
                        'subtotal'           => $subtotal,
                    ]);
                }
            } else {
                foreach ($detailsToSave as $d) {
                    $subtotal = $d['total_berat_bersih'] * $d['harga_per_kg'];
                    DetailRekap::create([
                        'rekap_id'           => $rekap->id,
                        'nama_produk'        => $d['nama_produk'],
                        'ukuran'             => $d['ukuran'] ?? null,
                        'jumlah_peti'        => $d['jumlah_peti'],
                        'total_berat_kotor'  => $d['total_berat_kotor'],
                        'total_berat_peti'   => $d['total_berat_peti'],
                        'total_berat_bersih' => $d['total_berat_bersih'],
                        'harga_per_kg'       => $d['harga_per_kg'],
                        'subtotal'           => $subtotal,
                    ]);
                }
            }

            // Simpan komplain — resolve detail_rekap_id dari nama_produk
            if (!empty($validated['komplain'])) {
                $detailMap = DetailRekap::where('rekap_id', $rekap->id)
                    ->get(['id', 'nama_produk'])
                    ->keyBy('nama_produk');

                foreach ($validated['komplain'] as $k) {
                    KomplainRekap::create([
                        'rekap_id'        => $rekap->id,
                        'detail_rekap_id' => $detailMap[$k['nama_produk']]?->id ?? null,
                        'nama_produk'     => $k['nama_produk'],
                        'jumlah_bs'       => $k['jumlah_bs'],
                        'harga_ganti'     => $k['harga_ganti'] ?? 0,
                        'total'           => $k['jumlah_bs'] * ($k['harga_ganti'] ?? 0),
                        'keterangan'      => $k['keterangan'] ?? null,
                    ]);
                }
            }

            // Simpan pengurang opsional (biaya peti, dll)
            if (!empty($validated['pengurang'])) {
                foreach ($validated['pengurang'] as $p) {
                    PengurangRekap::create([
                        'rekap_id' => $rekap->id,
                        'nama'     => $p['nama'],
                        'jumlah'   => $p['jumlah'],
                    ]);
                }
            }

            $rekap->recalculate();
            $rekap->load(['supplier', 'details', 'komplain', 'pengurang', 'dibuatOleh']);

            LogAktivitas::catat('rekap', 'create', "Rekap {$rekap->kode_rekap} dibuat", $rekap);

            return $this->success($rekap, 'Rekap berhasil dibuat.', 201);
        });
    }

    /**
     * Detail rekap
     */
    #[OA\Get(
        path: '/rekap/{id}',
        summary: 'Detail rekap',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function show(int $id)
    {
        $rekap = Rekap::with(['supplier', 'details', 'komplain', 'pengurang', 'dibuatOleh'])->findOrFail($id);
        return $this->success($rekap);
    }

    /**
     * Update rekap (hanya yang masih draft)
     */
    #[OA\Put(
        path: '/rekap/{id}',
        summary: 'Update rekap (hanya draft)',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'total_ongkos', type: 'number'),
                    new OA\Property(property: 'keterangan_ongkos', type: 'string'),
                    new OA\Property(property: 'details', type: 'array', items: new OA\Items()),
                    new OA\Property(property: 'komplain', type: 'array', items: new OA\Items()),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function update(Request $request, int $id)
    {
        $rekap = Rekap::findOrFail($id);

        if (!$rekap->isDraft()) {
            return $this->error('Rekap yang sudah final tidak bisa diubah.', 422);
        }

        $validated = $request->validate([
            'total_kuli'         => 'nullable|numeric|min:0',
            'total_ongkos'       => 'nullable|numeric|min:0',
            'keterangan_ongkos'  => 'nullable|string',
            'details'            => 'nullable|array',
            'details.*.id'                 => 'nullable|exists:detail_rekap,id',
            'details.*.nama_produk'        => 'required_with:details|string',
            'details.*.ukuran'             => 'nullable|string',
            'details.*.jumlah_peti'        => 'required_with:details|integer|min:1',
            'details.*.total_berat_kotor'  => 'required_with:details|numeric|min:0',
            'details.*.total_berat_peti'   => 'required_with:details|numeric|min:0',
            'details.*.total_berat_bersih' => 'required_with:details|numeric|min:0',
            'details.*.harga_per_kg'       => 'required_with:details|numeric|min:0',
            'komplain'                     => 'nullable|array',
            'komplain.*.id'                => 'nullable|exists:komplain_rekap,id',
            'komplain.*.nama_produk'       => 'required_with:komplain|string',
            'komplain.*.jumlah_bs'         => 'required_with:komplain|integer|min:1',
            'komplain.*.harga_ganti'       => 'required_with:komplain|numeric|min:0',
            'komplain.*.keterangan'        => 'nullable|string',
            'pengurang'                    => 'nullable|array',
            'pengurang.*.nama'             => 'required_with:pengurang|string',
            'pengurang.*.jumlah'           => 'required_with:pengurang|numeric|min:0',
        ]);

        return DB::transaction(function () use ($rekap, $validated) {
            $rekap->update([
                'total_kuli'        => $validated['total_kuli'] ?? $rekap->total_kuli,
                'total_ongkos'      => $validated['total_ongkos'] ?? $rekap->total_ongkos,
                'keterangan_ongkos' => $validated['keterangan_ongkos'] ?? $rekap->keterangan_ongkos,
            ]);

            if (isset($validated['details'])) {
                $rekap->details()->delete();
                foreach ($validated['details'] as $d) {
                    DetailRekap::create([
                        'rekap_id'           => $rekap->id,
                        'nama_produk'        => $d['nama_produk'],
                        'ukuran'             => $d['ukuran'] ?? null,
                        'jumlah_peti'        => $d['jumlah_peti'],
                        'total_berat_kotor'  => $d['total_berat_kotor'],
                        'total_berat_peti'   => $d['total_berat_peti'],
                        'total_berat_bersih' => $d['total_berat_bersih'],
                        'harga_per_kg'       => $d['harga_per_kg'],
                        'subtotal'           => $d['total_berat_bersih'] * $d['harga_per_kg'],
                    ]);
                }
            }

            if (isset($validated['komplain'])) {
                $rekap->komplain()->delete();

                $detailMap = DetailRekap::where('rekap_id', $rekap->id)
                    ->get(['id', 'nama_produk'])
                    ->keyBy('nama_produk');

                foreach ($validated['komplain'] as $k) {
                    KomplainRekap::create([
                        'rekap_id'        => $rekap->id,
                        'detail_rekap_id' => $detailMap[$k['nama_produk']]?->id ?? null,
                        'nama_produk'     => $k['nama_produk'],
                        'jumlah_bs'       => $k['jumlah_bs'],
                        'harga_ganti'     => $k['harga_ganti'] ?? 0,
                        'total'           => $k['jumlah_bs'] * ($k['harga_ganti'] ?? 0),
                        'keterangan'      => $k['keterangan'] ?? null,
                    ]);
                }
            }

            if (isset($validated['pengurang'])) {
                $rekap->pengurang()->delete();
                foreach ($validated['pengurang'] as $p) {
                    PengurangRekap::create([
                        'rekap_id' => $rekap->id,
                        'nama'     => $p['nama'],
                        'jumlah'   => $p['jumlah'],
                    ]);
                }
            }

            $rekap->recalculate();
            $rekap->load(['supplier', 'details', 'komplain', 'pengurang']);

            LogAktivitas::catat('rekap', 'update', "Rekap {$rekap->kode_rekap} diupdate", $rekap);

            return $this->success($rekap, 'Rekap berhasil diupdate.');
        });
    }

    /**
     * Finalisasi rekap
     */
    #[OA\Post(
        path: '/rekap/{id}/final',
        summary: 'Finalisasi rekap (tidak bisa diubah setelah ini)',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function finalisasi(int $id)
    {
        $rekap = Rekap::findOrFail($id);

        if (!$rekap->isDraft()) {
            return $this->error('Rekap sudah final.', 422);
        }

        $rekap->update([
            'status'   => 'final',
            'final_at' => now(),
        ]);

        LogAktivitas::catat('rekap', 'final', "Rekap {$rekap->kode_rekap} difinalisasi", $rekap);

        return $this->success($rekap, 'Rekap berhasil difinalisasi.');
    }

    /**
     * Delete rekap (hanya draft)
     */
    #[OA\Delete(
        path: '/rekap/{id}',
        summary: 'Hapus rekap (hanya draft)',
        tags: ['Rekap'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function destroy(int $id)
    {
        $rekap = Rekap::findOrFail($id);

        if (!$rekap->isDraft()) {
            return $this->error('Rekap yang sudah final tidak bisa dihapus.', 422);
        }

        $kode = $rekap->kode_rekap;
        $rekap->delete();

        LogAktivitas::catat('rekap', 'delete', "Rekap {$kode} dihapus");

        return $this->success(null, 'Rekap berhasil dihapus.');
    }
}
