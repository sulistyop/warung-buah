<?php

namespace App\Http\Controllers;

use App\Models\Kategori;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $query = Kategori::query();

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function($q) use ($cari) {
                $q->where('kode_kategori', 'like', "%{$cari}%")
                  ->orWhere('nama_kategori', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('aktif', $request->status === 'aktif');
        }

        $kategori = $query->withCount('produk')->orderBy('nama_kategori')->paginate(20);
        $kategori->appends($request->query());

        return view('kategori.index', compact('kategori'));
    }

    public function create()
    {
        $warna = Kategori::getWarnaOptions();
        return view('kategori.form', [
            'kategori' => null,
            'warna' => $warna,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'deskripsi' => 'nullable|string|max:255',
            'warna' => 'required|string|max:20',
        ]);

        Kategori::create([
            'kode_kategori' => Kategori::generateKode(),
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'warna' => $request->warna,
            'aktif' => $request->has('aktif'),
        ]);

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan');
    }

    public function edit(Kategori $kategori)
    {
        $warna = Kategori::getWarnaOptions();
        return view('kategori.form', compact('kategori', 'warna'));
    }

    public function update(Request $request, Kategori $kategori)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:100',
            'deskripsi' => 'nullable|string|max:255',
            'warna' => 'required|string|max:20',
        ]);

        $kategori->update([
            'nama_kategori' => $request->nama_kategori,
            'deskripsi' => $request->deskripsi,
            'warna' => $request->warna,
            'aktif' => $request->has('aktif'),
        ]);

        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil diperbarui');
    }

    public function destroy(Kategori $kategori)
    {
        if ($kategori->produk()->count() > 0) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih ada produk terkait');
        }

        $kategori->delete();
        return redirect()->route('kategori.index')
            ->with('success', 'Kategori berhasil dihapus');
    }

    // API untuk search
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $kategori = Kategori::aktif()
            ->where(function($q) use ($query) {
                $q->where('nama_kategori', 'like', "%{$query}%")
                  ->orWhere('kode_kategori', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get(['id', 'kode_kategori', 'nama_kategori', 'warna']);

        return response()->json($kategori);
    }
}
