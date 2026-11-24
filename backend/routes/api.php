<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DataNotaController;
use App\Http\Controllers\Api\KasirController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\KecamatanController;

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
Route::get('/analisa/pelanggan', [KecamatanController::class, 'analisa']);

// Data Nota Transaksi Kasir
Route::get('/nota', [DataNotaController::class, 'index']);
Route::get('/nota/{id}', [DataNotaController::class, 'show']);
Route::post('/nota/bayar', [DataNotaController::class, 'bayar']);

// Kasir (POS) & Booking Kalender
Route::get('/kasir/produk', [KasirController::class, 'getProduk']);
Route::get('/kasir/booked-dates', [KasirController::class, 'getBookedDates']);
Route::get('/booking/kalender', [KasirController::class, 'getBookedDates']);
Route::post('/kasir/checkout', [KasirController::class, 'checkout']);

// Booking & Pesanan Tabel
Route::get('/booking', [BookingController::class, 'index']);
Route::get('/booking/{id}', [BookingController::class, 'show']);
Route::delete('/booking/{id}', [BookingController::class, 'destroy']);
Route::get('/pesanan', [BookingController::class, 'pesanan']);

// Master Data: Produk, Pelanggan, Kategori, Kecamatan
Route::get('/kecamatan', [KecamatanController::class, 'index']);
Route::get('/produk', [ProdukController::class, 'index']);
Route::post('/produk', [ProdukController::class, 'store']);
Route::get('/produk/{id}', [ProdukController::class, 'show']);
Route::put('/produk/{id}', [ProdukController::class, 'update']);
Route::post('/produk/{id}', [ProdukController::class, 'update']); // fallback for multipart/form
Route::delete('/produk/{id}', [ProdukController::class, 'destroy']);
Route::get('/pelanggan', [PelangganController::class, 'index']);
Route::get('/pelanggan/{id}', [PelangganController::class, 'show']);
Route::post('/pelanggan', [PelangganController::class, 'store']);
Route::put('/pelanggan/{id}', [PelangganController::class, 'update']);
Route::post('/pelanggan/{id}', [PelangganController::class, 'update']); // fallback
Route::delete('/pelanggan/{id}', [PelangganController::class, 'destroy']);
Route::get('/kategori', [KategoriController::class, 'index']);
Route::get('/kategori/{id}', [KategoriController::class, 'show']);
Route::post('/kategori', [KategoriController::class, 'store']);
Route::put('/kategori/{id}', [KategoriController::class, 'update']);
Route::post('/kategori/{id}', [KategoriController::class, 'update']); // fallback
Route::delete('/kategori/{id}', [KategoriController::class, 'destroy']);

// Laporan Keuangan
Route::get('/laporan/pemasukan', [LaporanController::class, 'pemasukan']);
Route::get('/laporan/pemasukan/{id}', [LaporanController::class, 'showPemasukan']);
Route::post('/laporan/pemasukan', [LaporanController::class, 'storePemasukan']);
Route::put('/laporan/pemasukan/{id}', [LaporanController::class, 'updatePemasukan']);
Route::post('/laporan/pemasukan/{id}', [LaporanController::class, 'updatePemasukan']); // fallback
Route::delete('/laporan/pemasukan/{id}', [LaporanController::class, 'destroyPemasukan']);
Route::get('/laporan/pengeluaran', [LaporanController::class, 'pengeluaran']);
Route::get('/laporan/pengeluaran/{id}', [LaporanController::class, 'showPengeluaran']);
Route::post('/laporan/pengeluaran', [LaporanController::class, 'storePengeluaran']);
Route::put('/laporan/pengeluaran/{id}', [LaporanController::class, 'updatePengeluaran']);
Route::post('/laporan/pengeluaran/{id}', [LaporanController::class, 'updatePengeluaran']); // fallback
Route::delete('/laporan/pengeluaran/{id}', [LaporanController::class, 'destroyPengeluaran']);
