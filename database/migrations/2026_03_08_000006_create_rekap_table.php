<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * REKAP: rekap harian per supplier.
         * Dibuat otomatis saat semua produk dari supplier pada tanggal yang sama sudah habis.
         *
         * Formula:
         *   total_kotor   = sum(berat_bersih * harga_per_kg) per semua letter
         *   total_komisi  = total_kotor * komisi_persen / 100
         *   total_kuli    = kuli_per_peti * total_peti
         *   total_ongkos  = input bebas
         *   total_busuk   = sum(jumlah_bs * harga_ganti) per komplain
         *   pendapatan_bersih = total_kotor - total_komisi - total_kuli - total_ongkos
         *   sisa          = pendapatan_bersih - total_busuk
         */
        Schema::create('rekap', function (Blueprint $table) {
            $table->id();
            $table->string('kode_rekap')->unique();                          // RKP-YYYYMMDD-NNNN
            $table->foreignId('supplier_id')->constrained('supplier')->cascadeOnDelete();
            $table->date('tanggal');
            $table->decimal('komisi_persen', 5, 2)->default(0);              // snapshot komisi saat rekap
            $table->decimal('kuli_per_peti', 10, 2)->default(0);             // config kuli per peti (dari setting)
            $table->integer('total_peti')->default(0);                       // total seluruh peti
            $table->decimal('total_kotor', 15, 2)->default(0);
            $table->decimal('total_komisi', 15, 2)->default(0);
            $table->decimal('total_kuli', 15, 2)->default(0);
            $table->decimal('total_ongkos', 15, 2)->default(0);              // ongkos bebas input
            $table->text('keterangan_ongkos')->nullable();
            $table->decimal('total_busuk', 15, 2)->default(0);               // total nilai komplain BS
            $table->decimal('pendapatan_bersih', 15, 2)->default(0);         // kotor - komisi - kuli - ongkos
            $table->decimal('sisa', 15, 2)->default(0);                      // pendapatan_bersih - busuk
            $table->enum('status', ['draft', 'final'])->default('draft');
            $table->foreignId('dibuat_oleh')->constrained('users')->cascadeOnDelete();
            $table->timestamp('final_at')->nullable();
            $table->timestamps();

            $table->unique(['supplier_id', 'tanggal']);
            $table->index(['supplier_id', 'tanggal']);
        });

        /**
         * DETAIL REKAP: detail per letter/produk dalam rekap
         */
        Schema::create('detail_rekap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekap')->cascadeOnDelete();
            $table->string('nama_produk');                        // Letter A, B, C / nama buah
            $table->string('ukuran')->nullable();                 // grade A, B, C
            $table->integer('jumlah_peti');                       // total peti letter ini
            $table->decimal('total_berat_kotor', 10, 3)->default(0);
            $table->decimal('total_berat_peti', 10, 3)->default(0);  // tara peti
            $table->decimal('total_berat_bersih', 10, 3)->default(0);
            $table->decimal('harga_per_kg', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);       // berat_bersih * harga
            $table->timestamps();
        });

        /**
         * KOMPLAIN REKAP: busuk (BS) per letter dalam rekap
         */
        Schema::create('komplain_rekap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekap')->cascadeOnDelete();
            $table->string('nama_produk');                        // Letter yang dikomplain
            $table->integer('jumlah_bs');                         // jumlah BS/busuk
            $table->decimal('harga_ganti', 15, 2)->default(0);   // harga ganti rugi per kg
            $table->decimal('total', 15, 2)->default(0);          // jumlah_bs * harga_ganti (app level)
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('komplain_rekap');
        Schema::dropIfExists('detail_rekap');
        Schema::dropIfExists('rekap');
    }
};
