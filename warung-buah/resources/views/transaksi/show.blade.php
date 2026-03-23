@extends('layouts.app')
@section('title', 'Detail Transaksi')

@section('content')
<div class="py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fa-solid fa-receipt text-green-600 mr-2"></i>
            {{ $transaksi->kode_transaksi }}
        </h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('transaksi.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
            </a>
            <button onclick="window.print()"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-3 py-2 rounded-lg flex items-center gap-1">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
            <form method="POST" action="{{ route('transaksi.destroy', $transaksi->id) }}"
                onsubmit="return confirm('Yakin hapus transaksi ini?')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="bg-red-50 hover:bg-red-100 text-red-600 text-sm px-3 py-2 rounded-lg flex items-center gap-1">
                    <i class="fa-solid fa-trash"></i> Hapus
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- Left column: Info + Item -->
        <div class="lg:col-span-2 space-y-5">

            <!-- Info pelanggan -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-700 mb-3">Info Transaksi</h2>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <div>
                        <dt class="text-gray-500">Pelanggan</dt>
                        <dd class="font-medium">{{ $transaksi->nama_pelanggan }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Status Bayar</dt>
                        <dd>
                            @php $badge = ['lunas' => 'bg-green-100 text-green-700', 'tempo' => 'bg-yellow-100 text-yellow-700', 'cicil' => 'bg-blue-100 text-blue-700']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $badge[$transaksi->status_bayar] }}">
                                {{ ucfirst($transaksi->status_bayar) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Tanggal</dt>
                        <dd>{{ $transaksi->created_at->format('d F Y, H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Kasir</dt>
                        <dd>{{ $transaksi->user->name }}</dd>
                    </div>
                    @if($transaksi->tanggal_jatuh_tempo)
                    <div>
                        <dt class="text-gray-500">Jatuh Tempo</dt>
                        <dd>{{ $transaksi->tanggal_jatuh_tempo->format('d F Y') }}</dd>
                    </div>
                    @endif
                    @if($transaksi->catatan)
                    <div class="col-span-2">
                        <dt class="text-gray-500">Catatan</dt>
                        <dd>{{ $transaksi->catatan }}</dd>
                    </div>
                    @endif
                </dl>
            </div>

            <!-- Item buah per supplier -->
            @foreach($transaksi->itemTransaksi as $item)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-green-50 px-5 py-3 flex items-center justify-between border-b border-green-100">
                    <div>
                        <span class="font-semibold text-green-800">{{ $item->jenis_buah }} Ukuran {{ $item->ukuran }}</span>
                        <span class="text-gray-500 text-sm ml-2">— {{ $item->nama_supplier }}</span>
                    </div>
                    <span class="text-sm text-gray-500">Harga: <strong class="font-mono">Rp {{ number_format($item->harga_per_kg, 0, ',', '.') }}/kg</strong></span>
                </div>

                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600 font-medium">Peti</th>
                            <th class="px-4 py-2 text-right text-gray-600 font-medium">Berat Kotor</th>
                            <th class="px-4 py-2 text-right text-gray-600 font-medium">Berat Kemasan</th>
                            <th class="px-4 py-2 text-right text-gray-600 font-medium">Berat Bersih</th>
                            <th class="px-4 py-2 text-right text-gray-600 font-medium">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($item->detailPeti as $peti)
                        <tr>
                            <td class="px-4 py-2 text-gray-600">Peti #{{ $peti->no_peti }}</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($peti->berat_kotor, 2) }} kg</td>
                            <td class="px-4 py-2 text-right font-mono">{{ number_format($peti->berat_kemasan, 2) }} kg</td>
                            <td class="px-4 py-2 text-right font-mono font-medium">{{ number_format($peti->berat_bersih, 2) }} kg</td>
                            <td class="px-4 py-2 text-right font-mono">
                                Rp {{ number_format($peti->berat_bersih * $item->harga_per_kg, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-green-50 border-t-2 border-green-200">
                        <tr class="font-semibold">
                            <td class="px-4 py-2 text-gray-600">{{ $item->jumlah_peti }} peti</td>
                            <td colspan="2" class="px-4 py-2 text-right text-xs text-gray-500">Total berat bersih:</td>
                            <td class="px-4 py-2 text-right font-mono text-green-700">
                                {{ number_format($item->total_berat_bersih, 2) }} kg
                            </td>
                            <td class="px-4 py-2 text-right font-mono text-green-700">
                                Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @endforeach

        </div>

        <!-- Right column: Ringkasan -->
        <div class="space-y-5">

            <!-- Rekap per supplier -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-chart-bar text-blue-500"></i> Rekap per Supplier
                </h2>
                @php
                    $bySupplier = $transaksi->itemTransaksi->groupBy('nama_supplier');
                @endphp
                @foreach($bySupplier as $supplier => $items)
                <div class="mb-3 pb-3 border-b border-gray-100 last:border-0 last:mb-0 last:pb-0">
                    <p class="font-medium text-sm text-gray-800">{{ $supplier }}</p>
                    @foreach($items as $it)
                    <div class="text-xs text-gray-500 flex justify-between mt-1">
                        <span>{{ $it->jenis_buah }} {{ $it->ukuran }} ({{ $it->jumlah_peti }} peti, {{ number_format($it->total_berat_bersih,2) }} kg)</span>
                        <span class="font-mono">Rp {{ number_format($it->subtotal,0,',','.') }}</span>
                    </div>
                    @endforeach
                </div>
                @endforeach
            </div>

            <!-- Biaya operasional -->
            @if($transaksi->biayaOperasional->count())
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-coins text-yellow-500"></i> Biaya Operasional
                </h2>
                <div class="space-y-2">
                    @foreach($transaksi->biayaOperasional as $biaya)
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">{{ $biaya->nama_biaya }}</span>
                        <span class="font-mono">Rp {{ number_format($biaya->nominal, 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Ringkasan keuangan -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <h2 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-calculator text-green-500"></i> Ringkasan
                </h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Total Kotor</span>
                        <span class="font-mono">Rp {{ number_format($transaksi->total_kotor, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Komisi ({{ $transaksi->komisi_persen }}%)</span>
                        <span class="font-mono text-red-500">- Rp {{ number_format($transaksi->total_komisi, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Biaya Operasional</span>
                        <span class="font-mono text-red-500">- Rp {{ number_format($transaksi->total_biaya_operasional, 0, ',', '.') }}</span>
                    </div>
                    <div class="border-t pt-2 flex justify-between font-bold text-green-700 text-base">
                        <span>Net Pendapatan</span>
                        <span class="font-mono">Rp {{ number_format($transaksi->total_bersih, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
