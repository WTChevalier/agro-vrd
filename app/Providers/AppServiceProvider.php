<?php

namespace App\Providers;

use Filament\Support\Facades\FilamentView;

use App\Observers\TranslatableObserver;
use App\Models\Plato;
use App\Models\Restaurante;

use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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
        FilamentView::registerRenderHook(
            "panels::head.end",
            fn () => view("filament.hooks.theme-listener"),
        );

        // Sprint 653 — auto-translate hooks
        Plato::observe(TranslatableObserver::class);
        Restaurante::observe(TranslatableObserver::class);

        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Prevent lazy loading in non-production environments
        Model::preventLazyLoading(! $this->app->isProduction());

        // Prevent silently discarding attributes
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Configure Filament defaults
        $this->configureFilament();
    }

    /**
     * Configure Filament defaults for SazonRD.
     */
    protected function configureFilament(): void
    {
        // Set default Filament colors - using orange/amber for food theme
        FilamentColor::register([
            'danger' => Color::Rose,
            'gray' => Color::Gray,
            'info' => Color::Blue,
            'primary' => Color::Amber,
            'success' => Color::Emerald,
            'warning' => Color::Orange,
        ]);

        // Register custom icons (optional)
        FilamentIcon::register([
            'panels::sidebar.collapse-button' => 'heroicon-o-chevron-left',
            'panels::sidebar.expand-button' => 'heroicon-o-chevron-right',
        ]);
    }
}
