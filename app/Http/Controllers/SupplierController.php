<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::withCount('produk');

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama_supplier', 'like', "%{$cari}%")
                  ->orWhere('kode_supplier', 'like', "%{$cari}%")
                  ->orWhere('telepon', 'like', "%{$cari}%")
                  ->orWhere('kota', 'like', "%{$cari}%");
            });
        }

        if ($request->status === 'aktif') {
            $query->where('aktif', true);
        } elseif ($request->status === 'nonaktif') {
            $query->where('aktif', false);
        }

        $supplier = $query->orderBy('nama_supplier')->paginate(20);

        return view('supplier.index', compact('supplier'));
    }

    public function create()
    {
        return view('supplier.form', ['supplier' => null]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:100',
            'kontak_person' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        Supplier::create([
            'kode_supplier' => Supplier::generateKode(),
            'nama_supplier' => $request->nama_supplier,
            'telepon' => $request->telepon,
            'email' => $request->email,
            'alamat' => $request->alamat,
            'kota' => $request->kota,
            'kontak_person' => $request->kontak_person,
            'catatan' => $request->catatan,
            'aktif' => true,
        ]);

        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil ditambahkan!');
    }

    public function edit(Supplier $supplier)
    {
        return view('supplier.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $request->validate([
            'nama_supplier' => 'required|string|max:255',
            'telepon' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:100',
            'kontak_person' => 'nullable|string|max:255',
            'catatan' => 'nullable|string',
        ]);

        $supplier->update([
            'nama_supplier' => $request->nama_supplier,
            'telepon' => $request->telepon,
            'email' => $request->email,
            'alamat' => $request->alamat,
            'kota' => $request->kota,
            'kontak_person' => $request->kontak_person,
            'catatan' => $request->catatan,
            'aktif' => $request->boolean('aktif'),
        ]);

        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil diupdate!');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('supplier.index')
            ->with('success', 'Supplier berhasil dihapus!');
    }

    // API untuk autocomplete
    public function search(Request $request)
    {
        $term = $request->get('term', '');
        $supplier = Supplier::aktif()
            ->where(function ($q) use ($term) {
                $q->where('nama_supplier', 'like', "%{$term}%")
                  ->orWhere('kode_supplier', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get(['id', 'kode_supplier', 'nama_supplier', 'telepon', 'kota']);

        return response()->json($supplier);
    }
}
