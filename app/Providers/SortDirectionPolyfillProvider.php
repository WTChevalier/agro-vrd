<?php
/**
 * SortDirectionPolyfillProvider — Sprint 844.
 *
 * Persiste el polyfill del enum nativo PHP 8.6 `SortDirection` que symfony/polyfill-php86
 * provee como stub pero que el composer classmap-scanner NO registra (porque el stub
 * está envuelto en un `if (PHP_VERSION_ID < 80600)`).
 *
 * Se registra antes de Laravel autoload de cualquier Collection, garantizando que
 * `Illuminate\\Support\\Collection::sortBy()` (que usa `SortDirection::Ascending`)
 * pueda resolverse en runtime web bajo PHP 8.3.
 *
 * Sobrevive a `composer install` y `composer dump-autoload` porque vive en app/Providers/.
 *
 * Registro: agregar a bootstrap/providers.php (o config/app.php providers en L<11):
 *   App\Providers\SortDirectionPolyfillProvider::class,
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SortDirectionPolyfillProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! \enum_exists('SortDirection', false)) {
            // Eval evita parse-error si futuro PHP 8.6+ ya lo tiene built-in
            // (porque enum_exists con $autoload=false retorna true antes de evaluar)
            eval('enum SortDirection { case Ascending; case Descending; }');
        }
    }

    public function boot(): void
    {
        // No-op
    }
}
