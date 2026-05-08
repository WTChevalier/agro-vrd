<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cupon extends Model
{
    protected $table = 'cupones';

    protected $fillable = [
        'codigo', 'descripcion', 'tipo', 'valor', 'minimo_compra',
        'maximo_descuento', 'usos_maximos', 'usos_actuales',
        'fecha_inicio', 'fecha_fin', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
}