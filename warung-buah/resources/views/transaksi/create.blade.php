@extends('layouts.app')
@section('title', 'Transaksi Baru')

@section('content')
<div x-data="kasirApp({{ $komisiDefault }})" class="py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fa-solid fa-receipt text-green-600"></i> Transaksi Baru
        </h1>
        <a href="{{ route('transaksi.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
            <i class="fa-solid fa-arrow-left mr-1"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('transaksi.store') }}" @submit.prevent="submitForm">
        @csrf

        <!-- ===== INFO PELANGGAN ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
            <h2 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-user text-green-500"></i> Info Pelanggan
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="label">Nama Pelanggan *</label>
                    <input type="text" name="nama_pelanggan" required
                        class="input" placeholder="Nama pelanggan...">
                    @error('nama_pelanggan')<p class="err">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Komisi (%)</label>
                    <input type="number" name="komisi_persen" x-model="komisiPersen"
                        step="0.01" min="0" max="100"
                        class="input" placeholder="0">
                </div>
                <div>
                    <label class="label">Status Bayar *</label>
                    <select name="status_bayar" x-model="statusBayar" class="input">
                        <option value="lunas">Lunas</option>
                        <option value="tempo">Tempo</option>
                        <option value="cicil">Cicil</option>
                    </select>
                </div>
                <div x-show="statusBayar !== 'lunas'" x-cloak>
                    <label class="label">Tanggal Jatuh Tempo</label>
                    <input type="date" name="tanggal_jatuh_tempo" class="input">
                </div>
                <div>
                    <label class="label">Catatan</label>
                    <input type="text" name="catatan" class="input" placeholder="Opsional...">
                </div>
            </div>
        </div>

        <!-- ===== ITEM BUAH (REPEATER) ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-green-500"></i> Item Buah
                </h2>
                <button type="button" @click="addItem()"
                    class="bg-green-600 hover:bg-green-700 text-white text-sm px-3 py-1.5 rounded-lg flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>

            <div class="space-y-5">
                <template x-for="(item, iIdx) in items" :key="item.id">
                    <div class="border border-gray-200 rounded-xl p-4 bg-gray-50 fade-in">

                        <!-- Header item -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="font-medium text-gray-700 text-sm" x-text="'Item #' + (iIdx+1)"></span>
                            <button type="button" @click="removeItem(iIdx)" x-show="items.length > 1"
                                class="text-red-400 hover:text-red-600 text-sm">
                                <i class="fa-solid fa-trash"></i> Hapus Item
                            </button>
                        </div>

                        <!-- Info buah -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                            <div>
                                <label class="label">Supplier *</label>
                                <input type="text" :name="'items['+iIdx+'][nama_supplier]'"
                                    x-model="item.nama_supplier" required
                                    class="input" placeholder="Nama supplier">
                            </div>
                            <div>
                                <label class="label">Jenis Buah *</label>
                                <input type="text" :name="'items['+iIdx+'][jenis_buah]'"
                                    x-model="item.jenis_buah" required
                                    class="input" placeholder="Jeruk, Apel...">
                            </div>
                            <div>
                                <label class="label">Ukuran *</label>
                                <select :name="'items['+iIdx+'][ukuran]'" x-model="item.ukuran" class="input">
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Harga/kg (Rp) *</label>
                                <input type="number" :name="'items['+iIdx+'][harga_per_kg]'"
                                    x-model="item.harga_per_kg" @input="calcItem(iIdx)"
                                    required min="0" step="50"
                                    class="input font-mono" placeholder="8000">
                            </div>
                        </div>

                        <!-- Tabel peti -->
                        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-3">
                            <table class="w-full text-sm">
                                <thead class="bg-green-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-gray-600 font-medium w-12">#</th>
                                        <th class="px-3 py-2 text-left text-gray-600 font-medium">Berat Kotor (kg)</th>
                                        <th class="px-3 py-2 text-left text-gray-600 font-medium">Berat Kemasan (kg)</th>
                                        <th class="px-3 py-2 text-right text-gray-600 font-medium">Berat Bersih (kg)</th>
                                        <th class="px-3 py-2 text-right text-gray-600 font-medium">Subtotal</th>
                                        <th class="w-10"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(peti, pIdx) in item.peti" :key="peti.id">
                                        <tr class="border-t border-gray-100">
                                            <td class="px-3 py-2 text-gray-500" x-text="pIdx+1"></td>
                                            <td class="px-3 py-2">
                                                <input type="number"
                                                    :name="'items['+iIdx+'][peti]['+pIdx+'][berat_kotor]'"
                                                    x-model="peti.berat_kotor"
                                                    @input="calcItem(iIdx)"
                                                    required min="0" step="0.01"
                                                    class="input-sm w-full font-mono" placeholder="0.00">
                                            </td>
                                            <td class="px-3 py-2">
                                                <input type="number"
                                                    :name="'items['+iIdx+'][peti]['+pIdx+'][berat_kemasan]'"
                                                    x-model="peti.berat_kemasan"
                                                    @input="calcItem(iIdx)"
                                                    required min="0" step="0.01"
                                                    class="input-sm w-full font-mono" placeholder="0.00">
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono text-gray-700"
                                                x-text="beratBersih(peti).toFixed(2)">
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono text-gray-700"
                                                x-text="formatRp(beratBersih(peti) * item.harga_per_kg)">
                                            </td>
                                            <td class="px-2 py-2 text-center">
                                                <button type="button" @click="removePeti(iIdx, pIdx)"
                                                    x-show="item.peti.length > 1"
                                                    class="text-red-300 hover:text-red-500 text-xs">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Row total item -->
                                    <tr class="bg-green-50 border-t-2 border-green-200 font-semibold">
                                        <td colspan="2" class="px-3 py-2 text-xs text-gray-500"
                                            x-text="item.peti.length + ' peti'"></td>
                                        <td class="px-3 py-2 text-xs text-gray-500">Total bersih:</td>
                                        <td class="px-3 py-2 text-right font-mono text-green-700"
                                            x-text="totalBeratBersihItem(iIdx).toFixed(2) + ' kg'"></td>
                                        <td class="px-3 py-2 text-right font-mono text-green-700"
                                            x-text="formatRp(subtotalItem(iIdx))"></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" @click="addPeti(iIdx)"
                            class="text-green-600 hover:text-green-800 text-sm flex items-center gap-1">
                            <i class="fa-solid fa-plus"></i> Tambah Peti
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- ===== BIAYA OPERASIONAL ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-700 flex items-center gap-2">
                    <i class="fa-solid fa-coins text-yellow-500"></i> Biaya Operasional
                </h2>
                <button type="button" @click="addBiaya()"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-3 py-1.5 rounded-lg flex items-center gap-1">
                    <i class="fa-solid fa-plus"></i> Tambah Biaya
                </button>
            </div>

            <div x-show="biaya.length === 0" class="text-gray-400 text-sm py-2">
                Belum ada biaya operasional.
            </div>

            <div class="space-y-2">
                <template x-for="(b, bIdx) in biaya" :key="b.id">
                    <div class="flex gap-3 items-center fade-in">
                        <input type="text" :name="'biaya['+bIdx+'][nama_biaya]'"
                            x-model="b.nama_biaya"
                            class="input flex-1" placeholder="Kuli, Pengiriman, dll...">
                        <input type="number" :name="'biaya['+bIdx+'][nominal]'"
                            x-model="b.nominal" @input="calcTotal()"
                            min="0" step="1000"
                            class="input w-40 font-mono" placeholder="0">
                        <button type="button" @click="removeBiaya(bIdx)"
                            class="text-red-400 hover:text-red-600">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- ===== RINGKASAN TOTAL ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
            <h2 class="font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-calculator text-blue-500"></i> Ringkasan
            </h2>
            <div class="max-w-sm ml-auto space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Total Kotor</span>
                    <span class="font-mono" x-text="formatRp(totalKotor())"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Komisi (<span x-text="komisiPersen"></span>%)</span>
                    <span class="font-mono text-red-500" x-text="'- ' + formatRp(totalKomisi())"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>Biaya Operasional</span>
                    <span class="font-mono text-red-500" x-text="'- ' + formatRp(totalBiayaOps())"></span>
                </div>
                <div class="border-t pt-2 flex justify-between font-bold text-green-700 text-base">
                    <span>Net Pendapatan</span>
                    <span class="font-mono" x-text="formatRp(totalBersih())"></span>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <a href="{{ route('transaksi.index') }}"
                class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 text-sm">
                Batal
            </a>
            <button type="submit"
                class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg flex items-center gap-2 text-sm">
                <i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi
            </button>
        </div>

    </form>
</div>

<style>
.label { @apply block text-xs font-medium text-gray-600 mb-1; }
.input  { @apply w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-400 bg-white; }
.input-sm { @apply border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-green-400; }
.err    { @apply text-red-500 text-xs mt-1; }
</style>

<script>
function kasirApp(komisiDefault) {
    return {
        komisiPersen: komisiDefault,
        statusBayar: 'lunas',
        _id: 100,
        items: [],
        biaya: [],

        init() {
            this.addItem();
        },

        // ---- Item buah ----
        addItem() {
            this.items.push({
                id: this._id++,
                nama_supplier: '',
                jenis_buah: '',
                ukuran: 'A',
                harga_per_kg: '',
                peti: [this.newPeti()],
            });
        },
        removeItem(idx) {
            this.items.splice(idx, 1);
        },

        // ---- Peti ----
        newPeti() {
            return { id: this._id++, berat_kotor: '', berat_kemasan: '' };
        },
        addPeti(iIdx) {
            this.items[iIdx].peti.push(this.newPeti());
        },
        removePeti(iIdx, pIdx) {
            this.items[iIdx].peti.splice(pIdx, 1);
            this.calcItem(iIdx);
        },

        // ---- Biaya ----
        addBiaya() {
            this.biaya.push({ id: this._id++, nama_biaya: '', nominal: '' });
        },
        removeBiaya(idx) {
            this.biaya.splice(idx, 1);
        },

        // ---- Kalkulasi ----
        beratBersih(peti) {
            const kotor = parseFloat(peti.berat_kotor) || 0;
            const kemasan = parseFloat(peti.berat_kemasan) || 0;
            return Math.max(0, kotor - kemasan);
        },
        totalBeratBersihItem(iIdx) {
            return this.items[iIdx].peti.reduce((s, p) => s + this.beratBersih(p), 0);
        },
        subtotalItem(iIdx) {
            const item = this.items[iIdx];
            const harga = parseFloat(item.harga_per_kg) || 0;
            return this.totalBeratBersihItem(iIdx) * harga;
        },
        calcItem(iIdx) { /* reactive, nothing needed */ },

        totalKotor() {
            return this.items.reduce((s, _, i) => s + this.subtotalItem(i), 0);
        },
        totalKomisi() {
            return this.totalKotor() * ((parseFloat(this.komisiPersen) || 0) / 100);
        },
        totalBiayaOps() {
            return this.biaya.reduce((s, b) => s + (parseFloat(b.nominal) || 0), 0);
        },
        totalBersih() {
            return this.totalKotor() - this.totalKomisi() - this.totalBiayaOps();
        },
        calcTotal() { /* reactive */ },

        formatRp(val) {
            return 'Rp ' + Math.round(val).toLocaleString('id-ID');
        },

        // Submit: convert Alpine data ke form fields tersembunyi lalu submit
        submitForm(e) {
            e.target.submit();
        }
    }
}
</script>
@endsection
