<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Skenario Barang Datang:
 *
 * [A] 2026-02-05 — Pak Budi (Anggur A, B, C) → CONFIRMED, semua HABIS → bisa direkap
 * [B] 2026-02-06 — Bu Sari (Jeruk A, Buah Naga) → CONFIRMED, ada sisa stok
 * [C] 2026-02-06 — Pak Budi kirim ke-2 (Anggur A) → CONFIRMED, sebagian habis
 * [D] 2026-02-07 — Pak Hendra (Apel, Mangga) → DRAFT (belum dikonfirmasi)
 *
 * Setiap detail_barang_datang adalah produk unik (letter) dengan kode_produk sendiri.
 * Tidak ada lagi tabel produk — detail_barang_datang adalah sumber data produk.
 */
class BarangDatangSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $kasirId = DB::table('users')->where('role', 'kasir')->orderBy('id')->value('id');

        // ── [A] 2026-02-05 Pak Budi — kiriman ke-1 ───────────────────────────
        $bdA = DB::table('barang_datang')->insertGetId([
            'kode_bd'           => 'BD-20260205-0001',
            'supplier_id'       => 1, // Pak Budi
            'tanggal'           => '2026-02-05',
            'urutan_hari'       => 1,
            'catatan'           => 'Kiriman pertama bulan Februari',
            'status'            => 'confirmed',
            'dikonfirmasi_at'   => '2026-02-05 07:30:00',
            'dikonfirmasi_oleh' => $adminId,
            'created_at'        => '2026-02-05 07:00:00',
            'updated_at'        => '2026-02-05 07:30:00',
        ]);

        // Detail A: Anggur A — 33 peti, semua HABIS (terjual 33)
        DB::table('detail_barang_datang')->insert([
            'barang_datang_id' => $bdA,
            'kode_produk'      => 'PRD-0001',
            'nama_produk'      => 'Anggur',
            'ukuran'           => 'A',
            'kategori_id'      => 1,
            'satuan'           => 'peti',
            'harga_beli'       => 12000,
            'harga_jual'       => 14000,
            'jumlah'           => 33,
            'stok_awal'        => 33,
            'stok_terjual'     => 33,
            'stok_sisa'        => 0,
            'status_stok'      => 'habis',
            'aktif'            => true,
            'keterangan'       => null,
            'created_at'       => '2026-02-05 07:30:00',
            'updated_at'       => '2026-02-05 14:00:00',
        ]);

        // Detail A: Anggur B — 4 peti, semua HABIS
        DB::table('detail_barang_datang')->insert([
            'barang_datang_id' => $bdA,
            'kode_produk'      => 'PRD-0002',
            'nama_produk'      => 'Anggur',
            'ukuran'           => 'B',
            'kategori_id'      => 1,
            'satuan'           => 'peti',
            'harga_beli'       => 11000,
            'harga_jual'       => 12000,
            'jumlah'           => 4,
            'stok_awal'        => 4,
            'stok_terjual'     => 4,
            'stok_sisa'        => 0,
            'status_stok'      => 'habis',
            'aktif'            => true,
            'keterangan'       => null,
            'created_at'       => '2026-02-05 07:30:00',
            'updated_at'       => '2026-02-05 14:00:00',
        ]);

        // Detail A: Anggur C — 3 peti, semua HABIS
        DB::table('detail_barang_datang')->insert([
            'barang_datang_id' => $bdA,
            'kode_produk'      => 'PRD-0003',
            'nama_produk'      => 'Anggur',
            'ukuran'           => 'C',
            'kategori_id'      => 1,
            'satuan'           => 'peti',
            'harga_beli'       => 8500,
            'harga_jual'       => 9500,
            'jumlah'           => 3,
            'stok_awal'        => 3,
            'stok_terjual'     => 3,
            'stok_sisa'        => 0,
            'status_stok'      => 'habis',
            'aktif'            => true,
            'keterangan'       => null,
            'created_at'       => '2026-02-05 07:30:00',
            'updated_at'       => '2026-02-05 14:00:00',
        ]);

        // ── [B] 2026-02-06 Bu Sari — kiriman ke-1 ────────────────────────────
        $bdB = DB::table('barang_datang')->insertGetId([
            'kode_bd'           => 'BD-20260206-0001',
            'supplier_id'       => 2, // Bu Sari
            'tanggal'           => '2026-02-06',
            'urutan_hari'       => 1,
            'catatan'           => null,
            'status'            => 'confirmed',
            'dikonfirmasi_at'   => '2026-02-06 08:00:00',
            'dikonfirmasi_oleh' => $kasirId,
            'created_at'        => '2026-02-06 07:45:00',
            'updated_at'        => '2026-02-06 08:00:00',
        ]);

        // Detail B: Jeruk A — 115 peti, masih ada sisa 20
        DB::table('detail_barang_datang')->insert([
            'barang_datang_id' => $bdB,
            'kode_produk'      => 'PRD-0004',
            'nama_produk'      => 'Jeruk',
            'ukuran'           => 'A',
            'kategori_id'      => 2,
            'satuan'           => 'peti',
            'harga_beli'       => 19000,
            'harga_jual'       => 21000,
            'jumlah'           => 115,
            'stok_awal'        => 115,
            'stok_terjual'     => 95,
            'stok_sisa'        => 20,
            'status_stok'      => 'available',
            'aktif'            => true,
            'keterangan'       => 'Jeruk siam kualitas premium',
            'created_at'       => '2026-02-06 08:00:00',
            'updated_at'       => '2026-02-06 15:00:00',
        ]);

        // Detail B: Buah Naga — 137 peti, masih ada sisa 16
        DB::table('detail_barang_datang')->insert([
            'barang_datang_id' => $bdB,
            'kode_produk'      => 'PRD-0005',
            'nama_produk'      => 'Buah Naga',
            'ukuran'           => null,
            'kategori_id'      => 3,
            'satuan'           => 'peti',
            'harga_beli'       => 7500,
            'harga_jual'       => 8750,
            'jumlah'           => 137,
            'stok_awal'        => 137,
            'stok_terjual'     => 121,
            'stok_sisa'        => 16,
            'status_stok'      => 'available',
            'aktif'            => true,
            'keterangan'       => null,
            'created_at'       => '2026-02-06 08:00:00',
            'updated_at'       => '2026-02-06 15:00:00',
        ]);

        // ── [C] 2026-02-06 Pak Budi — kiriman ke-2 ───────────────────────────
        $bdC = DB::table('barang_datang')->insertGetId([
            'kode_bd'           => 'BD-20260206-0002',
            'supplier_id'       => 1, // Pak Budi
            'tanggal'           => '2026-02-06',
            'urutan_hari'       => 2,
            'catatan'           => 'Kiriman susulan',
            'status'            => 'confirmed',
            'dikonfirmasi_at'   => '2026-02-06 10:00:00',
            'dikonfirmasi_oleh' => $adminId,
            'created_at'        => '2026-02-06 09:45:00',
            'updated_at'        => '2026-02-06 10:00:00',
        ]);

        // Detail C: Anggur A (kiriman susulan) — 70 peti, masih ada 10 sisa
        // kode_produk baru karena ini letter baru dari kiriman berbeda
        DB::table('detail_barang_datang')->insert([
            'barang_datang_id' => $bdC,
            'kode_produk'      => 'PRD-0006',
            'nama_produk'      => 'Anggur',
            'ukuran'           => 'A',
            'kategori_id'      => 1,
            'satuan'           => 'peti',
            'harga_beli'       => 12000,
            'harga_jual'       => 13500,
            'jumlah'           => 70,
            'stok_awal'        => 70,
            'stok_terjual'     => 60,
            'stok_sisa'        => 10,
            'status_stok'      => 'available',
            'aktif'            => true,
            'keterangan'       => null,
            'created_at'       => '2026-02-06 10:00:00',
            'updated_at'       => '2026-02-06 15:00:00',
        ]);

        // ── [D] 2026-02-07 Pak Hendra — masih DRAFT ──────────────────────────
        $bdD = DB::table('barang_datang')->insertGetId([
            'kode_bd'           => 'BD-20260207-0001',
            'supplier_id'       => 3, // Pak Hendra
            'tanggal'           => '2026-02-07',
            'urutan_hari'       => 1,
            'catatan'           => 'Perlu dicek dulu kualitasnya',
            'status'            => 'draft',
            'dikonfirmasi_at'   => null,
            'dikonfirmasi_oleh' => null,
            'created_at'        => '2026-02-07 08:30:00',
            'updated_at'        => '2026-02-07 08:30:00',
        ]);

        // Detail D: Apel A — draft, kode_produk belum ada (diisi saat confirm)
        DB::table('detail_barang_datang')->insert([
            'barang_datang_id' => $bdD,
            'kode_produk'      => null,
            'nama_produk'      => 'Apel',
            'ukuran'           => 'A',
            'kategori_id'      => 4,
            'satuan'           => 'peti',
            'harga_beli'       => 22000,
            'harga_jual'       => 25000,
            'jumlah'           => 20,
            'stok_awal'        => 0,
            'stok_terjual'     => 0,
            'stok_sisa'        => 0,
            'status_stok'      => 'available',
            'aktif'            => false,
            'keterangan'       => null,
            'created_at'       => '2026-02-07 08:30:00',
            'updated_at'       => '2026-02-07 08:30:00',
        ]);

        // Detail D: Mangga — draft
        DB::table('detail_barang_datang')->insert([
            'barang_datang_id' => $bdD,
            'kode_produk'      => null,
            'nama_produk'      => 'Mangga',
            'ukuran'           => null,
            'kategori_id'      => 5,
            'satuan'           => 'peti',
            'harga_beli'       => 9000,
            'harga_jual'       => 11000,
            'jumlah'           => 15,
            'stok_awal'        => 0,
            'stok_terjual'     => 0,
            'stok_sisa'        => 0,
            'status_stok'      => 'available',
            'aktif'            => false,
            'keterangan'       => null,
            'created_at'       => '2026-02-07 08:30:00',
            'updated_at'       => '2026-02-07 08:30:00',
        ]);
    }
}
