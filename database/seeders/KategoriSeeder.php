<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KategoriSeeder extends Seeder
{
    public function run(): void
    {
        $kategori = [
            ['kode' => 'KAT001', 'nama' => 'Anggur',     'deskripsi' => 'Buah anggur lokal dan impor',   'warna' => '#9C27B0'],
            ['kode' => 'KAT002', 'nama' => 'Jeruk',      'deskripsi' => 'Jeruk siam, jeruk medan, dll',  'warna' => '#FF9800'],
            ['kode' => 'KAT003', 'nama' => 'Buah Naga',  'deskripsi' => 'Buah naga merah dan putih',     'warna' => '#F44336'],
            ['kode' => 'KAT004', 'nama' => 'Apel',       'deskripsi' => 'Apel fuji, apel hijau, dll',    'warna' => '#4CAF50'],
            ['kode' => 'KAT005', 'nama' => 'Mangga',     'deskripsi' => 'Mangga harum manis, gadung, dll','warna' => '#FFEB3B'],
            ['kode' => 'KAT006', 'nama' => 'Semangka',   'deskripsi' => 'Semangka merah dan kuning',     'warna' => '#E91E63'],
        ];

        foreach ($kategori as $k) {
            DB::table('kategori')->insert([
                'kode_kategori' => $k['kode'],
                'nama_kategori' => $k['nama'],
                'deskripsi'     => $k['deskripsi'],
                'warna'         => $k['warna'],
                'aktif'         => true,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
