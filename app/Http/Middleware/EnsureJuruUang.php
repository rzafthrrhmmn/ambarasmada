<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureJuruUang
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user(), 401);

        $user = $request->user();

        // Admin and Pembina always have access
        if (in_array($user->role, ['Admin', 'Pembina'], true)) {
            return $next($request);
        }

        // Check if user is Pengurus with Juru Uang position
        if ($user->role === 'Pengurus') {
            $isJuruUang = $user->member?->memberPositions()
                ->whereHas('position', fn ($q) => $q->whereIn('code', ['juru_uang_putra', 'juru_uang_putri']))
                ->exists();

            if ($isJuruUang) {
                return $next($request);
            }
        }

        abort(403, 'Akses ditolak. Hanya Juru Uang yang dapat mengakses fitur ini.');
    }
}
