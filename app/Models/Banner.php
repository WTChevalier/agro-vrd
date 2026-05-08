<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $table = 'banners';

    // Constantes de Tipo
    public const TIPO_PRINCIPAL = 'principal';
    public const TIPO_SECUNDARIO = 'secundario';
    public const TIPO_PROMOCION = 'promocion';

    // Constantes de Ubicación
    public const UBICACION_HOME = 'home';
    public const UBICACION_CATEGORIA = 'categoria';
    public const UBICACION_RESTAURANTE = 'restaurante';

    protected $fillable = [
        'titulo',
        'subtitulo',
        'imagen',
        'imagen_movil',
        'url',
        'tipo',
        'ubicacion',
        'orden',
        'activo',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public static function getTipos(): array
    {
        return [
            self::TIPO_PRINCIPAL => 'Principal',
            self::TIPO_SECUNDARIO => 'Secundario',
            self::TIPO_PROMOCION => 'Promoción',
        ];
    }

    public static function getUbicaciones(): array
    {
        return [
            self::UBICACION_HOME => 'Inicio',
            self::UBICACION_CATEGORIA => 'Categoría',
            self::UBICACION_RESTAURANTE => 'Restaurante',
        ];
    }

    /**
     * Scope para banners activos (nombre: active)
     * Usado por BannerResource::getNavigationBadge()
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Alias: scopeActivos
     */
    public function scopeActivos($query)
    {
        return $this->scopeActive($query);
    }

    /**
     * Scope para banners vigentes por fecha
     */
    public function scopeVigentes($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('fecha_inicio')->orWhere('fecha_inicio', '<=', now());
        })->where(function ($q) {
            $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', now());
        });
    }

    /**
     * Scope ordenado
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('created_at', 'desc');
    }
}