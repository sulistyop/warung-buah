<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Nonaktifkan foreign key check agar truncate bisa jalan
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::table('log_aktivitas')->truncate();
        DB::table('detail_pre_order')->truncate();
        DB::table('pre_order')->truncate();
        DB::table('deposit')->truncate();
        DB::table('komplain_rekap')->truncate();
        DB::table('detail_rekap')->truncate();
        DB::table('rekap')->truncate();
        DB::table('pembayaran')->truncate();
        DB::table('biaya_operasional')->truncate();
        DB::table('detail_peti')->truncate();
        DB::table('item_transaksi')->truncate();
        DB::table('transaksi')->truncate();
        DB::table('detail_barang_datang')->truncate();
        DB::table('barang_datang')->truncate();
        DB::table('kategori')->truncate();
        DB::table('supplier')->truncate();
        DB::table('pelanggan')->truncate();
        DB::table('settings')->truncate();
        DB::table('personal_access_tokens')->truncate();
        DB::table('users')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->call([
            UserSeeder::class,
            SettingSeeder::class,
            KategoriSeeder::class,
            SupplierSeeder::class,
            PelangganSeeder::class,
            BarangDatangSeeder::class,
            TransaksiSeeder::class,
            RekapSeeder::class,
            DepositSeeder::class,
            PreOrderSeeder::class,
            LogAktivitasSeeder::class,
        ]);
    }
}
