<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    public function handle(Request $request, Closure $next, $role)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Verificar rol
        if ($role === 'restaurante' && !$user->restaurante) {
            abort(403, 'No tienes acceso al panel de restaurante');
        }

        if ($role === 'repartidor' && !$user->repartidor) {
            abort(403, 'No tienes acceso al panel de repartidor');
        }

        return $next($request);
    }
}