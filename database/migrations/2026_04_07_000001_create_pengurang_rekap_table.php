<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengurang_rekap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekap')->cascadeOnDelete();
            $table->string('nama');           // e.g. "Biaya Peti", "Biaya Bongkar"
            $table->decimal('jumlah', 15, 2); // nominal pengurang
            $table->timestamps();
        });

        Schema::table('rekap', function (Blueprint $table) {
            $table->decimal('total_pengurang', 15, 2)->default(0)->after('total_ongkos');
        });
    }

    public function down(): void
    {
        Schema::table('rekap', function (Blueprint $table) {
            $table->dropColumn('total_pengurang');
        });

        Schema::dropIfExists('pengurang_rekap');
    }
};
