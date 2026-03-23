<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->unsignedBigInteger('detail_barang_datang_id')->nullable()->after('supplier_id');
            $table->foreign('detail_barang_datang_id')->references('id')->on('detail_barang_datang')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->dropForeign(['detail_barang_datang_id']);
            $table->dropColumn('detail_barang_datang_id');
        });
    }
};
