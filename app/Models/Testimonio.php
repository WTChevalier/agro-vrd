<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonio extends Model
{
    protected $table = 'testimonios';

    protected $fillable = [
        'nombre',
        'cargo',
        'empresa',
        'contenido',
        'imagen',
        'calificacion',
        'activo',
        'orden',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'calificacion' => 'integer',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('created_at', 'desc');
    }
}