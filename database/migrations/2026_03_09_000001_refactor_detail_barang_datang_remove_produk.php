<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom kode_produk dan aktif ke detail_barang_datang
        Schema::table('detail_barang_datang', function (Blueprint $table) {
            $table->string('kode_produk')->nullable()->unique()->after('barang_datang_id');
            $table->boolean('aktif')->default(true)->after('keterangan');
        });

        // 2. Hapus FK produk_id dari detail_barang_datang
        Schema::table('detail_barang_datang', function (Blueprint $table) {
            $table->dropForeign(['produk_id']);
            $table->dropColumn('produk_id');
        });

        // 3. Drop tabel produk (sudah tidak digunakan)
        Schema::dropIfExists('produk');
    }

    public function down(): void
    {
        // Re-create produk table
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->nullable()->constrained('supplier')->nullOnDelete();
            $table->string('kode_produk')->unique();
            $table->string('nama_produk');
            $table->string('ukuran')->nullable();
            $table->foreignId('kategori_id')->nullable()->constrained('kategori')->nullOnDelete();
            $table->string('kategori')->nullable();
            $table->string('satuan')->default('kg');
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->decimal('stok', 10, 2)->default(0);
            $table->decimal('stok_minimum', 10, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Re-add produk_id to detail_barang_datang
        Schema::table('detail_barang_datang', function (Blueprint $table) {
            $table->foreignId('produk_id')->nullable()->after('barang_datang_id')->constrained('produk')->nullOnDelete();
            $table->dropForeign(['kode_produk']);
            $table->dropColumn(['kode_produk', 'aktif']);
        });
    }
};
