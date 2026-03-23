<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'kode_supplier' => 'SUP-0001',
                'nama_supplier' => 'Pak Budi Santoso',
                'telepon'       => '08123456789',
                'email'         => 'budi.santoso@email.com',
                'alamat'        => 'Jl. Magelang KM 12, Sleman',
                'kota'          => 'Sleman',
                'kontak_person' => 'Pak Budi',
                'catatan'       => 'Supplier anggur utama. Kiriman setiap Selasa dan Jumat.',
                'komisi_persen' => 7.00,
                'aktif'         => true,
            ],
            [
                'kode_supplier' => 'SUP-0002',
                'nama_supplier' => 'Bu Sari Wulandari',
                'telepon'       => '08234567890',
                'email'         => null,
                'alamat'        => 'Jl. Wonosari KM 5, Bantul',
                'kota'          => 'Bantul',
                'kontak_person' => 'Bu Sari',
                'catatan'       => 'Supplier jeruk dan buah naga. Kiriman setiap Senin, Rabu, Sabtu.',
                'komisi_persen' => 5.00,
                'aktif'         => true,
            ],
            [
                'kode_supplier' => 'SUP-0003',
                'nama_supplier' => 'Pak Hendra Wijaya',
                'telepon'       => '08345678901',
                'email'         => 'hendra.w@email.com',
                'alamat'        => 'Pasar Induk Gamping, Kios B.07',
                'kota'          => 'Sleman',
                'kontak_person' => 'Pak Hendra',
                'catatan'       => 'Supplier buah campuran (apel, mangga, semangka).',
                'komisi_persen' => 6.00,
                'aktif'         => true,
            ],
        ];

        foreach ($suppliers as $s) {
            DB::table('supplier')->insert(array_merge($s, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
