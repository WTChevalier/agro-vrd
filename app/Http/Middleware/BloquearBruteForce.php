<?php

/**
 * Middleware: BloquearBruteForce
 *
 * Sprint 133B — Defensa contra brute-force basada en auditoria_login (132B).
 * Si la IP del request tiene >MAX_FALLOS fallos en VENTANA_MIN minutos,
 * retorna HTTP 429 con Retry-After hasta que la ventana expire.
 *
 * Pensado para aplicarse al endpoint /panel/login. La ventana móvil
 * significa que si un atacante prueba 5 contraseñas y queda baneado,
 * después de 15 minutos sin intentar puede volver a probar (no ban
 * permanente — eso requeriría tabla aparte).
 *
 * Defensivo: si auditoria_login no existe (rolling deploy) o cualquier
 * error de DB, NO bloquea (permite el request) — preferimos un fail-open
 * antes que cerrar el panel por un bug del middleware.
 */

namespace App\Http\Middleware;

use App\Models\AuditoriaLogin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BloquearBruteForce
{
    private const MAX_FALLOS = 5;
    private const VENTANA_MIN = 15;
    private const RETRY_SEGS = 3600;  // 1h

    public function handle(Request $request, Closure $next): Response
    {
        // Solo verificamos en POST (intento real de login). Los GET son la página.
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        try {
            $ip = $request->ip();
            $fallos = AuditoriaLogin::fallosRecientes($ip, self::VENTANA_MIN);

            if ($fallos >= self::MAX_FALLOS) {
                \Log::warning('brute_force_bloqueado', [
                    'ip' => $ip,
                    'fallos_recientes' => $fallos,
                    'ventana_min' => self::VENTANA_MIN,
                ]);

                return response()->json([
                    'error' => 'Demasiados intentos fallidos. Reintenta más tarde.',
                    'fallos_recientes' => $fallos,
                    'retry_after_seconds' => self::RETRY_SEGS,
                ], 429)
                    ->header('Retry-After', (string) self::RETRY_SEGS);
            }
        } catch (\Throwable $e) {
            // Fail-open: si el middleware se rompe, no bloqueamos al usuario
            \Log::warning('BloquearBruteForce error — permitiendo request', [
                'msg' => $e->getMessage(),
            ]);
        }

        return $next($request);
    }
}
