<?php

use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — SPA only
|--------------------------------------------------------------------------
| Laravel tidak lagi menyajikan halaman Blade. Seluruh UI hidup di Vue SPA
| (resources/js) yang dilayani lewat satu shell HTML di bawah. Web routes
| hanya berisi:
|   1. Endpoint auth berbasis session (dipakai form login/register SPA),
|   2. Logout web + /api/me ringan untuk sinkronisasi sesi,
|   3. Catch-all yang menyajikan shell SPA untuk semua path non-API.
*/

// Auth berbasis session (SPA memanggil ini lewat axios dengan cookie XSRF).
Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/login', [AuthController::class, 'loginPassword'])->name('auth.login');
Route::post('/auth/otp/send', [AuthController::class, 'sendOtp'])->name('auth.otp.send');
Route::post('/auth/otp/verify', [AuthController::class, 'verifyOtp'])->name('auth.otp.verify');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset');

Route::middleware('auth')->group(function () {
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logout berhasil.']);
    })->name('logout');

    // Profil sesi ringan dari session web (tanpa token Sanctum).
    Route::get('/api/me', function (Request $request) {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    })->name('me');
});

/*
| Catch-all SPA: semua path non-API menyajikan shell Vue. Negative lookahead
| '(?!api)' mencegah catch-all menelan /api/* dari routes/api.php (web routes
| terdaftar lebih dulu daripada api routes di Laravel).
*/
Route::get('/{any?}', function () {
    return view('spa');
})->where('any', '^(?!api).*$')->name('spa');
