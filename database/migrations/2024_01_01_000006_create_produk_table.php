<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->string('kode_produk')->unique();
            $table->string('nama_produk');
            $table->string('kategori')->nullable(); // Buah, Sayur, dll
            $table->string('satuan')->default('kg'); // kg, pcs, box
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->decimal('stok', 10, 2)->default(0);
            $table->decimal('stok_minimum', 10, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
