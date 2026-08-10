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
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Berapa menit OTP login berlaku. Sesuai requirement tugas: 1 menit.
     * (Catatan: kalau nanti dikeluhkan OTP keburu expired sebelum email
     * sempat dibaca, ini satu-satunya angka yang perlu diubah.)
     */
    private const OTP_TTL_MINUTES = 1;

    // Aturan validasi password, dipakai bareng di register() & resetPassword()
    // supaya kekuatan password konsisten di semua alur (sebelumnya register()
    // cuma mensyaratkan min:12 tanpa syarat huruf+angka).
    private const PASSWORD_RULES = [
        'required',
        'string',
        'min:12',
        'regex:/[A-Za-z]/',
        'regex:/\d/',
        'not_regex:/\s/',
        'confirmed',
    ];

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => self::PASSWORD_RULES,
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

        // Throttle key digabung IP + email (sebelumnya cuma IP), supaya:
        // - user lain di jaringan/WiFi yang sama tidak saling membatasi
        // - penyerang tidak bisa spam OTP ke satu akun korban dari banyak IP
        $throttleKey = 'otp-login:' . $request->ip() . '|' . strtolower($request->email);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan OTP. Coba lagi nanti.'
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Email atau password salah.'
            ], 401);
        }

        $this->generateAndSendOtp($user, $request->boolean('remember_me'));

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

        $throttleKey = 'otp-passwordless:' . $request->ip() . '|' . strtolower($request->email);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan OTP. Coba lagi nanti.'
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        $user = User::where('email', $request->email)->first();

        $this->generateAndSendOtp($user, $request->boolean('remember_me'));

        return response()->json([
            'message' => 'Kode OTP untuk masuk tanpa password telah dikirim ke email Anda.'
        ]);
    }

    /**
     * Logika bersama untuk membuat & mengirim OTP login.
     * Sebelumnya method ini diduplikasi persis di requestOtp() dan
     * requestPasswordlessOtp() — digabung supaya sekali ubah (mis. TTL,
     * hashing) langsung berlaku di kedua alur.
     *
     * OTP disimpan dalam bentuk HASH (bukan plaintext) di kolom `code`,
     * supaya kalau database bocor, kode OTP aktif tidak langsung terbaca.
     */
    private function generateAndSendOtp(User $user, bool $rememberMe): void
    {
        LoginOtp::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->delete();

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        LoginOtp::create([
            'user_id' => $user->id,
            'code' => $this->hashOtp($otp),
            'remember_me' => $rememberMe,
            'expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ]);

        Mail::to($user->email)->send(new OtpMail($otp, self::OTP_TTL_MINUTES));
    }

    private function hashOtp(string $otp): string
    {
        // HMAC dengan APP_KEY supaya tidak bisa di-brute-force offline
        // lewat rainbow table sederhana (beda dari sekadar sha256 polos).
        return hash_hmac('sha256', $otp, config('app.key'));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $throttleKey = 'otp-reset-request:' . $request->email;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan reset password. Coba lagi nanti.'
            ], 429);
        }
        RateLimiter::hit($throttleKey, 900);

        // Hapus OTP reset lama sebelum membuat yang baru
        Cache::forget('otp_reset_' . $request->email);

        $otp = (string) random_int(100000, 999999);

        // Simpan hash-nya saja di cache, bukan OTP plaintext.
        Cache::put('otp_reset_' . $request->email, $this->hashOtp($otp), now()->addMinutes(15));

        Mail::to($request->email)->send(new OtpMail($otp, 15));

        return response()->json([
            'message' => 'Kode OTP untuk reset password telah dikirim ke email Anda.'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
            'password' => self::PASSWORD_RULES,
        ]);

        $throttleKey = 'otp-reset-verify:' . $request->email;
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan OTP. Coba lagi nanti.'
            ], 429);
        }

        // Ambil hash OTP dari cache, lalu bandingkan dengan hash input user
        $cachedHash = Cache::get('otp_reset_' . $request->email);

        if (!$cachedHash || !hash_equals($cachedHash, $this->hashOtp((string) $request->otp))) {
            RateLimiter::hit($throttleKey, 900);
            return response()->json([
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.'
            ], 400);
        }

        RateLimiter::clear($throttleKey);

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

        // BUG UTAMA sebelumnya: rate limit di sini cuma di-hit(), tidak
        // pernah dicek tooManyAttempts() — jadi brute-force 6 digit OTP
        // tidak pernah benar-benar diblokir. Ditambahkan pengecekan di sini.
        $throttleKey = 'otp-verify:' . strtolower($email);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan OTP. Coba lagi nanti.'
            ], 429);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            RateLimiter::hit($throttleKey, 900);
            return response()->json([
                'message' => 'Email tidak ditemukan.'
            ], 404);
        }

        $loginOtp = LoginOtp::where('user_id', $user->id)
            ->where('code', $this->hashOtp($otp))
            ->whereNull('used_at')
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$loginOtp) {
            RateLimiter::hit($throttleKey, 900);
            return response()->json([
                'message' => 'Kode OTP tidak valid atau telah kedaluwarsa.'
            ], 401);
        }

        RateLimiter::clear($throttleKey);
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

        \Laravel\Sanctum\PersonalAccessToken::whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->delete();

        LoginOtp::whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->whereNull('used_at')
            ->delete();
    }
}