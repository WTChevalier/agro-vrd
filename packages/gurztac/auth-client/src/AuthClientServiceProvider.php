<?php

namespace Gurztac\AuthClient;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Gurztac\AuthClient\Http\Middleware\ValidarJwtGurztac;
use Gurztac\AuthClient\Http\Middleware\RequiereAutenticacion;
use Gurztac\AuthClient\Services\JwtValidator;
use Gurztac\AuthClient\Services\HubAuthClient;

class AuthClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config para que envs tomen efecto
        $this->mergeConfigFrom(
            __DIR__ . '/../config/gurztac-auth.php',
            'gurztac-auth'
        );

        // Singletons de servicios
        $this->app->singleton(JwtValidator::class, function ($app) {
            return new JwtValidator(
                jwksUrl: config('gurztac-auth.jwks_url'),
                cacheTtlMinutes: config('gurztac-auth.jwks_cache_ttl_minutes'),
                expectedIss: config('gurztac-auth.iss'),
                expectedAud: config('gurztac-auth.aud'),
                clockSkew: config('gurztac-auth.clock_skew_seconds'),
            );
        });

        $this->app->singleton(HubAuthClient::class, function ($app) {
            return new HubAuthClient(
                hubUrl: config('gurztac-auth.hub_url'),
            );
        });

        // Facade alias
        $this->app->bind('gurztac-auth', HubAuthClient::class);
    }

    public function boot(): void
    {
        // 1. Publish config
        $this->publishes([
            __DIR__ . '/../config/gurztac-auth.php' => config_path('gurztac-auth.php'),
        ], 'gurztac-auth-config');

        // 2. Publish views (opcional)
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'gurztac-auth');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/gurztac-auth'),
        ], 'gurztac-auth-views');

        // 3. Routes SSO
        if (config('gurztac-auth.sso.enabled')) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/sso.php');
        }

        // 4. Register middleware aliases
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('gurztac.jwt', ValidarJwtGurztac::class);
        $router->aliasMiddleware('gurztac.auth', RequiereAutenticacion::class);

        // 5. Migrations (si la app tenant las quiere)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'gurztac-auth-migrations');
        }
    }
}
