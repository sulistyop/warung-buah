<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_laci', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kas')->unique();
            $table->date('tanggal');
            $table->text('keterangan');
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->decimal('nominal', 15, 2);
            $table->enum('metode_sumber', ['tunai', 'transfer', 'qris', 'lainnya'])->default('tunai');
            $table->string('referensi_tipe')->nullable(); // 'transaksi', 'pembayaran', 'manual'
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->boolean('is_auto')->default(false);
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('tanggal');
            $table->index('jenis');
            $table->index(['referensi_tipe', 'referensi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_laci');
    }
};
