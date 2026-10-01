<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_register_stores_region_ids_and_derived_names(): void
    {
        DB::table('provinces')->insert(['id' => '32', 'name' => 'Jawa Barat']);
        DB::table('regencies')->insert(['id' => '3273', 'province_id' => '32', 'name' => 'Kota Bandung']);
        DB::table('districts')->insert(['id' => '3273010', 'regency_id' => '3273', 'name' => 'Coblong']);

        $response = $this->postJson('/api/register', [
            'name' => 'Region User',
            'negara_kode' => 'ID',
            'provinsi_id' => '32',
            'kota_id' => '3273',
            'kecamatan_id' => '3273010',
            'email' => 'region@example.com',
            'password' => 'Password12345',
            'password_confirmation' => 'Password12345',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'region@example.com')->first();
        $this->assertSame('ID', $user->negara_kode);
        $this->assertSame('Indonesia', $user->negara);
        $this->assertSame('32', $user->provinsi_id);
        $this->assertSame('Jawa Barat', $user->provinsi);
        $this->assertSame('3273', $user->kota_id);
        $this->assertSame('Kota Bandung', $user->kota);
        $this->assertSame('3273010', $user->kecamatan_id);
        $this->assertSame('Coblong', $user->kecamatan);
    }

    public function test_register_rejects_illegal_region_chain(): void
    {
        DB::table('provinces')->insert(['id' => '32', 'name' => 'Jawa Barat']);
        DB::table('provinces')->insert(['id' => '31', 'name' => 'DKI Jakarta']);
        DB::table('regencies')->insert(['id' => '3171', 'province_id' => '31', 'name' => 'Jakarta Selatan']);

        $response = $this->postJson('/api/register', [
            'name' => 'Region User',
            'negara_kode' => 'ID',
            'provinsi_id' => '32',
            'kota_id' => '3171',
            'email' => 'illegal@example.com',
            'password' => 'Password12345',
            'password_confirmation' => 'Password12345',
        ]);

        $response->assertStatus(422);
        $this->assertNull(User::where('email', 'illegal@example.com')->first());
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

    public function test_verify_reset_otp_accepts_valid_code_without_consuming_it(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'verify@example.com',
            'password' => 'OldPassword123',
        ]);

        $this->postJson('/api/forgot-password', ['email' => 'verify@example.com'])
            ->assertStatus(200);

        $otp = null;
        Mail::assertSent(OtpMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        // Kode benar: lolos, belum ada perubahan sandi.
        $this->postJson('/api/verify-reset-otp', [
            'email' => 'verify@example.com',
            'otp' => $otp,
        ])->assertStatus(200);

        $user = User::where('email', 'verify@example.com')->first();
        $this->assertTrue(Hash::check('OldPassword123', $user->password));

        // Kode belum dikonsumsi: masih bisa dipakai resetPassword().
        $this->postJson('/api/reset-password', [
            'email' => 'verify@example.com',
            'otp' => $otp,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
    }

    public function test_verify_reset_otp_rejects_wrong_code(): void
    {
        Mail::fake();

        User::factory()->create([
            'email' => 'wrongotp@example.com',
            'password' => 'OldPassword123',
        ]);

        $this->postJson('/api/forgot-password', ['email' => 'wrongotp@example.com'])
            ->assertStatus(200);

        $this->postJson('/api/verify-reset-otp', [
            'email' => 'wrongotp@example.com',
            'otp' => '000000',
        ])->assertStatus(400);
    }

    public function test_delete_account_with_sanctum_token_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'delete-me@example.com',
            'password' => 'Password12345',
        ]);

        $token = $user->createToken('spa')->plainTextToken;

        // Registrasi guard web agar sesi bisa diakhiri oleh controller.
        $this->app['session']->put('_token', 'test-token');
        $this->withSession(['_token' => 'test-token']);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/profile', [
                'confirmation' => 'HAPUS AKUN',
                'current_password' => 'Password12345',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['email' => 'delete-me@example.com']);
    }

    /**
     * Regresi untuk celah sesi: setelah reset sandi, sesi web yang ADA DI
     * BROWSER LAIN (teman kos/HP hilang) harus mati. Sebelum fix, hanya
     * token Sanctum yang dicabut — session cookie lama tetap sah hingga
     * kedaluwarsa sendiri.
     */
    public function test_password_reset_kills_sessions_created_before_it(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'denis@example.com',
            'password' => 'OldPassword123',
        ]);

        // "Browser A": sesi web sah milik penyusup (login sebelum reset).
        // SENGJAHA tanpa request antara login dan reset — persis alur SPA
        // nyata: axios login lalu pindah halaman TANPA reload, sehingga tidak
        // ada request ter-autentikasi yang mencap hash sandi ke session.
        // Versi test inilah yang membongkar celah kedua saat uji manual.
        $this->startSession();
        $this->postJson('/auth/login', [
            'email' => 'denis@example.com',
            'password' => 'OldPassword123',
        ])->assertStatus(200);

        // Ambil ID sesi A untuk dibuktikan mati setelah reset.
        $intruderSessionId = $this->app['session']->getId();

        // "Browser B": jalur reset (tidak login, hanya bawa OTP).",
        $this->postJson('/auth/forgot-password', ['email' => 'denis@example.com'])
            ->assertStatus(200);

        $otp = null;
        Mail::assertSent(OtpMail::class, function ($mail) use (&$otp) {
            $otp = $mail->otp;

            return true;
        });

        $this->postJson('/auth/reset-password', [
            'email' => 'denis@example.com',
            'otp' => $otp,
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertStatus(200);

        // Guard meng-cache model user (hash tua) sepanjang proses test —
        // paksa resolusi ulang agar request berikutnya seperti browser baru.
        $this->app['auth']->forgetGuards();

        // "Browser A" masih membawa sesi lama: begitu menyapa server lagi,
        // middleware AuthenticateSession membandingkan hash sandi di sesi
        // (hash LAMA, disimpan saat login) vs hash BARU di database →
        // sesi ditendang, request ditolak 401.
        $this->getJson('/api/me')->assertStatus(401);
        $this->assertFalse(
            $this->app['auth']->guard('web')->check(),
            'Sesi web lama masih hidup setelah sandi direset — celah penyusup belum tertutup.'
        );

        // Sandi benar-benar berganti di database.
        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123', $user->password));
    }
}
