<?php

namespace App\Providers;

use Illuminate\Foundation\Vite as BaseVite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('vite', function () {
            return new class extends BaseVite
            {
                protected function manifestPath($buildDirectory)
                {
                    if ($this->isRunningOnVercel()) {
                        return '/tmp/public/'.$buildDirectory.'/'.$this->manifestFilename;
                    }

                    return parent::manifestPath($buildDirectory);
                }

                protected function isRunningOnVercel(): bool
                {
                    return (bool) env('VERCEL');
                }
            };
        });
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
    }
}
