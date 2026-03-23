<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        // supplier_id: 1=Pak Budi, 2=Bu Sari, 3=Pak Hendra
        // kategori_id: 1=Anggur, 2=Jeruk, 3=Buah Naga, 4=Apel, 5=Mangga, 6=Semangka

        $produk = [
            // ── Anggur dari Pak Budi ──────────────────────────────────
            [
                'kode_produk'  => 'PRD-0001',
                'nama_produk'  => 'Anggur',
                'supplier_id'  => 1,
                'kategori_id'  => 1,
                'kategori'     => 'Anggur',
                'ukuran'       => 'A',
                'satuan'       => 'kg',
                'harga_beli'   => 12000,
                'harga_jual'   => 14000,
                'stok'         => 0,     // stok dikelola via barang_datang
                'stok_minimum' => 5,
                'keterangan'   => 'Anggur Grade A - besar, segar',
                'aktif'        => true,
            ],
            [
                'kode_produk'  => 'PRD-0002',
                'nama_produk'  => 'Anggur',
                'supplier_id'  => 1,
                'kategori_id'  => 1,
                'kategori'     => 'Anggur',
                'ukuran'       => 'B',
                'satuan'       => 'kg',
                'harga_beli'   => 11000,
                'harga_jual'   => 12000,
                'stok'         => 0,
                'stok_minimum' => 5,
                'keterangan'   => 'Anggur Grade B - ukuran sedang',
                'aktif'        => true,
            ],
            [
                'kode_produk'  => 'PRD-0003',
                'nama_produk'  => 'Anggur',
                'supplier_id'  => 1,
                'kategori_id'  => 1,
                'kategori'     => 'Anggur',
                'ukuran'       => 'C',
                'satuan'       => 'kg',
                'harga_beli'   => 8500,
                'harga_jual'   => 9500,
                'stok'         => 0,
                'stok_minimum' => 3,
                'keterangan'   => 'Anggur Grade C - kecil',
                'aktif'        => true,
            ],
            // ── Jeruk dari Bu Sari ────────────────────────────────────
            [
                'kode_produk'  => 'PRD-0004',
                'nama_produk'  => 'Jeruk Siam',
                'supplier_id'  => 2,
                'kategori_id'  => 2,
                'kategori'     => 'Jeruk',
                'ukuran'       => 'A',
                'satuan'       => 'kg',
                'harga_beli'   => 19000,
                'harga_jual'   => 21000,
                'stok'         => 0,
                'stok_minimum' => 10,
                'keterangan'   => 'Jeruk siam Grade A',
                'aktif'        => true,
            ],
            [
                'kode_produk'  => 'PRD-0005',
                'nama_produk'  => 'Jeruk Siam',
                'supplier_id'  => 2,
                'kategori_id'  => 2,
                'kategori'     => 'Jeruk',
                'ukuran'       => 'B',
                'satuan'       => 'kg',
                'harga_beli'   => 16000,
                'harga_jual'   => 18000,
                'stok'         => 0,
                'stok_minimum' => 10,
                'keterangan'   => 'Jeruk siam Grade B',
                'aktif'        => true,
            ],
            // ── Buah Naga dari Bu Sari ────────────────────────────────
            [
                'kode_produk'  => 'PRD-0006',
                'nama_produk'  => 'Buah Naga Merah',
                'supplier_id'  => 2,
                'kategori_id'  => 3,
                'kategori'     => 'Buah Naga',
                'ukuran'       => null,
                'satuan'       => 'kg',
                'harga_beli'   => 7500,
                'harga_jual'   => 8750,
                'stok'         => 0,
                'stok_minimum' => 10,
                'keterangan'   => 'Buah naga merah lokal',
                'aktif'        => true,
            ],
            // ── Campuran dari Pak Hendra ──────────────────────────────
            [
                'kode_produk'  => 'PRD-0007',
                'nama_produk'  => 'Apel Fuji',
                'supplier_id'  => 3,
                'kategori_id'  => 4,
                'kategori'     => 'Apel',
                'ukuran'       => 'A',
                'satuan'       => 'kg',
                'harga_beli'   => 22000,
                'harga_jual'   => 25000,
                'stok'         => 0,
                'stok_minimum' => 5,
                'keterangan'   => 'Apel fuji impor',
                'aktif'        => true,
            ],
            [
                'kode_produk'  => 'PRD-0008',
                'nama_produk'  => 'Mangga Harum Manis',
                'supplier_id'  => 3,
                'kategori_id'  => 5,
                'kategori'     => 'Mangga',
                'ukuran'       => null,
                'satuan'       => 'kg',
                'harga_beli'   => 9000,
                'harga_jual'   => 11000,
                'stok'         => 0,
                'stok_minimum' => 10,
                'keterangan'   => 'Mangga harum manis lokal',
                'aktif'        => true,
            ],
        ];

        foreach ($produk as $p) {
            DB::table('produk')->insert(array_merge($p, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
