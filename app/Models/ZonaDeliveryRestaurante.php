<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonaDeliveryRestaurante extends Model
{
    use HasFactory;

    protected $table = 'zonas_delivery_restaurante';

    protected $fillable = [
        'restaurante_id',
        'sector_id',
        'tarifa_delivery',
        'tiempo_estimado_min',
        'pedido_minimo',
        'activa',
    ];

    protected $casts = [
        'tarifa_delivery' => 'decimal:2',
        'tiempo_estimado_min' => 'integer',
        'pedido_minimo' => 'decimal:2',
        'activa' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class, 'restaurante_id');
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
