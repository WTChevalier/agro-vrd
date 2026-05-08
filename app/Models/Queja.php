<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queja extends Model
{
    protected $table = 'quejas';

    protected $fillable = [
        'numero_ticket', 'user_id', 'user_tipo', 'tipo', 'categoria',
        'prioridad', 'pedido_id', 'restaurante_id', 'repartidor_id',
        'asunto', 'descripcion', 'adjuntos', 'estado', 'asignado_a',
        'departamento', 'resolucion', 'satisfaccion_cliente',
        'compensacion_otorgada', 'puntos_compensacion', 'fecha_primera_respuesta',
        'fecha_resolucion', 'tiempo_resolucion_minutos'
    ];

    protected $casts = [
        'adjuntos' => 'array',
        'fecha_primera_respuesta' => 'datetime',
        'fecha_resolucion' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'user_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}