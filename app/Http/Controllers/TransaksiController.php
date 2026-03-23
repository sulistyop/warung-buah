<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Models\DetailPeti;
use App\Models\BiayaOperasional;
use App\Models\Pembayaran;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
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

        $transaksi = $query->orderByDesc('created_at')->paginate(20);

        return view('transaksi.index', compact('transaksi'));
    }

    public function create()
    {
        $komisiDefault = Setting::get('komisi_persen', 0);
        $suppliers = Supplier::aktif()->orderBy('nama_supplier')->get();
        $produks = Produk::aktif()->orderBy('nama_produk')->get();
        return view('transaksi.create', compact('komisiDefault', 'suppliers', 'produks'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_pelanggan'       => 'required|string|max:255',
            'status_bayar'         => 'required|in:lunas,tempo,cicil',
            'tanggal_jatuh_tempo'  => 'nullable|date',
            'komisi_persen'        => 'required|numeric|min:0|max:100',
            'catatan'              => 'nullable|string',
            'uang_diterima'        => 'nullable|numeric|min:0',
            'items'                => 'required|array|min:1',
            'items.*.nama_supplier'=> 'required|string',
            'items.*.jenis_buah'   => 'required|string',
            //'items.*.ukuran'       => 'required|string',
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
                'komisi_persen'    => $request->komisi_persen,
                'uang_diterima'    => $request->uang_diterima ?? 0,
                'status'           => 'selesai',
                'user_id'          => auth()->id(),
            ]);

            // Simpan item buah
            foreach ($request->items as $itemData) {
                // Cari supplier ID jika ada
                $supplierId = null;
                if (!empty($itemData['supplier_id'])) {
                    $supplierId = $itemData['supplier_id'];
                }

                $item = ItemTransaksi::create([
                    'transaksi_id'  => $transaksi->id,
                    'supplier_id'   => $supplierId,
                    'nama_supplier' => $itemData['nama_supplier'],
                    'jenis_buah'    => $itemData['jenis_buah'],
                    //'ukuran'        => $itemData['ukuran'],
                    'harga_per_kg'  => $itemData['harga_per_kg'],
                ]);

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

            // Jika status lunas dan ada uang diterima, catat sebagai pembayaran
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
            }

            DB::commit();

            return redirect()->route('transaksi.show', $transaksi->id)
                ->with('success', 'Transaksi berhasil disimpan!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function show(Transaksi $transaksi)
    {
        $transaksi->load([
            'itemTransaksi.detailPeti',
            'biayaOperasional',
            'pembayaran.user',
            'user',
        ]);

        return view('transaksi.show', compact('transaksi'));
    }

    public function destroy(Transaksi $transaksi)
    {
        $transaksi->delete();
        return redirect()->route('transaksi.index')
            ->with('success', 'Transaksi berhasil dihapus.');
    }
}
