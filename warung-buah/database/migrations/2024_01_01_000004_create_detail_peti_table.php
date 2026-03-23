<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Detail per peti: berat kotor, berat kemasan, berat bersih
        Schema::create('detail_peti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_transaksi_id')->constrained('item_transaksi')->onDelete('cascade');
            $table->integer('no_peti'); // urutan peti ke-1, 2, 3...
            $table->decimal('berat_kotor', 10, 3); // kg
            $table->decimal('berat_kemasan', 10, 3); // kg
            $table->decimal('berat_bersih', 10, 3)->storedAs('berat_kotor - berat_kemasan'); // otomatis
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_peti');
    }
};
