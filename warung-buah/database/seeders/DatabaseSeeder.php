<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin user
        DB::table('users')->insert([
            'name'       => 'Admin',
            'email'      => 'admin@warung.com',
            'password'   => Hash::make('password'),
            'role'       => 'admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Default settings
        $settings = [
            [
                'key'   => 'komisi_persen',
                'value' => '0',
                'label' => 'Komisi Default (%)',
                'type'  => 'percentage',
            ],
            [
                'key'   => 'nama_toko',
                'value' => 'Warung Buah',
                'label' => 'Nama Toko',
                'type'  => 'text',
            ],
            [
                'key'   => 'alamat_toko',
                'value' => '',
                'label' => 'Alamat Toko',
                'type'  => 'text',
            ],
        ];

        foreach ($settings as $s) {
            DB::table('settings')->insert(array_merge($s, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
