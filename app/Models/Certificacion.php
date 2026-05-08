<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Certificacion extends Model
{
    use HasFactory;

    protected $table = 'certificaciones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'icono',
        'color',
        'requisitos',
        'beneficios',
        'activo',
        'orden',
    ];

    protected $casts = [
        'requisitos' => 'array',
        'beneficios' => 'array',
        'activo' => 'boolean',
    ];

    /**
     * Restaurantes con esta certificación
     */
    public function restaurantes(): BelongsToMany
    {
        return $this->belongsToMany(Restaurante::class, 'certificacion_restaurante')
            ->withPivot('fecha_obtencion', 'fecha_vencimiento', 'estado')
            ->withTimestamps();
    }

    /**
     * Scope para certificaciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope ordenado
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }
}