<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Skenario Deposit:
 *
 * Mas Sidiq titip 15 juta (akan dipakai bayar piutang TRX-1, TRX-5, TRX-6)
 *   - DEP-1: 10 jt tunai
 *   - DEP-2:  5 jt transfer
 * Total deposit Mas Sidiq = 15 jt, sisa piutang = 21.610.500
 * Sehingga masih ada sisa piutang 6.610.500 setelah deposit habis.
 */
class DepositSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('role', 'admin')->value('id');
        $kasirId = DB::table('users')->where('role', 'kasir')->orderBy('id')->value('id');

        $plgSidiq = DB::table('pelanggan')->where('nama', 'like', '%Sidiq%')->value('id');
        $plgDewi  = DB::table('pelanggan')->where('nama', 'like', '%Dewi%')->value('id');

        // Deposit Mas Sidiq — 10 juta tunai
        DB::table('deposit')->insert([
            'kode_deposit' => 'DEP-20260207-0001',
            'pelanggan_id' => $plgSidiq,
            'nominal'      => 10000000,
            'terpakai'     => 0,
            'sisa'         => 10000000,
            'metode'       => 'tunai',
            'referensi'    => null,
            'catatan'      => 'Titip uang untuk bayar piutang anggur',
            'user_id'      => $kasirId,
            'created_at'   => '2026-02-07 08:00:00',
            'updated_at'   => '2026-02-07 08:00:00',
        ]);

        // Deposit Mas Sidiq — 5 juta transfer
        DB::table('deposit')->insert([
            'kode_deposit' => 'DEP-20260207-0002',
            'pelanggan_id' => $plgSidiq,
            'nominal'      => 5000000,
            'terpakai'     => 0,
            'sisa'         => 5000000,
            'metode'       => 'transfer',
            'referensi'    => 'BCA-02070001',
            'catatan'      => 'Transfer BCA',
            'user_id'      => $kasirId,
            'created_at'   => '2026-02-07 08:30:00',
            'updated_at'   => '2026-02-07 08:30:00',
        ]);

        // Deposit Bu Dewi — 500rb untuk menutup cicilan
        DB::table('deposit')->insert([
            'kode_deposit' => 'DEP-20260207-0003',
            'pelanggan_id' => $plgDewi,
            'nominal'      => 500000,
            'terpakai'     => 0,
            'sisa'         => 500000,
            'metode'       => 'tunai',
            'referensi'    => null,
            'catatan'      => 'Titip untuk cicilan berikutnya',
            'user_id'      => $adminId,
            'created_at'   => '2026-02-07 09:00:00',
            'updated_at'   => '2026-02-07 09:00:00',
        ]);
    }
}
