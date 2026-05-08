<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadisticaLanding extends Model
{
    protected $table = 'estadisticas_landing';

    protected $fillable = [
        'titulo',
        'valor',
        'icono',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }
}