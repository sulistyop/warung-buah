<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Skenario Transaksi:
 *
 * TRX-1  Mas Sidiq   — 14.510.500  tempo  (belum bayar sama sekali)
 * TRX-2  Mas Faiz    —  7.015.750  lunas  (bayar tunai)
 * TRX-3  Bu Dewi     —  3.213.000  cicil  (baru bayar sebagian)
 * TRX-4  Pak Ahmad   —  5.400.000  lunas  (bayar transfer)
 * TRX-5  Mas Sidiq   —  3.000.000  tempo  (belum bayar)
 * TRX-6  Mas Sidiq   —  4.100.000  tempo  (belum bayar)
 *
 * Skenario piutang Mas Sidiq: 14.5jt + 3jt + 4.1jt = 21.6jt total piutang
 */
class TransaksiSeeder extends Seeder
{
    public function run(): void
    {
        $adminId  = DB::table('users')->where('role', 'admin')->value('id');
        $kasirId  = DB::table('users')->where('role', 'kasir')->orderBy('id')->value('id');

        // Pelanggan IDs
        $plgSidiq = DB::table('pelanggan')->where('nama', 'like', '%Sidiq%')->value('id');
        $plgFaiz  = DB::table('pelanggan')->where('nama', 'like', '%Faiz%')->value('id');
        $plgDewi  = DB::table('pelanggan')->where('nama', 'like', '%Dewi%')->value('id');
        $plgAhmad = DB::table('pelanggan')->where('nama', 'like', '%Ahmad%')->value('id');

        // ── Lookup detail_barang_datang IDs ──────────────────────────────────
        // Helper closure: cari detail_id berdasarkan kode_bd + nama_produk + ukuran
        $detailId = fn(string $kode, string $nama, ?string $ukuran) =>
            DB::table('detail_barang_datang as dbd')
                ->join('barang_datang as bd', 'bd.id', '=', 'dbd.barang_datang_id')
                ->where('bd.kode_bd', $kode)
                ->where('dbd.nama_produk', $nama)
                ->where(fn($q) => $ukuran === null ? $q->whereNull('dbd.ukuran') : $q->where('dbd.ukuran', $ukuran))
                ->value('dbd.id');

        // [A] BD-20260205-0001 — Pak Budi kiriman ke-1
        $ddAnggurA_bdA = $detailId('BD-20260205-0001', 'Anggur', 'A');   // → item1a, FIFO 1
        $ddAnggurB_bdA = $detailId('BD-20260205-0001', 'Anggur', 'B');   // → item1b
        $ddAnggurC_bdA = $detailId('BD-20260205-0001', 'Anggur', 'C');   // → item1c

        // [B] BD-20260206-0001 — Bu Sari kiriman ke-1
        $ddJerukA_bdB    = $detailId('BD-20260206-0001', 'Jeruk', 'A');      // → item2b, item3, item6
        $ddBuahNaga_bdB  = $detailId('BD-20260206-0001', 'Buah Naga', null); // → item2c, item4

        // [C] BD-20260206-0002 — Pak Budi kiriman ke-2 (susulan)
        $ddAnggurA_bdC = $detailId('BD-20260206-0002', 'Anggur', 'A');   // → item5 (FIFO: bdA sudah habis)

        // ════════════════════════════════════════════════════════════════════
        // TRX-1: Mas Sidiq — Anggur A+B+C dari Pak Budi — 14.510.500 TEMPO
        // Sesuai nota fisik di foto
        // ════════════════════════════════════════════════════════════════════
        $trx1 = DB::table('transaksi')->insertGetId([
            'kode_transaksi'          => 'TRX-20260205-0001',
            'pelanggan_id'            => $plgSidiq,
            'nama_pelanggan'          => 'Mas Sidiq',
            'status_bayar'            => 'tempo',
            'tanggal_jatuh_tempo'     => '2026-02-12',
            'catatan'                 => 'Anggur dari Pak Budi tgl 05/02',
            'komisi_persen'           => 7.00,
            'total_kotor'             => 14510500,
            'total_komisi'            => 1015735,
            'total_biaya_operasional' => 920000,
            'total_bersih'            => 12574765,
            'total_tagihan'           => 14510500,
            'total_dibayar'           => 0,
            'sisa_tagihan'            => 14510500,
            'uang_diterima'           => 0,
            'kembalian'               => 0,
            'status'                  => 'selesai',
            'user_id'                 => $kasirId,
            'created_at'              => '2026-02-05 10:00:00',
            'updated_at'              => '2026-02-05 10:30:00',
        ]);

        // Item 1: Anggur A — 33 peti
        $item1a = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx1,
            'supplier_id'             => 1,
            'detail_barang_datang_id' => $ddAnggurA_bdA,
            'nama_supplier'           => 'Pak Budi Santoso',
            'jenis_buah'              => 'Anggur A',
            'harga_per_kg'            => 13500,
            'jumlah_peti'             => 33,
            'total_berat_bersih'      => 913.0,
            'subtotal'                => 12325500,
            'created_at'              => '2026-02-05 10:00:00',
            'updated_at'              => '2026-02-05 10:00:00',
        ]);

        // Detail peti — Anggur A (sesuai data nota: berat kotor berbeda-beda)
        $petiAnggurA = [
            [1, 32, 4],  // no_peti, berat_kotor, berat_kemasan
            [2, 331, 50],
            [3, 32, 4],
            [4, 67, 8],
            [5, 585, 72],
            [6, 30, 4],
        ];
        foreach ($petiAnggurA as $p) {
            DB::table('detail_peti')->insert([
                'item_transaksi_id' => $item1a,
                'no_peti'           => $p[0],
                'berat_kotor'       => $p[1],
                'berat_kemasan'     => $p[2],
                'created_at'        => '2026-02-05 10:00:00',
                'updated_at'        => '2026-02-05 10:00:00',
            ]);
        }

        // Item 2: Anggur B — 4 peti
        $item1b = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx1,
            'supplier_id'             => 1,
            'detail_barang_datang_id' => $ddAnggurB_bdA,
            'nama_supplier'           => 'Pak Budi Santoso',
            'jenis_buah'              => 'Anggur B',
            'harga_per_kg'            => 12000,
            'jumlah_peti'             => 4,
            'total_berat_bersih'      => 104.0,
            'subtotal'                => 1248000,
            'created_at'              => '2026-02-05 10:00:00',
            'updated_at'              => '2026-02-05 10:00:00',
        ]);

        $petiAnggurB = [
            [1, 31, 4],
            [2, 89, 12],
        ];
        foreach ($petiAnggurB as $p) {
            DB::table('detail_peti')->insert([
                'item_transaksi_id' => $item1b,
                'no_peti'           => $p[0],
                'berat_kotor'       => $p[1],
                'berat_kemasan'     => $p[2],
                'created_at'        => '2026-02-05 10:00:00',
                'updated_at'        => '2026-02-05 10:00:00',
            ]);
        }

        // Item 3: Anggur C — 3 peti
        $item1c = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx1,
            'supplier_id'             => 1,
            'detail_barang_datang_id' => $ddAnggurC_bdA,
            'nama_supplier'           => 'Pak Budi Santoso',
            'jenis_buah'              => 'Anggur C',
            'harga_per_kg'            => 9500,
            'jumlah_peti'             => 3,
            'total_berat_bersih'      => 90.0,
            'subtotal'                => 855000,
            'created_at'              => '2026-02-05 10:00:00',
            'updated_at'              => '2026-02-05 10:00:00',
        ]);

        DB::table('detail_peti')->insert([
            'item_transaksi_id' => $item1c,
            'no_peti'           => 1,
            'berat_kotor'       => 102,
            'berat_kemasan'     => 12,
            'created_at'        => '2026-02-05 10:00:00',
            'updated_at'        => '2026-02-05 10:00:00',
        ]);

        // Biaya Operasional TRX-1
        DB::table('biaya_operasional')->insert([
            ['transaksi_id' => $trx1, 'nama_biaya' => 'Ongkos Angkut', 'nominal' => 760000, 'created_at' => now(), 'updated_at' => now()],
            ['transaksi_id' => $trx1, 'nama_biaya' => 'Kuli', 'nominal' => 160000, 'created_at' => now(), 'updated_at' => now()],
        ]);


        // ════════════════════════════════════════════════════════════════════
        // TRX-2: Mas Faiz — Anggur D + Jeruk (Naga) — 7.015.750 LUNAS
        // Sesuai nota fisik ke-3 di foto
        // ════════════════════════════════════════════════════════════════════
        $trx2 = DB::table('transaksi')->insertGetId([
            'kode_transaksi'          => 'TRX-20260206-0001',
            'pelanggan_id'            => $plgFaiz,
            'nama_pelanggan'          => 'M. Faiz',
            'status_bayar'            => 'lunas',
            'tanggal_jatuh_tempo'     => null,
            'catatan'                 => 'Bayar tunai lunas',
            'komisi_persen'           => 0,
            'total_kotor'             => 6975750,
            'total_komisi'            => 0,
            'total_biaya_operasional' => 40000,
            'total_bersih'            => 6935750,
            'total_tagihan'           => 7015750,
            'total_dibayar'           => 7015750,
            'sisa_tagihan'            => 0,
            'uang_diterima'           => 7015750,
            'kembalian'               => 0,
            'status'                  => 'selesai',
            'user_id'                 => $kasirId,
            'created_at'              => '2026-02-06 09:00:00',
            'updated_at'              => '2026-02-06 09:30:00',
        ]);

        // Item: Anggur D — 96 peti (dari Pak Budi kiriman ke-2)
        // detail_barang_datang_id = null karena "Anggur D" tidak ada di data barang datang seed
        $item2a = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx2,
            'supplier_id'             => 1,
            'detail_barang_datang_id' => null,
            'nama_supplier'           => 'Pak Budi Santoso',
            'jenis_buah'              => 'Anggur D',
            'harga_per_kg'            => 13000,
            'jumlah_peti'             => 21,
            'total_berat_bersih'      => 208.0,
            'subtotal'                => 2704000,
            'created_at'              => '2026-02-06 09:00:00',
            'updated_at'              => '2026-02-06 09:00:00',
        ]);

        $petiAnggurD = [[1,24,24],[2,24,24],[3,25,23],[4,23,25],[5,5,5]];
        foreach ($petiAnggurD as $i => $p) {
            DB::table('detail_peti')->insert([
                'item_transaksi_id' => $item2a,
                'no_peti'           => $i + 1,
                'berat_kotor'       => $p[0],
                'berat_kemasan'     => $p[1],
                'created_at'        => '2026-02-06 09:00:00',
                'updated_at'        => '2026-02-06 09:00:00',
            ]);
        }

        // Item: Jeruk (K/Jeruk) — 115 peti dari Bu Sari
        $item2b = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx2,
            'supplier_id'             => 2,
            'detail_barang_datang_id' => $ddJerukA_bdB,
            'nama_supplier'           => 'Bu Sari Wulandari',
            'jenis_buah'              => 'Jeruk',
            'harga_per_kg'            => 21000,
            'jumlah_peti'             => 45,
            'total_berat_bersih'      => 153.0,
            'subtotal'                => 3213000,
            'created_at'              => '2026-02-06 09:00:00',
            'updated_at'              => '2026-02-06 09:00:00',
        ]);

        $petiJeruk = [[1,23,23],[2,24,22],[3,2,2],[4,22,22],[5,24,24]];
        foreach ($petiJeruk as $i => $p) {
            DB::table('detail_peti')->insert([
                'item_transaksi_id' => $item2b,
                'no_peti'           => $i + 1,
                'berat_kotor'       => $p[0],
                'berat_kemasan'     => $p[1],
                'created_at'        => '2026-02-06 09:00:00',
                'updated_at'        => '2026-02-06 09:00:00',
            ]);
        }

        // Item: Buah Naga dari Bu Sari
        $item2c = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx2,
            'supplier_id'             => 2,
            'detail_barang_datang_id' => $ddBuahNaga_bdB,
            'nama_supplier'           => 'Bu Sari Wulandari',
            'jenis_buah'              => 'Buah Naga',
            'harga_per_kg'            => 8750,
            'jumlah_peti'             => 34,
            'total_berat_bersih'      => 121.0,
            'subtotal'                => 1058750,
            'created_at'              => '2026-02-06 09:00:00',
            'updated_at'              => '2026-02-06 09:00:00',
        ]);

        DB::table('detail_peti')->insert([
            ['item_transaksi_id' => $item2c, 'no_peti' => 1, 'berat_kotor' => 34, 'berat_kemasan' => 36, 'created_at' => now(), 'updated_at' => now()],
            ['item_transaksi_id' => $item2c, 'no_peti' => 2, 'berat_kotor' => 35, 'berat_kemasan' => 32, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Biaya TRX-2
        DB::table('biaya_operasional')->insert([
            'transaksi_id' => $trx2, 'nama_biaya' => 'Ongkos', 'nominal' => 40000, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Pembayaran TRX-2 (LUNAS)
        DB::table('pembayaran')->insert([
            'transaksi_id'    => $trx2,
            'kode_pembayaran' => 'PAY-20260206-0001',
            'nominal'         => 7015750,
            'metode'          => 'tunai',
            'referensi'       => null,
            'catatan'         => 'Bayar lunas tunai',
            'sisa_tagihan'    => 0,
            'user_id'         => $kasirId,
            'created_at'      => '2026-02-06 09:30:00',
            'updated_at'      => '2026-02-06 09:30:00',
        ]);


        // ════════════════════════════════════════════════════════════════════
        // TRX-3: Bu Dewi — Jeruk — 3.213.000 CICIL (baru bayar 1.5jt)
        // ════════════════════════════════════════════════════════════════════
        $trx3 = DB::table('transaksi')->insertGetId([
            'kode_transaksi'          => 'TRX-20260206-0002',
            'pelanggan_id'            => $plgDewi,
            'nama_pelanggan'          => 'Bu Dewi',
            'status_bayar'            => 'cicil',
            'tanggal_jatuh_tempo'     => '2026-02-20',
            'catatan'                 => 'Cicil 2x, sisa dibayar tgl 20',
            'komisi_persen'           => 0,
            'total_kotor'             => 3213000,
            'total_komisi'            => 0,
            'total_biaya_operasional' => 0,
            'total_bersih'            => 3213000,
            'total_tagihan'           => 3213000,
            'total_dibayar'           => 1500000,
            'sisa_tagihan'            => 1713000,
            'uang_diterima'           => 1500000,
            'kembalian'               => 0,
            'status'                  => 'selesai',
            'user_id'                 => $kasirId,
            'created_at'              => '2026-02-06 11:00:00',
            'updated_at'              => '2026-02-06 13:00:00',
        ]);

        $item3 = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx3,
            'supplier_id'             => 2,
            'detail_barang_datang_id' => $ddJerukA_bdB,
            'nama_supplier'           => 'Bu Sari Wulandari',
            'jenis_buah'              => 'Jeruk',
            'harga_per_kg'            => 21000,
            'jumlah_peti'             => 25,
            'total_berat_bersih'      => 153.0,
            'subtotal'                => 3213000,
            'created_at'              => '2026-02-06 11:00:00',
            'updated_at'              => '2026-02-06 11:00:00',
        ]);

        foreach (range(1, 5) as $i) {
            DB::table('detail_peti')->insert([
                'item_transaksi_id' => $item3,
                'no_peti'           => $i,
                'berat_kotor'       => 32 + $i,
                'berat_kemasan'     => 7,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // Pembayaran cicilan pertama
        DB::table('pembayaran')->insert([
            'transaksi_id'    => $trx3,
            'kode_pembayaran' => 'PAY-20260206-0002',
            'nominal'         => 1500000,
            'metode'          => 'transfer',
            'referensi'       => 'BCA-TRF-0206',
            'catatan'         => 'Cicilan pertama',
            'sisa_tagihan'    => 1713000,
            'user_id'         => $kasirId,
            'created_at'      => '2026-02-06 13:00:00',
            'updated_at'      => '2026-02-06 13:00:00',
        ]);


        // ════════════════════════════════════════════════════════════════════
        // TRX-4: Pak Ahmad — Buah Naga — 5.400.000 LUNAS transfer
        // ════════════════════════════════════════════════════════════════════
        $trx4 = DB::table('transaksi')->insertGetId([
            'kode_transaksi'          => 'TRX-20260206-0003',
            'pelanggan_id'            => $plgAhmad,
            'nama_pelanggan'          => 'Pak Ahmad',
            'status_bayar'            => 'lunas',
            'tanggal_jatuh_tempo'     => null,
            'catatan'                 => null,
            'komisi_persen'           => 0,
            'total_kotor'             => 5400000,
            'total_komisi'            => 0,
            'total_biaya_operasional' => 0,
            'total_bersih'            => 5400000,
            'total_tagihan'           => 5400000,
            'total_dibayar'           => 5400000,
            'sisa_tagihan'            => 0,
            'uang_diterima'           => 5400000,
            'kembalian'               => 0,
            'status'                  => 'selesai',
            'user_id'                 => $adminId,
            'created_at'              => '2026-02-06 14:00:00',
            'updated_at'              => '2026-02-06 14:30:00',
        ]);

        $item4 = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx4,
            'supplier_id'             => 2,
            'detail_barang_datang_id' => $ddBuahNaga_bdB,
            'nama_supplier'           => 'Bu Sari Wulandari',
            'jenis_buah'              => 'Buah Naga',
            'harga_per_kg'            => 8750,
            'jumlah_peti'             => 35,
            'total_berat_bersih'      => 617.1,
            'subtotal'                => 5400000,
            'created_at'              => '2026-02-06 14:00:00',
            'updated_at'              => '2026-02-06 14:00:00',
        ]);

        foreach (range(1, 5) as $i) {
            DB::table('detail_peti')->insert([
                'item_transaksi_id' => $item4,
                'no_peti'           => $i,
                'berat_kotor'       => 135 + $i,
                'berat_kemasan'     => 12,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        DB::table('pembayaran')->insert([
            'transaksi_id'    => $trx4,
            'kode_pembayaran' => 'PAY-20260206-0003',
            'nominal'         => 5400000,
            'metode'          => 'transfer',
            'referensi'       => 'BNI-TRF-20260206',
            'catatan'         => 'Transfer BNI',
            'sisa_tagihan'    => 0,
            'user_id'         => $adminId,
            'created_at'      => '2026-02-06 14:30:00',
            'updated_at'      => '2026-02-06 14:30:00',
        ]);


        // ════════════════════════════════════════════════════════════════════
        // TRX-5: Mas Sidiq — Anggur — 3.000.000 TEMPO (belum bayar)
        // ════════════════════════════════════════════════════════════════════
        $trx5 = DB::table('transaksi')->insertGetId([
            'kode_transaksi'          => 'TRX-20260206-0004',
            'pelanggan_id'            => $plgSidiq,
            'nama_pelanggan'          => 'Mas Sidiq',
            'status_bayar'            => 'tempo',
            'tanggal_jatuh_tempo'     => '2026-02-13',
            'catatan'                 => null,
            'komisi_persen'           => 0,
            'total_kotor'             => 3000000,
            'total_komisi'            => 0,
            'total_biaya_operasional' => 0,
            'total_bersih'            => 3000000,
            'total_tagihan'           => 3000000,
            'total_dibayar'           => 0,
            'sisa_tagihan'            => 3000000,
            'uang_diterima'           => 0,
            'kembalian'               => 0,
            'status'                  => 'selesai',
            'user_id'                 => $kasirId,
            'created_at'              => '2026-02-06 15:00:00',
            'updated_at'              => '2026-02-06 15:00:00',
        ]);

        $item5 = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx5,
            'supplier_id'             => 1,
            'detail_barang_datang_id' => $ddAnggurA_bdC, // FIFO: bdA (02-05) sudah habis, pakai bdC (02-06)
            'nama_supplier'           => 'Pak Budi Santoso',
            'jenis_buah'              => 'Anggur A',
            'harga_per_kg'            => 13500,
            'jumlah_peti'             => 18,
            'total_berat_bersih'      => 222.2,
            'subtotal'                => 3000000,
            'created_at'              => '2026-02-06 15:00:00',
            'updated_at'              => '2026-02-06 15:00:00',
        ]);

        foreach (range(1, 5) as $i) {
            DB::table('detail_peti')->insert([
                'item_transaksi_id' => $item5,
                'no_peti'           => $i,
                'berat_kotor'       => 48 + $i,
                'berat_kemasan'     => 4,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }


        // ════════════════════════════════════════════════════════════════════
        // TRX-6: Mas Sidiq — Jeruk — 4.100.000 TEMPO (belum bayar)
        // ════════════════════════════════════════════════════════════════════
        $trx6 = DB::table('transaksi')->insertGetId([
            'kode_transaksi'          => 'TRX-20260206-0005',
            'pelanggan_id'            => $plgSidiq,
            'nama_pelanggan'          => 'Mas Sidiq',
            'status_bayar'            => 'tempo',
            'tanggal_jatuh_tempo'     => '2026-02-13',
            'catatan'                 => null,
            'komisi_persen'           => 0,
            'total_kotor'             => 4100000,
            'total_komisi'            => 0,
            'total_biaya_operasional' => 0,
            'total_bersih'            => 4100000,
            'total_tagihan'           => 4100000,
            'total_dibayar'           => 0,
            'sisa_tagihan'            => 4100000,
            'uang_diterima'           => 0,
            'kembalian'               => 0,
            'status'                  => 'selesai',
            'user_id'                 => $kasirId,
            'created_at'              => '2026-02-06 16:00:00',
            'updated_at'              => '2026-02-06 16:00:00',
        ]);

        $item6 = DB::table('item_transaksi')->insertGetId([
            'transaksi_id'            => $trx6,
            'supplier_id'             => 2,
            'detail_barang_datang_id' => $ddJerukA_bdB,
            'nama_supplier'           => 'Bu Sari Wulandari',
            'jenis_buah'              => 'Jeruk',
            'harga_per_kg'            => 21000,
            'jumlah_peti'             => 25,
            'total_berat_bersih'      => 195.2,
            'subtotal'                => 4100000,
            'created_at'              => '2026-02-06 16:00:00',
            'updated_at'              => '2026-02-06 16:00:00',
        ]);

        foreach (range(1, 5) as $i) {
            DB::table('detail_peti')->insert([
                'item_transaksi_id' => $item6,
                'no_peti'           => $i,
                'berat_kotor'       => 42 + $i,
                'berat_kemasan'     => 4,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }
}
