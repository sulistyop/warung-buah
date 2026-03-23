<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Rekap untuk Pak Budi tanggal 2026-02-05
 * Semua produk sudah habis (Anggur A=33, B=4, C=3 peti)
 * Sesuai nota fisik di foto.
 */
class RekapSeeder extends Seeder
{
    public function run(): void
    {
        $adminId = DB::table('users')->where('role', 'admin')->value('id');

        // ── Rekap 1: Pak Budi — 2026-02-05 (FINAL) ───────────────────────────
        // Formula (dari nota fisik):
        //   Total Kotor   = 12.325.500 + 1.248.000 + 855.000 = 14.428.500 (tanpa busuk)
        //   Komisi 7%     = 14.428.500 × 7% = 1.010.000 (dibulatkan)
        //   Kuli          = 2.000 × 40 peti = 80.000
        //   Ongkos        = 760.000
        //   Pend. Bersih  = 14.428.500 - 1.010.000 - 80.000 - 760.000 = 12.578.500
        //   Busuk (BS A)  = 22 × 13.500 = 297.000
        //   Sisa          = 12.578.500 - 297.000 = 12.281.500

        $rekapId = DB::table('rekap')->insertGetId([
            'kode_rekap'        => 'RKP-20260205-0001',
            'supplier_id'       => 1,
            'tanggal'           => '2026-02-05',
            'komisi_persen'     => 7.00,
            'kuli_per_peti'     => 2000,
            'total_peti'        => 40,
            'total_kotor'       => 14428500,
            'total_komisi'      => 1010000,
            'total_kuli'        => 80000,
            'total_ongkos'      => 760000,
            'keterangan_ongkos' => 'Ongkos angkut + biaya lain',
            'total_busuk'       => 297000,
            'pendapatan_bersih' => 12578500,
            'sisa'              => 12281500,
            'status'            => 'final',
            'dibuat_oleh'       => $adminId,
            'final_at'          => '2026-02-05 18:00:00',
            'created_at'        => '2026-02-05 17:30:00',
            'updated_at'        => '2026-02-05 18:00:00',
        ]);

        // Detail rekap — per letter
        DB::table('detail_rekap')->insert([
            [
                'rekap_id'           => $rekapId,
                'nama_produk'        => 'Anggur',
                'ukuran'             => 'A',
                'jumlah_peti'        => 33,
                'total_berat_kotor'  => 1077.0,  // sum berat_kotor semua peti A
                'total_berat_peti'   => 142.0,   // sum berat_kemasan semua peti A
                'total_berat_bersih' => 913.0,   // berat bersih bersih
                'harga_per_kg'       => 13500,
                'subtotal'           => 12325500,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'rekap_id'           => $rekapId,
                'nama_produk'        => 'Anggur',
                'ukuran'             => 'B',
                'jumlah_peti'        => 4,
                'total_berat_kotor'  => 120.0,
                'total_berat_peti'   => 16.0,
                'total_berat_bersih' => 104.0,
                'harga_per_kg'       => 12000,
                'subtotal'           => 1248000,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'rekap_id'           => $rekapId,
                'nama_produk'        => 'Anggur',
                'ukuran'             => 'C',
                'jumlah_peti'        => 3,
                'total_berat_kotor'  => 102.0,
                'total_berat_peti'   => 12.0,
                'total_berat_bersih' => 90.0,
                'harga_per_kg'       => 9500,
                'subtotal'           => 855000,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);

        // Komplain / BS
        DB::table('komplain_rekap')->insert([
            [
                'rekap_id'    => $rekapId,
                'nama_produk' => 'Anggur A',
                'jumlah_bs'   => 22,
                'harga_ganti' => 13500,
                'total'       => 297000,
                'keterangan'  => 'Busuk / BS dari Pak Budi',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);


        // ── Rekap 2: Pak Budi — 2026-02-06 (DRAFT, belum final) ──────────────
        // Kiriman ke-2 masih ada sisa 10 peti, belum bisa final
        $rekap2Id = DB::table('rekap')->insertGetId([
            'kode_rekap'        => 'RKP-20260206-0001',
            'supplier_id'       => 1,
            'tanggal'           => '2026-02-06',
            'komisi_persen'     => 7.00,
            'kuli_per_peti'     => 2000,
            'total_peti'        => 60,
            'total_kotor'       => 8100000,
            'total_komisi'      => 567000,
            'total_kuli'        => 120000,
            'total_ongkos'      => 500000,
            'keterangan_ongkos' => 'Ongkos angkut',
            'total_busuk'       => 0,
            'pendapatan_bersih' => 6913000,
            'sisa'              => 6913000,
            'status'            => 'draft',
            'dibuat_oleh'       => $adminId,
            'final_at'          => null,
            'created_at'        => '2026-02-06 17:00:00',
            'updated_at'        => '2026-02-06 17:00:00',
        ]);

        DB::table('detail_rekap')->insert([
            [
                'rekap_id'           => $rekap2Id,
                'nama_produk'        => 'Anggur',
                'ukuran'             => 'A',
                'jumlah_peti'        => 60,
                'total_berat_kotor'  => 720.0,
                'total_berat_peti'   => 120.0,
                'total_berat_bersih' => 600.0,
                'harga_per_kg'       => 13500,
                'subtotal'           => 8100000,
                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);
    }
}
