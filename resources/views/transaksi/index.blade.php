@extends('layouts.app')
@section('title', 'Daftar Transaksi')

@section('content')
<div class="py-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
                <i class="fa-solid fa-cash-register text-green-600"></i> Daftar Transaksi
            </h1>
            <p class="text-gray-500 mt-1">Riwayat semua transaksi penjualan</p>
        </div>
        <a href="{{ route('transaksi.create') }}"
           class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-3 rounded-xl text-lg flex items-center gap-2 shadow-lg transition btn-lg">
            <i class="fa-solid fa-plus"></i> Transaksi Baru
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
                           placeholder="Cari transaksi atau pelanggan...">
                </div>
            </div>
            <select name="status_bayar" class="px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                <option value="">Semua Status</option>
                <option value="lunas" {{ request('status_bayar') == 'lunas' ? 'selected' : '' }}>Lunas</option>
                <option value="tempo" {{ request('status_bayar') == 'tempo' ? 'selected' : '' }}>Tempo</option>
                <option value="cicil" {{ request('status_bayar') == 'cicil' ? 'selected' : '' }}>Cicil</option>
            </select>
            <input type="date" name="tanggal_dari" value="{{ request('tanggal_dari') }}"
                   class="px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
            <input type="date" name="tanggal_sampai" value="{{ request('tanggal_sampai') }}"
                   class="px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
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
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Pelanggan</th>
                        <th class="px-6 py-4 text-left text-gray-700 font-bold text-base">Tanggal</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Status</th>
                        <th class="px-6 py-4 text-right text-gray-700 font-bold text-base">Total</th>
                        <th class="px-6 py-4 text-right text-gray-700 font-bold text-base">Net Pendapatan</th>
                        <th class="px-6 py-4 text-center text-gray-700 font-bold text-base">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transaksi as $trx)
                    <tr class="hover:bg-green-50 transition">
                        <td class="px-6 py-4 font-mono text-gray-600">{{ $trx->kode_transaksi }}</td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-800 text-lg">{{ $trx->nama_pelanggan }}</div>
                            <div class="text-gray-500 text-sm">oleh {{ $trx->user->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <div>{{ $trx->created_at->format('d/m/Y') }}</div>
                            <div class="text-sm text-gray-400">{{ $trx->created_at->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $badge = [
                                    'lunas' => 'bg-green-100 text-green-700 border-green-300',
                                    'tempo' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                                    'cicil' => 'bg-blue-100 text-blue-700 border-blue-300'
                                ];
                            @endphp
                            <span class="px-3 py-1.5 rounded-full text-sm font-bold border {{ $badge[$trx->status_bayar] ?? '' }}">
                                {{ strtoupper($trx->status_bayar) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-mono text-gray-600 text-lg">
                            Rp {{ number_format($trx->total_kotor, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-green-700 text-lg">
                            Rp {{ number_format($trx->total_bersih, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('transaksi.show', $trx->id) }}"
                                   class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium transition">
                                    <i class="fa-solid fa-eye"></i> Detail
                                </a>
                                @if($trx->status_bayar !== 'lunas')
                                <a href="{{ route('pembayaran.show', $trx->id) }}"
                                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg font-medium transition">
                                    <i class="fa-solid fa-money-bill"></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                            <i class="fa-solid fa-inbox text-5xl mb-3 block"></i>
                            <p class="text-xl">Belum ada transaksi</p>
                            <a href="{{ route('transaksi.create') }}" class="text-green-600 hover:text-green-700 mt-2 inline-block font-medium">
                                + Buat Transaksi Baru
                            </a>
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
