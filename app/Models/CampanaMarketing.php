<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampanaMarketing extends Model
{
    protected $table = 'campanas_marketing';

    protected $fillable = [
        'nombre', 'descripcion', 'tipo', 'objetivo', 'segmento_id',
        'filtros_audiencia', 'audiencia_estimada', 'asunto', 'contenido_html',
        'contenido_texto', 'imagen_url', 'fecha_inicio', 'fecha_fin',
        'frecuencia', 'enviados', 'abiertos', 'clicks', 'conversiones',
        'estado', 'created_by'
    ];

    protected $casts = [
        'filtros_audiencia' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
}