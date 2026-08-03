<?php
// Halaman utama untuk login OTP
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth');
});

// Halaman dashboard setelah berhasil login
Route::get('/dashboard', function () {
    return view('dashboard');
});
