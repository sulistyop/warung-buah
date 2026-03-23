<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LogAktivitasSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $kasirId = DB::table('users')->where('role', 'kasir')->orderBy('id')->value('id');

        $logs = [
            // Barang datang
            [
                'user_id'    => $kasirId,
                'modul'      => 'barang_datang',
                'aksi'       => 'create',
                'model_type' => 'App\\Models\\BarangDatang',
                'model_id'   => 1,
                'deskripsi'  => 'Barang datang BD-20260205-0001 dibuat (Pak Budi - 3 letter)',
                'data_lama'  => null,
                'data_baru'  => json_encode(['kode_bd' => 'BD-20260205-0001', 'supplier' => 'Pak Budi Santoso']),
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-05 07:00:00',
            ],
            [
                'user_id'    => $adminId,
                'modul'      => 'barang_datang',
                'aksi'       => 'konfirmasi',
                'model_type' => 'App\\Models\\BarangDatang',
                'model_id'   => 1,
                'deskripsi'  => 'Barang datang BD-20260205-0001 dikonfirmasi oleh Admin',
                'data_lama'  => json_encode(['status' => 'draft']),
                'data_baru'  => json_encode(['status' => 'confirmed']),
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-05 07:30:00',
            ],
            // Transaksi
            [
                'user_id'    => $kasirId,
                'modul'      => 'transaksi',
                'aksi'       => 'create',
                'model_type' => 'App\\Models\\Transaksi',
                'model_id'   => 1,
                'deskripsi'  => 'Transaksi TRX-20260205-0001 dibuat untuk Mas Sidiq Rp 14.510.500',
                'data_lama'  => null,
                'data_baru'  => json_encode(['kode_transaksi' => 'TRX-20260205-0001', 'total_tagihan' => 14510500]),
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-05 10:00:00',
            ],
            [
                'user_id'    => $kasirId,
                'modul'      => 'transaksi',
                'aksi'       => 'create',
                'model_type' => 'App\\Models\\Transaksi',
                'model_id'   => 2,
                'deskripsi'  => 'Transaksi TRX-20260206-0001 dibuat untuk M. Faiz Rp 7.015.750 (LUNAS)',
                'data_lama'  => null,
                'data_baru'  => json_encode(['kode_transaksi' => 'TRX-20260206-0001', 'total_tagihan' => 7015750]),
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-06 09:00:00',
            ],
            // Pembayaran
            [
                'user_id'    => $kasirId,
                'modul'      => 'pembayaran',
                'aksi'       => 'create',
                'model_type' => 'App\\Models\\Pembayaran',
                'model_id'   => 1,
                'deskripsi'  => 'Pembayaran PAY-20260206-0001 lunas Rp 7.015.750 (tunai) untuk TRX-20260206-0001',
                'data_lama'  => null,
                'data_baru'  => json_encode(['nominal' => 7015750, 'metode' => 'tunai']),
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-06 09:30:00',
            ],
            // Rekap
            [
                'user_id'    => $adminId,
                'modul'      => 'rekap',
                'aksi'       => 'create',
                'model_type' => 'App\\Models\\Rekap',
                'model_id'   => 1,
                'deskripsi'  => 'Rekap RKP-20260205-0001 dibuat untuk Pak Budi tanggal 05/02/2026',
                'data_lama'  => null,
                'data_baru'  => json_encode(['kode_rekap' => 'RKP-20260205-0001', 'sisa' => 12281500]),
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-05 17:30:00',
            ],
            [
                'user_id'    => $adminId,
                'modul'      => 'rekap',
                'aksi'       => 'final',
                'model_type' => 'App\\Models\\Rekap',
                'model_id'   => 1,
                'deskripsi'  => 'Rekap RKP-20260205-0001 difinalisasi. Sisa: Rp 12.281.500',
                'data_lama'  => json_encode(['status' => 'draft']),
                'data_baru'  => json_encode(['status' => 'final']),
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-05 18:00:00',
            ],
            // Deposit
            [
                'user_id'    => $kasirId,
                'modul'      => 'deposit',
                'aksi'       => 'create',
                'model_type' => 'App\\Models\\Deposit',
                'model_id'   => 1,
                'deskripsi'  => 'Deposit DEP-20260207-0001 Rp 10.000.000 untuk Mas Sidiq (tunai)',
                'data_lama'  => null,
                'data_baru'  => json_encode(['nominal' => 10000000, 'metode' => 'tunai']),
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-07 08:00:00',
            ],
            // Pre Order
            [
                'user_id'    => $kasirId,
                'modul'      => 'pre_order',
                'aksi'       => 'create',
                'model_type' => 'App\\Models\\PreOrder',
                'model_id'   => 1,
                'deskripsi'  => 'PO PO-20260207-0001 dibuat untuk M. Faiz — 10 peti Jeruk',
                'data_lama'  => null,
                'data_baru'  => json_encode(['kode_po' => 'PO-20260207-0001', 'total' => 2310000]),
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-07 10:00:00',
            ],
            // User management
            [
                'user_id'    => $adminId,
                'modul'      => 'user',
                'aksi'       => 'create',
                'model_type' => 'App\\Models\\User',
                'model_id'   => $kasirId,
                'deskripsi'  => 'User kasir baru ditambahkan oleh admin',
                'data_lama'  => null,
                'data_baru'  => json_encode(['role' => 'kasir']),
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Flutter/WarungBuah/1.0',
                'created_at' => '2026-02-01 08:00:00',
            ],
        ];

        foreach ($logs as $log) {
            DB::table('log_aktivitas')->insert($log);
        }
    }
}
