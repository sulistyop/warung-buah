<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->decimal('kuli_per_peti', 10, 2)->default(0)->after('komisi_persen')
                ->comment('Biaya kuli per peti untuk supplier ini, misal 2000 = Rp 2.000/peti');
        });
    }

    public function down(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->dropColumn('kuli_per_peti');
        });
    }
};
