@extends('layouts.app')
@section('title', 'Pengaturan')

@section('content')
<div class="py-6 max-w-xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
        <i class="fa-solid fa-gear text-green-600"></i> Pengaturan
    </h1>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('settings.update') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                <input type="text" name="nama_toko"
                    value="{{ old('nama_toko', $settings['nama_toko']->value ?? '') }}"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                    placeholder="Warung Buah">
                @error('nama_toko')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Toko</label>
                <textarea name="alamat_toko" rows="2"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400"
                    placeholder="Alamat toko (opsional)">{{ old('alamat_toko', $settings['alamat_toko']->value ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Komisi Default (%)
                    <span class="text-gray-400 font-normal ml-1">— akan otomatis terisi saat buat transaksi baru</span>
                </label>
                <div class="flex items-center gap-2">
                    <input type="number" name="komisi_persen" step="0.01" min="0" max="100"
                        value="{{ old('komisi_persen', $settings['komisi_persen']->value ?? 0) }}"
                        class="w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 font-mono">
                    <span class="text-gray-500 text-sm">%</span>
                </div>
                @error('komisi_persen')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="pt-2">
                <button type="submit"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-5 py-2 rounded-lg text-sm flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
