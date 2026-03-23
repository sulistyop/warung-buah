<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\ItemTransaksi;
use App\Models\DetailPeti;
use App\Models\BiayaOperasional;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function index()
    {
        $transaksi = Transaksi::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('transaksi.index', compact('transaksi'));
    }

    public function create()
    {
        $komisiDefault = Setting::get('komisi_persen', 0);
        return view('transaksi.create', compact('komisiDefault'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_pelanggan'       => 'required|string|max:255',
            'status_bayar'         => 'required|in:lunas,tempo,cicil',
            'tanggal_jatuh_tempo'  => 'nullable|date',
            'komisi_persen'        => 'required|numeric|min:0|max:100',
            'catatan'              => 'nullable|string',
            'items'                => 'required|array|min:1',
            'items.*.nama_supplier'=> 'required|string',
            'items.*.jenis_buah'   => 'required|string',
            'items.*.ukuran'       => 'required|string',
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
                'status'           => 'selesai',
                'user_id'          => auth()->id(),
            ]);

            // Simpan item buah
            foreach ($request->items as $itemData) {
                $item = ItemTransaksi::create([
                    'transaksi_id'  => $transaksi->id,
                    'nama_supplier' => $itemData['nama_supplier'],
                    'jenis_buah'    => $itemData['jenis_buah'],
                    'ukuran'        => $itemData['ukuran'],
                    'harga_per_kg'  => $itemData['harga_per_kg'],
                ]);

                // Simpan peti-peti
                foreach ($itemData['peti'] as $idx => $petiData) {
                    $beratBersih = $petiData['berat_kotor'] - $petiData['berat_kemasan'];
                    DetailPeti::create([
                        'item_transaksi_id' => $item->id,
                        'no_peti'           => $idx + 1,
                        'berat_kotor'       => $petiData['berat_kotor'],
                        'berat_kemasan'     => $petiData['berat_kemasan'],
                        'berat_bersih'      => $beratBersih,
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
