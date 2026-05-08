<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ConfiguracionLanding extends Model
{
    protected $table = 'configuracion_landing';

    protected $fillable = [
        'seccion',
        'clave',
        'valor',
        'tipo',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Obtener valor de configuración
     */
    public static function obtener(string $seccion, string $clave, $default = null)
    {
        $config = static::where('seccion', $seccion)
            ->where('clave', $clave)
            ->where('activo', true)
            ->first();

        return $config ? $config->valor : $default;
    }

    /**
     * Obtener toda una sección
     */
    public static function seccion(string $seccion): array
    {
        return static::where('seccion', $seccion)
            ->where('activo', true)
            ->orderBy('orden')
            ->pluck('valor', 'clave')
            ->toArray();
    }

    /**
     * Obtener todas las configuraciones agrupadas por sección
     */
    public static function todas(): array
    {
        return Cache::remember('landing_config', 3600, function () {
            return static::where('activo', true)
                ->orderBy('seccion')
                ->orderBy('orden')
                ->get()
                ->groupBy('seccion')
                ->map(fn($items) => $items->pluck('valor', 'clave'))
                ->toArray();
        });
    }

    /**
     * Limpiar caché al guardar
     */
    protected static function booted()
    {
        static::saved(fn() => Cache::forget('landing_config'));
        static::deleted(fn() => Cache::forget('landing_config'));
    }

    /**
     * Obtener secciones disponibles
     */
    public static function getSecciones(): array
    {
        return [
            'hero' => 'Hero / Banner Principal',
            'como_funciona' => 'Cómo Funciona',
            'restaurantes' => 'Sección Restaurantes',
            'app' => 'Sección App Móvil',
            'footer' => 'Pie de Página',
            'seo' => 'SEO / Meta Tags',
        ];
    }

    /**
     * Obtener tipos de campo
     */
    public static function getTipos(): array
    {
        return [
            'text' => 'Texto',
            'textarea' => 'Área de Texto',
            'number' => 'Número',
            'image' => 'Imagen',
            'color' => 'Color',
            'url' => 'URL',
            'boolean' => 'Sí/No',
        ];
    }
}