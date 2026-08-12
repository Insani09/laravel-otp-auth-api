<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\RegionController;

Route::get('/provinces', [RegionController::class, 'getProvinces']);
Route::get('/regencies/{province_id}', [RegionController::class, 'getRegencies']);
Route::get('/districts/{regency_id}', [RegionController::class, 'getDistricts']);
Route::get('/geo/countries', [RegionController::class, 'getCountries']);
Route::get('/geo/subdivisions/{countryCode}', [RegionController::class, 'getSubdivisions']);
Route::get('/geo/cities/{countryCode}/{adminCode1}', [RegionController::class, 'getCities']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'loginPassword']);
Route::post('/otp/send', [AuthController::class, 'sendOtp']);
Route::post('/otp/verify', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', function (Request $request) {
        return response()->json([
            'message' => 'Selamat datang di Dashboard!',
            'user' => $request->user(),
        ]);
    });
});
