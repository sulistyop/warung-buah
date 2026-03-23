<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    /**
     * Daftar transaksi yang belum lunas (tempo/cicil)
     */
    public function index(Request $request)
    {
        $query = Transaksi::with('user')
            ->whereIn('status_bayar', ['tempo', 'cicil'])
            ->where('sisa_tagihan', '>', 0);

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_pelanggan', 'like', "%{$cari}%")
                  ->orWhere('kode_transaksi', 'like', "%{$cari}%");
            });
        }

        if ($request->status === 'jatuh_tempo') {
            $query->where('tanggal_jatuh_tempo', '<', now());
        }

        $transaksi = $query->orderBy('tanggal_jatuh_tempo')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('pembayaran.index', compact('transaksi'));
    }

    /**
     * Detail pembayaran untuk satu transaksi
     */
    public function show(Transaksi $transaksi)
    {
        $transaksi->load(['pembayaran.user', 'itemTransaksi', 'user']);
        return view('pembayaran.show', compact('transaksi'));
    }

    /**
     * Form catat pembayaran baru
     */
    public function create(Transaksi $transaksi)
    {
        return view('pembayaran.form', compact('transaksi'));
    }

    /**
     * Simpan pembayaran baru
     */
    public function store(Request $request, Transaksi $transaksi)
    {
        $request->validate([
            'nominal' => 'required|numeric|min:1',
            'metode' => 'required|in:tunai,transfer,qris,lainnya',
            'referensi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        if ($request->nominal > $transaksi->sisa_tagihan) {
            return back()->with('error', 'Nominal pembayaran melebihi sisa tagihan!');
        }

        DB::beginTransaction();
        try {
            $sisaSetelahBayar = $transaksi->sisa_tagihan - $request->nominal;

            Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'kode_pembayaran' => Pembayaran::generateKode(),
                'nominal' => $request->nominal,
                'metode' => $request->metode,
                'referensi' => $request->referensi,
                'catatan' => $request->catatan,
                'sisa_tagihan' => max(0, $sisaSetelahBayar),
                'user_id' => auth()->id(),
            ]);

            // Recalculate transaksi
            $transaksi->recalculate();

            DB::commit();

            return redirect()->route('pembayaran.show', $transaksi->id)
                ->with('success', 'Pembayaran berhasil dicatat!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * Hapus pembayaran
     */
    public function destroy(Pembayaran $pembayaran)
    {
        $transaksi = $pembayaran->transaksi;
        
        DB::beginTransaction();
        try {
            $pembayaran->delete();
            $transaksi->recalculate();
            
            // Update status bayar jika masih ada sisa
            if ($transaksi->sisa_tagihan > 0) {
                $transaksi->update(['status_bayar' => $transaksi->pembayaran()->count() > 0 ? 'cicil' : 'tempo']);
            }

            DB::commit();

            return redirect()->route('pembayaran.show', $transaksi->id)
                ->with('success', 'Pembayaran berhasil dihapus!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }
    }
}
