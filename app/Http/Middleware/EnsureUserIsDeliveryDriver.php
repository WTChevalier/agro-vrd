<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsDeliveryDriver
{
    /**
     * Handle an incoming request.
     * Verifica que el usuario autenticado sea un repartidor aprobado.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado.'], 401);
            }
            return redirect()->route('login');
        }

        // Admins pueden acceder a todo
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Verificar que el usuario tenga rol de delivery
        if (!$user->isDeliveryDriver()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tienes permiso para acceder a esta sección.'], 403);
            }
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        // Verificar que tenga un perfil de repartidor
        $driverProfile = $user->deliveryDriver;

        if (!$driverProfile) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Debes completar tu perfil de repartidor.'], 403);
            }
            return redirect()->route('filament.delivery.pages.register-driver')
                ->with('warning', 'Debes completar tu perfil de repartidor.');
        }

        // Verificar que el perfil esté aprobado
        if ($driverProfile->status !== 'approved') {
            $message = match ($driverProfile->status) {
                'pending' => 'Tu perfil está pendiente de aprobación.',
                'rejected' => 'Tu perfil ha sido rechazado: ' . ($driverProfile->rejection_reason ?? 'Sin razón especificada.'),
                'suspended' => 'Tu cuenta de repartidor está suspendida.',
                default => 'No puedes acceder al panel de repartidor.',
            };

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'status' => $driverProfile->status], 403);
            }

            return redirect()->route('filament.delivery.pages.dashboard')
                ->with('error', $message);
        }

        return $next($request);
    }
}
