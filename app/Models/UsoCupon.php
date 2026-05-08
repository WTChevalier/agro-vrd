<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsoCupon extends Model
{
    use HasFactory;

    protected $table = 'uso_cupones';

    protected $fillable = [
        'cupon_id',
        'usuario_id',
        'pedido_id',
        'descuento_aplicado',
    ];

    protected $casts = [
        'descuento_aplicado' => 'decimal:2',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function cupon()
    {
        return $this->belongsTo(Cupon::class, 'cupon_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getDescuentoFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->descuento_aplicado, 2);
    }
}
