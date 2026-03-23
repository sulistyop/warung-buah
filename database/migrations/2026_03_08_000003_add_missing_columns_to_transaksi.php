<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            // Kolom yang ada di model tapi belum di migration
            if (!Schema::hasColumn('transaksi', 'total_tagihan')) {
                $table->decimal('total_tagihan', 15, 2)->default(0)->after('total_bersih');
            }
            if (!Schema::hasColumn('transaksi', 'total_dibayar')) {
                $table->decimal('total_dibayar', 15, 2)->default(0)->after('total_tagihan');
            }
            if (!Schema::hasColumn('transaksi', 'sisa_tagihan')) {
                $table->decimal('sisa_tagihan', 15, 2)->default(0)->after('total_dibayar');
            }
            if (!Schema::hasColumn('transaksi', 'uang_diterima')) {
                $table->decimal('uang_diterima', 15, 2)->default(0)->after('sisa_tagihan');
            }
            if (!Schema::hasColumn('transaksi', 'kembalian')) {
                $table->decimal('kembalian', 15, 2)->default(0)->after('uang_diterima');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropColumn(['total_tagihan', 'total_dibayar', 'sisa_tagihan', 'uang_diterima', 'kembalian']);
        });
    }
};
