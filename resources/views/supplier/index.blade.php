@extends('layouts.app')
@section('title', 'Master Supplier')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fa-solid fa-truck text-green-600"></i> Master Supplier
            </h1>
            <p class="text-gray-500 mt-1">Kelola data supplier/pemasok buah</p>
        </div>
        <a href="{{ route('supplier.create') }}"
           class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-xl text-lg flex items-center gap-2 shadow-lg transition btn-lg">
            <i class="fa-solid fa-plus"></i> Tambah Supplier
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
                           placeholder="Cari supplier...">
                </div>
            </div>
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
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Nama Supplier</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Telepon</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Kota</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Jml Produk</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Status</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($supplier as $s)
                    <tr class="hover:bg-green-50 transition">
                        <td class="px-6 py-4 font-mono text-gray-600">{{ $s->kode_supplier }}</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800 text-lg">{{ $s->nama_supplier }}</div>
                            @if($s->email)
                            <div class="text-gray-500 text-sm">{{ $s->email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($s->telepon)
                            <a href="tel:{{ $s->telepon }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fa-solid fa-phone mr-1"></i>{{ $s->telepon }}
                            </a>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($s->kota)
                            <span class="font-medium text-gray-700">{{ $s->kota }}</span>
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('produk.index', ['supplier' => $s->id]) }}" class="hover:underline">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-bold">
                                    {{ $s->produk_count }} produk
                                </span>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($s->aktif)
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
                                <a href="{{ route('supplier.edit', $s) }}"
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition">
                                    <i class="fa-solid fa-edit"></i> Edit
                                </a>
                                <form method="POST" action="{{ route('supplier.destroy', $s) }}" class="inline"
                                      onsubmit="return confirm('Yakin hapus supplier ini?')">
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
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-truck text-5xl mb-3 block"></i>
                            <p class="text-xl">Belum ada data supplier</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($supplier->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $supplier->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
