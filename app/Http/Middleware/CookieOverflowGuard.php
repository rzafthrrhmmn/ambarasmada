<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieOverflowGuard
{
    protected const COOKIE_SIZE_LIMIT = 3800;

    public function handle(Request $request, Closure $next): Response
    {
        $cookies = $request->cookies->all();
        $totalSize = 0;
        $sessionCookieName = config('session.cookie', 'ambara-sistem-digital-session');

        foreach ($cookies as $name => $value) {
            $size = strlen($name) . strlen($value) + 4;
            $totalSize += $size;
            if ($size > 1500 && $name != $sessionCookieName) {
                // Flag large cookies for potential cleanup
            }
        }

        if (isset($cookies[$sessionCookieName]) && strlen($cookies[$sessionCookieName]) > self::COOKIE_SIZE_LIMIT) {
            return $this->handleOverflow($request, $next, $sessionCookieName);
        }

        if ($totalSize > self::COOKIE_SIZE_LIMIT) {
            return $this->handleOverflow($request, $next, $sessionCookieName);
        }

        return $next($request);
    }

    protected function handleOverflow(Request $request, Closure $next, string $sessionCookieName): Response
    {
        if ($request->hasSession()) {
            $request->session()->flush();
        }

        $response = $next($request);

        $response->headers->clearCookie(
            $sessionCookieName,
            config('session.path', '/'),
            config('session.domain')
        );

        $flashKeys = ['success', 'error', '_old_input', 'remember_token', '_intent'];
        foreach ($flashKeys as $key) {
            $flashCookie = $sessionCookieName . '_' . $key;
            $response->headers->clearCookie($flashCookie, config('session.path', '/'), config('session.domain'));
        }

        if ($request->hasSession()) {
            $request->session()->flash('warning', 'Sesi Anda telah diperbarui.');
        }

        return $response;
    }
}
