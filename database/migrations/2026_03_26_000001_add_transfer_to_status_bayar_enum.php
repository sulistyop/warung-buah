<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN status_bayar ENUM('lunas', 'transfer', 'tempo', 'cicil') NOT NULL DEFAULT 'lunas'");
    }

    public function down(): void
    {
        // Update any 'transfer' records back to 'lunas' before reverting enum
        DB::statement("UPDATE transaksi SET status_bayar = 'lunas' WHERE status_bayar = 'transfer'");
        DB::statement("ALTER TABLE transaksi MODIFY COLUMN status_bayar ENUM('lunas', 'tempo', 'cicil') NOT NULL DEFAULT 'lunas'");
    }
};
