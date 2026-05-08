<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repartidor extends Model
{
    protected $table = 'repartidores';

    protected $fillable = [
        'usuario_id', 'nombre', 'telefono', 'email', 'cedula', 'foto',
        'vehiculo_tipo', 'vehiculo_marca', 'vehiculo_placa', 'licencia_numero',
        'disponible', 'activo', 'latitud_actual', 'longitud_actual',
        'ultima_ubicacion', 'calificacion_promedio', 'total_entregas', 'fcm_token',
    ];

    protected $casts = [
        'disponible' => 'boolean',
        'activo' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}