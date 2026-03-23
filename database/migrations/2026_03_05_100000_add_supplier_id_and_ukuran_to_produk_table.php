<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('id')->constrained('supplier')->nullOnDelete();
            $table->string('ukuran')->nullable()->after('nama_produk'); // A, B, C, etc.
        });
    }

    public function down(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'ukuran']);
        });
    }
};
