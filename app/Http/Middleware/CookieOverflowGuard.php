<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieOverflowGuard
{
    /**
     * Maximum safe cookie size before browsers start rejecting requests.
     * Most browsers limit ~4096 bytes per cookie and ~4093 bytes per domain.
     * We use 3800 as a safe threshold to leave headroom.
     */
    protected const COOKIE_SIZE_LIMIT = 3800;

    /**
     * Handle an incoming request.
     *
     * Prevents 500 errors caused by cookie/session overflow.
     * When session cookie exceeds browser limits (e.g., after storing large
     * data like profile photos in session), browsers may reject requests
     * with 431/500 errors. This middleware proactively detects and handles
     * such cases.
     */
    public function handle(Request , Closure ): Response
    {
         = ->cookies->all();
         = 0;
         = [];
         = config('session.cookie', 'ambara-sistem-digital-session');

        foreach ( as  => ) {
             = strlen() + strlen() + 4;
             += ;
            
            // Flag cookies exceeding 1.5KB (likely session data)
            if ( > 1500 &&  !== ) {
                [] = ;
            }
        }

        // Check if session cookie itself is too large
        if (isset([]) && strlen([]) > self::COOKIE_SIZE_LIMIT) {
            // Session data overflow - reset the session
            return ->handleOverflow(, , );
        }

        // If total cookie size approaches limit, clear non-essential cookies
        if ( > self::COOKIE_SIZE_LIMIT && count() > 0) {
            return ->handleOverflow(, , );
        }

        return ();
    }

    /**
     * Handle cookie/session overflow by resetting session and returning response.
     */
    protected function handleOverflow(Request , Closure , string ): Response
    {
        // Clear the session data in storage
        if (->hasSession()) {
            ->session()->flush();
        }

         = ();

        // Expire the oversized session cookie
        ->headers->clearCookie(
            ,
            config('session.path', '/'),
            config('session.domain')
        );

        // Also clear any flash message cookies
         = ['success', 'error', '_old_input', 'remember_token', '_intent'];
        foreach ( as ) {
             =  . '_' . ;
            ->headers->clearCookie(, config('session.path', '/'), config('session.domain'));
        }

        // Set a flash message to inform user
        if (->hasSession()) {
            ->session()->flash('warning', 'Sesi Anda telah diperbarui untuk mengoptimalkan performa.');
        }

        return ;
    }
}
