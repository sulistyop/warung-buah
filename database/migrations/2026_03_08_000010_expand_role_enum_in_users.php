<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Expand enum role agar mencakup 'operator'
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'kasir', 'operator') NOT NULL DEFAULT 'kasir'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir'");
    }
};
