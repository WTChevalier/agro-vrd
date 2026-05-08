<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GananciaRepartidor extends Model
{
    use HasFactory;

    protected $table = 'ganancias_repartidor';

    protected $fillable = [
        'repartidor_id',
        'pedido_id',
        'monto',
        'tipo',
        'descripcion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function repartidor()
    {
        return $this->belongsTo(Repartidor::class, 'repartidor_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getMontoFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->monto, 2);
    }

    public function getEsEntregaAttribute(): bool
    {
        return $this->tipo === 'entrega';
    }

    public function getEsPropinaAttribute(): bool
    {
        return $this->tipo === 'propina';
    }

    public function getEsBonoAttribute(): bool
    {
        return $this->tipo === 'bono';
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeEstaSemana($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeEsteMes($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }
}
