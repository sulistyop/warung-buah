@extends('layouts.app')

@section('title', 'Master Kategori')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fa-solid fa-tags text-purple-500 mr-2"></i>Master Kategori
            </h1>
            <p class="text-gray-500 mt-1">Kelola kategori produk</p>
        </div>
        <a href="{{ route('kategori.create') }}" 
           class="w-full sm:w-auto flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white py-3 px-6 rounded-xl font-semibold text-lg transition-colors shadow-lg">
            <i class="fa-solid fa-plus"></i>
            Tambah Kategori
        </a>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="cari" value="{{ request('cari') }}"
                       class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-purple-500 focus:outline-none"
                       placeholder="Cari kategori...">
            </div>
            <select name="status" class="px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-purple-500 focus:outline-none">
                <option value="">Semua Status</option>
                <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ request('status') == 'nonaktif' ? 'selected' : '' }}>Non-Aktif</option>
            </select>
            <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white py-3 px-6 rounded-xl font-semibold text-lg transition-colors">
                <i class="fa-solid fa-search mr-2"></i>Cari
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
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Nama Kategori</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Warna</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Jumlah Produk</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Status</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($kategori as $k)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-mono text-lg font-semibold text-gray-700">{{ $k->kode_kategori }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="w-4 h-4 rounded-full" style="background-color: {{ $k->warna }}"></span>
                                <div>
                                    <div class="font-semibold text-gray-800 text-lg">{{ $k->nama_kategori }}</div>
                                    @if($k->deskripsi)
                                    <div class="text-gray-500 text-sm">{{ Str::limit($k->deskripsi, 50) }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-white text-sm font-semibold"
                                  style="background-color: {{ $k->warna }}">
                                {{ $k->warna }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-purple-100 text-purple-700 font-bold">
                                {{ $k->produk_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($k->aktif)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-semibold">
                                    <i class="fa-solid fa-check-circle"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-sm font-semibold">
                                    <i class="fa-solid fa-times-circle"></i> Non-Aktif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('kategori.edit', $k) }}"
                                   class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 transition-colors"
                                   title="Edit">
                                    <i class="fa-solid fa-pen text-lg"></i>
                                </a>
                                @if($k->produk_count == 0)
                                <form action="{{ route('kategori.destroy', $k) }}" method="POST" 
                                      onsubmit="return confirm('Yakin hapus kategori ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="flex items-center justify-center w-12 h-12 rounded-xl bg-red-100 hover:bg-red-200 text-red-700 transition-colors"
                                            title="Hapus">
                                        <i class="fa-solid fa-trash text-lg"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fa-solid fa-tags text-6xl text-gray-300 mb-4"></i>
                            <p class="text-xl">Belum ada kategori</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($kategori->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $kategori->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
