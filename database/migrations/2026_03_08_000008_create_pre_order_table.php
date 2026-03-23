<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * PRE ORDER: pesanan yang mengurangi stok sebelum transaksi final dibuat.
         * Flow: PO dibuat → stok dipesan (reserved) → PO dikonversi jadi Transaksi → stok dikurangi permanent
         */
        Schema::create('pre_order', function (Blueprint $table) {
            $table->id();
            $table->string('kode_po')->unique();           // PO-YYYYMMDD-NNNN
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggan')->nullOnDelete();
            $table->string('nama_pelanggan');              // bisa free text juga
            $table->date('tanggal_po');
            $table->date('tanggal_kirim')->nullable();
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('status', ['pending', 'diproses', 'selesai', 'dibatalkan'])->default('pending');
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksi')->nullOnDelete()
                ->comment('Diisi saat PO dikonversi jadi transaksi');
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('detail_pre_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pre_order_id')->constrained('pre_order')->cascadeOnDelete();
            $table->foreignId('detail_barang_datang_id')->nullable()
                ->constrained('detail_barang_datang')->nullOnDelete()
                ->comment('Stok spesifik yang dipesan (batch tertentu)');
            $table->string('nama_produk');
            $table->string('ukuran')->nullable();
            $table->integer('jumlah_peti');
            $table->decimal('harga_per_kg', 15, 2)->default(0);
            $table->decimal('estimasi_berat_bersih', 10, 3)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pre_order');
        Schema::dropIfExists('pre_order');
    }
};
