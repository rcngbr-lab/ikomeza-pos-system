<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\RestaurantTable;
use App\Models\Role;
use App\Models\Permission;
use App\Models\StockCount;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\EnterpriseAuditObserver;

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
        Password::defaults(function () {
            return Password::min(10)
                ->letters()
                ->mixedCase()
                ->numbers();
        });

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

        foreach ([
            Product::class,
            User::class,
            Role::class,
            Permission::class,
            Supplier::class,
            Purchase::class,
            Customer::class,
            RestaurantTable::class,
            StockCount::class,
        ] as $model) {
            $model::observe(EnterpriseAuditObserver::class);
        }

        //
    }
}
