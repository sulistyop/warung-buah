<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kategori', 20)->unique();
            $table->string('nama_kategori', 100);
            $table->string('deskripsi')->nullable();
            $table->string('warna', 20)->default('#4CAF50'); // Warna badge
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Add kategori_id to produk table
        Schema::table('produk', function (Blueprint $table) {
            $table->foreignId('kategori_id')->nullable()->after('nama_produk')
                  ->constrained('kategori')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropForeign(['kategori_id']);
            $table->dropColumn('kategori_id');
        });

        Schema::dropIfExists('kategori');
    }
};
