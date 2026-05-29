<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */


    
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $appUrl = rtrim((string) config('app.url'), '/');

        if ($appUrl !== '' && $appUrl !== 'http://localhost') {
            URL::forceRootUrl($appUrl);
        }

        if ($this->app->environment('production') || str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }

        Gate::before(function ($user, $ability) {
            return $user->hasOperationalRole('ADMIN', 'ADMINISTRATOR')
                ? true
                : null;
        });
    }
}
