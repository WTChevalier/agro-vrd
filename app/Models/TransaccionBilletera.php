<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaccionBilletera extends Model
{
    use HasFactory;

    protected $table = 'transacciones_billetera';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'monto',
        'saldo_anterior',
        'saldo_nuevo',
        'descripcion',
        'referencia_tipo',
        'referencia_id',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'saldo_anterior' => 'decimal:2',
        'saldo_nuevo' => 'decimal:2',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getMontoFormateadoAttribute(): string
    {
        $signo = $this->monto >= 0 ? '+' : '';
        return $signo . 'RD$ ' . number_format($this->monto, 2);
    }

    public function getEsIngresoAttribute(): bool
    {
        return in_array($this->tipo, ['recarga', 'reembolso', 'bono', 'cashback']);
    }

    public function getEsEgresoAttribute(): bool
    {
        return $this->tipo === 'pago';
    }

    public function getColorTipoAttribute(): string
    {
        return $this->es_ingreso ? 'green' : 'red';
    }

    public function getIconoTipoAttribute(): string
    {
        return match ($this->tipo) {
            'recarga' => 'fas-plus-circle',
            'pago' => 'fas-shopping-cart',
            'reembolso' => 'fas-undo',
            'bono' => 'fas-gift',
            'cashback' => 'fas-coins',
            default => 'fas-exchange-alt',
        };
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function referencia()
    {
        return $this->morphTo('referencia', 'referencia_tipo', 'referencia_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeIngresos($query)
    {
        return $query->whereIn('tipo', ['recarga', 'reembolso', 'bono', 'cashback']);
    }

    public function scopeEgresos($query)
    {
        return $query->where('tipo', 'pago');
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

    // =============================================
    // MÉTODOS ESTÁTICOS
    // =============================================

    public static function registrar(
        int $usuarioId,
        string $tipo,
        float $monto,
        string $descripcion,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null
    ): self {
        $usuario = Usuario::findOrFail($usuarioId);
        $saldoAnterior = $usuario->saldo_billetera;
        $saldoNuevo = $saldoAnterior + $monto;

        $transaccion = static::create([
            'usuario_id' => $usuarioId,
            'tipo' => $tipo,
            'monto' => $monto,
            'saldo_anterior' => $saldoAnterior,
            'saldo_nuevo' => $saldoNuevo,
            'descripcion' => $descripcion,
            'referencia_tipo' => $referenciaTipo,
            'referencia_id' => $referenciaId,
        ]);

        $usuario->update(['saldo_billetera' => $saldoNuevo]);

        return $transaccion;
    }
}
