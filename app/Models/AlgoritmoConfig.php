<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlgoritmoConfig extends Model
{
    protected $table = 'algoritmo_config';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'version', 'es_version_activa',
        'factores', 'configuracion', 'cache_duracion_minutos', 'activo', 'created_by'
    ];

    protected $casts = [
        'factores' => 'array',
        'configuracion' => 'array',
        'es_version_activa' => 'boolean',
        'activo' => 'boolean',
    ];
}