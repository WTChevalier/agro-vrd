<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Define gates for common authorization checks
        Gate::define('access-admin-panel', function ($user) {
            return $user->hasAnyRole(['admin', 'super-admin']);
        });

        Gate::define('access-restaurant-panel', function ($user) {
            return $user->hasRole('restaurant-owner') || $user->restaurant_id !== null;
        });

        Gate::define('manage-orders', function ($user) {
            return $user->hasAnyRole(['admin', 'super-admin', 'restaurant-owner']);
        });
    }
}
