<?php
/**
 * HubConfigClient — Sprint 830.
 *
 * Cliente liviano para que apps tenant del ecosistema lean configs
 * compartidas desde el Hub Corporativo (single source of truth).
 *
 * Uso típico (en una app tenant — ej VRD, GurzTicket):
 *
 *   $key = HubConfigClient::get('AI', 'groq.api_key');
 *
 * Cache local 5 min en memoria + cache Laravel 1h para resiliencia
 * si el Hub cae temporalmente.
 *
 * Esta clase se replica a las 13 apps tenant (Sprint 831).
 */
namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubConfigClient
{
    private const HUB_URL = 'https://corporativo.gurztacproductions.com';
    private const TTL = 3600; // 1 hora

    private static array $memoryCache = [];

    /**
     * Lee una clave concreta de un grupo.
     */
    public static function get(string $grupo, string $clave, $default = null)
    {
        $configs = self::group($grupo);
        return $configs[$clave] ?? $default;
    }

    /**
     * Lee todo un grupo de configs.
     */
    public static function group(string $grupo): array
    {
        if (isset(self::$memoryCache[$grupo])) {
            return self::$memoryCache[$grupo];
        }

        $cacheKey = "hub_config_{$grupo}";

        $configs = Cache::remember($cacheKey, self::TTL, function () use ($grupo) {
            $token = env('HUB_CONFIG_API_TOKEN');
            if (! $token) {
                Log::warning("HubConfigClient: HUB_CONFIG_API_TOKEN no configurado en .env");
                return [];
            }

            try {
                $resp = Http::timeout(8)
                    ->withToken($token)
                    ->retry(2, 200)
                    ->get(self::HUB_URL . "/api/configs/{$grupo}");

                if ($resp->ok()) {
                    return $resp->json('configs', []);
                }

                Log::warning("HubConfigClient: Hub respondió {$resp->status()} para grupo {$grupo}");
                return [];
            } catch (\Throwable $e) {
                Log::warning("HubConfigClient: excepción consultando Hub", [
                    'grupo' => $grupo,
                    'error' => $e->getMessage(),
                ]);
                return [];
            }
        });

        self::$memoryCache[$grupo] = $configs;
        return $configs;
    }

    /**
     * Force-refresh: limpia cache y vuelve a pedir.
     */
    public static function refresh(?string $grupo = null): void
    {
        if ($grupo) {
            Cache::forget("hub_config_{$grupo}");
            unset(self::$memoryCache[$grupo]);
        } else {
            foreach (['AI', 'Slack', 'GitHub', 'Cloudflare', 'Performance', 'General'] as $g) {
                Cache::forget("hub_config_{$g}");
            }
            self::$memoryCache = [];
        }
    }
}
