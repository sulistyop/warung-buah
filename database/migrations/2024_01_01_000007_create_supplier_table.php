<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier', function (Blueprint $table) {
            $table->id();
            $table->string('kode_supplier')->unique();
            $table->string('nama_supplier');
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota')->nullable();
            $table->string('kontak_person')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Add supplier_id to item_transaksi for relation
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('transaksi_id')->constrained('supplier')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_transaksi', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn('supplier_id');
        });
        Schema::dropIfExists('supplier');
    }
};
