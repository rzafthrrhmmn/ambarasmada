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
        Inertia::share([
            'pendingCount' => function () {
                if (! auth()->check() || ! in_array(auth()->user()->role, ['Admin', 'Pembina'], true)) {
                    return null;
                }

                return User::where('status', 'pending')->count();
            },
            'ambalan' => function () {
                return Ambalan::first();
            },
        ]);
    }
}
