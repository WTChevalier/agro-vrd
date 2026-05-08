<?php
namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\MenuItem;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Support\Facades\Blade;
use Filament\Navigation\NavigationGroup;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->darkMode(true) // Sprint 139Y
            ->colors([
                'primary' => Color::hex(env('BRAND_PRIMARY', '#3b82f6')),
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'info' => Color::Blue,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => Blade::render('<x-hub-theme-overrides />')) // Sprint 139Y
            ->font('Poppins')
            ->brandName('SazónRD Admin')
            ->brandLogo(asset('logos/sazonrd.svg')) // Sprint 139Y
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\StatsOverviewWidget::class,
                \App\Filament\Widgets\ResumenGeneralWidget::class,
                \App\Filament\Widgets\PedidosChartWidget::class,
                \App\Filament\Widgets\IngresosChartWidget::class,
            ])
            ->navigationItems([
                NavigationItem::make('Ver Sitio Web')
                    ->url('/')
                    ->icon('heroicon-o-globe-alt')
                    ->group('Enlaces')
                    ->sort(100)
                    ->openUrlInNewTab(),
            ])
            ->userMenuItems([
                'sitio' => MenuItem::make()
                    ->label('Ver Sitio Web')
                    ->url('/')
                    ->icon('heroicon-o-globe-alt')
                    ->openUrlInNewTab(),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DispatchServingFilamentEvent::class,
            ])
            ->navigationItems([
                NavigationItem::make('Ver Sitio Web')
                    ->url('/', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-globe-alt')
                    ->group('Enlaces')
                    ->sort(100),
            ])
            
            ->navigationGroups([
                \Filament\Navigation\NavigationGroup::make()
                    ->label('Turismo RD')
                    ->icon('heroicon-o-globe-americas')
                    ->collapsed(false),
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('🏝️ Panel Visit RD')
                    ->url('https://visitrepublicadominicana.com/admin', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->group('Turismo RD')
                    ->sort(1),
                \Filament\Navigation\NavigationItem::make('Atracciones')
                    ->url('https://visitrepublicadominicana.com/admin/attractions', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-map-pin')
                    ->group('Turismo RD')
                    ->sort(2),
                \Filament\Navigation\NavigationItem::make('Playas')
                    ->url('https://visitrepublicadominicana.com/admin/beaches', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-sun')
                    ->group('Turismo RD')
                    ->sort(3),
                \Filament\Navigation\NavigationItem::make('Eventos')
                    ->url('https://visitrepublicadominicana.com/admin/events', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-calendar')
                    ->group('Turismo RD')
                    ->sort(4),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Calidad')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Landing Page')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Operaciones')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Personal')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Suscripciones')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Catálogo')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('Marketing')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Sistema')
                    ->collapsed(true),
            ]);
    }
}
