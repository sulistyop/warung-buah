@extends('layouts.app')
@section('title', 'Catat Pembayaran')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fa-solid fa-money-bill-wave text-green-600"></i> Catat Pembayaran
            </h1>
            <p class="text-gray-500 mt-1">{{ $transaksi->kode_transaksi }} - {{ $transaksi->nama_pelanggan }}</p>
        </div>
        <a href="{{ route('pembayaran.show', $transaksi) }}" class="text-gray-600 hover:text-gray-800 text-lg font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Form Pembayaran -->
        <div>
            <form method="POST" action="{{ route('pembayaran.store', $transaksi) }}">
                @csrf
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-credit-card text-blue-500"></i> Form Pembayaran
                    </h2>

                    <div class="space-y-5">
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2 text-lg">
                                Nominal Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">Rp</span>
                                <input type="number" name="nominal" value="{{ old('nominal') }}"
                                       required min="1" max="{{ $transaksi->sisa_tagihan }}" step="1000"
                                       class="w-full pl-12 pr-4 py-4 border-2 border-gray-200 rounded-xl text-2xl font-mono font-bold focus:border-green-500 focus:outline-none"
                                       placeholder="0" autofocus>
                            </div>
                            <p class="text-gray-500 mt-2">
                                Maksimal: <span class="font-mono font-semibold">Rp {{ number_format($transaksi->sisa_tagihan, 0, ',', '.') }}</span>
                            </p>
                            @error('nominal')<p class="text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>

                        <!-- Quick Amount Buttons -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">Pilih Cepat</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" onclick="setNominal({{ $transaksi->sisa_tagihan }})"
                                        class="bg-green-100 hover:bg-green-200 text-green-700 py-3 rounded-xl font-bold transition">
                                    LUNAS SEMUA
                                </button>
                                <button type="button" onclick="setNominal({{ floor($transaksi->sisa_tagihan / 2) }})"
                                        class="bg-blue-100 hover:bg-blue-200 text-blue-700 py-3 rounded-xl font-bold transition">
                                    50% (Rp {{ number_format(floor($transaksi->sisa_tagihan / 2), 0, ',', '.') }})
                                </button>
                                <button type="button" onclick="setNominal(100000)"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg font-medium transition">
                                    Rp 100.000
                                </button>
                                <button type="button" onclick="setNominal(500000)"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg font-medium transition">
                                    Rp 500.000
                                </button>
                                <button type="button" onclick="setNominal(1000000)"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg font-medium transition">
                                    Rp 1.000.000
                                </button>
                                <button type="button" onclick="setNominal(2000000)"
                                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg font-medium transition">
                                    Rp 2.000.000
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2 text-lg">
                                Metode Pembayaran <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer hover:bg-gray-50 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                                    <input type="radio" name="metode" value="tunai" checked class="w-5 h-5 text-green-600">
                                    <i class="fa-solid fa-money-bill text-green-600 text-xl"></i>
                                    <span class="font-semibold">Tunai</span>
                                </label>
                                <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer hover:bg-gray-50 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                                    <input type="radio" name="metode" value="transfer" class="w-5 h-5 text-green-600">
                                    <i class="fa-solid fa-building-columns text-blue-600 text-xl"></i>
                                    <span class="font-semibold">Transfer</span>
                                </label>
                                <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer hover:bg-gray-50 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                                    <input type="radio" name="metode" value="qris" class="w-5 h-5 text-green-600">
                                    <i class="fa-solid fa-qrcode text-purple-600 text-xl"></i>
                                    <span class="font-semibold">QRIS</span>
                                </label>
                                <label class="flex items-center gap-3 p-4 border-2 rounded-xl cursor-pointer hover:bg-gray-50 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                                    <input type="radio" name="metode" value="lainnya" class="w-5 h-5 text-green-600">
                                    <i class="fa-solid fa-ellipsis text-gray-600 text-xl"></i>
                                    <span class="font-semibold">Lainnya</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2 text-lg">
                                Referensi / No. Rekening
                            </label>
                            <input type="text" name="referensi" value="{{ old('referensi') }}"
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                                   placeholder="Opsional...">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2 text-lg">Catatan</label>
                            <textarea name="catatan" rows="2"
                                      class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none"
                                      placeholder="Catatan pembayaran (opsional)...">{{ old('catatan') }}</textarea>
                        </div>

                        <button type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-xl font-bold text-xl flex items-center justify-center gap-2 transition shadow-lg">
                            <i class="fa-solid fa-check"></i> Simpan Pembayaran
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Info Transaksi -->
        <div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-20">
                <h2 class="text-xl font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice text-blue-500"></i> Info Tagihan
                </h2>

                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-gray-500 text-sm">Pelanggan</p>
                        <p class="font-bold text-xl text-gray-800">{{ $transaksi->nama_pelanggan }}</p>
                    </div>

                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Total Tagihan</span>
                        <span class="font-mono font-semibold text-lg">Rp {{ number_format($transaksi->total_tagihan, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Sudah Dibayar</span>
                        <span class="font-mono font-semibold text-lg text-green-600">Rp {{ number_format($transaksi->total_dibayar, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-4 bg-red-50 rounded-xl px-4 -mx-2">
                        <span class="font-bold text-gray-800 text-lg">SISA TAGIHAN</span>
                        <span class="font-mono font-bold text-2xl text-red-600">
                            Rp {{ number_format($transaksi->sisa_tagihan, 0, ',', '.') }}
                        </span>
                    </div>

                    @if($transaksi->tanggal_jatuh_tempo)
                    <div class="bg-yellow-50 rounded-xl p-4">
                        <p class="text-gray-500 text-sm">Jatuh Tempo</p>
                        <p class="font-bold text-lg {{ $transaksi->isJatuhTempo() ? 'text-red-600' : 'text-gray-800' }}">
                            @if($transaksi->isJatuhTempo())
                            <i class="fa-solid fa-exclamation-triangle mr-1"></i>
                            @endif
                            {{ $transaksi->tanggal_jatuh_tempo->format('d F Y') }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function setNominal(value) {
    document.querySelector('input[name="nominal"]').value = value;
}
</script>
@endsection
