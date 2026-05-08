<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nivel de Confianza
 *
 * Define los niveles de confianza INTERNOS para restaurantes
 * Estos NO son visibles al público
 */
class NivelConfianza extends Model
{
    protected $table = 'niveles_confianza';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'color',
        'icono',
        'puntuacion_minima',
        'puntuacion_maxima',
        'beneficios',
        'restricciones',
        'requiere_verificacion_frecuente',
        'dias_verificacion',
        'puede_recibir_pedidos',
        'puede_manejar_efectivo',
        'limite_credito',
        'activo',
        'orden',
    ];

    protected $casts = [
        'beneficios' => 'array',
        'restricciones' => 'array',
        'requiere_verificacion_frecuente' => 'boolean',
        'puede_recibir_pedidos' => 'boolean',
        'puede_manejar_efectivo' => 'boolean',
        'limite_credito' => 'decimal:2',
        'activo' => 'boolean',
    ];

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Restaurantes con este nivel de confianza
     */
    public function restaurantes(): HasMany
    {
        return $this->hasMany(Restaurante::class, 'nivel_confianza_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }

    // =========================================================================
    // MÉTODOS ESTÁTICOS
    // =========================================================================

    /**
     * Obtener nivel por puntuación
     */
    public static function obtenerPorPuntuacion(float $puntuacion): ?self
    {
        return self::where('puntuacion_minima', '<=', $puntuacion)
            ->where('puntuacion_maxima', '>=', $puntuacion)
            ->first();
    }

    /**
     * Obtener nivel por código
     */
    public static function obtenerPorCodigo(string $codigo): ?self
    {
        return self::where('codigo', $codigo)->first();
    }

    /**
     * Obtener nivel inicial para nuevos restaurantes
     */
    public static function obtenerNivelInicial(): ?self
    {
        return self::where('codigo', 'nuevo')->first();
    }

    // =========================================================================
    // MÉTODOS DE INSTANCIA
    // =========================================================================

    /**
     * Verificar si permite una acción específica
     */
    public function permiteAccion(string $accion): bool
    {
        return match ($accion) {
            'recibir_pedidos' => $this->puede_recibir_pedidos,
            'manejar_efectivo' => $this->puede_manejar_efectivo,
            default => true,
        };
    }

    /**
     * Obtener próximo nivel de confianza
     */
    public function obtenerSiguienteNivel(): ?self
    {
        return self::where('orden', '>', $this->orden)
            ->where('codigo', '!=', 'suspendido')
            ->orderBy('orden')
            ->first();
    }

    /**
     * Obtener nivel anterior
     */
    public function obtenerNivelAnterior(): ?self
    {
        return self::where('orden', '<', $this->orden)
            ->orderBy('orden', 'desc')
            ->first();
    }

    /**
     * Verificar si es el nivel más alto
     */
    public function esNivelMaximo(): bool
    {
        return $this->codigo === 'socio';
    }

    /**
     * Verificar si está suspendido
     */
    public function estaSuspendido(): bool
    {
        return $this->codigo === 'suspendido';
    }
}
