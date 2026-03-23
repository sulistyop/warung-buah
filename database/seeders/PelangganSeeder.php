<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PelangganSeeder extends Seeder
{
    public function run(): void
    {
        $pelanggan = [
            [
                'kode_pelanggan' => 'PLG-0001',
                'nama'           => 'Sidiq Prasetyo',
                'telepon'        => '08112233445',
                'toko'           => 'Toko Buah Sidiq',
                'alamat'         => 'Jl. Kaliurang KM 8, Sleman',
                'catatan'        => 'Pelanggan tetap. Biasanya ambil tempo 7 hari.',
                'aktif'          => true,
            ],
            [
                'kode_pelanggan' => 'PLG-0002',
                'nama'           => 'M. Faiz Abdillah',
                'telepon'        => '08223344556',
                'toko'           => 'Toko M. Faiz',
                'alamat'         => 'Pasar Sleman, Kios 23',
                'catatan'        => 'Bayar tunai, langganan jeruk dan anggur.',
                'aktif'          => true,
            ],
            [
                'kode_pelanggan' => 'PLG-0003',
                'nama'           => 'Dewi Rahayu',
                'telepon'        => '08334455667',
                'toko'           => 'Warung Buah Dewi',
                'alamat'         => 'Jl. Parangtritis KM 3, Bantul',
                'catatan'        => 'Pelanggan baru. Sering ambil buah naga.',
                'aktif'          => true,
            ],
            [
                'kode_pelanggan' => 'PLG-0004',
                'nama'           => 'Ahmad Fauzi',
                'telepon'        => '08445566778',
                'toko'           => 'Supermarket Fresh Ahmad',
                'alamat'         => 'Jl. Godean KM 5, Sleman',
                'catatan'        => 'Order besar, bayar via transfer.',
                'aktif'          => true,
            ],
            [
                'kode_pelanggan' => 'PLG-0005',
                'nama'           => 'Rina Susanti',
                'telepon'        => '08556677889',
                'toko'           => 'Kios Buah Rina',
                'alamat'         => 'Pasar Gamping, Kios A.14',
                'catatan'        => null,
                'aktif'          => true,
            ],
        ];

        foreach ($pelanggan as $p) {
            DB::table('pelanggan')->insert(array_merge($p, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
