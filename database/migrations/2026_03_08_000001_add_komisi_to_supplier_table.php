<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->decimal('komisi_persen', 5, 2)->default(0)->after('catatan')
                ->comment('Persentase komisi supplier, misal 7 = 7%');
        });
    }

    public function down(): void
    {
        Schema::table('supplier', function (Blueprint $table) {
            $table->dropColumn('komisi_persen');
        });
    }
};
