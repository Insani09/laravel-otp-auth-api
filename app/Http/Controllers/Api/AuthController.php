<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\User;
use App\Services\RegionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Masa berlaku kode OTP, dalam menit.
    private const OTP_TTL_MINUTES = 1;

    private const RESET_OTP_TTL_MINUTES = 5;

    private const PASSWORD_RULES = [
        'required',
        'string',
        'min:12',
        'regex:/[A-Za-z]/',
        'regex:/\d/',
        'not_regex:/\s/',
        'confirmed',
    ];

    private const PASSWORD_MESSAGES = [
        'password.required' => 'Kata sandi wajib diisi.',
        'password.min' => 'Kata sandi minimal harus 12 karakter.',
        'password.regex' => 'Kata sandi harus mengandung kombinasi huruf dan angka.',
        'password.not_regex' => 'Kata sandi tidak boleh mengandung spasi.',
        'password.confirmed' => 'Konfirmasi kata sandi tidak cocok. Silakan periksa kembali.',
    ];

    public function register(Request $request, RegionResolver $resolver)
    {
        // Otomatis set negara ke Indonesia bila form tidak mengirim kode maupun label.
        if (! $request->filled('negara_kode') && ! $request->filled('negara')) {
            $request->merge(['negara_kode' => 'ID']);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'negara' => 'nullable|string|max:100',
                'negara_kode' => 'nullable|string|size:2',
                'provinsi' => 'nullable|string|max:100',
                'provinsi_id' => 'nullable|string|max:20',
                'kota' => 'nullable|string|max:100',
                'kota_id' => 'nullable|string|max:20',
                'kecamatan' => 'nullable|string|max:100',
                'kecamatan_id' => 'nullable|string|max:20',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => self::PASSWORD_RULES,
            ], array_merge([
                'name.required' => 'Nama lengkap dan negara wajib diisi.',
                'negara.required' => 'Nama lengkap dan negara wajib diisi.',
                'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
                'email.email' => 'Alamat email wajib diisi dengan format yang benar.',
                'email.unique' => 'Email ini sudah terdaftar, silakan gunakan email lain.',
            ], self::PASSWORD_MESSAGES));
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $region = $resolver->resolve(
            $validated['negara_kode'] ?? null,
            $validated['provinsi_id'] ?? null,
            $validated['kota_id'] ?? null,
            $validated['kecamatan_id'] ?? null,
            $validated['negara'] ?? null,
            $validated['provinsi'] ?? null,
            $validated['kota'] ?? null,
            $validated['kecamatan'] ?? null,
        );

        if ($region['errors'] !== []) {
            throw ValidationException::withMessages($region['errors']);
        }

        if (filled($region['negara_kode']) && $region['negara_kode'] !== 'ID') {
            $labels = $resolver->fillForeignLabels($region);
            $region['negara'] = $labels['negara'] ?? $region['negara'];
            $region['provinsi'] = $region['provinsi'] ?? $labels['provinsi'];
            $region['kota'] = $region['kota'] ?? $labels['kota'];
        }

        $user = User::create([
            'name' => $validated['name'],
            'negara' => $region['negara'],
            'negara_kode' => $region['negara_kode'],
            'provinsi' => $region['provinsi'],
            'provinsi_id' => $region['provinsi_id'],
            'kota' => $region['kota'],
            'kota_id' => $region['kota_id'],
            'kecamatan' => $region['kecamatan'],
            'kecamatan_id' => $region['kecamatan_id'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
        ]);

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan masuk untuk melanjutkan.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Login dengan kata sandi.
     */
    public function loginPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'sometimes|boolean',
        ], [
            'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
            'email.email' => 'Alamat email wajib diisi dengan format yang benar.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $throttleKey = 'login-password:'.$request->ip().'|'.strtolower($request->email);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan masuk. Silakan coba lagi nanti.',
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->password || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return response()->json([
                'message' => 'Email atau kata sandi salah.',
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($user, $request->boolean('remember'));
        if ($request->hasSession()) {
            $request->session()->regenerate();

            // Cap hash sandi ke sesi SEKARANG (bukan menunggu request
            // ter-autentikasi pertama). Tanpa ini, SPA yang tidak pernah
            // me-reload halaman meninggalkan sesi tanpa cap — dan begitu
            // sandi direset, middleware justru MENGECAP sesi penyusup dengan
            // hash baru (dia "diselamatkan" alih-alih ditendang).
            $request->session()->put(
                'password_hash_web',
                method_exists($user, 'hashPasswordForCookie')
                    ? $user->hashPasswordForCookie($user->getAuthPassword())
                    : $user->getAuthPassword()
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'redirect' => $user->isAdmin()
                ? '/admin'
                : '/dashboard',
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    /**
     * Kirim OTP enam digit untuk login. OTP berlaku selama satu menit.
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
            'email.email' => 'Alamat email wajib diisi dengan format yang benar.',
        ]);

        $throttleKey = 'otp-send:'.$request->ip().'|'.strtolower($request->email);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan OTP. Silakan coba lagi nanti.',
            ], 429);
        }

        RateLimiter::hit($throttleKey, 10);

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'message' => 'Email belum terdaftar. Silakan buat akun terlebih dahulu.',
            ], 404);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'otp_code' => $otp,
            'otp_expires_at' => now()->addMinutes(self::OTP_TTL_MINUTES),
        ])->save();

        try {
            Mail::to($user->email)->send(
                new OtpMail($otp, self::OTP_TTL_MINUTES)
            );
        } catch (\Throwable $exception) {
            $user->forceFill([
                'otp_code' => null,
                'otp_expires_at' => null,
            ])->save();

            Log::error('Gagal mengirim OTP login.', [
                'user_id' => $user->id,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'OTP gagal dikirim. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda. Berlaku selama '
                .self::OTP_TTL_MINUTES
                .' menit.',
            'expires_in' => self::OTP_TTL_MINUTES * 60,
        ]);
    }

    /**
     * Verifikasi OTP lalu login menggunakan session dan token.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'remember' => 'sometimes|boolean',
        ], [
            'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
            'email.email' => 'Alamat email wajib diisi dengan format yang benar.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus berupa 6 digit angka.',
        ]);

        $throttleKey = 'otp-verify:'.strtolower($request->email);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan OTP. Silakan coba lagi nanti.',
            ], 429);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            RateLimiter::hit($throttleKey, 300);

            return response()->json([
                'message' => 'Email tidak ditemukan.',
            ], 404);
        }

        $otpValid = $user->otp_code
            && hash_equals((string) $user->otp_code, (string) $request->otp)
            && $user->otp_expires_at
            && $user->otp_expires_at->isFuture();

        if (! $otpValid) {
            RateLimiter::hit($throttleKey, 300);

            return response()->json([
                'message' => 'Kode OTP tidak valid atau telah kedaluwarsa.',
            ], 401);
        }

        RateLimiter::clear($throttleKey);

        $user->forceFill([
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        Auth::login($user, $request->boolean('remember'));
        if ($request->hasSession()) {
            $request->session()->regenerate();

            // Cap hash sandi saat login OTP juga — konsisten dengan
            // loginPassword(). Lihat komentar di sana untuk alasannya.
            $request->session()->put(
                'password_hash_web',
                method_exists($user, 'hashPasswordForCookie')
                    ? $user->hashPasswordForCookie($user->getAuthPassword())
                    : $user->getAuthPassword()
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'redirect' => $user->isAdmin()
                ? '/admin'
                : '/dashboard',
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    /**
     * Mengirim OTP untuk reset password. OTP reset berlaku satu menit.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
            'email.email' => 'Alamat email wajib diisi dengan format yang benar.',
            'email.exists' => 'Email tidak ditemukan di sistem kami.',
        ]);

        $throttleKey = 'otp-reset-request:'.strtolower($request->email);
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return response()->json([
                'message' => 'Terlalu banyak permintaan reset kata sandi. Silakan coba lagi nanti.',
            ], 429);
        }

        // Pembatasan request reset tetap 15 menit; ini berbeda dari masa berlaku OTP.
        RateLimiter::hit($throttleKey, 900);

        Cache::forget('otp_reset_'.$request->email);

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put(
            'otp_reset_'.$request->email,
            hash_hmac('sha256', $otp, config('app.key')),
            now()->addMinutes(self::RESET_OTP_TTL_MINUTES)
        );

        try {
            Mail::to($request->email)->send(
                new OtpMail($otp, self::RESET_OTP_TTL_MINUTES)
            );
        } catch (\Throwable $exception) {
            Cache::forget('otp_reset_'.$request->email);

            Log::error('Gagal mengirim OTP reset password.', [
                'email' => $request->email,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'OTP reset password gagal dikirim. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'message' => 'Kode OTP untuk reset kata sandi telah dikirim ke email Anda. Berlaku selama '
                .self::RESET_OTP_TTL_MINUTES
                .' menit.',
            'expires_in' => self::RESET_OTP_TTL_MINUTES * 60,
        ]);
    }

    /**
     * Verifikasi kode OTP reset TANPA mengubah apa pun. Kode tidak dikonsumsi
     * di sini — tetap divalidasi ulang oleh resetPassword() saat penyimpanan,
     * jadi kode tetap hanya bisa dipakai untuk satu perubahan sandi.
     */
    public function verifyResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
        ], [
            'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
            'email.exists' => 'Email tidak ditemukan di sistem kami.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus berupa 6 digit angka.',
        ]);

        $throttleKey = 'otp-reset-verify:'.strtolower($request->email);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan OTP. Silakan coba lagi nanti.',
            ], 429);
        }

        $cachedHash = Cache::get('otp_reset_'.$request->email);
        $inputHash = hash_hmac('sha256', (string) $request->otp, config('app.key'));

        if (! $cachedHash || ! hash_equals($cachedHash, $inputHash)) {
            RateLimiter::hit($throttleKey, 900);

            return response()->json([
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.',
            ], 400);
        }

        RateLimiter::clear($throttleKey);

        return response()->json(['message' => 'Kode OTP terverifikasi. Silakan buat kata sandi baru.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
            'password' => self::PASSWORD_RULES,
        ], array_merge([
            'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
            'email.exists' => 'Email tidak ditemukan di sistem kami.',
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits' => 'Kode OTP harus berupa 6 digit angka.',
        ], self::PASSWORD_MESSAGES));

        $throttleKey = 'otp-reset-verify:'.strtolower($request->email);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return response()->json([
                'message' => 'Terlalu banyak percobaan OTP. Silakan coba lagi nanti.',
            ], 429);
        }

        $cachedHash = Cache::get('otp_reset_'.$request->email);
        $inputHash = hash_hmac('sha256', (string) $request->otp, config('app.key'));

        if (! $cachedHash || ! hash_equals($cachedHash, $inputHash)) {
            RateLimiter::hit($throttleKey, 900);

            return response()->json([
                'message' => 'Kode OTP tidak valid atau sudah kedaluwarsa.',
            ], 400);
        }

        RateLimiter::clear($throttleKey);

        $user = User::where('email', $request->email)->first();
        $user->password = $request->password;
        $user->save();

        Cache::forget('otp_reset_'.$request->email);
        $user->tokens()->delete();

        // Reset sandi juga mencabut cookie "remember me" (remember_token):
        // tanpa ini, sesi lama yang terselamatkan cookie remember-me masih
        // bisa login ulang otomatis meski middleware sesi sudah menendang
        // sesi biasa. Bagian dari pembuktian: siapa pun yang memegang sesi
        // lama tidak punya jalan untuk bertahan setelah sandi diganti.
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        return response()->json([
            'message' => 'Kata sandi berhasil diubah. Silakan masuk kembali.',
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $token = $request->user()->currentAccessToken();
            if ($token && method_exists($token, 'delete')) {
                $token->delete();
            }
        }

        Auth::guard('web')->logout();

        // Route /api tidak membawa session store — invalidasi hanya bila ada.
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logout berhasil.']);
        }

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
