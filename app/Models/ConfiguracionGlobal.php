<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Configuración Global del Sistema
 *
 * Almacena configuraciones clave-valor para toda la plataforma
 */
class ConfiguracionGlobal extends Model
{
    protected $table = 'configuracion_global';

    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'grupo',
        'editable',
    ];

    protected $casts = [
        'editable' => 'boolean',
    ];

    // =========================================================================
    // CONSTANTES
    // =========================================================================

    const TIPO_STRING = 'string';
    const TIPO_INTEGER = 'integer';
    const TIPO_FLOAT = 'float';
    const TIPO_BOOLEAN = 'boolean';
    const TIPO_JSON = 'json';

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Obtener valor tipado
     */
    public function getValorTipadoAttribute(): mixed
    {
        return match ($this->tipo) {
            self::TIPO_INTEGER => (int) $this->valor,
            self::TIPO_FLOAT => (float) $this->valor,
            self::TIPO_BOOLEAN => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            self::TIPO_JSON => json_decode($this->valor, true),
            default => $this->valor,
        };
    }

    // =========================================================================
    // MÉTODOS ESTÁTICOS
    // =========================================================================

    /**
     * Obtener valor de configuración
     */
    public static function obtener(string $clave, mixed $defecto = null): mixed
    {
        $cacheKey = "config_global_{$clave}";

        return Cache::remember($cacheKey, 3600, function () use ($clave, $defecto) {
            $config = self::where('clave', $clave)->first();
            return $config ? $config->valor_tipado : $defecto;
        });
    }

    /**
     * Establecer valor de configuración
     */
    public static function establecer(string $clave, mixed $valor, string $tipo = null): void
    {
        if ($tipo === null) {
            $tipo = match (true) {
                is_bool($valor) => self::TIPO_BOOLEAN,
                is_int($valor) => self::TIPO_INTEGER,
                is_float($valor) => self::TIPO_FLOAT,
                is_array($valor) => self::TIPO_JSON,
                default => self::TIPO_STRING,
            };
        }

        if ($tipo === self::TIPO_JSON && is_array($valor)) {
            $valor = json_encode($valor);
        }

        if ($tipo === self::TIPO_BOOLEAN) {
            $valor = $valor ? 'true' : 'false';
        }

        self::updateOrCreate(
            ['clave' => $clave],
            ['valor' => (string) $valor, 'tipo' => $tipo]
        );

        // Limpiar caché
        Cache::forget("config_global_{$clave}");
    }

    /**
     * Verificar si una función está habilitada globalmente
     */
    public static function funcionHabilitada(string $codigoFuncion): bool
    {
        return self::obtener("funciones.{$codigoFuncion}_habilitado", false);
    }

    /**
     * Obtener todas las configuraciones como array
     */
    public static function obtenerTodas(): array
    {
        return Cache::remember('configuracion_global', 3600, function () {
            $configs = [];
            foreach (self::all() as $config) {
                $configs[$config->clave] = $config->valor_tipado;
            }
            return $configs;
        });
    }

    /**
     * Obtener configuraciones por grupo
     */
    public static function obtenerPorGrupo(string $grupo): array
    {
        return self::where('grupo', $grupo)
            ->get()
            ->mapWithKeys(fn ($config) => [$config->clave => $config->valor_tipado])
            ->toArray();
    }

    /**
     * Limpiar caché de configuración
     */
    public static function limpiarCache(): void
    {
        Cache::forget('configuracion_global');

        foreach (self::pluck('clave') as $clave) {
            Cache::forget("config_global_{$clave}");
        }
    }
}
