<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\SettingController;
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

    // Settings (admin only)
    Route::middleware('can:admin')->group(function () {
        Route::get('/pengaturan', [SettingController::class, 'index'])->name('settings.index');
        Route::post('/pengaturan', [SettingController::class, 'update'])->name('settings.update');
    });
});
