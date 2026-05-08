<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modelo LandingConfig — Sprint 1067 (Fase 1 — Vive RD).
 *
 * Acceso CMS-driven a strings de la landing pública.
 *
 * Uso típico desde Blade:
 *   {{ \App\Models\LandingConfig::get('hero.title') }}
 *   {{ \App\Models\LandingConfig::get('hero.subtitle', 'Fallback si no existe') }}
 *
 * Uso vía helper global:
 *   {{ landing_get('hero.title') }}
 *
 * Cache: cada lookup se cachea 5 minutos por (clave + locale actual).
 * Auto-invalidación: el observer en boot() limpia el cache al save/delete.
 *
 * Campos translatable: el trait HasTranslations (ecosistema) intercepta el getter
 * de `valor` y devuelve la traducción del locale actual desde traducciones_contenido.
 */
class LandingConfig extends Model
{
    use HasTranslations;

    protected $table = 'landings_config';

    protected $fillable = [
        'clave',
        'valor',
        'grupo',
        'is_translatable',
        'descripcion',
        'tipo_input',
        'orden',
        'activo',
    ];

    protected $casts = [
        'valor' => 'array',
        'is_translatable' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Campos a traducir vía trait HasTranslations.
     * El trait revisa is_translatable antes de aplicar.
     */
    protected $translatable = ['valor'];

    // ─── Cache TTL ──────────────────────────────────────────────────────────────────

    public const CACHE_TTL_SECONDS = 300; // 5 minutos

    public const CACHE_KEY_PREFIX = 'landing_config:';

    // ─── Helper estático principal ──────────────────────────────────────────────────

    /**
     * Lookup CMS-driven con cache.
     *
     * @param  string  $clave  Ej: "hero.title"
     * @param  mixed  $fallback  Valor si la clave no existe o está inactiva
     * @param  string|null  $locale  Override locale (default = app()->getLocale())
     * @return mixed
     */
    public static function get(string $clave, $fallback = null, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = self::CACHE_KEY_PREFIX . $clave . ':' . $locale;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($clave, $fallback) {
            $row = self::where('clave', $clave)
                ->where('activo', true)
                ->first();

            if (! $row) {
                return $fallback;
            }

            // El trait HasTranslations devuelve la traducción del locale actual
            // si is_translatable=true, sino devuelve el valor base.
            $valor = $row->valor;

            // valor está casteado a array (porque es JSON). Si es string simple, devuelve string.
            if (is_array($valor) && count($valor) === 1 && isset($valor[0])) {
                return $valor[0];
            }

            return $valor;
        });
    }

    /**
     * Obtener todas las claves de un grupo (ej: "hero", "footer").
     *
     * @return array<string, mixed>
     */
    public static function getByGroup(string $grupo, ?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = self::CACHE_KEY_PREFIX . 'group:' . $grupo . ':' . $locale;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($grupo) {
            return self::where('grupo', $grupo)
                ->where('activo', true)
                ->orderBy('orden')
                ->get()
                ->mapWithKeys(function ($row) {
                    $sufijoClave = str_replace($row->grupo . '.', '', $row->clave);
                    $valor = $row->valor;
                    if (is_array($valor) && count($valor) === 1 && isset($valor[0])) {
                        $valor = $valor[0];
                    }
                    return [$sufijoClave => $valor];
                })
                ->toArray();
        });
    }

    // ─── Cache invalidation ─────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saved(function (LandingConfig $config) {
            self::invalidarCache($config->clave, $config->grupo);
        });

        static::deleted(function (LandingConfig $config) {
            self::invalidarCache($config->clave, $config->grupo);
        });
    }

    protected static function invalidarCache(string $clave, string $grupo): void
    {
        $locales = config('app.locales', ['es', 'en', 'fr', 'de', 'it', 'pt', 'ja', 'zh']);

        foreach ($locales as $locale) {
            Cache::forget(self::CACHE_KEY_PREFIX . $clave . ':' . $locale);
            Cache::forget(self::CACHE_KEY_PREFIX . 'group:' . $grupo . ':' . $locale);
        }
    }
}
