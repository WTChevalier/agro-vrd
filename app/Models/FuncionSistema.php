<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Función del Sistema
 *
 * Representa una función específica dentro de un módulo
 */
class FuncionSistema extends Model
{
    protected $table = 'funciones_sistema';

    protected $fillable = [
        'modulo_id',
        'codigo',
        'nombre',
        'descripcion',
        'tipo',
        'valor_defecto',
        'configurable_zona',
        'configurable_restaurante',
        'activo',
        'orden',
    ];

    protected $casts = [
        'configurable_zona' => 'boolean',
        'configurable_restaurante' => 'boolean',
        'activo' => 'boolean',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Módulo al que pertenece esta función
     */
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(ModuloSistema::class, 'modulo_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }

    public function scopeConfigurablesPorZona($query)
    {
        return $query->where('configurable_zona', true);
    }

    public function scopeConfigurablesPorRestaurante($query)
    {
        return $query->where('configurable_restaurante', true);
    }

    // =========================================================================
    // MÉTODOS
    // =========================================================================

    /**
     * Obtener el valor de esta función para un contexto específico
     *
     * Prioridad: Restaurante > Zona > Global
     */
    public function obtenerValor(?int $restauranteId = null, ?int $zonaId = null): mixed
    {
        // 1. Verificar si el módulo padre está activo
        if (!$this->modulo || !$this->modulo->activo_global) {
            return $this->convertirValor('false');
        }

        // 2. Si hay restaurante, buscar configuración específica
        if ($restauranteId && $this->configurable_restaurante) {
            $valorRestaurante = $this->obtenerValorRestaurante($restauranteId);
            if ($valorRestaurante !== null) {
                return $valorRestaurante;
            }
        }

        // 3. Si hay zona, buscar configuración de zona
        if ($zonaId && $this->configurable_zona) {
            $valorZona = $this->obtenerValorZona($zonaId);
            if ($valorZona !== null) {
                return $valorZona;
            }
        }

        // 4. Retornar valor por defecto
        return $this->convertirValor($this->valor_defecto);
    }

    /**
     * Obtener valor configurado para un restaurante
     */
    private function obtenerValorRestaurante(int $restauranteId): mixed
    {
        $restaurante = Restaurante::find($restauranteId);

        if (!$restaurante || !$restaurante->funciones_habilitadas) {
            return null;
        }

        $funciones = $restaurante->funciones_habilitadas;

        if (isset($funciones[$this->codigo])) {
            return $this->convertirValor($funciones[$this->codigo]);
        }

        return null;
    }

    /**
     * Obtener valor configurado para una zona
     */
    private function obtenerValorZona(int $zonaId): mixed
    {
        $cacheKey = "funcion_{$this->codigo}_zona_{$zonaId}";

        return Cache::remember($cacheKey, 300, function () use ($zonaId) {
            $config = ConfiguracionZona::where('zona_id', $zonaId)
                ->where('modulo_id', $this->modulo_id)
                ->first();

            if (!$config || !$config->configuracion) {
                return null;
            }

            $configuracion = $config->configuracion;

            if (isset($configuracion[$this->codigo])) {
                return $this->convertirValor($configuracion[$this->codigo]);
            }

            return null;
        });
    }

    /**
     * Convertir valor según el tipo de la función
     */
    public function convertirValor(mixed $valor): mixed
    {
        return match ($this->tipo) {
            'booleano' => filter_var($valor, FILTER_VALIDATE_BOOLEAN),
            'numero' => (int) $valor,
            'decimal' => (float) $valor,
            'json' => is_string($valor) ? json_decode($valor, true) : $valor,
            default => $valor,
        };
    }

    /**
     * Verificar si la función está habilitada en un contexto
     */
    public function estaHabilitada(?int $restauranteId = null, ?int $zonaId = null): bool
    {
        if ($this->tipo !== 'booleano') {
            return true; // Las funciones no booleanas siempre están "habilitadas"
        }

        return (bool) $this->obtenerValor($restauranteId, $zonaId);
    }
}
