<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user(), 401);

        $userRole = $request->user()->role;
        $normalizedRoles = array_map('trim', $roles);
        abort_unless(in_array($userRole, $normalizedRoles, true), 403);

        return $next($request);
    }
}
