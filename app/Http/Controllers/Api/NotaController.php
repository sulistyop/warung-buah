<?php

namespace App\Http\Controllers\Api;

use App\Models\Transaksi;
use App\Models\Rekap;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotaController extends Controller
{
    /**
     * Nota penjualan dalam format HTML (dapat diprint atau dikonversi ke PDF di Flutter).
     * Endpoint ini mengembalikan HTML string yang siap print.
     */
    #[OA\Get(
        path: '/nota/transaksi/{id}',
        summary: 'Nota transaksi penjualan (HTML untuk print)',
        tags: ['Nota'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'format', in: 'query', description: 'html atau json', schema: new OA\Schema(type: 'string', default: 'json')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success - data nota transaksi'),
        ]
    )]
    public function notaTransaksi(Request $request, int $id)
    {
        $transaksi = Transaksi::with([
            'itemTransaksi.detailPeti',
            'biayaOperasional',
            'pembayaran',
            'pelanggan',
            'user',
        ])->findOrFail($id);

        $namaUsaha   = Setting::get('nama_toko', 'Warung Buah');
        $alamatUsaha = Setting::get('alamat_toko', '');
        $teleponUsaha = Setting::get('telepon_toko', '');

        $data = [
            'usaha' => [
                'nama'    => $namaUsaha,
                'alamat'  => $alamatUsaha,
                'telepon' => $teleponUsaha,
            ],
            'transaksi' => [
                'kode'            => $transaksi->kode_transaksi,
                'tanggal'         => $transaksi->created_at->format('d/m/Y H:i'),
                'nama_pelanggan'  => $transaksi->nama_pelanggan,
                'toko_pelanggan'  => $transaksi->pelanggan?->toko,
                'kasir'           => $transaksi->user->name,
                'status_bayar'    => $transaksi->status_bayar,
            ],
            'items' => $transaksi->itemTransaksi->map(function ($item) {
                $totalKotor = $item->detailPeti->sum('berat_kotor');
                return [
                    'nama_supplier'     => $item->nama_supplier,
                    'jenis_buah'        => $item->jenis_buah,
                    'jumlah_peti'       => $item->jumlah_peti,
                    'total_berat_bersih'=> $item->total_berat_bersih,
                    'total_kotor'       => $totalKotor,
                    'harga_per_kg'      => $item->harga_per_kg,
                    'subtotal'          => $item->subtotal,
                    'peti' => $item->detailPeti->map(fn($p) => [
                        'no_peti'       => $p->no_peti,
                        'berat_kotor'   => $p->berat_kotor,
                        'berat_kemasan' => $p->berat_kemasan,
                        'berat_bersih'  => $p->berat_bersih,
                    ]),
                ];
            }),
            'biaya_operasional' => $transaksi->biayaOperasional->map(fn($b) => [
                'nama_biaya' => $b->nama_biaya,
                'nominal'    => $b->nominal,
            ]),
            'pembayaran' => $transaksi->pembayaran->map(fn($p) => [
                'kode'    => $p->kode_pembayaran,
                'nominal' => $p->nominal,
                'metode'  => $p->metode,
                'tanggal' => $p->created_at->format('d/m/Y H:i'),
            ]),
            'summary' => [
                'total_kotor'            => $transaksi->total_kotor,
                'total_komisi'           => $transaksi->total_komisi,
                'komisi_persen'          => $transaksi->komisi_persen,
                'total_biaya_operasional'=> $transaksi->total_biaya_operasional,
                'total_bersih'           => $transaksi->total_bersih,
                'total_tagihan'          => $transaksi->total_tagihan,
                'total_dibayar'          => $transaksi->total_dibayar,
                'sisa_tagihan'           => $transaksi->sisa_tagihan,
            ],
        ];

        if ($request->input('format') === 'html') {
            return response($this->renderNotaTransaksiHtml($data))
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        return $this->success($data);
    }

    /**
     * Nota rekap supplier dalam format JSON/HTML
     */
    #[OA\Get(
        path: '/nota/rekap/{id}',
        summary: 'Nota rekap supplier (HTML untuk print)',
        tags: ['Nota'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'format', in: 'query', description: 'html atau json', schema: new OA\Schema(type: 'string', default: 'json')),
        ],
        responses: [new OA\Response(response: 200, description: 'Success')]
    )]
    public function notaRekap(Request $request, int $id)
    {
        $rekap = Rekap::with(['supplier', 'details', 'komplain', 'dibuatOleh'])->findOrFail($id);

        $namaUsaha   = Setting::get('nama_usaha', 'Warung Buah');
        $alamatUsaha = Setting::get('alamat_usaha', '');

        $data = [
            'usaha' => [
                'nama'   => $namaUsaha,
                'alamat' => $alamatUsaha,
            ],
            'rekap' => [
                'kode'        => $rekap->kode_rekap,
                'tanggal'     => $rekap->tanggal->format('d/m/Y'),
                'supplier'    => $rekap->supplier->nama_supplier,
                'dibuat_oleh' => $rekap->dibuatOleh->name,
                'status'      => $rekap->status,
            ],
            'details' => $rekap->details->map(fn($d) => [
                'nama_produk'        => $d->nama_produk,
                'ukuran'             => $d->ukuran,
                'jumlah_peti'        => $d->jumlah_peti,
                'total_berat_kotor'  => $d->total_berat_kotor,
                'total_berat_peti'   => $d->total_berat_peti,
                'total_berat_bersih' => $d->total_berat_bersih,
                'harga_per_kg'       => $d->harga_per_kg,
                'subtotal'           => $d->subtotal,
            ]),
            'komplain' => $rekap->komplain->map(fn($k) => [
                'nama_produk' => $k->nama_produk,
                'jumlah_bs'   => $k->jumlah_bs,
                'harga_ganti' => $k->harga_ganti,
                'total'       => $k->total,
                'keterangan'  => $k->keterangan,
            ]),
            'summary' => [
                'total_peti'        => $rekap->total_peti,
                'total_kotor'       => $rekap->total_kotor,
                'komisi_persen'     => $rekap->komisi_persen,
                'total_komisi'      => $rekap->total_komisi,
                'kuli_per_peti'     => $rekap->kuli_per_peti,
                'total_kuli'        => $rekap->total_kuli,
                'total_ongkos'      => $rekap->total_ongkos,
                'keterangan_ongkos' => $rekap->keterangan_ongkos,
                'total_busuk'       => $rekap->total_busuk,
                'pendapatan_bersih' => $rekap->pendapatan_bersih,
                'sisa'              => $rekap->sisa,
            ],
        ];

        $format = $request->input('format');

        if ($format === 'html') {
            return response($this->renderNotaRekapHtml($data))
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        if ($format === 'pdf') {
            $html = $this->renderNotaRekapHtml($data);
            $pdf  = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
            return $pdf->download("rekap-{$rekap->kode_rekap}.pdf");
        }

        return $this->success($data);
    }

    private function getLogoTag(int $maxSize = 70): string
    {
        $logoPath = public_path('images/logo.jpeg');
        if (!file_exists($logoPath)) return '';
        $mime    = mime_content_type($logoPath) ?: 'image/jpeg';
        $base64  = base64_encode(file_get_contents($logoPath));
        return "<img src=\"data:{$mime};base64,{$base64}\" style=\"max-width:{$maxSize}px;max-height:{$maxSize}px;object-fit:contain\" alt=\"logo\">";
    }

    private function renderNotaTransaksiHtml(array $data): string
    {
        $usaha    = $data['usaha'];
        $trx      = $data['transaksi'];
        $summary  = $data['summary'];
        $items    = $data['items'];
        $biaya    = $data['biaya_operasional'];
        $bayar    = $data['pembayaran'];

        $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');

        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "<tr><td>{$item['jenis_buah']}</td><td style='text-align:right'>{$item['jumlah_peti']} peti</td></tr>";
            foreach ($item['peti'] as $p) {
                $itemsHtml .= "<tr><td style='padding-left:12px;font-size:11px'>{$p['no_peti']}. {$p['berat_kotor']}-{$p['berat_kemasan']}={$p['berat_bersih']}kg</td><td></td></tr>";
            }
            $itemsHtml .= "<tr><td colspan='2' style='text-align:right;font-size:11px'>{$item['total_berat_bersih']}kg × {$fmt($item['harga_per_kg'])} = {$fmt($item['subtotal'])}</td></tr>";
            $itemsHtml .= "<tr><td colspan='2'><hr style='border:none;border-top:1px dashed #ccc'></td></tr>";
        }

        $biayaHtml = '';
        foreach ($biaya as $b) {
            $biayaHtml .= "<tr><td>{$b['nama_biaya']}</td><td style='text-align:right'>{$fmt($b['nominal'])}</td></tr>";
        }

        $bayarHtml = '';
        foreach ($bayar as $p) {
            $bayarHtml .= "<tr><td>{$p['metode']} ({$p['tanggal']})</td><td style='text-align:right'>{$fmt($p['nominal'])}</td></tr>";
        }

        $logoTag = $this->getLogoTag(60);
        return "<!DOCTYPE html>
<html><head><meta charset='UTF-8'>
<style>
  body{font-family:monospace;font-size:12px;width:80mm;margin:0 auto;padding:8px}
  .logo{text-align:center;margin-bottom:4px}
  .logo img{max-width:60px;max-height:60px;object-fit:contain}
  h2{text-align:center;font-size:14px;margin:4px 0}
  p{margin:2px 0;text-align:center;font-size:11px}
  table{width:100%;border-collapse:collapse}
  td{padding:2px 4px;vertical-align:top}
  .divider{border-top:1px dashed #000;margin:6px 0}
  .total-row td{font-weight:bold}
  .highlight{background:#f0f0f0}
</style>
</head><body>
  <div class='logo'>{$logoTag}</div>
  <h2>{$usaha['nama']}</h2>
  <p>{$usaha['alamat']}</p>
  <p>{$usaha['telepon']}</p>
  <div class='divider'></div>
  <table>
    <tr><td>No.</td><td>{$trx['kode']}</td></tr>
    <tr><td>Tgl</td><td>{$trx['tanggal']}</td></tr>
    <tr><td>Pelanggan</td><td>{$trx['nama_pelanggan']}</td></tr>
    <tr><td>Toko</td><td>{$trx['toko_pelanggan']}</td></tr>
    <tr><td>Kasir</td><td>{$trx['kasir']}</td></tr>
  </table>
  <div class='divider'></div>
  <table>{$itemsHtml}</table>
  <div class='divider'></div>
  <table>
    <tr><td>Total Kotor</td><td style='text-align:right'>{$fmt($summary['total_kotor'])}</td></tr>
    {$biayaHtml}
    <tr class='total-row'><td>TOTAL TAGIHAN</td><td style='text-align:right'>{$fmt($summary['total_tagihan'])}</td></tr>
  </table>
  <div class='divider'></div>
  <table>
    {$bayarHtml}
    <tr class='total-row highlight'><td>SISA</td><td style='text-align:right'>{$fmt($summary['sisa_tagihan'])}</td></tr>
  </table>
  <div class='divider'></div>
  <p style='font-size:10px'>Status: <b>" . strtoupper($trx['status_bayar']) . "</b></p>
  <p style='font-size:10px'>Terima kasih atas kepercayaan Anda</p>
</body></html>";
    }

    private function renderNotaRekapHtml(array $data): string
    {
        $usaha   = $data['usaha'];
        $rekap   = $data['rekap'];
        $summary = $data['summary'];
        $details = $data['details'];
        $komplain= $data['komplain'];

        $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');

        $detailsHtml = '';
        $currentProduk = '';
        foreach ($details as $d) {
            if ($d['nama_produk'] !== $currentProduk) {
                $currentProduk = $d['nama_produk'];
                $detailsHtml .= "<tr><td colspan='5'><b>{$d['nama_produk']}" . ($d['ukuran'] ? " ({$d['ukuran']})" : '') . "</b></td></tr>";
            }
            $detailsHtml .= "<tr>
                <td>{$d['jumlah_peti']} peti</td>
                <td style='text-align:right'>{$d['total_berat_kotor']}</td>
                <td style='text-align:right'>{$d['total_berat_peti']}</td>
                <td style='text-align:right'>{$d['total_berat_bersih']}</td>
                <td style='text-align:right'>{$fmt($d['subtotal'])}</td>
            </tr>";
        }

        $komplainHtml = '';
        foreach ($komplain as $k) {
            $komplainHtml .= "<tr>
                <td>{$k['nama_produk']}</td>
                <td style='text-align:right'>{$k['jumlah_bs']} BS</td>
                <td style='text-align:right'>{$fmt($k['harga_ganti'])}</td>
                <td style='text-align:right'>{$fmt($k['total'])}</td>
            </tr>";
        }

        $logoTag = $this->getLogoTag(80);
        return "<!DOCTYPE html>
<html><head><meta charset='UTF-8'>
<style>
  body{font-family:Arial,sans-serif;font-size:12px;margin:0;padding:16px}
  .logo{text-align:center;margin-bottom:6px}
  .logo img{max-width:80px;max-height:80px;object-fit:contain}
  h2{text-align:center;font-size:16px;margin:4px 0}
  h3{font-size:13px;margin:8px 0 4px}
  p{margin:2px 0;text-align:center;font-size:11px}
  table{width:100%;border-collapse:collapse;margin-bottom:8px}
  th{background:#eee;padding:4px 6px;text-align:left;border:1px solid #ddd}
  td{padding:3px 6px;border:1px solid #ddd}
  .divider{border-top:2px solid #000;margin:8px 0}
  .total-section{margin-top:12px}
  .total-row{font-weight:bold;background:#f5f5f5}
  .highlight{background:#e8f5e9;font-weight:bold}
</style>
</head><body>
  <div class='logo'>{$logoTag}</div>
  <h2>{$usaha['nama']}</h2>
  <p>{$usaha['alamat']}</p>
  <div class='divider'></div>
  <table style='border:none'>
    <tr><td style='border:none'><b>REKAP HARIAN SUPPLIER</b></td><td style='border:none;text-align:right'>{$rekap['kode']}</td></tr>
    <tr><td style='border:none'>Supplier</td><td style='border:none;text-align:right'><b>{$rekap['supplier']}</b></td></tr>
    <tr><td style='border:none'>Tanggal</td><td style='border:none;text-align:right'>{$rekap['tanggal']}</td></tr>
    <tr><td style='border:none'>Dibuat oleh</td><td style='border:none;text-align:right'>{$rekap['dibuat_oleh']}</td></tr>
  </table>
  <div class='divider'></div>
  <h3>Detail Produk</h3>
  <table>
    <thead><tr><th>Peti</th><th>B.Kotor</th><th>B.Peti</th><th>B.Bersih</th><th>Jumlah</th></tr></thead>
    <tbody>{$detailsHtml}</tbody>
    <tfoot>
      <tr class='total-row'><td>Total: {$summary['total_peti']} peti</td><td colspan='3'></td><td style='text-align:right'>{$fmt($summary['total_kotor'])}</td></tr>
    </tfoot>
  </table>" .
  (!empty($komplain) ? "
  <h3>Komplain / BS</h3>
  <table>
    <thead><tr><th>Produk</th><th>Qty</th><th>Harga Ganti</th><th>Total</th></tr></thead>
    <tbody>{$komplainHtml}</tbody>
  </table>" : '') . "
  <div class='divider'></div>
  <div class='total-section'>
    <table style='border:none'>
      <tr><td style='border:none'>Total</td><td style='border:none;text-align:right'>{$fmt($summary['total_kotor'])}</td></tr>
      <tr><td style='border:none'>Komisi ({$summary['komisi_persen']}%)</td><td style='border:none;text-align:right'>{$fmt($summary['total_komisi'])}</td></tr>
      <tr><td style='border:none'>Kuli</td><td style='border:none;text-align:right'>{$fmt($summary['total_kuli'])}</td></tr>
      <tr><td style='border:none'>Ongkos" . ($summary['keterangan_ongkos'] ? " ({$summary['keterangan_ongkos']})" : '') . "</td><td style='border:none;text-align:right'>{$fmt($summary['total_ongkos'])}</td></tr>
      <tr class='total-row'><td style='border:none'>Pendapatan Bersih</td><td style='border:none;text-align:right'>{$fmt($summary['pendapatan_bersih'])}</td></tr>
      <tr><td style='border:none'>Busuk / Komplain</td><td style='border:none;text-align:right'>- {$fmt($summary['total_busuk'])}</td></tr>
      <tr class='highlight'><td style='border:none'>SISA</td><td style='border:none;text-align:right'>{$fmt($summary['sisa'])}</td></tr>
    </table>
  </div>
  <div class='divider'></div>
  <p style='font-size:10px'>Status: <b>" . strtoupper($rekap['status']) . "</b></p>
</body></html>";
    }
}
