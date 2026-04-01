<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\PembayaranController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\BarangDatangController;
use App\Http\Controllers\Api\RekapController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\DepositController;
use App\Http\Controllers\Api\PiutangController;
use App\Http\Controllers\Api\PreOrderController;
use App\Http\Controllers\Api\NotaController;
use App\Http\Controllers\Api\KasLaciController;
use App\Http\Controllers\Api\LogAktivitasController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\LaporanController;
use Illuminate\Support\Facades\Route;

// Root API endpoint
Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Warung Buah API',
        'version' => '2.0.0',
    ]);
});

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/settings/app-info', [SettingController::class, 'appInfo']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ─────────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });

    // ── User Management (admin only) ──────────────────────────────────────────
    Route::prefix('users')->group(function () {
        Route::get('/', [UserManagementController::class, 'index']);
        Route::post('/', [UserManagementController::class, 'store']);
        Route::put('/{id}', [UserManagementController::class, 'update']);
        Route::delete('/{id}', [UserManagementController::class, 'destroy']);
    });

    // ── Pelanggan ─────────────────────────────────────────────────────────────
    Route::prefix('pelanggan')->group(function () {
        Route::get('/all', [PelangganController::class, 'all'])->middleware('permission:pelanggan,read');
        Route::get('/', [PelangganController::class, 'index'])->middleware('permission:pelanggan,read');
        Route::post('/', [PelangganController::class, 'store'])->middleware('permission:pelanggan,create');
        Route::get('/{id}', [PelangganController::class, 'show'])->middleware('permission:pelanggan,read');
        Route::put('/{id}', [PelangganController::class, 'update'])->middleware('permission:pelanggan,update');
        Route::delete('/{id}', [PelangganController::class, 'destroy'])->middleware('permission:pelanggan,delete');
    });

    // ── Deposit ───────────────────────────────────────────────────────────────
    Route::prefix('deposit')->group(function () {
        Route::get('/', [DepositController::class, 'index'])->middleware('permission:deposit,read');
        Route::post('/', [DepositController::class, 'store'])->middleware('permission:deposit,create');
        Route::post('/bayar-piutang', [DepositController::class, 'bayarPiutang'])->middleware('permission:deposit,create');
    });

    // ── Piutang ───────────────────────────────────────────────────────────────
    Route::prefix('piutang')->group(function () {
        Route::get('/', [PiutangController::class, 'index'])->middleware('permission:piutang,read');
        Route::post('/bayar', [PiutangController::class, 'bayar'])->middleware('permission:piutang,update');
        Route::get('/rekap-pelanggan', [PiutangController::class, 'rekapPelanggan'])->middleware('permission:piutang,read');
    });

    // ── Transaksi (Penjualan) ─────────────────────────────────────────────────
    Route::prefix('transaksi')->group(function () {
        Route::get('/', [TransaksiController::class, 'index'])->middleware('permission:transaksi,read');
        Route::get('/form-data', [TransaksiController::class, 'formData'])->middleware('permission:transaksi,read');
        Route::get('/statistics', [TransaksiController::class, 'statistics'])->middleware('permission:transaksi,read');
        Route::post('/', [TransaksiController::class, 'store'])->middleware('permission:transaksi,create');
        Route::get('/{id}', [TransaksiController::class, 'show'])->middleware('permission:transaksi,read');
        Route::put('/{id}', [TransaksiController::class, 'update'])->middleware('permission:transaksi,update');
        Route::post('/{id}/komplain', [TransaksiController::class, 'saveKomplain'])->middleware('permission:transaksi,update');
        Route::delete('/{id}', [TransaksiController::class, 'destroy'])->middleware('permission:transaksi,delete');
    });

    // ── Pembayaran ────────────────────────────────────────────────────────────
    Route::prefix('pembayaran')->group(function () {
        Route::get('/', [PembayaranController::class, 'index'])->middleware('permission:transaksi,read');
        Route::get('/summary', [PembayaranController::class, 'summary'])->middleware('permission:transaksi,read');
        Route::get('/metode-options', [PembayaranController::class, 'metodeOptions'])->middleware('permission:transaksi,read');
        Route::get('/transaksi/{transaksi_id}', [PembayaranController::class, 'show'])->middleware('permission:transaksi,read');
        Route::post('/transaksi/{transaksi_id}', [PembayaranController::class, 'store'])->middleware('permission:transaksi,create');
        Route::delete('/{id}', [PembayaranController::class, 'destroy'])->middleware('permission:transaksi,delete');
    });

    // ── Supplier ──────────────────────────────────────────────────────────────
    Route::prefix('supplier')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->middleware('permission:supplier,read');
        Route::get('/search', [SupplierController::class, 'search'])->middleware('permission:supplier,read');
        Route::post('/', [SupplierController::class, 'store'])->middleware('permission:supplier,create');
        Route::get('/{id}', [SupplierController::class, 'show'])->middleware('permission:supplier,read');
        Route::put('/{id}', [SupplierController::class, 'update'])->middleware('permission:supplier,update');
        Route::delete('/{id}', [SupplierController::class, 'destroy'])->middleware('permission:supplier,delete');
    });

    // ── Kategori ──────────────────────────────────────────────────────────────
    Route::prefix('kategori')->group(function () {
        Route::get('/', [KategoriController::class, 'index'])->middleware('permission:kategori,read');
        Route::get('/all', [KategoriController::class, 'all'])->middleware('permission:kategori,read');
        Route::get('/search', [KategoriController::class, 'search'])->middleware('permission:kategori,read');
        Route::get('/warna-options', [KategoriController::class, 'warnaOptions'])->middleware('permission:kategori,read');
        Route::post('/', [KategoriController::class, 'store'])->middleware('permission:kategori,create');
        Route::get('/{id}', [KategoriController::class, 'show'])->middleware('permission:kategori,read');
        Route::put('/{id}', [KategoriController::class, 'update'])->middleware('permission:kategori,update');
        Route::delete('/{id}', [KategoriController::class, 'destroy'])->middleware('permission:kategori,delete');
    });

    // ── Barang Datang (Stock In) ──────────────────────────────────────────────
    Route::prefix('barang-datang')->group(function () {
        Route::get('/', [BarangDatangController::class, 'index'])->middleware('permission:barang_datang,read');
        Route::get('/stok-tersedia', [BarangDatangController::class, 'stokTersedia'])->middleware('permission:barang_datang,read');
        Route::get('/letter-terpakai', [BarangDatangController::class, 'letterTerpakai'])->middleware('permission:barang_datang,read');
        Route::post('/', [BarangDatangController::class, 'store'])->middleware('permission:barang_datang,create');
        Route::get('/{id}', [BarangDatangController::class, 'show'])->middleware('permission:barang_datang,read');
        Route::put('/{id}', [BarangDatangController::class, 'update'])->middleware('permission:barang_datang,update');
        Route::delete('/{id}', [BarangDatangController::class, 'destroy'])->middleware('permission:barang_datang,delete');
        Route::post('/{id}/confirm', [BarangDatangController::class, 'confirm'])->middleware('permission:barang_datang,update');
        Route::get('/{id}/transaksi', [BarangDatangController::class, 'transaksi'])->middleware('permission:barang_datang,read');
    });

    // ── Rekap Harian Supplier ─────────────────────────────────────────────────
    Route::prefix('rekap')->group(function () {
        Route::get('/', [RekapController::class, 'index'])->middleware('permission:rekap,read');
        Route::post('/', [RekapController::class, 'store'])->middleware('permission:rekap,create');
        Route::get('/siap-direkap', [RekapController::class, 'siapDirekap'])->middleware('permission:rekap,read');
        Route::get('/cek-siap/{supplier_id}/{tanggal}', [RekapController::class, 'cekSiapRekap'])->middleware('permission:rekap,read');
        Route::get('/suggestion/{supplier_id}/{tanggal}', [RekapController::class, 'suggestionRekap'])->middleware('permission:rekap,read');
        Route::get('/{id}', [RekapController::class, 'show'])->middleware('permission:rekap,read');
        Route::put('/{id}', [RekapController::class, 'update'])->middleware('permission:rekap,update');
        Route::delete('/{id}', [RekapController::class, 'destroy'])->middleware('permission:rekap,delete');
        Route::post('/{id}/final', [RekapController::class, 'finalisasi'])->middleware('permission:rekap,update');
    });

    // ── Pre Order ─────────────────────────────────────────────────────────────
    Route::prefix('pre-order')->group(function () {
        Route::get('/', [PreOrderController::class, 'index'])->middleware('permission:pre_order,read');
        Route::post('/', [PreOrderController::class, 'store'])->middleware('permission:pre_order,create');
        Route::get('/{id}', [PreOrderController::class, 'show'])->middleware('permission:pre_order,read');
        Route::get('/{id}/form-transaksi', [PreOrderController::class, 'formTransaksi'])->middleware('permission:pre_order,read');
        Route::post('/{id}/proses', [PreOrderController::class, 'proses'])->middleware('permission:pre_order,update');
        Route::post('/{id}/batal', [PreOrderController::class, 'batal'])->middleware('permission:pre_order,update');
    });

    // ── Nota (Print / PDF) ────────────────────────────────────────────────────
    Route::prefix('nota')->group(function () {
        Route::get('/transaksi/{id}', [NotaController::class, 'notaTransaksi'])->middleware('permission:transaksi,read');
        Route::get('/rekap/{id}', [NotaController::class, 'notaRekap'])->middleware('permission:rekap,read');
    });

    // ── Settings ──────────────────────────────────────────────────────────────
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::put('/', [SettingController::class, 'update']);
        Route::get('/printer', [SettingController::class, 'getPrinter']);
        Route::put('/printer', [SettingController::class, 'updatePrinter']);
        Route::get('/{key}', [SettingController::class, 'show']);
    });

    // ── Kas Laci (Cash Drawer) ────────────────────────────────────────────────
    Route::prefix('kas-laci')->group(function () {
        Route::get('/', [KasLaciController::class, 'index'])->middleware('permission:kas_laci,read');
        Route::get('/summary', [KasLaciController::class, 'summary'])->middleware('permission:kas_laci,read');
        Route::post('/', [KasLaciController::class, 'store'])->middleware('permission:kas_laci,create');
        Route::delete('/{id}', [KasLaciController::class, 'destroy'])->middleware('permission:kas_laci,delete');
    });

    // ── Log Aktivitas (admin only) ────────────────────────────────────────────
    Route::get('/log-aktivitas', [LogAktivitasController::class, 'index']);

    // ── Laporan ───────────────────────────────────────────────────────────────
    Route::prefix('laporan')->group(function () {
        Route::get('/penjualan', [LaporanController::class, 'penjualan'])->middleware('permission:transaksi,read');
        Route::get('/penjualan/export', [LaporanController::class, 'exportPenjualan'])->middleware('permission:transaksi,read');

        Route::get('/rekap-supplier', [LaporanController::class, 'rekapSupplier'])->middleware('permission:rekap,read');
        Route::get('/rekap-supplier/export', [LaporanController::class, 'exportRekapSupplier'])->middleware('permission:rekap,read');

        Route::get('/piutang', [LaporanController::class, 'piutang'])->middleware('permission:piutang,read');
        Route::get('/piutang/export', [LaporanController::class, 'exportPiutang'])->middleware('permission:piutang,read');

        Route::get('/kas-laci', [LaporanController::class, 'kasLaci'])->middleware('permission:kas_laci,read');
        Route::get('/kas-laci/export', [LaporanController::class, 'exportKasLaci'])->middleware('permission:kas_laci,read');

        Route::get('/stok-masuk', [LaporanController::class, 'stokMasuk'])->middleware('permission:barang_datang,read');
        Route::get('/stok-masuk/export', [LaporanController::class, 'exportStokMasuk'])->middleware('permission:barang_datang,read');

        Route::get('/pelanggan-terbaik', [LaporanController::class, 'pelangganTerbaik'])->middleware('permission:transaksi,read');
        Route::get('/pelanggan-terbaik/export', [LaporanController::class, 'exportPelangganTerbaik'])->middleware('permission:transaksi,read');

        Route::get('/penjualan-per-item', [LaporanController::class, 'penjualanPerItem'])->middleware('permission:transaksi,read');
        Route::get('/penjualan-per-item/export', [LaporanController::class, 'exportPenjualanPerItem'])->middleware('permission:transaksi,read');
    });
});
