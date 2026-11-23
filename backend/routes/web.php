<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Web Routes (Sanggara Management System - Laravel 13)
|--------------------------------------------------------------------------
|
| http://127.0.0.1:8000/ langsung menampilkan menu login Sanggara
|
*/

// Halaman Login Utama
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/login', [AuthController::class, 'showLoginForm']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Pintasan ke Dashboard
Route::get('/dashboard', function () {
    return redirect('/pages/dashboard.html');
});
