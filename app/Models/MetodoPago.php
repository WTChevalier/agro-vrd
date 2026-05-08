<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodos_pago';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'proveedor',
        'ultimos_digitos',
        'marca_tarjeta',
        'nombre_titular',
        'fecha_expiracion',
        'token',
        'predeterminado',
        'activo',
    ];

    protected $casts = [
        'predeterminado' => 'boolean',
        'activo' => 'boolean',
    ];

    protected $hidden = [
        'token',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getDescripcionAttribute(): string
    {
        if ($this->tipo === 'tarjeta') {
            return "{$this->marca_tarjeta} ****{$this->ultimos_digitos}";
        }

        return match ($this->tipo) {
            'billetera' => 'Billetera SazónRD',
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia bancaria',
            default => $this->tipo,
        };
    }

    public function getIconoAttribute(): string
    {
        return match ($this->marca_tarjeta ?? $this->tipo) {
            'visa' => 'fab-cc-visa',
            'mastercard' => 'fab-cc-mastercard',
            'amex' => 'fab-cc-amex',
            'billetera' => 'fas-wallet',
            'efectivo' => 'fas-money-bill',
            'transferencia' => 'fas-university',
            default => 'fas-credit-card',
        };
    }

    public function getEsTarjetaAttribute(): bool
    {
        return $this->tipo === 'tarjeta';
    }

    public function getEsBilleteraAttribute(): bool
    {
        return $this->tipo === 'billetera';
    }

    public function getEstaExpiradaAttribute(): bool
    {
        if (!$this->fecha_expiracion) {
            return false;
        }

        [$mes, $ano] = explode('/', $this->fecha_expiracion);
        $fechaExpiracion = \Carbon\Carbon::createFromDate(2000 + $ano, $mes, 1)->endOfMonth();

        return $fechaExpiracion->isPast();
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'metodo_pago_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeTarjetas($query)
    {
        return $query->where('tipo', 'tarjeta');
    }

    public function scopePredeterminado($query)
    {
        return $query->where('predeterminado', true);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function marcarPredeterminado(): void
    {
        // Quitar predeterminado de otros métodos del usuario
        static::where('usuario_id', $this->usuario_id)
            ->where('id', '!=', $this->id)
            ->update(['predeterminado' => false]);

        $this->update(['predeterminado' => true]);
    }
}
