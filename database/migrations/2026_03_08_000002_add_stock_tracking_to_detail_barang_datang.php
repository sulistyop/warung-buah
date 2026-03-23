<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detail_barang_datang', function (Blueprint $table) {
            // stok_awal = jumlah peti saat barang datang (copy dari jumlah)
            $table->decimal('stok_awal', 10, 2)->default(0)->after('jumlah')
                ->comment('Stok peti saat pertama kali masuk (sama dengan jumlah)');
            // stok_terjual = total peti yang sudah terjual
            $table->decimal('stok_terjual', 10, 2)->default(0)->after('stok_awal')
                ->comment('Akumulasi peti yang sudah terjual');
            // stok_sisa = stok_awal - stok_terjual
            $table->decimal('stok_sisa', 10, 2)->default(0)->after('stok_terjual')
                ->comment('Sisa stok = stok_awal - stok_terjual');
            // status available / habis
            $table->enum('status_stok', ['available', 'habis'])->default('available')->after('stok_sisa');
        });
    }

    public function down(): void
    {
        Schema::table('detail_barang_datang', function (Blueprint $table) {
            $table->dropColumn(['stok_awal', 'stok_terjual', 'stok_sisa', 'status_stok']);
        });
    }
};
