<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DataNotaController;
use App\Http\Controllers\Api\KasirController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\LaporanController;

/*
|--------------------------------------------------------------------------
| SANGGARA REST API Routes (Laravel 13)
|--------------------------------------------------------------------------
*/

// Health check endpoint
Route::get('/ping', function () {
    return response()->json([
        'status' => 'online',
        'system' => 'Sanggara Management System API (Laravel 13)',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Dashboard & Analytics
Route::get('/dashboard', [DashboardController::class, 'index']);

// Data Nota Transaksi Kasir
Route::get('/nota', [DataNotaController::class, 'index']);
Route::get('/nota/{id}', [DataNotaController::class, 'show']);
Route::post('/nota/bayar', [DataNotaController::class, 'bayar']);

// Kasir (POS)
Route::get('/kasir/produk', [KasirController::class, 'getProduk']);
Route::post('/kasir/checkout', [KasirController::class, 'checkout']);

// Master Data: Produk, Pelanggan, Kategori
Route::get('/produk', [ProdukController::class, 'index']);
Route::get('/produk/{id}', [ProdukController::class, 'show']);
Route::put('/produk/{id}', [ProdukController::class, 'update']);
Route::post('/produk/{id}', [ProdukController::class, 'update']); // fallback for multipart/form
Route::delete('/produk/{id}', [ProdukController::class, 'destroy']);
Route::get('/pelanggan', [PelangganController::class, 'index']);
Route::get('/kategori', [KategoriController::class, 'index']);

// Laporan Keuangan
Route::get('/laporan/pemasukan', [LaporanController::class, 'pemasukan']);
Route::get('/laporan/pengeluaran', [LaporanController::class, 'pengeluaran']);
