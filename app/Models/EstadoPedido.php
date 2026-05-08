<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoPedido extends Model
{
    use HasFactory;

    protected $table = 'estados_pedido';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'color',
        'icono',
        'orden',
        'notificar_cliente',
        'notificar_restaurante',
        'notificar_repartidor',
    ];

    protected $casts = [
        'orden' => 'integer',
        'notificar_cliente' => 'boolean',
        'notificar_restaurante' => 'boolean',
        'notificar_repartidor' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'estado_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    // =============================================
    // MÉTODOS ESTÁTICOS
    // =============================================

    public static function pendiente(): ?self
    {
        return static::where('codigo', 'pendiente')->first();
    }

    public static function confirmado(): ?self
    {
        return static::where('codigo', 'confirmado')->first();
    }

    public static function preparando(): ?self
    {
        return static::where('codigo', 'preparando')->first();
    }

    public static function listoParaRecoger(): ?self
    {
        return static::where('codigo', 'listo_recoger')->first();
    }

    public static function enCamino(): ?self
    {
        return static::where('codigo', 'en_camino')->first();
    }

    public static function entregado(): ?self
    {
        return static::where('codigo', 'entregado')->first();
    }

    public static function cancelado(): ?self
    {
        return static::where('codigo', 'cancelado')->first();
    }
}
