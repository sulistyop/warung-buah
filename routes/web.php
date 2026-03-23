<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\KategoriController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    Route::get('/', fn() => redirect()->route('transaksi.index'));

    // Transaksi
    Route::get('/transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
    Route::get('/transaksi/buat', [TransaksiController::class, 'create'])->name('transaksi.create');
    Route::post('/transaksi', [TransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/{transaksi}', [TransaksiController::class, 'show'])->name('transaksi.show');
    Route::delete('/transaksi/{transaksi}', [TransaksiController::class, 'destroy'])->name('transaksi.destroy');

    // Master Produk
    Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
    Route::get('/produk/buat', [ProdukController::class, 'create'])->name('produk.create');
    Route::post('/produk', [ProdukController::class, 'store'])->name('produk.store');
    Route::get('/produk/{produk}/edit', [ProdukController::class, 'edit'])->name('produk.edit');
    Route::put('/produk/{produk}', [ProdukController::class, 'update'])->name('produk.update');
    Route::delete('/produk/{produk}', [ProdukController::class, 'destroy'])->name('produk.destroy');
    Route::get('/api/produk/search', [ProdukController::class, 'search'])->name('produk.search');

    // Master Supplier
    Route::get('/supplier', [SupplierController::class, 'index'])->name('supplier.index');
    Route::get('/supplier/buat', [SupplierController::class, 'create'])->name('supplier.create');
    Route::post('/supplier', [SupplierController::class, 'store'])->name('supplier.store');
    Route::get('/supplier/{supplier}/edit', [SupplierController::class, 'edit'])->name('supplier.edit');
    Route::put('/supplier/{supplier}', [SupplierController::class, 'update'])->name('supplier.update');
    Route::delete('/supplier/{supplier}', [SupplierController::class, 'destroy'])->name('supplier.destroy');


    // Master Kategori
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
    Route::get('/kategori/buat', [KategoriController::class, 'create'])->name('kategori.create');
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::get('/kategori/{kategori}/edit', [KategoriController::class, 'edit'])->name('kategori.edit');
    Route::put('/kategori/{kategori}', [KategoriController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{kategori}', [KategoriController::class, 'destroy'])->name('kategori.destroy');
    Route::get('/api/kategori/search', [KategoriController::class, 'search'])->name('kategori.search');

    // Pembayaran / Piutang
    Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
    Route::get('/pembayaran/{transaksi}', [PembayaranController::class, 'show'])->name('pembayaran.show');
    Route::get('/pembayaran/{transaksi}/bayar', [PembayaranController::class, 'create'])->name('pembayaran.create');
    Route::post('/pembayaran/{transaksi}', [PembayaranController::class, 'store'])->name('pembayaran.store');
    Route::delete('/pembayaran/hapus/{pembayaran}', [PembayaranController::class, 'destroy'])->name('pembayaran.destroy');

    // Settings (admin only)
    Route::middleware('can:admin')->group(function () {
        Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    });
});
