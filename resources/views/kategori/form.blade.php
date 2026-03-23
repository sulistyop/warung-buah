@extends('layouts.app')

@section('title', $kategori ? 'Edit Kategori' : 'Tambah Kategori')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('kategori.index') }}" 
           class="flex items-center justify-center w-12 h-12 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
            <i class="fa-solid fa-arrow-left text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                {{ $kategori ? 'Edit Kategori' : 'Tambah Kategori Baru' }}
            </h1>
            @if($kategori)
            <p class="text-gray-500 mt-1">{{ $kategori->kode_kategori }}</p>
            @endif
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ $kategori ? route('kategori.update', $kategori) : route('kategori.store') }}" method="POST">
            @csrf
            @if($kategori)
            @method('PUT')
            @endif

            <div class="space-y-6">
                <!-- Nama Kategori -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        Nama Kategori <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nama_kategori" 
                           value="{{ old('nama_kategori', $kategori?->nama_kategori) }}"
                           class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-purple-500 focus:outline-none @error('nama_kategori') border-red-500 @enderror"
                           placeholder="Contoh: Buah Tropis"
                           required>
                    @error('nama_kategori')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Deskripsi</label>
                    <textarea name="deskripsi" rows="3"
                              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-purple-500 focus:outline-none"
                              placeholder="Deskripsi kategori (opsional)">{{ old('deskripsi', $kategori?->deskripsi) }}</textarea>
                </div>

                <!-- Warna -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">
                        Warna Badge <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-4 sm:grid-cols-8 gap-3">
                        @foreach($warna as $hex => $nama)
                        <label class="cursor-pointer">
                            <input type="radio" name="warna" value="{{ $hex }}" 
                                   {{ old('warna', $kategori?->warna ?? '#4CAF50') == $hex ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center transition-all 
                                        peer-checked:ring-4 peer-checked:ring-offset-2 peer-checked:ring-purple-500
                                        hover:scale-110"
                                 style="background-color: {{ $hex }}"
                                 title="{{ $nama }}">
                                <i class="fa-solid fa-check text-white opacity-0 peer-checked:opacity-100 transition-opacity text-xl"></i>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    <p class="text-gray-500 text-sm mt-2">Pilih warna untuk badge kategori</p>
                </div>

                <!-- Status Aktif -->
                <div>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="aktif" value="1"
                               {{ old('aktif', $kategori?->aktif ?? true) ? 'checked' : '' }}
                               class="w-6 h-6 rounded border-2 border-gray-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-gray-700 font-semibold text-lg">Kategori Aktif</span>
                    </label>
                    <p class="text-gray-500 text-sm mt-1 ml-9">Kategori yang aktif akan muncul di pilihan produk</p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row gap-3 mt-8 pt-6 border-t border-gray-200">
                <button type="submit" 
                        class="flex-1 flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white py-4 px-6 rounded-xl font-semibold text-lg transition-colors">
                    <i class="fa-solid fa-save"></i>
                    {{ $kategori ? 'Simpan Perubahan' : 'Simpan Kategori' }}
                </button>
                <a href="{{ route('kategori.index') }}" 
                   class="flex items-center justify-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 py-4 px-6 rounded-xl font-semibold text-lg transition-colors">
                    <i class="fa-solid fa-times"></i>
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show checkmark on selected color
    document.querySelectorAll('input[name="warna"]').forEach(input => {
        const icon = input.nextElementSibling.querySelector('i');
        icon.classList.toggle('opacity-0', !input.checked);
        icon.classList.toggle('opacity-100', input.checked);
        
        input.addEventListener('change', function() {
            document.querySelectorAll('input[name="warna"]').forEach(otherInput => {
                const otherIcon = otherInput.nextElementSibling.querySelector('i');
                otherIcon.classList.toggle('opacity-0', !otherInput.checked);
                otherIcon.classList.toggle('opacity-100', otherInput.checked);
            });
        });
    });
});
</script>
@endsection
