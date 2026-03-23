<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel history pembayaran untuk cicilan/tempo
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->onDelete('cascade');
            $table->string('kode_pembayaran')->unique();
            $table->decimal('nominal', 15, 2);
            $table->enum('metode', ['tunai', 'transfer', 'qris', 'lainnya'])->default('tunai');
            $table->string('referensi')->nullable(); // no rekening/no reference
            $table->text('catatan')->nullable();
            $table->decimal('sisa_tagihan', 15, 2)->default(0); // sisa setelah pembayaran ini
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        // Add payment fields to transaksi
        Schema::table('transaksi', function (Blueprint $table) {
            $table->decimal('total_tagihan', 15, 2)->default(0)->after('total_bersih');
            $table->decimal('total_dibayar', 15, 2)->default(0)->after('total_tagihan');
            $table->decimal('sisa_tagihan', 15, 2)->default(0)->after('total_dibayar');
            $table->decimal('uang_diterima', 15, 2)->default(0)->after('sisa_tagihan');
            $table->decimal('kembalian', 15, 2)->default(0)->after('uang_diterima');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn([
                'total_tagihan',
                'total_dibayar', 
                'sisa_tagihan',
                'uang_diterima',
                'kembalian'
            ]);
        });
        Schema::dropIfExists('pembayaran');
    }
};
