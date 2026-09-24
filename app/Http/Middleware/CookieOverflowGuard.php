<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieOverflowGuard
{
    /**
     * Handle an incoming request.
     *
     * Prevent 500 errors caused by cookie overflow (browser cookie size limit).
     * When cookies exceed ~4KB limit, servers may reject the request entirely.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check total cookie size before processing
        $cookies = $request->cookies->all();
        $totalSize = 0;
        $largeCookies = [];

        foreach ($cookies as $name => $value) {
            $size = strlen($name) + strlen($value) + 4; // +4 for overhead (name=, ; and spaces)
            $totalSize += $size;
            if ($size > 1800) {
                $largeCookies[] = $name;
            }
        }

        // Laravel cookie session limit is ~4096 bytes per domain
        // If we're close to the limit, clear non-essential cookies
        $safeLimit = 3800; // Leave 300 bytes headroom for Set-Cookie responses

        if ($totalSize > $safeLimit || count($largeCookies) > 0) {
            // Clear old flash messages and non-essential cookies
            $request->cookies->remove(config('session.cookie', 'ambara-sistem-digital') . '_session');
            
            // If still too large, clear all flash data cookies
            $response = $next($request);
            
            // Expire any flash cookies that are too large
            foreach ($response->headers->getCookies() as $cookie) {
                if ($cookie->getValue() && strlen($cookie->getValue()) > 1800) {
                    $response->headers->clearCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
                }
            }
            
            return $response;
        }

        return $next($request);
    }
}
