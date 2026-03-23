@extends('layouts.app')
@section('title', 'Transaksi Baru')

@section('content')
<div x-data="kasirApp({{ $komisiDefault }}, {{ json_encode($suppliers) }}, {{ json_encode($produks) }})" class="py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-3">
            <i class="fa-solid fa-cash-register text-green-600"></i> Transaksi Baru
        </h1>
        <a href="{{ route('transaksi.index') }}" class="text-gray-600 hover:text-gray-800 text-lg font-medium">
            <i class="fa-solid fa-arrow-left mr-2"></i> Kembali
        </a>
    </div>

    <form method="POST" action="{{ route('transaksi.store') }}" @submit.prevent="submitForm">
        @csrf

        <!-- ===== INFO PELANGGAN ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-5">
            <h2 class="text-xl font-bold text-gray-700 mb-5 flex items-center gap-2">
                <i class="fa-solid fa-user text-green-500"></i> Info Pelanggan
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Nama Pelanggan <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_pelanggan" required
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                        placeholder="Nama pelanggan...">
                    @error('nama_pelanggan')<p class="text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Komisi (%)</label>
                    <input type="number" name="komisi_persen" x-model="komisiPersen"
                        step="0.01" min="0" max="100"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg font-mono"
                        placeholder="0">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Status Bayar <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="flex items-center justify-center gap-2 p-3 border-2 rounded-xl cursor-pointer hover:bg-gray-50 has-[:checked]:border-green-500 has-[:checked]:bg-green-50">
                            <input type="radio" name="status_bayar" value="lunas" x-model="statusBayar" class="w-5 h-5 text-green-600">
                            <span class="font-bold text-green-700">LUNAS</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 p-3 border-2 rounded-xl cursor-pointer hover:bg-gray-50 has-[:checked]:border-yellow-500 has-[:checked]:bg-yellow-50">
                            <input type="radio" name="status_bayar" value="tempo" x-model="statusBayar" class="w-5 h-5 text-yellow-600">
                            <span class="font-bold text-yellow-700">TEMPO</span>
                        </label>
                        <label class="flex items-center justify-center gap-2 p-3 border-2 rounded-xl cursor-pointer hover:bg-gray-50 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50">
                            <input type="radio" name="status_bayar" value="cicil" x-model="statusBayar" class="w-5 h-5 text-blue-600">
                            <span class="font-bold text-blue-700">CICIL</span>
                        </label>
                    </div>
                </div>
                <div x-show="statusBayar !== 'lunas'" x-cloak>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Tanggal Jatuh Tempo</label>
                    <input type="date" name="tanggal_jatuh_tempo"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2 text-lg">Catatan</label>
                    <input type="text" name="catatan"
                        class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none input-lg"
                        placeholder="Opsional...">
                </div>
            </div>
        </div>

        <!-- ===== ITEM BUAH (REPEATER) ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-5">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-bold text-gray-700 flex items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-green-500"></i> Item Buah
                </h2>
                <button type="button" @click="addItem()"
                    class="bg-green-600 hover:bg-green-700 text-white font-bold px-5 py-2.5 rounded-xl flex items-center gap-2 shadow-md transition">
                    <i class="fa-solid fa-plus"></i> Tambah Item
                </button>
            </div>

            <div class="space-y-6">
                <template x-for="(item, iIdx) in items" :key="item.id">
                    <div class="border-2 border-gray-200 rounded-xl p-5 bg-gray-50 fade-in">

                        <!-- Header item -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="bg-green-600 text-white px-4 py-1 rounded-full font-bold" x-text="'ITEM #' + (iIdx+1)"></span>
                            <button type="button" @click="removeItem(iIdx)" x-show="items.length > 1"
                                class="text-red-500 hover:text-red-700 font-medium flex items-center gap-1">
                                <i class="fa-solid fa-trash"></i> Hapus Item
                            </button>
                        </div>

                        <!-- Info buah -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Supplier <span class="text-red-500">*</span></label>
                                <input type="text" :name="'items['+iIdx+'][nama_supplier]'"
                                    x-model="item.nama_supplier" required list="supplierList"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none bg-white"
                                    placeholder="Ketik/pilih supplier">
                                <datalist id="supplierList">
                                    <template x-for="s in supplierList" :key="s.id">
                                        <option :value="s.nama_supplier"></option>
                                    </template>
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Jenis Buah <span class="text-red-500">*</span></label>
                                <input type="text" :name="'items['+iIdx+'][jenis_buah]'"
                                    x-model="item.jenis_buah"
                                    @input="onProductChange(iIdx, $event.target.value)"
                                    required list="productList"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-green-500 focus:outline-none"
                                    placeholder="Ketik/pilih buah...">
                                <datalist id="productList">
                                    <template x-for="p in productList" :key="p.id">
                                        <option :value="p.nama_produk" :data-price="p.harga_jual"></option>
                                    </template>
                                </datalist>
                            </div>
                           <div>
                                <label class="block text-gray-700 font-semibold mb-2">Ukuran <span class="text-red-500">*</span></label>
                                <div class="flex gap-1">
                                    <template x-for="uk in ['A','B','C','D','E']" :key="uk">
                                        <label class="flex-1">
                                            <input type="radio" :name="'items['+iIdx+'][ukuran]'" :value="uk"
                                                x-model="item.ukuran" class="sr-only peer">
                                            <div class="text-center py-3 border-2 rounded-lg cursor-pointer font-bold
                                                peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600
                                                hover:bg-gray-100" x-text="uk"></div>
                                        </label>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">Harga/kg (Rp) <span class="text-red-500">*</span></label>
                                <input type="number" :name="'items['+iIdx+'][harga_per_kg]'"
                                    x-model="item.harga_per_kg" @input="calcItem(iIdx)"
                                    required min="0" step="50"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono focus:border-green-500 focus:outline-none bg-green-50"
                                    placeholder="8000">
                            </div>
                        </div>

                        <!-- Tabel peti -->
                        <div class="bg-white rounded-xl border-2 border-gray-200 overflow-hidden mb-4">
                            <table class="w-full">
                                <thead class="bg-green-100">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-gray-700 font-bold w-16">#</th>
                                        <th class="px-4 py-3 text-left text-gray-700 font-bold">Berat Kotor (kg)</th>
                                        <th class="px-4 py-3 text-left text-gray-700 font-bold">Berat Kemasan (kg)</th>
                                        <th class="px-4 py-3 text-right text-gray-700 font-bold">Berat Bersih</th>
                                        <th class="px-4 py-3 text-right text-gray-700 font-bold">Subtotal</th>
                                        <th class="w-12"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(peti, pIdx) in item.peti" :key="peti.id">
                                        <tr class="border-t border-gray-100 hover:bg-gray-50">
                                            <td class="px-4 py-3 text-gray-500 font-bold" x-text="pIdx+1"></td>
                                            <td class="px-4 py-3">
                                                <input type="number"
                                                    :name="'items['+iIdx+'][peti]['+pIdx+'][berat_kotor]'"
                                                    x-model="peti.berat_kotor"
                                                    @input="calcItem(iIdx)"
                                                    required min="0" step="0.01"
                                                    class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg font-mono text-lg focus:border-green-500 focus:outline-none"
                                                    placeholder="0.00">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="number"
                                                    :name="'items['+iIdx+'][peti]['+pIdx+'][berat_kemasan]'"
                                                    x-model="peti.berat_kemasan"
                                                    @input="calcItem(iIdx)"
                                                    required min="0" step="0.01"
                                                    class="w-full px-3 py-2.5 border-2 border-gray-200 rounded-lg font-mono text-lg focus:border-green-500 focus:outline-none"
                                                    placeholder="0.00">
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono text-lg font-semibold text-gray-700"
                                                x-text="beratBersih(peti).toFixed(2) + ' kg'">
                                            </td>
                                            <td class="px-4 py-3 text-right font-mono text-lg font-semibold text-green-700"
                                                x-text="formatRp(beratBersih(peti) * item.harga_per_kg)">
                                            </td>
                                            <td class="px-2 py-3 text-center">
                                                <button type="button" @click="removePeti(iIdx, pIdx)"
                                                    x-show="item.peti.length > 1"
                                                    class="text-red-400 hover:text-red-600 text-lg p-2">
                                                    <i class="fa-solid fa-circle-xmark"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>

                                    <!-- Row total item -->
                                    <tr class="bg-green-50 border-t-2 border-green-300">
                                        <td colspan="2" class="px-4 py-3">
                                            <span class="bg-green-600 text-white px-3 py-1 rounded-full font-bold text-sm"
                                                x-text="item.peti.length + ' PETI'"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-gray-600">Total:</td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-lg text-green-700"
                                            x-text="totalBeratBersihItem(iIdx).toFixed(2) + ' kg'"></td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-xl text-green-700"
                                            x-text="formatRp(subtotalItem(iIdx))"></td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="button" @click="addPeti(iIdx)"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold flex items-center gap-2 transition">
                            <i class="fa-solid fa-plus"></i> Tambah Peti
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- ===== BIAYA OPERASIONAL ===== -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-5">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-bold text-gray-700 flex items-center gap-2">
                    <i class="fa-solid fa-coins text-yellow-500"></i> Biaya Operasional
                </h2>
                <button type="button" @click="addBiaya()"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold px-5 py-2.5 rounded-xl flex items-center gap-2 shadow-md transition">
                    <i class="fa-solid fa-plus"></i> Tambah Biaya
                </button>
            </div>

            <div x-show="biaya.length === 0" class="text-gray-400 text-lg py-4 text-center">
                <i class="fa-solid fa-info-circle mr-2"></i> Belum ada biaya operasional
            </div>

            <div class="space-y-3">
                <template x-for="(b, bIdx) in biaya" :key="b.id">
                    <div class="flex gap-4 items-center fade-in bg-yellow-50 p-4 rounded-xl border border-yellow-200">
                        <input type="text" :name="'biaya['+bIdx+'][nama_biaya]'"
                            x-model="b.nama_biaya"
                            class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-xl text-lg focus:border-yellow-500 focus:outline-none"
                            placeholder="Kuli, Pengiriman, dll...">
                        <div class="relative w-48">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-semibold">Rp</span>
                            <input type="number" :name="'biaya['+bIdx+'][nominal]'"
                                x-model="b.nominal" @input="calcTotal()"
                                min="0" step="1000"
                                class="w-full pl-12 pr-4 py-3 border-2 border-gray-200 rounded-xl text-lg font-mono focus:border-yellow-500 focus:outline-none"
                                placeholder="0">
                        </div>
                        <button type="button" @click="removeBiaya(bIdx)"
                            class="text-red-400 hover:text-red-600 text-xl p-2">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <!-- ===== RINGKASAN & PEMBAYARAN ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
            <!-- Ringkasan -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-xl font-bold text-gray-700 mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-calculator text-blue-500"></i> Ringkasan
                </h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 text-lg">Total Kotor</span>
                        <span class="font-mono text-xl font-semibold" x-text="formatRp(totalKotor())"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 text-lg">Komisi (<span x-text="komisiPersen"></span>%)</span>
                        <span class="font-mono text-xl text-red-500" x-text="'- ' + formatRp(totalKomisi())"></span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-600 text-lg">Biaya Operasional</span>
                        <span class="font-mono text-xl text-red-500" x-text="'- ' + formatRp(totalBiayaOps())"></span>
                    </div>
                    <div class="flex justify-between items-center py-3 bg-green-50 rounded-xl px-4 -mx-2">
                        <span class="font-bold text-gray-800 text-xl">Net Pendapatan</span>
                        <span class="font-mono font-bold text-2xl text-green-700" x-text="formatRp(totalBersih())"></span>
                    </div>
                </div>
            </div>

            <!-- Pembayaran (jika lunas) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6" x-show="statusBayar === 'lunas'" x-cloak>
                <h2 class="text-xl font-bold text-gray-700 mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-money-bill-wave text-green-500"></i> Pembayaran
                </h2>
                <div class="space-y-4">
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-200">
                        <label class="block text-gray-700 font-semibold mb-2 text-lg">TOTAL TAGIHAN</label>
                        <div class="font-mono text-3xl font-bold text-blue-700" x-text="formatRp(totalKotor())"></div>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2 text-lg">Uang Diterima (Rp)</label>
                        <input type="number" name="uang_diterima" x-model="uangDiterima"
                            min="0" step="1000"
                            class="w-full px-4 py-4 border-2 border-gray-200 rounded-xl text-2xl font-mono font-bold focus:border-green-500 focus:outline-none"
                            placeholder="0">
                    </div>

                    <!-- Quick Amount Buttons -->
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="setUangPas()"
                            class="bg-green-100 hover:bg-green-200 text-green-700 py-3 rounded-xl font-bold transition">
                            UANG PAS
                        </button>
                        <button type="button" @click="addUang(50000)"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold transition">
                            +50rb
                        </button>
                        <button type="button" @click="addUang(100000)"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 py-3 rounded-xl font-semibold transition">
                            +100rb
                        </button>
                    </div>

                    <div class="bg-yellow-50 rounded-xl p-4 border border-yellow-200" x-show="kembalian() > 0">
                        <label class="block text-gray-600 font-semibold mb-1">KEMBALIAN</label>
                        <div class="font-mono text-3xl font-bold text-yellow-700" x-text="formatRp(kembalian())"></div>
                    </div>
                    <div class="bg-red-50 rounded-xl p-4 border border-red-200" x-show="kembalian() < 0">
                        <label class="block text-gray-600 font-semibold mb-1">KURANG BAYAR</label>
                        <div class="font-mono text-3xl font-bold text-red-700" x-text="formatRp(Math.abs(kembalian()))"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('transaksi.index') }}"
                class="px-8 py-4 rounded-xl border-2 border-gray-300 text-gray-600 hover:bg-gray-100 text-lg font-semibold transition">
                Batal
            </a>
            <button type="submit"
                class="px-10 py-4 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-xl flex items-center gap-3 shadow-lg transition">
                <i class="fa-solid fa-floppy-disk text-2xl"></i> SIMPAN TRANSAKSI
            </button>
        </div>

    </form>
</div>

<script>
function kasirApp(komisiDefault, suppliers, products) {
    return {
        komisiPersen: komisiDefault,
        statusBayar: 'lunas',
        uangDiterima: 0,
        _id: 100,
        items: [],
        biaya: [],
        supplierList: suppliers || [],
        productList: products || [],

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

        // ---- Auto-fill product price ----
        onProductChange(iIdx, productName) {
            const product = this.productList.find(p => p.nama_produk === productName);
            if (product && product.harga_jual) {
                this.items[iIdx].harga_per_kg = product.harga_jual;
                this.calcItem(iIdx);
            }
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

        // ---- Pembayaran ----
        kembalian() {
            const uang = parseFloat(this.uangDiterima) || 0;
            return uang - this.totalKotor();
        },
        setUangPas() {
            this.uangDiterima = this.totalKotor();
        },
        addUang(amount) {
            this.uangDiterima = (parseFloat(this.uangDiterima) || 0) + amount;
        },

        formatRp(val) {
            return 'Rp ' + Math.round(val).toLocaleString('id-ID');
        },

        // Submit
        submitForm(e) {
            e.target.submit();
        }
    }
}
</script>
@endsection
