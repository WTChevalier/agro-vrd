<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $fillable = [
        'codigo', 'usuario_id', 'restaurante_id', 'repartidor_id', 'cupon_id',
        'estado', 'subtotal', 'costo_delivery', 'descuento', 'itbis', 'total',
        'metodo_pago', 'direccion_entrega', 'latitud_entrega', 'longitud_entrega',
        'notas', 'tiempo_preparacion', 'tiempo_estimado', 'hora_recogida',
        'hora_entrega', 'calificacion', 'comentario_calificacion',
        'cancelado_por', 'motivo_cancelacion', 'comision_repartidor',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    public function repartidor(): BelongsTo
    {
        return $this->belongsTo(Repartidor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class);
    }
}