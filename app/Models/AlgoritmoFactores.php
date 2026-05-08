<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlgoritmoFactores extends Model
{
    protected $table = 'algoritmo_factores';

    protected $fillable = [
        'nombre', 'descripcion', 'categoria', 'tipo_valor',
        'valor_minimo', 'valor_maximo', 'valor_default',
        'invertido', 'permite_negativo', 'modelo_fuente',
        'campo_fuente', 'calculo_custom', 'activo', 'orden'
    ];

    protected $casts = [
        'invertido' => 'boolean',
        'permite_negativo' => 'boolean',
        'activo' => 'boolean',
    ];
}