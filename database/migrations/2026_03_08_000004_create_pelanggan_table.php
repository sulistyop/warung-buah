<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pelanggan')->unique();
            $table->string('nama');
            $table->string('telepon')->nullable();
            $table->string('toko')->nullable();      // nama toko/kios
            $table->text('alamat')->nullable();
            $table->text('catatan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Tambah relasi pelanggan ke transaksi (nullable agar backward-compat)
        Schema::table('transaksi', function (Blueprint $table) {
            $table->foreignId('pelanggan_id')->nullable()->after('nama_pelanggan')
                ->constrained('pelanggan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropForeign(['pelanggan_id']);
            $table->dropColumn('pelanggan_id');
        });
        Schema::dropIfExists('pelanggan');
    }
};
