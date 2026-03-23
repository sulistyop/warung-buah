<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Warung Buah API',
    description: 'API Documentation untuk aplikasi Warung Buah - Sistem manajemen transaksi buah dengan fitur lengkap untuk supplier, produk, kategori, transaksi, dan pembayaran.',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'support@warungbuah.com'
    )
)]
#[OA\Server(
    url: '/api',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Masukkan token yang didapat dari endpoint login'
)]
#[OA\Tag(name: 'Auth', description: 'Autentikasi dan manajemen user')]
#[OA\Tag(name: 'Transaksi', description: 'Manajemen transaksi penjualan')]
#[OA\Tag(name: 'Produk', description: 'Manajemen master produk')]
#[OA\Tag(name: 'Supplier', description: 'Manajemen master supplier')]
#[OA\Tag(name: 'Kategori', description: 'Manajemen master kategori')]
#[OA\Tag(name: 'Pembayaran', description: 'Manajemen pembayaran dan piutang')]
#[OA\Tag(name: 'Settings', description: 'Pengaturan aplikasi')]
#[OA\Tag(name: 'BarangDatang', description: 'Manajemen penerimaan barang / stock in dari supplier')]
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Return success response
     */
    protected function success($data = null, string $message = 'Success', int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return error response
     */
    protected function error(string $message = 'Error', int $code = 400, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
