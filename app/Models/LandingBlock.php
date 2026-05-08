<?php

namespace App\Models;

use App\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Modelo LandingBlock — Sprint 1067 (Fase 1 — Vive RD).
 *
 * Bloques de contenido estructurado: testimonials, FAQ, features, etc.
 *
 * El campo `contenido` es JSON con schema variable según `tipo`.
 * El trait HasTranslations traduce el contenido completo (toda la estructura JSON).
 *
 * Uso típico desde Blade:
 *   @foreach (\App\Models\LandingBlock::activos('testimonial') as $t)
 *     <p>{{ $t->contenido['texto'] }} — {{ $t->contenido['nombre'] }}</p>
 *   @endforeach
 */
class LandingBlock extends Model
{
    use HasTranslations;

    protected $table = 'landing_blocks';

    protected $fillable = [
        'tipo',
        'titulo',
        'contenido',
        'orden',
        'activo',
        'metadata',
    ];

    protected $casts = [
        'contenido' => 'array',
        'metadata' => 'array',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Campos a traducir vía trait HasTranslations.
     */
    protected $translatable = ['titulo', 'contenido'];

    public const CACHE_TTL_SECONDS = 300;

    public const CACHE_KEY_PREFIX = 'landing_blocks:';

    public const TIPOS_VALIDOS = [
        'testimonial',
        'faq',
        'feature',
        'rich_text',
        'category_highlight',
        'cta_secundario',
        'partner_logo',
    ];

    // ─── Scope helpers ──────────────────────────────────────────────────────────────

    public function scopeActivos($query, ?string $tipo = null)
    {
        $query->where('activo', true)->orderBy('orden');

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        return $query;
    }

    public static function activos(?string $tipo = null, ?string $locale = null)
    {
        $locale = $locale ?? app()->getLocale();
        $cacheKey = self::CACHE_KEY_PREFIX . ($tipo ?? 'all') . ':' . $locale;

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($tipo) {
            return self::query()->activos($tipo)->get();
        });
    }

    // ─── Cache invalidation ─────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saved(function (LandingBlock $block) {
            self::invalidarCache($block->tipo);
        });

        static::deleted(function (LandingBlock $block) {
            self::invalidarCache($block->tipo);
        });
    }

    protected static function invalidarCache(string $tipo): void
    {
        $locales = config('app.locales', ['es', 'en', 'fr', 'de', 'it', 'pt', 'ja', 'zh']);

        foreach ($locales as $locale) {
            Cache::forget(self::CACHE_KEY_PREFIX . $tipo . ':' . $locale);
            Cache::forget(self::CACHE_KEY_PREFIX . 'all:' . $locale);
        }
    }
}
