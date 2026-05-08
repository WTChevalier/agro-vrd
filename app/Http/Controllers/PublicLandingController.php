<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * PublicLandingController — Sprint 1067 (Fase 1 — Vive RD).
 *
 * Punto de entrada del frontend público.
 * Reemplaza la lógica antigua de welcome/inicio/home blades sueltos.
 *
 * Por ahora delega TODO el rendering a la vista, que a su vez consume
 * los modelos LandingConfig + LandingBlock vía componentes Blade.
 *
 * En el futuro (Fase 4: cross-vertical search):
 * - Inyectar prefetch de búsqueda
 * - Resolver filtros pre-seleccionados desde URL
 * - SSR de Featured Listings con paginación
 */
class PublicLandingController extends Controller
{
    /**
     * Home pública del vertical.
     */
    public function home(Request $request)
    {
        // Render directo — todo CMS-driven en componentes Blade.
        // Headers de caché para Cloudflare (5 min en edge, 0 en browser).
        return response()
            ->view('public.home')
            ->header('Cache-Control', 'public, max-age=0, s-maxage=300, must-revalidate')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Cambio de idioma — preserva la URL del referer.
     * Sprint 4 ecosistema: ya implementado en otros verticales, replicar.
     */
    public function switchLocale(Request $request, string $locale)
    {
        $localesActivos = config('app.locales', ['es', 'en', 'fr', 'de', 'it', 'pt', 'ja', 'zh']);

        if (! in_array($locale, $localesActivos, true)) {
            abort(404);
        }

        session()->put('locale', $locale);

        $referer = $request->headers->get('referer', url('/'));

        return redirect($referer);
    }
}
