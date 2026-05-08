<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiKey
{
    /**
     * Handle an incoming request.
     * Verifica que la solicitud tenga un API key válido para la sincronización con visitRD.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key no proporcionado.',
            ], 401);
        }

        $validKey = config('sazonrd.visitrd.api_key');

        if (!$validKey || $apiKey !== $validKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key inválido.',
            ], 403);
        }

        return $next($request);
    }
}
