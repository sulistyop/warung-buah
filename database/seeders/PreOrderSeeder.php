<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PreOrderSeeder extends Seeder
{
    public function run(): void
    {
        $kasirId = DB::table('users')->where('role', 'kasir')->orderBy('id')->value('id');
        $adminId = DB::table('users')->where('role', 'admin')->value('id');

        $plgFaiz  = DB::table('pelanggan')->where('nama', 'like', '%Faiz%')->value('id');
        $plgAhmad = DB::table('pelanggan')->where('nama', 'like', '%Ahmad%')->value('id');
        $plgRina  = DB::table('pelanggan')->where('nama', 'like', '%Rina%')->value('id');

        // Stok jeruk yang masih ada (20 peti sisa di BD-20260206-0001)
        $stokJerukId = DB::table('detail_barang_datang')
            ->where('nama_produk', 'Jeruk')
            ->where('status_stok', 'available')
            ->value('id');

        // Stok Anggur A yang masih ada (10 peti sisa di BD-20260206-0002)
        $stokAnggurId = DB::table('detail_barang_datang')
            ->where('nama_produk', 'Anggur')
            ->where('status_stok', 'available')
            ->value('id');

        // ── PO-1: Mas Faiz pesan Jeruk 10 peti — PENDING ─────────────────────
        $po1 = DB::table('pre_order')->insertGetId([
            'kode_po'        => 'PO-20260207-0001',
            'pelanggan_id'   => $plgFaiz,
            'nama_pelanggan' => 'M. Faiz Abdillah',
            'tanggal_po'     => '2026-02-07',
            'tanggal_kirim'  => '2026-02-08',
            'total'          => 2310000, // 110kg × 21.000
            'status'         => 'pending',
            'transaksi_id'   => null,
            'catatan'        => 'Tolong pisahkan yang kualitas bagus',
            'user_id'        => $kasirId,
            'created_at'     => '2026-02-07 10:00:00',
            'updated_at'     => '2026-02-07 10:00:00',
        ]);

        DB::table('detail_pre_order')->insert([
            'pre_order_id'            => $po1,
            'detail_barang_datang_id' => $stokJerukId,
            'nama_produk'             => 'Jeruk',
            'ukuran'                  => 'A',
            'jumlah_peti'             => 10,
            'harga_per_kg'            => 21000,
            'estimasi_berat_bersih'   => 110.0,
            'subtotal'                => 2310000,
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        // ── PO-2: Pak Ahmad pesan Anggur 5 peti + Jeruk 5 peti — DIPROSES ────
        $po2 = DB::table('pre_order')->insertGetId([
            'kode_po'        => 'PO-20260207-0002',
            'pelanggan_id'   => $plgAhmad,
            'nama_pelanggan' => 'Ahmad Fauzi',
            'tanggal_po'     => '2026-02-07',
            'tanggal_kirim'  => '2026-02-08',
            'total'          => 3445000,
            'status'         => 'diproses',
            'transaksi_id'   => null,
            'catatan'        => 'Untuk acara pernikahan',
            'user_id'        => $adminId,
            'created_at'     => '2026-02-07 11:00:00',
            'updated_at'     => '2026-02-07 12:00:00',
        ]);

        DB::table('detail_pre_order')->insert([
            [
                'pre_order_id'            => $po2,
                'detail_barang_datang_id' => $stokAnggurId,
                'nama_produk'             => 'Anggur',
                'ukuran'                  => 'A',
                'jumlah_peti'             => 5,
                'harga_per_kg'            => 13500,
                'estimasi_berat_bersih'   => 70.0,
                'subtotal'                => 945000,
                'created_at'              => now(),
                'updated_at'              => now(),
            ],
            [
                'pre_order_id'            => $po2,
                'detail_barang_datang_id' => $stokJerukId,
                'nama_produk'             => 'Jeruk',
                'ukuran'                  => 'A',
                'jumlah_peti'             => 5,
                'harga_per_kg'            => 21000,
                'estimasi_berat_bersih'   => 55.0,
                'subtotal'                => 1155000,
                'created_at'              => now(),
                'updated_at'              => now(),
            ],
        ]);

        // ── PO-3: Rina (free text, tidak ada di pelanggan) — sudah SELESAI ────
        $trxTerkait = DB::table('transaksi')
            ->where('kode_transaksi', 'TRX-20260206-0001')
            ->value('id');

        $po3 = DB::table('pre_order')->insertGetId([
            'kode_po'        => 'PO-20260205-0001',
            'pelanggan_id'   => $plgRina,
            'nama_pelanggan' => 'Rina Susanti',
            'tanggal_po'     => '2026-02-05',
            'tanggal_kirim'  => '2026-02-05',
            'total'          => 14510500,
            'status'         => 'selesai',
            'transaksi_id'   => $trxTerkait, // sudah dikonversi jadi transaksi
            'catatan'        => 'PO dikonversi jadi transaksi TRX-20260205-0001',
            'user_id'        => $kasirId,
            'created_at'     => '2026-02-04 15:00:00',
            'updated_at'     => '2026-02-05 10:30:00',
        ]);

        DB::table('detail_pre_order')->insert([
            [
                'pre_order_id'            => $po3,
                'detail_barang_datang_id' => null,
                'nama_produk'             => 'Anggur',
                'ukuran'                  => 'A',
                'jumlah_peti'             => 33,
                'harga_per_kg'            => 13500,
                'estimasi_berat_bersih'   => 913.0,
                'subtotal'                => 12325500,
                'created_at'              => now(),
                'updated_at'              => now(),
            ],
            [
                'pre_order_id'            => $po3,
                'detail_barang_datang_id' => null,
                'nama_produk'             => 'Anggur',
                'ukuran'                  => 'B',
                'jumlah_peti'             => 4,
                'harga_per_kg'            => 12000,
                'estimasi_berat_bersih'   => 104.0,
                'subtotal'                => 1248000,
                'created_at'              => now(),
                'updated_at'              => now(),
            ],
        ]);
    }
}
