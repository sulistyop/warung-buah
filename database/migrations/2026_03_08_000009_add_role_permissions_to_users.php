<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // role: admin, kasir, operator
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('kasir')->after('email');
            }
            $table->boolean('aktif')->default(true)->after('role');
            $table->timestamp('last_login_at')->nullable()->after('aktif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['aktif', 'last_login_at']);
        });
    }
};
