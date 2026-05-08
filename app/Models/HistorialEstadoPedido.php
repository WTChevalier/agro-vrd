<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialEstadoPedido extends Model
{
    use HasFactory;

    protected $table = 'historial_estados_pedido';

    protected $fillable = [
        'pedido_id',
        'estado_id',
        'estado_anterior_id',
        'comentario',
        'cambiado_por',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function estado()
    {
        return $this->belongsTo(EstadoPedido::class, 'estado_id');
    }

    public function estadoAnterior()
    {
        return $this->belongsTo(EstadoPedido::class, 'estado_anterior_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'cambiado_por');
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getTiempoTranscurridoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }
}
