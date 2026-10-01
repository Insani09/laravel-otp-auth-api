<?php

use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->redirectGuestsTo('/');
        $middleware->redirectUsersTo('/dashboard');

        // Keamanan sesi: jika hash kata sandi pengguna berubah (reset sandi,
        // ubah sandi), semua sesi web LAIN yang masih memakai sandi lama
        // otomatis dianggap tidak valid dan diminta login ulang. Untuk API
        // stateful (SPA), tameng yang sama sudah menyala lewat pipeline
        // Sanctum (config/sanctum.php → authenticate_session).
        $middleware->authenticateSessions();

        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
