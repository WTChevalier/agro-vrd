<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permiso del Sistema
 *
 * Define los permisos individuales del sistema
 */
class Permiso extends Model
{
    protected $table = 'permisos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'modulo',
        'nivel_riesgo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // =========================================================================
    // CONSTANTES
    // =========================================================================

    const RIESGO_BAJO = 'bajo';
    const RIESGO_MEDIO = 'medio';
    const RIESGO_ALTO = 'alto';
    const RIESGO_CRITICO = 'critico';

    // =========================================================================
    // RELACIONES
    // =========================================================================

    /**
     * Roles que tienen este permiso
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id')
            ->withTimestamps();
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorModulo($query, string $modulo)
    {
        return $query->where('modulo', $modulo);
    }

    public function scopePorNivelRiesgo($query, string $nivel)
    {
        return $query->where('nivel_riesgo', $nivel);
    }

    public function scopeCriticos($query)
    {
        return $query->where('nivel_riesgo', self::RIESGO_CRITICO);
    }

    // =========================================================================
    // ACCESSORS
    // =========================================================================

    /**
     * Verificar si es permiso crítico
     */
    public function getEsCriticoAttribute(): bool
    {
        return $this->nivel_riesgo === self::RIESGO_CRITICO;
    }

    /**
     * Verificar si es permiso de alto riesgo
     */
    public function getEsAltoRiesgoAttribute(): bool
    {
        return in_array($this->nivel_riesgo, [self::RIESGO_ALTO, self::RIESGO_CRITICO]);
    }

    // =========================================================================
    // MÉTODOS ESTÁTICOS
    // =========================================================================

    /**
     * Obtener todos los módulos únicos
     */
    public static function obtenerModulos(): array
    {
        return self::select('modulo')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo')
            ->toArray();
    }

    /**
     * Obtener permisos agrupados por módulo
     */
    public static function obtenerAgrupadosPorModulo(): \Illuminate\Support\Collection
    {
        return self::activos()
            ->orderBy('modulo')
            ->orderBy('codigo')
            ->get()
            ->groupBy('modulo');
    }
}
