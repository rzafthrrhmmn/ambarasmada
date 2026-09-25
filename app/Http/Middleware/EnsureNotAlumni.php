<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotAlumni
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->role === 'Alumni') {
            // Alumni users should only access alumni.* routes
            // Redirect them to their dashboard
            return redirect()->route('alumni.dashboard');
        }

        return $next($request);
    }
}
