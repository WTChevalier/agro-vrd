<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingPedido extends Model
{
    use HasFactory;

    protected $table = 'tracking_pedido';

    protected $fillable = [
        'pedido_id',
        'latitud',
        'longitud',
    ];

    protected $casts = [
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getCoordenadasAttribute(): array
    {
        return [
            'lat' => (float) $this->latitud,
            'lng' => (float) $this->longitud,
        ];
    }

    public function getUrlGoogleMapsAttribute(): string
    {
        return "https://www.google.com/maps?q={$this->latitud},{$this->longitud}";
    }
}
