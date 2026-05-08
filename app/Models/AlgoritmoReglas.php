<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlgoritmoReglas extends Model
{
    protected $table = 'algoritmo_reglas';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'aplicar_a', 'condiciones',
        'modificador_tipo', 'modificador_valor', 'fecha_inicio',
        'fecha_fin', 'prioridad', 'activo'
    ];

    protected $casts = [
        'condiciones' => 'array',
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];
}