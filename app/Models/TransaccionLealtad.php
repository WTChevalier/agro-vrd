<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaccionLealtad extends Model
{
    use HasFactory;

    protected $table = 'transacciones_lealtad';

    protected $fillable = [
        'usuario_id',
        'pedido_id',
        'tipo',
        'puntos',
        'descripcion',
    ];

    protected $casts = [
        'puntos' => 'integer',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getPuntosFormateadosAttribute(): string
    {
        $signo = $this->puntos >= 0 ? '+' : '';
        return $signo . number_format($this->puntos);
    }

    public function getEsGananciaAttribute(): bool
    {
        return $this->tipo === 'ganancia';
    }

    public function getEsCanjeAttribute(): bool
    {
        return $this->tipo === 'canje';
    }

    public function getEsExpiracionAttribute(): bool
    {
        return $this->tipo === 'expiracion';
    }

    public function getEsAjusteAttribute(): bool
    {
        return $this->tipo === 'ajuste';
    }

    public function getColorTipoAttribute(): string
    {
        return match ($this->tipo) {
            'ganancia' => 'green',
            'canje' => 'blue',
            'expiracion' => 'red',
            'ajuste' => 'yellow',
            default => 'gray',
        };
    }

    public function getIconoTipoAttribute(): string
    {
        return match ($this->tipo) {
            'ganancia' => 'fas-plus-circle',
            'canje' => 'fas-gift',
            'expiracion' => 'fas-clock',
            'ajuste' => 'fas-edit',
            default => 'fas-star',
        };
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeGanancias($query)
    {
        return $query->where('tipo', 'ganancia');
    }

    public function scopeCanjes($query)
    {
        return $query->where('tipo', 'canje');
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeEsteMes($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }
}
