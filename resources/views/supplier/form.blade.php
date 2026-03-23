@extends('layouts.app')
@section('title', $supplier ? 'Edit Supplier' : 'Tambah Supplier')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fa-solid fa-{{ $supplier ? 'edit' : 'plus' }} text-green-600"></i>
                {{ $supplier ? 'Edit Supplier' : 'Tambah Supplier Baru' }}
            </h1>
            @if($supplier)
            <p class="text-gray-500 mt-1">{{ $supplier->kode_supplier }} - {{ $supplier->nama_supplier }}</p>
            @endif
        </div>
        <a href="{{ route('supplier.index') }}" class="text-gray-600 hover:text-gray-800 text-lg font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ $supplier ? route('supplier.update', $supplier) : route('supplier.store') }}">
        @csrf
        @if($supplier) @method('PUT') @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-building text-blue-500"></i> Informasi Supplier
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        Nama Supplier <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_supplier" value="{{ old('nama_supplier', $supplier?->nama_supplier) }}"
                           required class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="Contoh: PT Buah Segar Abadi">
                    @error('nama_supplier')<p class="text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        <i class="fa-solid fa-phone text-gray-400 mr-1"></i> Telepon
                    </label>
                    <input type="text" name="telepon" value="{{ old('telepon', $supplier?->telepon) }}"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="08123456789">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        <i class="fa-solid fa-envelope text-gray-400 mr-1"></i> Email
                    </label>
                    <input type="email" name="email" value="{{ old('email', $supplier?->email) }}"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="supplier@email.com">
                    @error('email')<p class="text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        <i class="fa-solid fa-city text-gray-400 mr-1"></i> Kota
                    </label>
                    <input type="text" name="kota" value="{{ old('kota', $supplier?->kota) }}"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="Jakarta">
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        <i class="fa-solid fa-user text-gray-400 mr-1"></i> Kontak Person
                    </label>
                    <input type="text" name="kontak_person" value="{{ old('kontak_person', $supplier?->kontak_person) }}"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="Nama kontak">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        <i class="fa-solid fa-location-dot text-gray-400 mr-1"></i> Alamat Lengkap
                    </label>
                    <textarea name="alamat" rows="2"
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none"
                              placeholder="Alamat lengkap supplier...">{{ old('alamat', $supplier?->alamat) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        <i class="fa-solid fa-sticky-note text-gray-400 mr-1"></i> Catatan
                    </label>
                    <textarea name="catatan" rows="2"
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none"
                              placeholder="Catatan tambahan (opsional)...">{{ old('catatan', $supplier?->catatan) }}</textarea>
                </div>

                @if($supplier)
                <div class="md:col-span-2">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="aktif" value="1" {{ old('aktif', $supplier->aktif) ? 'checked' : '' }}
                               class="w-6 h-6 text-green-600 rounded focus:ring-green-500">
                        <span class="text-lg font-medium text-gray-700">Supplier Aktif</span>
                    </label>
                </div>
                @endif
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('supplier.index') }}"
               class="px-8 py-3 rounded-xl border-2 border-gray-300 text-gray-600 hover:bg-gray-100 text-lg font-semibold transition btn-lg">
                Batal
            </a>
            <button type="submit"
                    class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-lg flex items-center gap-2 shadow-lg transition btn-lg">
                <i class="fa-solid fa-save"></i> 
                {{ $supplier ? 'Update Supplier' : 'Simpan Supplier' }}
            </button>
        </div>
    </form>
</div>
@endsection
