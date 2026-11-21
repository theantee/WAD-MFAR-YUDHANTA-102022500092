<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Katalog
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/produk/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('product.show');

// Keranjang (disimpan di sesi, tidak perlu login)
Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
Route::post('/keranjang/tambah', [CartController::class, 'add'])->name('cart.add');
Route::post('/keranjang/ubah', [CartController::class, 'update'])->name('cart.update');

// Akun
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1');
});
Route::post('/keluar', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Checkout dan pesanan (wajib login)
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/ongkir', [CheckoutController::class, 'quote'])->name('shipping.quote');

    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/berhasil', [OrderController::class, 'success'])->name('orders.success');
});
