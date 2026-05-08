<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCarrito extends Model
{
    use HasFactory;

    protected $table = 'items_carrito';

    protected $fillable = [
        'carrito_id',
        'plato_id',
        'combo_id',
        'cantidad',
        'opciones_seleccionadas',
        'precio_unitario',
        'precio_opciones',
        'subtotal',
        'instrucciones_especiales',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'opciones_seleccionadas' => 'array',
        'precio_unitario' => 'decimal:2',
        'precio_opciones' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getNombreAttribute(): string
    {
        return $this->plato?->nombre ?? $this->combo?->nombre ?? '';
    }

    public function getImagenUrlAttribute(): string
    {
        return $this->plato?->url_imagen ?? $this->combo?->url_imagen ?? '';
    }

    public function getPrecioTotalUnitarioAttribute(): float
    {
        return $this->precio_unitario + $this->precio_opciones;
    }

    public function getSubtotalFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->subtotal, 2);
    }

    public function getEsPlatoAttribute(): bool
    {
        return $this->plato_id !== null;
    }

    public function getEsComboAttribute(): bool
    {
        return $this->combo_id !== null;
    }

    public function getTextoOpcionesAttribute(): string
    {
        if (!$this->opciones_seleccionadas || count($this->opciones_seleccionadas) === 0) {
            return '';
        }

        $opciones = [];
        foreach ($this->opciones_seleccionadas as $opcionId => $cantidad) {
            $opcion = OpcionPlato::find($opcionId);
            if ($opcion) {
                $texto = $opcion->nombre;
                if ($cantidad > 1) {
                    $texto = "{$cantidad}x {$texto}";
                }
                $opciones[] = $texto;
            }
        }

        return implode(', ', $opciones);
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function carrito()
    {
        return $this->belongsTo(Carrito::class, 'carrito_id');
    }

    public function plato()
    {
        return $this->belongsTo(Plato::class, 'plato_id');
    }

    public function combo()
    {
        return $this->belongsTo(Combo::class, 'combo_id');
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function actualizarSubtotal(): void
    {
        $this->update([
            'subtotal' => ($this->precio_unitario + $this->precio_opciones) * $this->cantidad,
        ]);
    }

    public function incrementarCantidad(int $cantidad = 1): void
    {
        $this->increment('cantidad', $cantidad);
        $this->actualizarSubtotal();
        $this->carrito->recalcularTotales();
    }

    public function decrementarCantidad(int $cantidad = 1): void
    {
        if ($this->cantidad <= $cantidad) {
            $this->delete();
            $this->carrito->recalcularTotales();
            return;
        }

        $this->decrement('cantidad', $cantidad);
        $this->actualizarSubtotal();
        $this->carrito->recalcularTotales();
    }
}
