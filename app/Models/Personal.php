<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personal extends Model
{
    use HasFactory;

    protected $table = 'personal';

    // Constantes de Tipo
    public const TIPO_INTERNO = 'interno';
    public const TIPO_EXTERNO = 'externo';
    public const TIPO_TEMPORAL = 'temporal';
    public const TIPO_VERIFICADOR = 'verificador';

    // Constantes de Estado
    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';
    public const ESTADO_VACACIONES = 'vacaciones';
    public const ESTADO_SUSPENDIDO = 'suspendido';

    // Constantes de Cargo
    public const CARGO_VERIFICADOR = 'verificador';
    public const CARGO_GERENTE = 'gerente';
    public const CARGO_SUPERVISOR = 'supervisor';

    protected $fillable = [
        'user_id',
        'restaurante_id',
        'rol_id',
        'cargo',
        'departamento',
        'tipo',
        'estado',
        'fecha_ingreso',
        'fecha_salida',
        'salario',
        'tipo_contrato',
        'horario',
        'telefono_emergencia',
        'contacto_emergencia',
        'documentos',
        'notas',
        'activo',
        'es_verificador',
    ];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_salida' => 'date',
        'salario' => 'decimal:2',
        'horario' => 'array',
        'documentos' => 'array',
        'activo' => 'boolean',
        'es_verificador' => 'boolean',
    ];

    /**
     * Usuario asociado
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Restaurante donde trabaja
     */
    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    /**
     * Rol del personal
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    /**
     * Nombre completo del empleado
     */
    public function getNombreCompletoAttribute(): string
    {
        return $this->user ? $this->user->name : 'Sin usuario';
    }

    /**
     * Obtener tipos disponibles
     */
    public static function getTipos(): array
    {
        return [
            self::TIPO_INTERNO => 'Interno',
            self::TIPO_EXTERNO => 'Externo',
            self::TIPO_TEMPORAL => 'Temporal',
            self::TIPO_VERIFICADOR => 'Verificador',
        ];
    }

    /**
     * Obtener estados disponibles
     */
    public static function getEstados(): array
    {
        return [
            self::ESTADO_ACTIVO => 'Activo',
            self::ESTADO_INACTIVO => 'Inactivo',
            self::ESTADO_VACACIONES => 'Vacaciones',
            self::ESTADO_SUSPENDIDO => 'Suspendido',
        ];
    }

    /**
     * Scope para personal activo
     */
    public function scopeActivos($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Scope para personal activo (alias)
     */
    public function scopeActive($query)
    {
        return $this->scopeActivos($query);
    }

    /**
     * Scope para verificadores
     * Usado por RestauranteResource
     */
    public function scopeVerificadores($query)
    {
        return $query->where(function($q) {
            $q->where('es_verificador', true)
              ->orWhere('cargo', self::CARGO_VERIFICADOR)
              ->orWhere('tipo', self::TIPO_VERIFICADOR);
        })->where('estado', self::ESTADO_ACTIVO);
    }

    /**
     * Scope por tipo
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope por restaurante
     */
    public function scopeDelRestaurante($query, $restauranteId)
    {
        return $query->where('restaurante_id', $restauranteId);
    }

    /**
     * Scope por cargo
     */
    public function scopeCargo($query, string $cargo)
    {
        return $query->where('cargo', $cargo);
    }
}