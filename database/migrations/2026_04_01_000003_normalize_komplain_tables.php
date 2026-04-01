<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. komplain_transaksi: tambah item_transaksi_id FK
        Schema::table('komplain_transaksi', function (Blueprint $table) {
            $table->unsignedBigInteger('item_transaksi_id')->nullable()->after('transaksi_id');
            $table->foreign('item_transaksi_id')->references('id')->on('item_transaksi')->nullOnDelete();
        });

        // Isi item_transaksi_id dari data yang sudah ada via string match (best effort)
        DB::statement("
            UPDATE komplain_transaksi kt
            JOIN item_transaksi it ON it.transaksi_id = kt.transaksi_id
                AND it.jenis_buah = kt.nama_produk
            SET kt.item_transaksi_id = it.id
        ");

        // 2. komplain_rekap: tambah detail_rekap_id FK + fix jumlah_bs ke decimal
        Schema::table('komplain_rekap', function (Blueprint $table) {
            $table->unsignedBigInteger('detail_rekap_id')->nullable()->after('rekap_id');
            $table->foreign('detail_rekap_id')->references('id')->on('detail_rekap')->nullOnDelete();
            $table->decimal('jumlah_bs', 10, 2)->default(0)->change();
        });

        // Isi detail_rekap_id dari data yang sudah ada via string match (best effort)
        DB::statement("
            UPDATE komplain_rekap kr
            JOIN detail_rekap dr ON dr.rekap_id = kr.rekap_id
                AND dr.nama_produk = kr.nama_produk
            SET kr.detail_rekap_id = dr.id
        ");
    }

    public function down(): void
    {
        Schema::table('komplain_transaksi', function (Blueprint $table) {
            $table->dropForeign(['item_transaksi_id']);
            $table->dropColumn('item_transaksi_id');
        });

        Schema::table('komplain_rekap', function (Blueprint $table) {
            $table->dropForeign(['detail_rekap_id']);
            $table->dropColumn('detail_rekap_id');
            $table->integer('jumlah_bs')->default(0)->change();
        });
    }
};
