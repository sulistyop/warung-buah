@extends('layouts.app')
@section('title', 'Kelola Piutang')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fa-solid fa-money-bill-wave text-green-600"></i> Kelola Piutang
            </h1>
            <p class="text-gray-500 mt-1">Transaksi tempo dan cicilan yang belum lunas</p>
        </div>
    </div>

    <!-- Summary Cards -->
    @php
        $totalPiutang = $transaksi->sum('sisa_tagihan');
        $jatuhTempo = $transaksi->filter(fn($t) => $t->isJatuhTempo())->count();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-file-invoice-dollar text-2xl text-blue-600"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm font-medium">Total Piutang</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $transaksi->count() }} Transaksi</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-money-bill text-2xl text-green-600"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm font-medium">Nilai Piutang</p>
                    <p class="text-2xl font-bold text-green-700">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-exclamation-triangle text-2xl text-red-600"></i>
                </div>
                <div>
                    <p class="text-gray-500 text-sm font-medium">Jatuh Tempo</p>
                    <p class="text-2xl font-bold text-red-600">{{ $jatuhTempo }} Transaksi</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <div class="relative">
                    <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="cari" value="{{ request('cari') }}"
                           class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                           placeholder="Cari pelanggan atau kode transaksi...">
                </div>
            </div>
            <select name="status" class="px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                <option value="">Semua Status</option>
                <option value="jatuh_tempo" {{ request('status') == 'jatuh_tempo' ? 'selected' : '' }}>Sudah Jatuh Tempo</option>
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
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Kode Transaksi</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Pelanggan</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Tanggal</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Jatuh Tempo</th>
                        <th class="px-6 py-4 text-right text-gray-700 font-bold text-base">Total Tagihan</th>
                        <th class="px-6 py-4 text-right text-gray-700 font-bold text-base">Sudah Dibayar</th>
                        <th class="px-6 py-4 text-right text-gray-700 font-bold text-base">Sisa Tagihan</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transaksi as $trx)
                    <tr class="hover:bg-green-50 transition {{ $trx->isJatuhTempo() ? 'bg-red-50' : '' }}">
                        <td class="px-6 py-4">
                            <span class="font-mono text-gray-600">{{ $trx->kode_transaksi }}</span>
                            @if($trx->status_bayar === 'cicil')
                            <span class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium">CICIL</span>
                            @else
                            <span class="ml-2 px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">TEMPO</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-800 text-lg">{{ $trx->nama_pelanggan }}</td>
                        <td class="px-6 py-4 text-gray-600">{{ $trx->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            @if($trx->tanggal_jatuh_tempo)
                                @if($trx->isJatuhTempo())
                                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full font-bold">
                                    <i class="fa-solid fa-exclamation-circle mr-1"></i>
                                    {{ $trx->tanggal_jatuh_tempo->format('d/m/Y') }}
                                </span>
                                @else
                                <span class="text-gray-600">{{ $trx->tanggal_jatuh_tempo->format('d/m/Y') }}</span>
                                @endif
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-gray-600">
                            Rp {{ number_format($trx->total_tagihan, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-green-600">
                            Rp {{ number_format($trx->total_dibayar, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-red-600 text-lg">
                            Rp {{ number_format($trx->sisa_tagihan, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('pembayaran.show', $trx) }}"
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </a>
                                <a href="{{ route('pembayaran.create', $trx) }}"
                                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-bold transition">
                                    <i class="fa-solid fa-plus"></i> Bayar
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-check-circle text-5xl mb-3 block text-green-400"></i>
                            <p class="text-xl">Semua transaksi sudah lunas!</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transaksi->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $transaksi->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
