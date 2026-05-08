<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Servicio de SSO con visitRD
 *
 * Maneja la comunicación OAuth 2.0 con visitrepublicadominicana.com
 */
class VisitRDSSOService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.visitrd.url', 'https://visitrepublicadominicana.com');
        $this->clientId = config('services.visitrd.client_id');
        $this->clientSecret = config('services.visitrd.client_secret');
    }

    /**
     * Intercambiar código de autorización por tokens.
     */
    public function intercambiarCodigo(string $codigo): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'authorization_code',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => route('sso.callback'),
                    'code' => $codigo,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error intercambiando código SSO', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción intercambiando código SSO', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtener datos del usuario desde visitRD.
     */
    public function obtenerUsuario(string $accessToken): ?array
    {
        try {
            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get("{$this->baseUrl}/api/user");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error obteniendo usuario SSO', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción obteniendo usuario SSO', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Refrescar token de acceso.
     */
    public function refrescarToken(string $refreshToken): ?array
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'refresh_token',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $refreshToken,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Error refrescando token SSO', [
                'status' => $response->status(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción refrescando token SSO', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Revocar token de acceso.
     */
    public function revocarToken(string $token): bool
    {
        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post("{$this->baseUrl}/oauth/revoke", [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'token' => $token,
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::warning('Excepción revocando token SSO', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sincronizar restaurante desde visitRD.
     */
    public function sincronizarRestaurante(int $visitrdId): ?array
    {
        $apiKey = config('services.visitrd.api_key');

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])
                ->timeout(30)
                ->get("{$this->baseUrl}/api/restaurants/{$visitrdId}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error sincronizando restaurante desde visitRD', [
                'visitrd_id' => $visitrdId,
                'status' => $response->status(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Excepción sincronizando restaurante', [
                'visitrd_id' => $visitrdId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtener lista de restaurantes de visitRD para sincronización.
     */
    public function obtenerRestaurantesParaSincronizar(int $pagina = 1, int $porPagina = 50): ?array
    {
        $cacheKey = "visitrd_restaurantes_sync_{$pagina}_{$porPagina}";

        return Cache::remember($cacheKey, 300, function () use ($pagina, $porPagina) {
            $apiKey = config('services.visitrd.api_key');

            try {
                $response = Http::withHeaders([
                    'X-API-Key' => $apiKey,
                ])
                    ->timeout(60)
                    ->get("{$this->baseUrl}/api/restaurants", [
                        'page' => $pagina,
                        'per_page' => $porPagina,
                        'category' => 'restaurante', // Solo restaurantes
                        'active' => true,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                return null;

            } catch (\Exception $e) {
                Log::error('Excepción obteniendo restaurantes para sincronización', [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Notificar a visitRD sobre un nuevo pedido.
     */
    public function notificarPedido(int $visitrdRestauranteId, array $datosPedido): bool
    {
        $apiKey = config('services.visitrd.api_key');

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
            ])
                ->timeout(30)
                ->post("{$this->baseUrl}/api/sazonrd/orders", [
                    'restaurant_id' => $visitrdRestauranteId,
                    'order_data' => $datosPedido,
                ]);

            return $response->successful();

        } catch (\Exception $e) {
            Log::warning('Excepción notificando pedido a visitRD', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
