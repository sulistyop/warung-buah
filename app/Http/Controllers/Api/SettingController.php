<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SettingController extends Controller
{
    /**
     * Get all settings
     */
    #[OA\Get(
        path: '/settings',
        summary: 'Get semua pengaturan',
        description: 'Dapatkan semua pengaturan aplikasi',
        operationId: 'getSettings',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'komisi_persen', type: 'number', example: 10),
                                new OA\Property(property: 'nama_toko', type: 'string', example: 'Warung Buah Segar'),
                                new OA\Property(property: 'alamat_toko', type: 'string', example: 'Jl. Pasar No. 123'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');

        return $this->success($settings);
    }

    /**
     * Get single setting
     */
    #[OA\Get(
        path: '/settings/{key}',
        summary: 'Get pengaturan berdasarkan key',
        description: 'Dapatkan nilai pengaturan berdasarkan key',
        operationId: 'getSettingByKey',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'key', in: 'path', required: true, description: 'Key pengaturan', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'key', type: 'string', example: 'komisi_persen'),
                                new OA\Property(property: 'value', type: 'string', example: '10'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function show(string $key)
    {
        $value = Setting::get($key);

        return $this->success([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Update settings
     */
    #[OA\Put(
        path: '/settings',
        summary: 'Update pengaturan',
        description: 'Update pengaturan aplikasi (admin only)',
        operationId: 'updateSettings',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'komisi_persen', type: 'number', example: 10),
                    new OA\Property(property: 'nama_toko', type: 'string', example: 'Warung Buah Segar'),
                    new OA\Property(property: 'alamat_toko', type: 'string', example: 'Jl. Pasar No. 123', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pengaturan berhasil disimpan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengaturan berhasil disimpan'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden - Admin only'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request)
    {
        // Check if user is admin
        if (!$request->user()->isAdmin()) {
            return $this->error('Anda tidak memiliki akses untuk mengubah pengaturan', 403);
        }

        $request->validate([
            'komisi_persen' => 'sometimes|required|numeric|min:0|max:100',
            'nama_toko'     => 'sometimes|required|string|max:255',
            'alamat_toko'   => 'nullable|string|max:500',
        ]);

        if ($request->has('komisi_persen')) {
            Setting::set('komisi_persen', $request->komisi_persen);
        }

        if ($request->has('nama_toko')) {
            Setting::set('nama_toko', $request->nama_toko);
        }

        if ($request->has('alamat_toko')) {
            Setting::set('alamat_toko', $request->alamat_toko);
        }

        return $this->success(null, 'Pengaturan berhasil disimpan');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRINTER SETTINGS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get printer settings
     */
    #[OA\Get(
        path: '/settings/printer',
        summary: 'Get pengaturan printer',
        description: 'Ambil konfigurasi printer Bluetooth yang tersimpan di server. Digunakan Flutter untuk restore setting printer terakhir.',
        operationId: 'getPrinterSettings',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'printer_address', type: 'string', example: '00:11:22:33:44:55', nullable: true, description: 'MAC address Bluetooth printer'),
                                new OA\Property(property: 'printer_nama', type: 'string', example: 'RPP02N', nullable: true, description: 'Nama / label printer yang dipilih user'),
                                new OA\Property(property: 'printer_lebar_kertas', type: 'integer', example: 58, description: 'Lebar kertas dalam mm. Nilai: 58 atau 80'),
                                new OA\Property(property: 'printer_template', type: 'string', example: 'simple', description: 'Template nota: simple | detail | merchant'),
                                new OA\Property(property: 'printer_footer', type: 'string', example: 'Terima kasih sudah berbelanja!', nullable: true, description: 'Teks footer nota'),
                                new OA\Property(property: 'printer_auto_print', type: 'boolean', example: true, description: 'Auto print setelah transaksi selesai'),
                                new OA\Property(property: 'printer_copies', type: 'integer', example: 1, description: 'Jumlah salinan cetakan'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function getPrinter()
    {
        return $this->success([
            'printer_address'      => Setting::get('printer_address'),
            'printer_nama'         => Setting::get('printer_nama'),
            'printer_lebar_kertas' => (int) Setting::get('printer_lebar_kertas', 58),
            'printer_template'     => Setting::get('printer_template', 'simple'),
            'printer_footer'       => Setting::get('printer_footer', 'Terima kasih sudah berbelanja!'),
            'printer_auto_print'   => filter_var(Setting::get('printer_auto_print', 'true'), FILTER_VALIDATE_BOOLEAN),
            'printer_copies'       => (int) Setting::get('printer_copies', 1),
            'printer_font_size'    => (int) Setting::get('printer_font_size', 1),
            'printer_autocut'      => filter_var(Setting::get('printer_autocut', 'true'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Update printer settings
     */
    #[OA\Put(
        path: '/settings/printer',
        summary: 'Update pengaturan printer',
        description: 'Simpan konfigurasi printer Bluetooth ke server. Bisa dipanggil oleh semua role (bukan admin-only) karena tiap kasir bisa punya printer berbeda. Kirim hanya field yang ingin diupdate (partial update).',
        operationId: 'updatePrinterSettings',
        tags: ['Settings'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [],
                properties: [
                    new OA\Property(property: 'printer_address', type: 'string', example: '00:11:22:33:44:55', nullable: true, description: 'MAC address Bluetooth printer. Kirim null untuk hapus/disconnect.'),
                    new OA\Property(property: 'printer_nama', type: 'string', example: 'RPP02N', nullable: true, description: 'Nama printer yang tampil di UI'),
                    new OA\Property(property: 'printer_lebar_kertas', type: 'integer', example: 58, description: 'Lebar kertas: 58 atau 80 (mm)'),
                    new OA\Property(property: 'printer_template', type: 'string', example: 'simple', description: 'Template nota: simple | detail | merchant'),
                    new OA\Property(property: 'printer_footer', type: 'string', example: 'Terima kasih sudah berbelanja!', nullable: true, description: 'Teks footer di bawah nota'),
                    new OA\Property(property: 'printer_auto_print', type: 'boolean', example: true, description: 'Auto print setelah transaksi selesai'),
                    new OA\Property(property: 'printer_copies', type: 'integer', example: 1, description: 'Jumlah salinan cetakan (1-5)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pengaturan printer berhasil disimpan',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Pengaturan printer berhasil disimpan'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'printer_address', type: 'string', example: '00:11:22:33:44:55', nullable: true),
                                new OA\Property(property: 'printer_nama', type: 'string', example: 'RPP02N', nullable: true),
                                new OA\Property(property: 'printer_lebar_kertas', type: 'integer', example: 58),
                                new OA\Property(property: 'printer_template', type: 'string', example: 'simple'),
                                new OA\Property(property: 'printer_footer', type: 'string', example: 'Terima kasih sudah berbelanja!', nullable: true),
                                new OA\Property(property: 'printer_auto_print', type: 'boolean', example: true),
                                new OA\Property(property: 'printer_copies', type: 'integer', example: 1),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updatePrinter(Request $request)
    {
        $request->validate([
            'printer_address'      => 'sometimes|nullable|string|max:17|regex:/^([0-9A-Fa-f]{2}[:\-]){5}([0-9A-Fa-f]{2})$/',
            'printer_nama'         => 'sometimes|nullable|string|max:100',
            'printer_lebar_kertas' => 'sometimes|required|integer|in:58,80',
            'printer_template'     => 'sometimes|required|string|in:simple,detail,merchant',
            'printer_footer'       => 'sometimes|nullable|string|max:200',
            'printer_auto_print'   => 'sometimes|required|boolean',
            'printer_copies'       => 'sometimes|required|integer|min:1|max:5',
            'printer_font_size'    => 'sometimes|required|integer|in:1,2,3',
            'printer_autocut'      => 'sometimes|required|boolean',
        ]);

        $fields = [
            'printer_address',
            'printer_nama',
            'printer_lebar_kertas',
            'printer_template',
            'printer_footer',
            'printer_auto_print',
            'printer_copies',
            'printer_font_size',
            'printer_autocut',
        ];

        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = $request->$field;
                // Simpan boolean sebagai string agar konsisten dengan key-value store
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                Setting::set($field, $value);
            }
        }

        return $this->success([
            'printer_address'      => Setting::get('printer_address'),
            'printer_nama'         => Setting::get('printer_nama'),
            'printer_lebar_kertas' => (int) Setting::get('printer_lebar_kertas', 58),
            'printer_template'     => Setting::get('printer_template', 'simple'),
            'printer_footer'       => Setting::get('printer_footer', 'Terima kasih sudah berbelanja!'),
            'printer_auto_print'   => filter_var(Setting::get('printer_auto_print', 'true'), FILTER_VALIDATE_BOOLEAN),
            'printer_copies'       => (int) Setting::get('printer_copies', 1),
            'printer_font_size'    => (int) Setting::get('printer_font_size', 1),
            'printer_autocut'      => filter_var(Setting::get('printer_autocut', 'true'), FILTER_VALIDATE_BOOLEAN),
        ], 'Pengaturan printer berhasil disimpan');
    }

    /**
     * Get app info
     */
    #[OA\Get(
        path: '/settings/app-info',
        summary: 'Get info aplikasi',
        description: 'Dapatkan informasi aplikasi untuk ditampilkan di Flutter',
        operationId: 'getAppInfo',
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'nama_toko', type: 'string', example: 'Warung Buah Segar'),
                                new OA\Property(property: 'alamat_toko', type: 'string', example: 'Jl. Pasar No. 123'),
                                new OA\Property(property: 'api_version', type: 'string', example: '1.0.0'),
                            ]
                        ),
                    ]
                )
            ),
        ]
    )]
    public function appInfo()
    {
        return $this->success([
            'nama_toko' => Setting::get('nama_toko', 'Warung Buah'),
            'alamat_toko' => Setting::get('alamat_toko', ''),
            'api_version' => '1.0.0',
        ]);
    }
}
