<?php

/**
 * Sprint 139AA — BaseAdminPanelProvider compartido del ecosistema Gurztac.
 *
 * Cada AdminPanelProvider de cada app tenant debe extender esta clase y solo
 * sobrescribir 3 métodos abstractos: codigoMarca(), nombreMarca(), color().
 *
 * Lo que esta clase aplica automáticamente a todas las marcas:
 *   - darkMode(true) toggle ☀️/🌙/💻
 *   - brandLogo apuntando a /public/logos/{codigoMarca}.svg
 *   - brandLogoHeight 2.5rem
 *   - colors expandidos (primary marca + info/success/warning/danger/gray vivos)
 *   - renderHook con CSS theme overrides (contraste, bordes, hover, headings)
 *   - userMenu con "Ir a la Web"
 *   - sidebarCollapsibleOnDesktop
 *   - middleware estándar
 *   - login enabled
 *
 * Cada app puede agregar más config sobreescribiendo el método panel() y
 * llamando parent::panel($panel) primero.
 *
 * Compatibilidad: Filament 3.x + Laravel 11/12. Para Filament 5 (GurzTicket)
 * existe BaseAdminPanelProviderV5 separado.
 *
 * Última revisión: 2026-04-28 — WT Chevalier (Founder)
 */

namespace App\Gurztac\FilamentBase\Providers;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

abstract class BaseAdminPanelProvider extends PanelProvider
{
    /**
     * Código corto de la marca (ej. 'sazonrd', 'vrd', 'gurzmed').
     * Se usa para resolver el logo: /public/logos/{codigo}.svg
     */
    abstract protected function codigoMarca(): string;

    /**
     * Nombre visible en el header. Ej: "SazónRD", "Visit RD — Admin".
     */
    abstract protected function nombreMarca(): string;

    /**
     * Color primario de la marca (un Filament Color::*).
     * Ej: Color::Red para SRD, Color::Sky para VRD, etc.
     * Si no se sobrescribe, usa Color::Amber (identidad Gurztac).
     */
    protected function color(): array
    {
        return Color::Amber;
    }

    /**
     * URL pública de la marca para el "Ir a la Web" del userMenu.
     * Default: el host actual del request. Cada marca puede sobrescribir.
     */
    protected function urlPublica(): string
    {
        return 'https://' . request()->getHost();
    }

    /**
     * Path del panel admin. Default: 'admin'. Cada marca puede sobrescribir.
     */
    protected function pathPanel(): string
    {
        return 'admin';
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path($this->pathPanel())
            ->login()
            ->darkMode(true)
            ->brandName($this->nombreMarca())
            ->brandLogo(asset('logos/' . $this->codigoMarca() . '.svg'))
            ->brandLogoHeight('2.5rem')
            ->favicon(asset('favicon.ico'))
            ->colors([
                'primary' => $this->color(),
                'info'    => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger'  => Color::Red,
                'gray'    => Color::Slate,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('<x-hub-theme-overrides />')
            )
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->userMenuItems([
                MenuItem::make()
                    ->label('Ir a la Web')
                    ->icon('heroicon-o-globe-alt')
                    ->url($this->urlPublica(), shouldOpenInNewTab: true),
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
