<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pembayaran MODIFY COLUMN metode ENUM('tunai', 'transfer', 'qris', 'lainnya', 'deposit') NOT NULL DEFAULT 'tunai'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pembayaran MODIFY COLUMN metode ENUM('tunai', 'transfer', 'qris', 'lainnya') NOT NULL DEFAULT 'tunai'");
    }
};
