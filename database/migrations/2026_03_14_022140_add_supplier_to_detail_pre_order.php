<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('detail_pre_order', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->after('detail_barang_datang_id')
                ->constrained('supplier')
                ->nullOnDelete();
            $table->string('nama_supplier')->nullable()->after('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::table('detail_pre_order', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'nama_supplier']);
        });
    }
};
