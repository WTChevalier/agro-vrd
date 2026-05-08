<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'precio_mensual',
        'precio_anual',
        'moneda',
        'caracteristicas',
        'limites',
        'popular',
        'activo',
        'orden',
        'color',
        'icono',
    ];

    protected $casts = [
        'precio_mensual' => 'decimal:2',
        'precio_anual' => 'decimal:2',
        'caracteristicas' => 'array',
        'limites' => 'array',
        'popular' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Suscripciones de este plan
     */
    public function suscripciones(): HasMany
    {
        return $this->hasMany(Suscripcion::class);
    }

    /**
     * Precio con descuento anual
     */
    public function getAhorroAnualAttribute(): float
    {
        $mensualAnualizado = $this->precio_mensual * 12;
        return $mensualAnualizado - $this->precio_anual;
    }

    /**
     * Porcentaje de descuento anual
     */
    public function getPorcentajeDescuentoAttribute(): float
    {
        if ($this->precio_mensual <= 0) return 0;
        $mensualAnualizado = $this->precio_mensual * 12;
        return round((($mensualAnualizado - $this->precio_anual) / $mensualAnualizado) * 100, 1);
    }

    /**
     * Scope para planes activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope ordenado
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('precio_mensual');
    }

    /**
     * Scope para el plan popular
     */
    public function scopePopular($query)
    {
        return $query->where('popular', true);
    }
}