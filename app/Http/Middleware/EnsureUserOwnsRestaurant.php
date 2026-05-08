<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserOwnsRestaurant
{
    /**
     * Handle an incoming request.
     * Verifica que el usuario autenticado sea propietario del restaurante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admins pueden acceder a todo
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Verificar que el usuario tenga rol de restaurant_owner
        if (!$user->isRestaurantOwner()) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        // Verificar que tenga al menos un restaurante
        if ($user->restaurants()->count() === 0) {
            return redirect()->route('filament.restaurant.pages.register-restaurant')
                ->with('warning', 'Primero debes registrar tu restaurante.');
        }

        return $next($request);
    }
}
