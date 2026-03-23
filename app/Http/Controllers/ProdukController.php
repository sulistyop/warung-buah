<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use App\Models\Kategori;
use App\Models\Supplier;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $query = Produk::with(['kategoriRelasi', 'supplier']);

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_produk', 'like', "%{$cari}%")
                  ->orWhere('kode_produk', 'like', "%{$cari}%")
                  ->orWhere('kategori', 'like', "%{$cari}%")
                  ->orWhere('ukuran', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori_id', $request->kategori);
        }

        if ($request->filled('supplier')) {
            $query->where('supplier_id', $request->supplier);
        }

        if ($request->status === 'aktif') {
            $query->where('aktif', true);
        } elseif ($request->status === 'nonaktif') {
            $query->where('aktif', false);
        }

        $produk = $query->orderBy('nama_produk')->paginate(20);
        $kategori = Kategori::aktif()->orderBy('nama_kategori')->get();
        $supplier = Supplier::aktif()->orderBy('nama_supplier')->get();

        return view('produk.index', compact('produk', 'kategori', 'supplier'));
    }

    public function create()
    {
        $kategori = Kategori::aktif()->orderBy('nama_kategori')->get();
        $supplier = Supplier::aktif()->orderBy('nama_supplier')->get();
        return view('produk.form', [
            'produk' => null,
            'kategori' => $kategori,
            'supplier' => $supplier,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_produk' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:supplier,id',
            'ukuran' => 'nullable|string|max:50',
            'kategori_id' => 'nullable|exists:kategori,id',
            'satuan' => 'required|string|max:20',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'nullable|numeric|min:0',
            'stok_minimum' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        Produk::create([
            'kode_produk' => Produk::generateKode(),
            'nama_produk' => $request->nama_produk,
            'supplier_id' => $request->supplier_id,
            'ukuran' => $request->ukuran,
            'kategori_id' => $request->kategori_id,
            'satuan' => $request->satuan,
            'harga_beli' => $request->harga_beli,
            'harga_jual' => $request->harga_jual,
            'stok' => $request->stok ?? 0,
            'stok_minimum' => $request->stok_minimum ?? 0,
            'keterangan' => $request->keterangan,
            'aktif' => true,
        ]);

        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil ditambahkan!');
    }

    public function edit(Produk $produk)
    {
        $kategori = Kategori::aktif()->orderBy('nama_kategori')->get();
        $supplier = Supplier::aktif()->orderBy('nama_supplier')->get();
        return view('produk.form', compact('produk', 'kategori', 'supplier'));
    }

    public function update(Request $request, Produk $produk)
    {
        $request->validate([
            'nama_produk' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:supplier,id',
            'ukuran' => 'nullable|string|max:50',
            'kategori_id' => 'nullable|exists:kategori,id',
            'satuan' => 'required|string|max:20',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'nullable|numeric|min:0',
            'stok_minimum' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $produk->update([
            'nama_produk' => $request->nama_produk,
            'supplier_id' => $request->supplier_id,
            'ukuran' => $request->ukuran,
            'kategori_id' => $request->kategori_id,
            'satuan' => $request->satuan,
            'harga_beli' => $request->harga_beli,
            'harga_jual' => $request->harga_jual,
            'stok' => $request->stok ?? 0,
            'stok_minimum' => $request->stok_minimum ?? 0,
            'keterangan' => $request->keterangan,
            'aktif' => $request->boolean('aktif'),
        ]);

        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil diupdate!');
    }

    public function destroy(Produk $produk)
    {
        $produk->delete();
        return redirect()->route('produk.index')
            ->with('success', 'Produk berhasil dihapus!');
    }

    // API untuk autocomplete
    public function search(Request $request)
    {
        $term = $request->get('term', '');
        $produk = Produk::aktif()
            ->where(function ($q) use ($term) {
                $q->where('nama_produk', 'like', "%{$term}%")
                  ->orWhere('kode_produk', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get(['id', 'kode_produk', 'nama_produk', 'harga_jual', 'satuan', 'stok']);

        return response()->json($produk);
    }
}
