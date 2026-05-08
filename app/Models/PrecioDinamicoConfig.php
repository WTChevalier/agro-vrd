<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrecioDinamicoConfig extends Model
{
    protected $table = 'precios_dinamicos_config';

    protected $fillable = [
        'nombre', 'aplica_a', 'factores', 'multiplicador_minimo',
        'multiplicador_maximo', 'mostrar_explicacion', 'mensaje_surge', 'activo'
    ];

    protected $casts = [
        'factores' => 'array',
        'mostrar_explicacion' => 'boolean',
        'activo' => 'boolean',
    ];
}