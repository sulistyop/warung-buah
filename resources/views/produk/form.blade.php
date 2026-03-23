@extends('layouts.app')
@section('title', $produk ? 'Edit Produk' : 'Tambah Produk')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fa-solid fa-{{ $produk ? 'edit' : 'plus' }} text-green-600"></i>
                {{ $produk ? 'Edit Produk' : 'Tambah Produk Baru' }}
            </h1>
            @if($produk)
            <p class="text-gray-500 mt-1">{{ $produk->kode_produk }} - {{ $produk->nama_produk }}</p>
            @endif
        </div>
        <a href="{{ route('produk.index') }}" class="text-gray-600 hover:text-gray-800 text-lg font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ $produk ? route('produk.update', $produk) : route('produk.store') }}">
        @csrf
        @if($produk) @method('PUT') @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-info-circle text-blue-500"></i> Informasi Produk
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        Nama Produk <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_produk" value="{{ old('nama_produk', $produk?->nama_produk) }}"
                           required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="Contoh: Jeruk Pontianak">
                    @error('nama_produk')<p class="text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        <i class="fa-solid fa-truck text-gray-400 mr-1"></i> Supplier
                    </label>
                    <select name="supplier_id"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                        <option value="">Pilih Supplier...</option>
                        @foreach($supplier as $sup)
                        <option value="{{ $sup->id }}" {{ old('supplier_id', $produk?->supplier_id) == $sup->id ? 'selected' : '' }}>
                            {{ $sup->nama_supplier }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-gray-500 text-sm mt-1">
                        <a href="{{ route('supplier.create') }}" class="text-green-600 hover:underline">
                            <i class="fa-solid fa-plus mr-1"></i>Tambah supplier baru
                        </a>
                    </p>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        <i class="fa-solid fa-ruler text-gray-400 mr-1"></i> Ukuran
                    </label>
                    <input type="text" name="ukuran" value="{{ old('ukuran', $produk?->ukuran) }}"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="Contoh: A, B, C, Super, dll">
                    <p class="text-gray-500 text-sm mt-1">Ukuran atau grade produk</p>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Kategori</label>
                    <select name="kategori_id"
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                        <option value="">Pilih Kategori...</option>
                        @foreach($kategori as $kat)
                        <option value="{{ $kat->id }}" {{ old('kategori_id', $produk?->kategori_id) == $kat->id ? 'selected' : '' }}>
                            {{ $kat->nama_kategori }}
                        </option>
                        @endforeach
                    </select>
                    <p class="text-gray-500 text-sm mt-1">
                        <a href="{{ route('kategori.create') }}" class="text-green-600 hover:underline">
                            <i class="fa-solid fa-plus mr-1"></i>Tambah kategori baru
                        </a>
                    </p>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        Satuan <span class="text-red-500">*</span>
                    </label>
                    <select name="satuan" required
                            class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                        <option value="kg" {{ old('satuan', $produk?->satuan) == 'kg' ? 'selected' : '' }}>Kilogram (kg)</option>
                        <option value="pcs" {{ old('satuan', $produk?->satuan) == 'pcs' ? 'selected' : '' }}>Pieces (pcs)</option>
                        <option value="box" {{ old('satuan', $produk?->satuan) == 'box' ? 'selected' : '' }}>Box</option>
                        <option value="ikat" {{ old('satuan', $produk?->satuan) == 'ikat' ? 'selected' : '' }}>Ikat</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-coins text-yellow-500"></i> Harga & Stok
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        Harga Beli (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="harga_beli" value="{{ old('harga_beli', $produk?->harga_beli ?? 0) }}"
                           required min="0" step="100"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono focus:border-green-500 focus:outline-none input-lg"
                           placeholder="0">
                    @error('harga_beli')<p class="text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        Harga Jual (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="harga_jual" value="{{ old('harga_jual', $produk?->harga_jual ?? 0) }}"
                           required min="0" step="100"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono focus:border-green-500 focus:outline-none input-lg bg-green-50"
                           placeholder="0">
                    @error('harga_jual')<p class="text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Stok Saat Ini</label>
                    <input type="number" name="stok" value="{{ old('stok', $produk?->stok ?? 0) }}"
                           min="0" step="0.01"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono focus:border-green-500 focus:outline-none input-lg"
                           placeholder="0">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Stok Minimum</label>
                    <input type="number" name="stok_minimum" value="{{ old('stok_minimum', $produk?->stok_minimum ?? 0) }}"
                           min="0" step="0.01"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono focus:border-green-500 focus:outline-none input-lg"
                           placeholder="0">
                    <p class="text-gray-500 text-sm mt-1">Peringatan jika stok dibawah ini</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-sticky-note text-gray-500"></i> Keterangan
            </h2>

            <textarea name="keterangan" rows="3"
                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none"
                      placeholder="Catatan tambahan (opsional)...">{{ old('keterangan', $produk?->keterangan) }}</textarea>

            @if($produk)
            <div class="mt-4 flex items-center gap-3">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="aktif" value="1" {{ old('aktif', $produk->aktif) ? 'checked' : '' }}
                           class="w-6 h-6 text-green-600 rounded focus:ring-green-500">
                    <span class="text-lg font-medium text-gray-700">Produk Aktif</span>
                </label>
            </div>
            @endif
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('produk.index') }}"
               class="px-8 py-3 rounded-xl border-2 border-gray-300 text-gray-600 hover:bg-gray-100 text-lg font-semibold transition btn-lg">
                Batal
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-lg flex items-center gap-2 shadow-lg transition btn-lg">
                <i class="fa-solid fa-save"></i> 
                {{ $produk ? 'Update Produk' : 'Simpan Produk' }}
            </button>
        </div>
    </form>
</div>
@endsection
