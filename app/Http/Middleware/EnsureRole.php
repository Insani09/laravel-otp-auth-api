<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            // Aplikasi murni SPA + API JSON: semua penolakan akses berbentuk
            // JSON; guard vue-router yang mengarahkan ke halaman yang sesuai.
            return response()->json([
                'message' => 'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
            ], 403);
        }

        return $next($request);
    }
}
