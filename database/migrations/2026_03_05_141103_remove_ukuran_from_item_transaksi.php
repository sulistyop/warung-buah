<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->dropColumn('ukuran');
        });
    }

    public function down(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->string('ukuran')->after('jenis_buah');
        });
    }
};