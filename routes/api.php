<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/request-passwordless-otp', [AuthController::class, 'requestPasswordlessOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/dashboard', function (Request $request) {
    return response()->json([
        'message' => 'Selamat datang di Dashboard!',
        'user' => $request->user(),
    ]);
});
