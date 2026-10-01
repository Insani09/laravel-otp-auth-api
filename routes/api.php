<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/provinces', [RegionController::class, 'getProvinces']);
Route::get('/regencies/{province_id}', [RegionController::class, 'getRegencies']);
Route::get('/districts/{regency_id}', [RegionController::class, 'getDistricts']);

// GeoNames adalah layanan eksternal berkuota: tiap endpoint dilindungi throttle
// per-IP supaya tidak bisa dipakai untuk membanjiri request ke pihak ketiga.
Route::middleware('throttle:geonames')->group(function () {
    Route::get('/geo/countries', [RegionController::class, 'getCountries']);
    Route::get('/geo/subdivisions/{countryCode}', [RegionController::class, 'getSubdivisions']);
    Route::get('/geo/cities/{countryCode}/{adminCode1}', [RegionController::class, 'getCities']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'loginPassword']);
Route::post('/otp/send', [AuthController::class, 'sendOtp']);
Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Data sesi untuk guard vue-router + header aplikasi SPA.
    Route::get('/user', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'negara' => $user->negara,
                'negara_kode' => $user->negara_kode,
                'provinsi' => $user->provinsi,
                'provinsi_id' => $user->provinsi_id,
                'kota' => $user->kota,
                'kota_id' => $user->kota_id,
                'kecamatan' => $user->kecamatan,
                'kecamatan_id' => $user->kecamatan_id,
                'avatar_url' => $user->avatarUrl(),
            ],
        ]);
    });

    // Profil SPA (JSON) — memakai ulang logic ProfileController.
    Route::post('/profile', [ProfileController::class, 'update'])->name('api.profile.update');
    Route::put('/profile/password', [ProfileController::class, 'changePassword'])->name('api.profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('api.profile.destroy');

    // Admin CRUD (JSON) — dipakai halaman admin SPA.
    Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function () {
        Route::get('/users', [AdminController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [AdminController::class, 'show'])->name('users.show');
        // Multipart file upload dikirim sebagai POST + _method=PUT (supaya $_FILES
        // terisi); Laravel method spoofing mencocokkannya ke rute PUT di sini.
        Route::put('/users/{user}', [AdminController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminController::class, 'destroy'])->name('users.destroy');
    });

    Route::get('/dashboard', function (Request $request) {
        return response()->json([
            'message' => 'Selamat datang di Dashboard!',
            'user' => $request->user(),
        ]);
    });
});
