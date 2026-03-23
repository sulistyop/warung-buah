@extends('layouts.app')
@section('title', 'Detail Pembayaran')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fa-solid fa-money-bill-wave text-green-600"></i> Detail Pembayaran
            </h1>
            <p class="text-gray-500 mt-1">{{ $transaksi->kode_transaksi }} - {{ $transaksi->nama_pelanggan }}</p>
        </div>
        <a href="{{ route('pembayaran.index') }}" class="text-gray-600 hover:text-gray-800 text-lg font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Info Transaksi -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice text-blue-500"></i> Info Transaksi
                </h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-gray-500 text-sm">Kode Transaksi</p>
                        <p class="font-mono font-semibold text-lg">{{ $transaksi->kode_transaksi }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Pelanggan</p>
                        <p class="font-semibold text-lg">{{ $transaksi->nama_pelanggan }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Tanggal Transaksi</p>
                        <p class="font-medium">{{ $transaksi->created_at->format('d F Y, H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Jatuh Tempo</p>
                        @if($transaksi->tanggal_jatuh_tempo)
                            @if($transaksi->isJatuhTempo())
                            <p class="font-bold text-red-600">
                                <i class="fa-solid fa-exclamation-circle"></i>
                                {{ $transaksi->tanggal_jatuh_tempo->format('d F Y') }} (LEWAT)
                            </p>
                            @else
                            <p class="font-medium">{{ $transaksi->tanggal_jatuh_tempo->format('d F Y') }}</p>
                            @endif
                        @else
                        <p class="text-gray-400">-</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Riwayat Pembayaran -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-700 flex items-center gap-2">
                        <i class="fa-solid fa-history text-green-500"></i> Riwayat Pembayaran
                    </h2>
                    @if($transaksi->sisa_tagihan > 0)
                    <a href="{{ route('pembayaran.create', $transaksi) }}"
                       class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg font-bold transition">
                        <i class="fa-solid fa-plus mr-1"></i> Catat Pembayaran
                    </a>
                    @endif
                </div>

                @if($transaksi->pembayaran->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Tanggal</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Kode</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Metode</th>
                                <th class="px-4 py-3 text-right text-gray-600 font-semibold">Nominal</th>
                                <th class="px-4 py-3 text-right text-gray-600 font-semibold">Sisa</th>
                                <th class="px-4 py-3 text-left text-gray-600 font-semibold">Kasir</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($transaksi->pembayaran->sortByDesc('created_at') as $pay)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-600">{{ $pay->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-mono text-sm text-gray-500">{{ $pay->kode_pembayaran }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $metodeColors = [
                                            'tunai' => 'bg-green-100 text-green-700',
                                            'transfer' => 'bg-blue-100 text-blue-700',
                                            'qris' => 'bg-purple-100 text-purple-700',
                                            'lainnya' => 'bg-gray-100 text-gray-700',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 rounded text-sm font-medium {{ $metodeColors[$pay->metode] ?? '' }}">
                                        {{ ucfirst($pay->metode) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-green-600">
                                    + Rp {{ number_format($pay->nominal, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-gray-500">
                                    Rp {{ number_format($pay->sisa_tagihan, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 text-sm">{{ $pay->user->name }}</td>
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('pembayaran.destroy', $pay) }}"
                                          onsubmit="return confirm('Yakin hapus pembayaran ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @if($pay->catatan)
                            <tr class="bg-gray-50">
                                <td colspan="7" class="px-4 py-2 text-gray-500 text-sm italic">
                                    <i class="fa-solid fa-sticky-note mr-1"></i> {{ $pay->catatan }}
                                </td>
                            </tr>
                            @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-8 text-gray-400">
                    <i class="fa-solid fa-inbox text-4xl mb-2 block"></i>
                    <p>Belum ada pembayaran</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Summary -->
        <div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-20">
                <h2 class="text-xl font-bold text-gray-700 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-calculator text-blue-500"></i> Ringkasan
                </h2>

                <div class="space-y-4">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Total Tagihan</span>
                        <span class="font-mono font-semibold text-lg">Rp {{ number_format($transaksi->total_tagihan, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600">Sudah Dibayar</span>
                        <span class="font-mono font-semibold text-lg text-green-600">Rp {{ number_format($transaksi->total_dibayar, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-3 bg-gray-50 rounded-xl px-4 -mx-2">
                        <span class="font-bold text-gray-800 text-lg">SISA TAGIHAN</span>
                        <span class="font-mono font-bold text-2xl {{ $transaksi->sisa_tagihan > 0 ? 'text-red-600' : 'text-green-600' }}">
                            Rp {{ number_format($transaksi->sisa_tagihan, 0, ',', '.') }}
                        </span>
                    </div>

                    @if($transaksi->sisa_tagihan <= 0)
                    <div class="bg-green-100 border-2 border-green-500 rounded-xl p-4 text-center">
                        <i class="fa-solid fa-check-circle text-4xl text-green-600 mb-2"></i>
                        <p class="font-bold text-green-700 text-lg">LUNAS</p>
                    </div>
                    @else
                    <a href="{{ route('pembayaran.create', $transaksi) }}"
                       class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-2 transition">
                        <i class="fa-solid fa-plus"></i> Catat Pembayaran
                    </a>
                    @endif
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    <a href="{{ route('transaksi.show', $transaksi) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        <i class="fa-solid fa-file-invoice mr-1"></i> Lihat Detail Transaksi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
