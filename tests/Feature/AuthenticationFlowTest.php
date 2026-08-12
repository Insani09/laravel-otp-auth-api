<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_login_with_password(): void
    {
        $registerData = [
            'name' => 'Test User',
            'negara' => 'Indonesia',
            'provinsi' => 'Jawa Barat',
            'kota' => 'Bandung',
            'kecamatan' => 'Coblong',
            'email' => 'test@example.com',
            'password' => 'Password12345',
            'password_confirmation' => 'Password12345',
        ];

        $registerResponse = $this->postJson('/api/register', $registerData);
        $registerResponse->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Indonesia', $user->negara);
        $this->assertTrue(Hash::check('Password12345', $user->password));

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'Password12345',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure(['message', 'token', 'redirect', 'user']);
    }

    public function test_user_can_login_with_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'otp@example.com',
            'password' => 'Password12345',
            'negara' => 'Indonesia',
        ]);

        $sendResponse = $this->postJson('/api/otp/send', [
            'email' => 'otp@example.com',
        ]);

        $sendResponse->assertStatus(200);

        Mail::assertSent(OtpMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) && preg_match('/^\d{6}$/', $mail->otp);
        });

        $otp = null;
        Mail::assertSent(OtpMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $user->refresh();
        $this->assertSame($otp, $user->otp_code);
        $this->assertNotNull($user->otp_expires_at);

        $verifyResponse = $this->postJson('/api/otp/verify', [
            'email' => 'otp@example.com',
            'otp' => $otp,
        ]);

        $verifyResponse->assertStatus(200);
        $verifyResponse->assertJsonStructure(['message', 'token', 'redirect', 'user']);
    }

    public function test_user_can_reset_password_using_otp(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'OldPassword123',
        ]);

        $forgotResponse = $this->postJson('/api/forgot-password', [
            'email' => 'reset@example.com',
        ]);

        $forgotResponse->assertStatus(200);

        $otp = null;
        Mail::assertSent(OtpMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $resetResponse = $this->postJson('/api/reset-password', [
            'email' => 'reset@example.com',
            'otp' => $otp,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ]);

        $resetResponse->assertStatus(200);

        $user = User::where('email', 'reset@example.com')->first();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
    }
}
