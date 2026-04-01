<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('komplain_transaksi', function (Blueprint $table) {
            $table->decimal('jumlah_bs', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('komplain_transaksi', function (Blueprint $table) {
            $table->integer('jumlah_bs')->default(0)->change();
        });
    }
};
