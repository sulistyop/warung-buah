<?php

namespace App\Http\Controllers\Api\Schemas;

use OpenApi\Attributes as OA;

/**
 * Schema definitions for Swagger documentation
 */

#[OA\Schema(
    schema: 'Transaksi',
    title: 'Transaksi',
    description: 'Model Transaksi',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kode_transaksi', type: 'string', example: 'TRX-20260305-0001'),
        new OA\Property(property: 'nama_pelanggan', type: 'string', example: 'Toko Buah Segar'),
        new OA\Property(property: 'status_bayar', type: 'string', enum: ['lunas', 'tempo', 'cicil']),
        new OA\Property(property: 'tanggal_jatuh_tempo', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'catatan', type: 'string', nullable: true),
        new OA\Property(property: 'komisi_persen', type: 'number', example: 10),
        new OA\Property(property: 'total_kotor', type: 'number', example: 1000000),
        new OA\Property(property: 'total_komisi', type: 'number', example: 100000),
        new OA\Property(property: 'total_biaya_operasional', type: 'number', example: 50000),
        new OA\Property(property: 'total_bersih', type: 'number', example: 850000),
        new OA\Property(property: 'total_tagihan', type: 'number', example: 850000),
        new OA\Property(property: 'total_dibayar', type: 'number', example: 500000),
        new OA\Property(property: 'sisa_tagihan', type: 'number', example: 350000),
        new OA\Property(property: 'uang_diterima', type: 'number', example: 500000),
        new OA\Property(property: 'kembalian', type: 'number', example: 0),
        new OA\Property(property: 'status', type: 'string', example: 'selesai'),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'TransaksiDetail',
    title: 'Transaksi Detail',
    description: 'Model Transaksi dengan relasi',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Transaksi'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                new OA\Property(property: 'item_transaksi', type: 'array', items: new OA\Items(ref: '#/components/schemas/ItemTransaksi')),
                new OA\Property(property: 'biaya_operasional', type: 'array', items: new OA\Items(ref: '#/components/schemas/BiayaOperasional')),
                new OA\Property(property: 'pembayaran', type: 'array', items: new OA\Items(ref: '#/components/schemas/Pembayaran')),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'ItemTransaksi',
    title: 'Item Transaksi',
    description: 'Model Item Transaksi',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'transaksi_id', type: 'integer', example: 1),
        new OA\Property(property: 'supplier_id', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'detail_barang_datang_id', type: 'integer', nullable: true, example: 12, description: 'FK ke detail_barang_datang — diisi saat transaksi (FIFO)'),
        new OA\Property(property: 'nama_supplier', type: 'string', example: 'Pak Ahmad'),
        new OA\Property(property: 'jenis_buah', type: 'string', example: 'Mangga Harum Manis'),
        new OA\Property(property: 'harga_per_kg', type: 'number', example: 15000),
        new OA\Property(property: 'jumlah_peti', type: 'integer', example: 3),
        new OA\Property(property: 'total_berat_bersih', type: 'number', example: 90.5),
        new OA\Property(property: 'subtotal', type: 'number', example: 1357500),
        new OA\Property(property: 'detail_peti', type: 'array', items: new OA\Items(ref: '#/components/schemas/DetailPeti')),
    ]
)]
#[OA\Schema(
    schema: 'TransaksiRiwayatKiriman',
    title: 'Transaksi Riwayat per Kiriman',
    description: 'Transaksi yang menjual stok dari satu kiriman (barang datang) tertentu. item_transaksi hanya berisi item yang berasal dari kiriman tersebut.',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Transaksi'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'item_transaksi',
                    type: 'array',
                    description: 'Hanya item yang berasal dari kiriman ini (sudah difilter)',
                    items: new OA\Items(ref: '#/components/schemas/ItemTransaksi')
                ),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'DetailPeti',
    title: 'Detail Peti',
    description: 'Model Detail Peti',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'item_transaksi_id', type: 'integer', example: 1),
        new OA\Property(property: 'no_peti', type: 'integer', example: 1),
        new OA\Property(property: 'berat_kotor', type: 'number', example: 27.5),
        new OA\Property(property: 'berat_kemasan', type: 'number', example: 2.5),
        new OA\Property(property: 'berat_bersih', type: 'number', example: 25),
    ]
)]
#[OA\Schema(
    schema: 'BiayaOperasional',
    title: 'Biaya Operasional',
    description: 'Model Biaya Operasional',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'transaksi_id', type: 'integer', example: 1),
        new OA\Property(property: 'nama_biaya', type: 'string', example: 'Ongkos kirim'),
        new OA\Property(property: 'nominal', type: 'number', example: 50000),
    ]
)]
#[OA\Schema(
    schema: 'Produk',
    title: 'Produk',
    description: 'Model Produk',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kode_produk', type: 'string', example: 'PRD-0001'),
        new OA\Property(property: 'nama_produk', type: 'string', example: 'Apel Fuji'),
        new OA\Property(property: 'supplier_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'ukuran', type: 'string', example: 'A', nullable: true),
        new OA\Property(property: 'kategori_id', type: 'integer', nullable: true),
        new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
        new OA\Property(property: 'harga_beli', type: 'number', example: 20000),
        new OA\Property(property: 'harga_jual', type: 'number', example: 25000),
        new OA\Property(property: 'stok', type: 'number', example: 100),
        new OA\Property(property: 'stok_minimum', type: 'number', example: 10),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'aktif', type: 'boolean', example: true),
        new OA\Property(property: 'nama_kategori', type: 'string', example: 'Buah Import'),
        new OA\Property(property: 'nama_supplier', type: 'string', example: 'PT Buah Segar'),
        new OA\Property(property: 'kategori_relasi', ref: '#/components/schemas/Kategori', nullable: true),
        new OA\Property(property: 'supplier', ref: '#/components/schemas/Supplier', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Supplier',
    title: 'Supplier',
    description: 'Model Supplier',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kode_supplier', type: 'string', example: 'SUP-0001'),
        new OA\Property(property: 'nama_supplier', type: 'string', example: 'PT Buah Segar'),
        new OA\Property(property: 'telepon', type: 'string', example: '08123456789'),
        new OA\Property(property: 'email', type: 'string', example: 'supplier@example.com'),
        new OA\Property(property: 'alamat', type: 'string'),
        new OA\Property(property: 'kota', type: 'string', example: 'Jakarta'),
        new OA\Property(property: 'kontak_person', type: 'string'),
        new OA\Property(property: 'catatan', type: 'string'),
        new OA\Property(property: 'aktif', type: 'boolean', example: true),
        new OA\Property(property: 'produk_count', type: 'integer', example: 5),
        new OA\Property(property: 'produk', type: 'array', items: new OA\Items(ref: '#/components/schemas/Produk')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Kategori',
    title: 'Kategori',
    description: 'Model Kategori',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kode_kategori', type: 'string', example: 'KAT001'),
        new OA\Property(property: 'nama_kategori', type: 'string', example: 'Buah Lokal'),
        new OA\Property(property: 'deskripsi', type: 'string', nullable: true),
        new OA\Property(property: 'warna', type: 'string', example: '#4CAF50'),
        new OA\Property(property: 'aktif', type: 'boolean', example: true),
        new OA\Property(property: 'produk_count', type: 'integer', example: 15),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Pembayaran',
    title: 'Pembayaran',
    description: 'Model Pembayaran',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'transaksi_id', type: 'integer', example: 1),
        new OA\Property(property: 'kode_pembayaran', type: 'string', example: 'PAY-20260305-0001'),
        new OA\Property(property: 'nominal', type: 'number', example: 500000),
        new OA\Property(property: 'metode', type: 'string', enum: ['tunai', 'transfer', 'qris', 'lainnya']),
        new OA\Property(property: 'referensi', type: 'string', nullable: true),
        new OA\Property(property: 'catatan', type: 'string', nullable: true),
        new OA\Property(property: 'sisa_tagihan', type: 'number', example: 350000),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'user', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'User',
    title: 'User',
    description: 'Model User',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Admin'),
        new OA\Property(property: 'email', type: 'string', example: 'admin@example.com'),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'kasir'], example: 'admin'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'DetailBarangDatang',
    title: 'Detail Barang Datang',
    description: 'Satu letter (produk baru) dalam sebuah kiriman. Diinput via repeater saat barang datang. produk_id diisi otomatis setelah konfirmasi.',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'barang_datang_id', type: 'integer', example: 1),
        new OA\Property(property: 'produk_id', type: 'integer', example: 5, nullable: true, description: 'Diisi otomatis saat barang datang dikonfirmasi'),
        new OA\Property(property: 'nama_produk', type: 'string', example: 'Apel Fuji'),
        new OA\Property(property: 'ukuran', type: 'string', example: 'A', nullable: true, description: 'Grade/ukuran: A, B, C, dll'),
        new OA\Property(property: 'kategori_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'satuan', type: 'string', example: 'kg'),
        new OA\Property(property: 'harga_beli', type: 'number', example: 15000.0),
        new OA\Property(property: 'harga_jual', type: 'number', example: 20000.0),
        new OA\Property(property: 'jumlah', type: 'number', example: 100.0),
        new OA\Property(property: 'keterangan', type: 'string', nullable: true),
        new OA\Property(property: 'stok_awal', type: 'number', example: 100.0, nullable: true),
        new OA\Property(property: 'stok_terjual', type: 'number', example: 20.0, nullable: true),
        new OA\Property(property: 'stok_sisa', type: 'number', example: 80.0, nullable: true),
        new OA\Property(property: 'status_stok', type: 'string', example: 'available', nullable: true),
        new OA\Property(property: 'kode_bd', type: 'string', example: 'BD-20260307-0001', nullable: true),
        new OA\Property(property: 'kategori', ref: '#/components/schemas/Kategori', nullable: true),
        new OA\Property(property: 'produk', ref: '#/components/schemas/Produk', nullable: true, description: 'Terisi setelah konfirmasi'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'BarangDatang',
    title: 'Barang Datang',
    description: 'Header penerimaan barang/stock in dari supplier',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kode_bd', type: 'string', example: 'BD-20260307-0001'),
        new OA\Property(property: 'supplier_id', type: 'integer', example: 1),
        new OA\Property(property: 'tanggal', type: 'string', format: 'date', example: '2026-03-07'),
        new OA\Property(property: 'urutan_hari', type: 'integer', example: 1, description: 'Kiriman ke-N dari supplier ini pada hari tersebut'),
        new OA\Property(property: 'catatan', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'confirmed'], example: 'draft'),
        new OA\Property(property: 'dikonfirmasi_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'dikonfirmasi_oleh', type: 'integer', nullable: true),
        new OA\Property(property: 'supplier', ref: '#/components/schemas/Supplier', nullable: true),
        new OA\Property(
            property: 'details',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DetailBarangDatang')
        ),
        new OA\Property(property: 'details_count', type: 'integer', example: 3, nullable: true),
        new OA\Property(property: 'dikonfirmasi_oleh_user', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'LetterTerpakaiItem',
    title: 'Letter Terpakai Item',
    description: 'Satu letter yang sudah digunakan dalam kiriman hari ini (untuk validasi duplikat di frontend)',
    properties: [
        new OA\Property(property: 'nama_produk', type: 'string', example: 'Apel Fuji'),
        new OA\Property(property: 'ukuran', type: 'string', example: 'A', nullable: true),
        new OA\Property(property: 'key', type: 'string', example: 'apel fuji|a', description: 'Lowercase key: nama_produk|ukuran untuk pencocokan di frontend'),
    ]
)]
class SwaggerSchemas
{
    // This class is just for Swagger schema definitions
}
