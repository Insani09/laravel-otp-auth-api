<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\OtpMail;

class AuthController extends Controller
{
    public function requestOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        // Generate user otomatis jika tidak ada di database
        User::firstOrCreate(
            ['email' => $email],
            ['name' => 'User Baru', 'password' => bcrypt('rahasia123')]
        );

        $otp = rand(100000, 999999);

        // Simpan di cache (dijadikan string agar cocok dengan input Postman)
        cache()->put('otp_' . $email, (string) $otp, now()->addMinutes(2));

        // Kirim email
        Mail::to($email)->send(new OtpMail($otp));

        return response()->json([
            'message' => 'Kode OTP telah dikirim ke email Anda.'
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

        $cachedOtp = cache()->get('otp_' . $email);

        if (!$cachedOtp || $cachedOtp !== $otp) {
            return response()->json([
                'message' => 'Kode OTP tidak valid atau telah kedaluwarsa.'
            ], 401);
        }

        // Hapus OTP dari cache agar tidak bisa dipakai dua kali
        cache()->forget('otp_' . $email);

        $user = User::where('email', $email)->first();
        $user->tokens()->delete(); // Hapus token lama
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}