<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'          => 'Admin Utama',
                'email'         => 'admin@warung.com',
                'password'      => Hash::make('password'),
                'role'          => 'admin',
                'aktif'         => true,
                'last_login_at' => now()->subHours(2),
            ],
            [
                'name'          => 'Sari Kasir',
                'email'         => 'kasir1@warung.com',
                'password'      => Hash::make('password'),
                'role'          => 'kasir',
                'aktif'         => true,
                'last_login_at' => now()->subHours(1),
            ],
            [
                'name'          => 'Budi Kasir',
                'email'         => 'kasir2@warung.com',
                'password'      => Hash::make('password'),
                'role'          => 'kasir',
                'aktif'         => true,
                'last_login_at' => now()->subDays(1),
            ],
            [
                'name'          => 'Andi Operator',
                'email'         => 'operator@warung.com',
                'password'      => Hash::make('password'),
                'role'          => 'operator',
                'aktif'         => true,
                'last_login_at' => now()->subDays(2),
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->insert(array_merge($user, [
                'email_verified_at' => now(),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]));
        }
    }
}
