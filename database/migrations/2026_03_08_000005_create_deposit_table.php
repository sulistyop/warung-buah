<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit', function (Blueprint $table) {
            $table->id();
            $table->string('kode_deposit')->unique();
            $table->foreignId('pelanggan_id')->constrained('pelanggan')->cascadeOnDelete();
            $table->decimal('nominal', 15, 2);             // jumlah deposit masuk
            $table->decimal('terpakai', 15, 2)->default(0); // sudah digunakan utk bayar
            $table->decimal('sisa', 15, 2)->default(0);    // nominal - terpakai
            $table->string('metode')->default('tunai');     // tunai/transfer/qris
            $table->string('referensi')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->index('pelanggan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit');
    }
};
