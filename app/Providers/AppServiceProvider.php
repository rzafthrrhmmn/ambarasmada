<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Boost\BoostServiceProvider::class)) {
            $this->app->register(\Laravel\Boost\BoostServiceProvider::class);
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
        ]);
    }
}
