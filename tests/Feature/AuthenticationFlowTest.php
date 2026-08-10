<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Mail\OtpMail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_then_login_with_otp()
    {
        Mail::fake();

        $registerData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
        ];

        $registerResponse = $this->postJson('/api/register', $registerData);
        $registerResponse->assertStatus(201);
        $registerResponse->assertJson(['message' => 'Registrasi berhasil. Silakan login untuk mendapatkan token akses.']);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('Password123', $user->password));

        $loginResponse = $this->postJson('/api/request-otp', [
            'email' => 'test@example.com',
            'password' => 'Password123',
            'remember_me' => false,
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJson(['message' => 'Kredensial benar. Silakan masukkan kode OTP yang telah dikirim ke email Anda.']);

        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && preg_match('/^\d{6}$/', $mail->otp);
        });

        $otp = null;
        Mail::assertSent(OtpMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otp;
            return true;
        });

        $verifyResponse = $this->postJson('/api/verify-otp', [
            'email' => 'test@example.com',
            'otp' => $otp,
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonStructure(['message', 'token', 'token_type', 'expires_at']);
    }

    public function test_user_can_reset_password_using_otp_and_login_with_new_password()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('OldPassword123'),
        ]);

        $forgotResponse = $this->postJson('/api/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $forgotResponse->assertStatus(200);
        $forgotResponse->assertJson(['message' => 'Kode OTP untuk reset password telah dikirim ke email Anda.']);

        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && preg_match('/^\d{6}$/', $mail->otp);
        });

        $resetOtp = null;
        Mail::assertSent(OtpMail::class, function ($mail) use (&$resetOtp) {
            $resetOtp = $mail->otp;
            return true;
        });

        $resetResponse = $this->postJson('/api/reset-password', [
            'email' => 'reset@example.com',
            'otp' => $resetOtp,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $resetResponse->assertStatus(200);
        $resetResponse->assertJson(['message' => 'Password berhasil diubah. Silakan login kembali.']);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));

        $loginResponse = $this->postJson('/api/request-otp', [
            'email' => 'reset@example.com',
            'password' => 'NewPassword123',
            'remember_me' => false,
        ]);

        $loginResponse->assertStatus(200);
    }
}
