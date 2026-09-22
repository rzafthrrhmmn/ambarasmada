<?php

namespace App\Providers;

use App\Models\Ambalan;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Boost\BoostServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(BoostServiceProvider::class)) {
            $this->app->register(BoostServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }

        if ($this->app->environment('local')) {
            config([
                'app.csp.script_src' => "'self' 'unsafe-inline' 'unsafe-eval' http://[::1]:5173",
                'app.csp.style_src' => "'self' 'unsafe-inline' http://[::1]:5173 https://fonts.googleapis.com",
                'app.csp.font_src' => "'self' data: https://fonts.gstatic.com http://[::1]:5173",
            ]);
        }

        Inertia::share([
            'pendingCount' => function () {
                if (! auth()->check() || ! in_array(auth()->user()->role, ['Admin', 'Pembina'], true)) {
                    return null;
                }

                return User::where('status', 'pending')->count();
            },
            'unreadNotificationCount' => function () {
                if (! auth()->check()) {
                    return 0;
                }

                return \App\Models\Notification::where('user_id', auth()->user()->id)->where('is_read', false)->count();
            },
            'ambalan' => function () {
                return Ambalan::first();
            },
        ]);
    }
}
