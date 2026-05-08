<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * HubErrorReporter
 *
 * Sprint 670 — Envía uncaught exceptions al endpoint /api/internal/logs del Hub Corporativo.
 *
 * Wire-up en bootstrap/app.php (Laravel 11+):
 *   ->withExceptions(function (Exceptions $exceptions) {
 *       $exceptions->reportable(function (Throwable $e) {
 *           \App\Support\HubErrorReporter::report($e);
 *       });
 *   })
 *
 * Configuración (.env):
 *   HUB_LOGS_URL=https://corporativo.gurztacproductions.com/api/internal/logs
 *   HUB_LOGS_TOKEN=gpat_xxx.yyy
 *   HUB_LOGS_APP=<slug-de-la-app>     (ej: vrd, rmaus, sazonrd)
 *   HUB_LOGS_ENABLED=true
 *
 * Características:
 *  - No-op si HUB_LOGS_ENABLED=false o credenciales faltantes
 *  - Timeout 3s para no bloquear el response al usuario
 *  - Catch+log local si Hub está down (no propaga exception)
 *  - Dedup automático en el Hub side (mismo error en 24h → increment count)
 *  - Skip ciertos exception types ruidosos (NotFoundHttpException, ValidationException)
 */
class HubErrorReporter
{
    private const SKIP_EXCEPTIONS = [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
        \Illuminate\Validation\ValidationException::class,
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
    ];

    public static function report(Throwable $e): void
    {
        if (! env('HUB_LOGS_ENABLED', false)) {
            return;
        }

        $url = env('HUB_LOGS_URL');
        $token = env('HUB_LOGS_TOKEN');
        $app = env('HUB_LOGS_APP', config('app.name', 'unknown'));

        if (! $url || ! $token) {
            return;
        }

        // Skip noisy exception types
        foreach (self::SKIP_EXCEPTIONS as $skip) {
            if ($e instanceof $skip) {
                return;
            }
        }

        try {
            $payload = [
                'app_origen' => substr($app, 0, 80),
                'nivel' => 'error',
                'mensaje' => substr(get_class($e) . ': ' . $e->getMessage(), 0, 500),
                'contexto' => [
                    'file' => $e->getFile() . ':' . $e->getLine(),
                    'trace_first_3' => self::shortTrace($e),
                    'request_url' => request()->fullUrl(),
                    'request_method' => request()->method(),
                    'user_id' => optional(auth()->user())->id,
                ],
            ];

            Http::timeout(3)
                ->withToken($token)
                ->acceptJson()
                ->post($url, $payload);
        } catch (Throwable $reportException) {
            // Hub está down? No queremos cascadear. Log local y seguir.
            Log::warning('HubErrorReporter failed', [
                'reporter_error' => $reportException->getMessage(),
                'original_error' => $e->getMessage(),
            ]);
        }
    }

    private static function shortTrace(Throwable $e): array
    {
        $trace = explode("\n", $e->getTraceAsString());
        return array_slice($trace, 0, 3);
    }
}
