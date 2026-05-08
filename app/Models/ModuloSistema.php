<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Módulo del Sistema
 *
 * Representa un módulo que puede activarse/desactivarse globalmente
 */
class ModuloSistema extends Model
{
    protected $table = 'modulos_sistema';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'icono',
        'color',
        'activo_global',
        'requiere_configuracion',
        'orden',
    ];

    protected $casts = [
        'activo_global' => 'boolean',
        'requiere_configuracion' => 'boolean',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Funciones que pertenecen a este módulo
     */
    public function funciones(): HasMany
    {
        return $this->hasMany(FuncionSistema::class, 'modulo_id');
    }

    /**
     * Configuraciones de zona para este módulo
     */
    public function configuracionesZona(): HasMany
    {
        return $this->hasMany(ConfiguracionZona::class, 'modulo_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Solo módulos activos globalmente
     */
    public function scopeActivos($query)
    {
        return $query->where('activo_global', true);
    }

    /**
     * Ordenar por campo orden
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }

    // =========================================================================
    // MÉTODOS ESTÁTICOS
    // =========================================================================

    /**
     * Verificar si un módulo está activo globalmente
     */
    public static function estaActivo(string $codigo): bool
    {
        return Cache::remember("modulo_activo_{$codigo}", 300, function () use ($codigo) {
            return self::where('codigo', $codigo)
                ->where('activo_global', true)
                ->exists();
        });
    }

    /**
     * Obtener todos los módulos activos
     */
    public static function obtenerActivos(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember('modulos_activos', 300, function () {
            return self::activos()->ordenado()->get();
        });
    }

    /**
     * Limpiar caché cuando cambia un módulo
     */
    public static function limpiarCache(): void
    {
        Cache::forget('modulos_activos');

        self::all()->each(function ($modulo) {
            Cache::forget("modulo_activo_{$modulo->codigo}");
        });
    }

    // =========================================================================
    // MÉTODOS DE INSTANCIA
    // =========================================================================

    /**
     * Activar módulo globalmente
     */
    public function activar(): bool
    {
        $this->activo_global = true;
        $result = $this->save();

        self::limpiarCache();

        return $result;
    }

    /**
     * Desactivar módulo globalmente
     */
    public function desactivar(): bool
    {
        $this->activo_global = false;
        $result = $this->save();

        self::limpiarCache();

        return $result;
    }

    /**
     * Verificar si el módulo está activo en una zona específica
     */
    public function estaActivoEnZona(int $zonaId): bool
    {
        if (!$this->activo_global) {
            return false;
        }

        $configZona = $this->configuracionesZona()
            ->where('zona_id', $zonaId)
            ->first();

        // Si no hay configuración específica, usar el valor global
        return $configZona ? $configZona->activo : $this->activo_global;
    }
}
