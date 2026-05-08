<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitrdLugar extends Model
{
    protected $table = 'visitrd_lugares';

    protected $fillable = [
        'visitrd_id', 'nombre', 'slug', 'descripcion', 'descripcion_corta',
        'tipo', 'categoria', 'tags', 'provincia', 'municipio', 'direccion',
        'latitud', 'longitud', 'imagen_principal', 'imagenes', 'video_url',
        'horarios', 'precios', 'servicios', 'contacto', 'sitio_web',
        'calificacion', 'total_resenas', 'popularidad', 'sincronizado_at',
        'datos_raw', 'activo'
    ];

    protected $casts = [
        'tags' => 'array',
        'imagenes' => 'array',
        'horarios' => 'array',
        'precios' => 'array',
        'servicios' => 'array',
        'contacto' => 'array',
        'datos_raw' => 'array',
        'activo' => 'boolean',
        'sincronizado_at' => 'datetime',
    ];
}