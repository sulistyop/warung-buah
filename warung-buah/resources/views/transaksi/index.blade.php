@extends('layouts.app')
@section('title', 'Daftar Transaksi')

@section('content')
<div class="py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-list text-green-600"></i> Transaksi
        </h1>
        <a href="{{ route('transaksi.create') }}"
            class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-lg text-sm flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Transaksi Baru
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-gray-600 font-medium">Kode</th>
                    <th class="px-4 py-3 text-left text-gray-600 font-medium">Pelanggan</th>
                    <th class="px-4 py-3 text-left text-gray-600 font-medium">Tanggal</th>
                    <th class="px-4 py-3 text-left text-gray-600 font-medium">Status</th>
                    <th class="px-4 py-3 text-right text-gray-600 font-medium">Total Kotor</th>
                    <th class="px-4 py-3 text-right text-gray-600 font-medium">Net Pendapatan</th>
                    <th class="px-4 py-3 text-center text-gray-600 font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($transaksi as $trx)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $trx->kode_transaksi }}</td>
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $trx->nama_pelanggan }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $trx->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">
                        @php
                            $badge = ['lunas' => 'bg-green-100 text-green-700', 'tempo' => 'bg-yellow-100 text-yellow-700', 'cicil' => 'bg-blue-100 text-blue-700'];
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badge[$trx->status_bayar] ?? '' }}">
                            {{ ucfirst($trx->status_bayar) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right font-mono text-gray-600">
                        Rp {{ number_format($trx->total_kotor, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-right font-mono font-semibold text-green-700">
                        Rp {{ number_format($trx->total_bersih, 0, ',', '.') }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('transaksi.show', $trx->id) }}"
                            class="text-blue-500 hover:text-blue-700 text-xs font-medium">
                            <i class="fa-solid fa-eye"></i> Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-10 text-center text-gray-400">
                        <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                        Belum ada transaksi
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($transaksi->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $transaksi->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
