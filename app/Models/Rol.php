<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends SpatieRole
{
    protected $table = 'roles';

    // Constantes de Roles del Sistema
    public const SUPER_ADMIN = 'super_admin';
    public const ADMIN = 'admin';
    public const GERENTE = 'gerente';
    public const CAJERO = 'cajero';
    public const MESERO = 'mesero';
    public const COCINERO = 'cocinero';
    public const REPARTIDOR = 'repartidor';
    public const CLIENTE = 'cliente';

    protected $fillable = [
        'name',
        'nombre',
        'guard_name',
        'descripcion',
        'nivel',
        'activo',
        'es_sistema',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'es_sistema' => 'boolean',
    ];

    /**
     * Personal con este rol
     */
    public function personal(): HasMany
    {
        return $this->hasMany(Personal::class, 'rol_id');
    }

    /**
     * Scope para roles activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope ordenado por nivel
     */
    public function scopeOrdenadoPorNivel($query)
    {
        return $query->orderBy('nivel', 'desc');
    }

    /**
     * Verificar si es rol del sistema
     */
    public function esDelSistema(): bool
    {
        return $this->es_sistema || in_array($this->name, self::getRolesDelSistema());
    }

    /**
     * Obtener roles del sistema que no se pueden eliminar
     */
    public static function getRolesDelSistema(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
        ];
    }

    /**
     * Obtener nombre para mostrar
     */
    public function getNombreDisplayAttribute(): string
    {
        return $this->nombre ?? $this->name;
    }
}