<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── Identitas Usaha ───────────────────────────────────────────────
            [
                'key'   => 'nama_usaha',
                'value' => 'Lestari Buah',
                'label' => 'Nama Usaha',
                'type'  => 'text',
            ],
            [
                'key'   => 'alamat_usaha',
                'value' => 'Kios Blok C.11 dan D.09, Pasar Induk Buah, Gamping, Sleman, Yogyakarta',
                'label' => 'Alamat Usaha',
                'type'  => 'text',
            ],
            [
                'key'   => 'telepon_usaha',
                'value' => '0274-617423 / 087839461614',
                'label' => 'Telepon Usaha',
                'type'  => 'text',
            ],
            [
                'key'   => 'pemilik',
                'value' => 'NY. Sudarmi',
                'label' => 'Nama Pemilik',
                'type'  => 'text',
            ],

            // ── Keuangan ──────────────────────────────────────────────────────
            [
                'key'   => 'komisi_persen',
                'value' => '7',
                'label' => 'Komisi Default (%)',
                'type'  => 'percentage',
            ],
            [
                'key'   => 'kuli_per_peti',
                'value' => '2000',
                'label' => 'Biaya Kuli per Peti (Rp)',
                'type'  => 'number',
            ],

            // ── Printer Bluetooth ─────────────────────────────────────────────
            [
                'key'   => 'printer_address',
                'value' => null,
                'label' => 'MAC Address Printer Bluetooth',
                'type'  => 'text',
            ],
            [
                'key'   => 'printer_nama',
                'value' => null,
                'label' => 'Nama Printer',
                'type'  => 'text',
            ],
            [
                'key'   => 'printer_lebar_kertas',
                'value' => '58',
                'label' => 'Lebar Kertas Printer (mm)',
                'type'  => 'number',
            ],
            [
                'key'   => 'printer_template',
                'value' => 'simple',
                'label' => 'Template Nota',
                'type'  => 'select',  // simple | detail | merchant
            ],
            [
                'key'   => 'printer_footer',
                'value' => 'Terima kasih sudah berbelanja!',
                'label' => 'Teks Footer Nota',
                'type'  => 'text',
            ],
            [
                'key'   => 'printer_auto_print',
                'value' => 'false',
                'label' => 'Auto Print setelah Transaksi',
                'type'  => 'boolean',
            ],
            [
                'key'   => 'printer_copies',
                'value' => '1',
                'label' => 'Jumlah Salinan Cetakan',
                'type'  => 'number',
            ],
        ];

        foreach ($settings as $s) {
            DB::table('settings')->updateOrInsert(
                ['key' => $s['key']],
                array_merge($s, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // Hapus setting printer lama berbasis IP yang sudah tidak dipakai
        DB::table('settings')->whereIn('key', [
            'printer_ip',
            'printer_port',
            'printer_width',
        ])->delete();
    }
}
