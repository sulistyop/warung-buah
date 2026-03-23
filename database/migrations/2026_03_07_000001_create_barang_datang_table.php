<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_datang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_bd')->unique(); // BD-YYYYMMDD-NNNN
            $table->foreignId('supplier_id')->constrained('supplier')->cascadeOnDelete();
            $table->date('tanggal');
            $table->unsignedTinyInteger('urutan_hari')->default(1); // 1 = kiriman pertama hari ini, 2 = kedua, dst
            $table->text('catatan')->nullable();
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->timestamp('dikonfirmasi_at')->nullable();
            $table->foreignId('dikonfirmasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['supplier_id', 'tanggal']);
            $table->unique(['supplier_id', 'tanggal', 'urutan_hari']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_datang');
    }
};
