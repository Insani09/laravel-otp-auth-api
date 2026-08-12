<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
                ], 403);
            }

            if ($user && $user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }

            if ($user) {
                return redirect()->route('profile');
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
