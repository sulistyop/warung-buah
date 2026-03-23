<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->unique();
            $table->string('nama_pelanggan');
            $table->enum('status_bayar', ['lunas', 'tempo', 'cicil'])->default('lunas');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->text('catatan')->nullable();
            $table->decimal('komisi_persen', 5, 2)->default(0);
            $table->decimal('total_kotor', 15, 2)->default(0);
            $table->decimal('total_komisi', 15, 2)->default(0);
            $table->decimal('total_biaya_operasional', 15, 2)->default(0);
            $table->decimal('total_bersih', 15, 2)->default(0);
            $table->enum('status', ['draft', 'selesai'])->default('draft');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi');
    }
};
