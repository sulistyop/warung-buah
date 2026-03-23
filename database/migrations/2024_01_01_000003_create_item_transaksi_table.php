<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grup item: misal "Jeruk A dari Supplier Budi, harga 8000/kg"
        Schema::create('item_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksi')->onDelete('cascade');
            $table->string('nama_supplier');
            $table->string('jenis_buah');
            $table->string('ukuran'); // A, B, C, D, E
            $table->decimal('harga_per_kg', 15, 2);
            $table->integer('jumlah_peti')->default(0); // dihitung otomatis
            $table->decimal('total_berat_bersih', 10, 3)->default(0); // dihitung otomatis
            $table->decimal('subtotal', 15, 2)->default(0); // dihitung otomatis
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_transaksi');
    }
};
