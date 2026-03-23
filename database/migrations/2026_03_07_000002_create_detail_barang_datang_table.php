<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_barang_datang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_datang_id')->constrained('barang_datang')->cascadeOnDelete();

            // produk_id nullable — diisi otomatis saat barang datang dikonfirmasi
            $table->foreignId('produk_id')->nullable()->constrained('produk')->nullOnDelete();

            // Data produk/letter yang diinput saat barang datang (sebelum produk ada di master)
            $table->string('nama_produk');
            $table->string('ukuran')->nullable();            // Grade A, B, C, dll
            $table->foreignId('kategori_id')->nullable()->constrained('kategori')->nullOnDelete();
            $table->string('satuan')->default('kg');         // kg, pcs, box, dll
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->decimal('jumlah', 10, 2);               // stok yang masuk
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Letter (nama_produk + ukuran) harus unik dalam 1 kiriman
            // Uniqueness lintas kiriman per hari per supplier dihandle di application level
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_barang_datang');
    }
};
