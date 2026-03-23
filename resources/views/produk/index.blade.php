@extends('layouts.app')
@section('title', 'Master Produk')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fa-solid fa-apple-whole text-green-600"></i> Master Produk
            </h1>
            <p class="text-gray-500 mt-1">Kelola data produk buah dan lainnya</p>
        </div>
        <a href="{{ route('produk.create') }}"
           class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-xl text-lg flex items-center gap-2 shadow-lg transition btn-lg">
            <i class="fa-solid fa-plus"></i> Tambah Produk
        </a>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="cari" value="{{ request('cari') }}"
                           class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="Cari produk...">
                </div>
            </div>
            <select name="supplier" class="px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                <option value="">Semua Supplier</option>
                @foreach($supplier as $sup)
                <option value="{{ $sup->id }}" {{ request('supplier') == $sup->id ? 'selected' : '' }}>{{ $sup->nama_supplier }}</option>
                @endforeach
            </select>
            <select name="kategori" class="px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                <option value="">Semua Kategori</option>
                @foreach($kategori as $kat)
                <option value="{{ $kat->id }}" {{ request('kategori') == $kat->id ? 'selected' : '' }}>{{ $kat->nama_kategori }}</option>
                @endforeach
            </select>
            <select name="status" class="px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                <option value="">Semua Status</option>
                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <button type="submit" class="bg-gray-800 text-white px-6 py-3 rounded-xl font-semibold hover:bg-gray-900 transition btn-lg">
                <i class="fa-solid fa-filter mr-2"></i> Filter
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Kode</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Nama Produk</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Supplier</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Ukuran</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Kategori</th>
                        <th class="px-6 py-4 text-right text-gray-700 font-bold text-base">Harga Beli</th>
                        <th class="px-6 py-4 text-right text-gray-700 font-bold text-base">Harga Jual</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Stok</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Status</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($produk as $p)
                    <tr class="hover:bg-green-50 transition">
                        <td class="px-6 py-4 font-mono text-gray-600">{{ $p->kode_produk }}</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800 text-lg">{{ $p->nama_produk }}</div>
                            <div class="text-gray-500 text-sm">{{ $p->satuan }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($p->supplier)
                            <span class="text-gray-700 font-medium">{{ $p->supplier->nama_supplier }}</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($p->ukuran)
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-bold">
                                {{ $p->ukuran }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($p->kategoriRelasi)
                            <span class="px-3 py-1 rounded-full text-sm font-medium text-white" style="background-color: {{ $p->kategoriRelasi->warna }}">
                                {{ $p->kategoriRelasi->nama_kategori }}
                            </span>
                            @elseif($p->kategori)
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                                {{ $p->kategori }}
                            </span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-gray-600">
                            Rp {{ number_format($p->harga_beli, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-semibold text-green-700">
                            Rp {{ number_format($p->harga_jual, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($p->stok <= $p->stok_minimum)
                            <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full font-bold">
                                {{ number_format($p->stok, 2) }}
                            </span>
                            @else
                            <span class="font-mono">{{ number_format($p->stok, 2) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($p->aktif)
                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                                Aktif
                            </span>
                            @else
                            <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm font-medium">
                                Nonaktif
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('produk.edit', $p) }}"
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition">
                                    <i class="fa-solid fa-edit"></i> Edit
                                </a>
                                <form method="POST" action="{{ route('produk.destroy', $p) }}" class="inline"
                                      onsubmit="return confirm('Yakin hapus produk ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-box-open text-5xl mb-3 block"></i>
                            <p class="text-xl">Belum ada data produk</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($produk->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $produk->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
