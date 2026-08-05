<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Models\LoginOtp;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:12|confirmed',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    // Jangan auto-login setelah registrasi — minta user login secara eksplisit
    return response()->json([
        'message' => 'Registrasi berhasil. Silakan login untuk mendapatkan token akses.'
    ], 201);
}
    public function requestOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember_me' => 'sometimes|boolean',
        ]);

        $user = User::where('email', $request->email)->first();
        $rememberMe = $request->boolean('remember_me');

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.'
            ], 401);
        }

        $otp = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginOtp::create([
            'user_id' => $user->id,
            'code' => $otp,
            'remember_me' => $rememberMe,
            'expires_at' => now()->addMinutes(1),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json([
            'message' => 'Kredensial benar. Silakan masukkan kode OTP yang telah dikirim ke email Anda.'
        ]);
    }

    public function requestPasswordlessOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'remember_me' => 'sometimes|boolean',
        ]);

        $user = User::where('email', $request->email)->first();
        $rememberMe = $request->boolean('remember_me');

        $otp = str_pad((string) rand(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginOtp::create([
            'user_id' => $user->id,
            'code' => $otp,
            'remember_me' => $rememberMe,
            'expires_at' => now()->addMinutes(1),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json([
            'message' => 'Kode OTP untuk masuk tanpa password telah dikirim ke email Anda.'
        ]);
    }

    public function forgotPassword(Request $request)
{
    $request->validate(['email' => 'required|email|exists:users,email']);

    // Generate 6 digit OTP
    $otp = rand(100000, 999999);

    // Simpan di Laravel Cache selama 15 menit
    Cache::put('otp_reset_' . $request->email, $otp, now()->addMinutes(15));

    // Kirim email (Sesuaikan dengan setup Mailable kamu)
    Mail::to($request->email)->send(new OtpMail($otp));

    return response()->json([
        'message' => 'Kode OTP untuk reset password telah dikirim ke email Anda.'
    ]);
}

public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'otp' => 'required|numeric|digits:6',
        'password' => [
        'required',
        'string',
        'min:12',
        'regex:/[A-Za-z]/', 
        'regex:/\d/',       
        'not_regex:/\s/', 
        'confirmed',
        ],
    ]);

    // Ambil OTP dari cache
    $cachedOtp = Cache::get('otp_reset_' . $request->email);

    if (!$cachedOtp || $cachedOtp != $request->otp) {
        return response()->json([
            'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'
        ], 400);
    }

    // Update password user
    $user = User::where('email', $request->email)->first();
    $user->password = Hash::make($request->password);
    $user->save();

    // Hapus OTP dari cache setelah berhasil digunakan
    Cache::forget('otp_reset_' . $request->email);

    // Revoke semua token lama agar user harus login ulang dengan password baru
    $user->tokens()->delete();

    return response()->json([
        'message' => 'Password berhasil diubah. Silakan login kembali.'
    ]);
}

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email', 
            'otp' => 'required|digits:6'
        ]);

        $email = $request->email;
        $otp = $request->otp;

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Email tidak ditemukan.'
            ], 404);
        }

        $loginOtp = LoginOtp::where('user_id', $user->id)
            ->where('code', $otp)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$loginOtp) {
            return response()->json([
                'message' => 'Kode OTP tidak valid atau telah kedaluwarsa.'
            ], 401);
        }

        $loginOtp->update(['used_at' => now()]);

        $expiresAt = $loginOtp->remember_me
            ? now()->addDays(30)
            : now()->addHours(8);

        $token = $user->createToken('auth_token', ['*'], $expiresAt)->plainTextToken;

        $this->cleanupExpiredTokens();

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.'
        ]);
    }

    private function cleanupExpiredTokens(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $expired = \Laravel\Sanctum\PersonalAccessToken::whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->delete();

        LoginOtp::whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->whereNull('used_at')
            ->delete();
    }
}