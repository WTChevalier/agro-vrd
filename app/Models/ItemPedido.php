<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPedido extends Model
{
    use HasFactory;

    protected $table = 'items_pedido';

    protected $fillable = [
        'pedido_id',
        'plato_id',
        'combo_id',
        'nombre',
        'cantidad',
        'precio_unitario',
        'precio_opciones',
        'subtotal',
        'opciones_seleccionadas',
        'instrucciones_especiales',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'precio_opciones' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'opciones_seleccionadas' => 'array',
    ];

    // =============================================
    // ACCESORES
    // =============================================

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
        if (!$this->opciones_seleccionadas) {
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

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
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

    public function calcularSubtotal(): float
    {
        return ($this->precio_unitario + $this->precio_opciones) * $this->cantidad;
    }

    public static function crearDesdePlato(Pedido $pedido, Plato $plato, int $cantidad, array $opciones = [], ?string $instrucciones = null): self
    {
        $precioOpciones = 0;
        foreach ($opciones as $opcionId => $cantidadOpcion) {
            $opcion = OpcionPlato::find($opcionId);
            if ($opcion) {
                $precioOpciones += $opcion->precio_adicional * $cantidadOpcion;
            }
        }

        $precioUnitario = $plato->precio_final;
        $subtotal = ($precioUnitario + $precioOpciones) * $cantidad;

        return static::create([
            'pedido_id' => $pedido->id,
            'plato_id' => $plato->id,
            'nombre' => $plato->nombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'precio_opciones' => $precioOpciones,
            'subtotal' => $subtotal,
            'opciones_seleccionadas' => $opciones,
            'instrucciones_especiales' => $instrucciones,
        ]);
    }

    public static function crearDesdeCombo(Pedido $pedido, Combo $combo, int $cantidad): self
    {
        return static::create([
            'pedido_id' => $pedido->id,
            'combo_id' => $combo->id,
            'nombre' => $combo->nombre,
            'cantidad' => $cantidad,
            'precio_unitario' => $combo->precio,
            'precio_opciones' => 0,
            'subtotal' => $combo->precio * $cantidad,
        ]);
    }
}
