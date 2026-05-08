<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LealtadRecompensa extends Model
{
    protected $table = 'lealtad_recompensas';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'puntos_requeridos', 'valor',
        'descuento_porcentaje', 'nivel_minimo_id', 'max_canjes_por_usuario',
        'max_canjes_totales', 'canjes_actuales', 'fecha_inicio', 'fecha_fin',
        'dias_validez', 'imagen', 'activo', 'orden'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];
}