<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    use HasFactory;

    protected $table = 'carritos';

    protected $fillable = [
        'usuario_id',
        'id_sesion',
        'restaurante_id',
        'cupon_id',
        'subtotal',
        'descuento',
        'expira_en',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'expira_en' => 'datetime',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getTotalAttribute(): float
    {
        return $this->subtotal - $this->descuento;
    }

    public function getTotalFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->total, 2);
    }

    public function getSubtotalFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->subtotal, 2);
    }

    public function getDescuentoFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->descuento, 2);
    }

    public function getCantidadItemsAttribute(): int
    {
        return $this->items->sum('cantidad');
    }

    public function getEstaVacioAttribute(): bool
    {
        return $this->items->count() === 0;
    }

    public function getEstaExpiradoAttribute(): bool
    {
        return $this->expira_en && $this->expira_en->isPast();
    }

    public function getCumplePedidoMinimoAttribute(): bool
    {
        if (!$this->restaurante) {
            return true;
        }

        return $this->subtotal >= $this->restaurante->pedido_minimo;
    }

    public function getFaltaParaMinimoAttribute(): float
    {
        if (!$this->restaurante || $this->cumple_pedido_minimo) {
            return 0;
        }

        return $this->restaurante->pedido_minimo - $this->subtotal;
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class, 'restaurante_id');
    }

    public function cupon()
    {
        return $this->belongsTo(Cupon::class, 'cupon_id');
    }

    public function items()
    {
        return $this->hasMany(ItemCarrito::class, 'carrito_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeActivos($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expira_en')
                ->orWhere('expira_en', '>', now());
        });
    }

    public function scopeExpirados($query)
    {
        return $query->where('expira_en', '<=', now());
    }

    public function scopeDelUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeDeSesion($query, string $idSesion)
    {
        return $query->where('id_sesion', $idSesion);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function agregarItem(Plato $plato, int $cantidad = 1, array $opciones = [], ?string $instrucciones = null): ItemCarrito
    {
        // Verificar si el plato es del mismo restaurante
        if ($this->restaurante_id && $this->restaurante_id !== $plato->restaurante_id) {
            throw new \Exception('No puedes agregar platos de diferentes restaurantes al carrito');
        }

        if (!$this->restaurante_id) {
            $this->update(['restaurante_id' => $plato->restaurante_id]);
        }

        // Buscar item existente con las mismas opciones
        $itemExistente = $this->items()
            ->where('plato_id', $plato->id)
            ->where('opciones_seleccionadas', json_encode($opciones))
            ->first();

        if ($itemExistente) {
            $itemExistente->increment('cantidad', $cantidad);
            $itemExistente->actualizarSubtotal();
            $this->recalcularTotales();
            return $itemExistente;
        }

        // Crear nuevo item
        $precioOpciones = $this->calcularPrecioOpciones($opciones);
        $precioUnitario = $plato->precio_final;
        $subtotal = ($precioUnitario + $precioOpciones) * $cantidad;

        $item = $this->items()->create([
            'plato_id' => $plato->id,
            'cantidad' => $cantidad,
            'opciones_seleccionadas' => $opciones,
            'precio_unitario' => $precioUnitario,
            'precio_opciones' => $precioOpciones,
            'subtotal' => $subtotal,
            'instrucciones_especiales' => $instrucciones,
        ]);

        $this->recalcularTotales();

        return $item;
    }

    public function agregarCombo(Combo $combo, int $cantidad = 1): ItemCarrito
    {
        // Verificar restaurante
        if ($this->restaurante_id && $this->restaurante_id !== $combo->restaurante_id) {
            throw new \Exception('No puedes agregar combos de diferentes restaurantes al carrito');
        }

        if (!$this->restaurante_id) {
            $this->update(['restaurante_id' => $combo->restaurante_id]);
        }

        $item = $this->items()->create([
            'combo_id' => $combo->id,
            'cantidad' => $cantidad,
            'precio_unitario' => $combo->precio,
            'precio_opciones' => 0,
            'subtotal' => $combo->precio * $cantidad,
        ]);

        $this->recalcularTotales();

        return $item;
    }

    public function actualizarCantidadItem(int $itemId, int $cantidad): void
    {
        $item = $this->items()->findOrFail($itemId);

        if ($cantidad <= 0) {
            $item->delete();
        } else {
            $item->update(['cantidad' => $cantidad]);
            $item->actualizarSubtotal();
        }

        $this->recalcularTotales();
    }

    public function eliminarItem(int $itemId): void
    {
        $this->items()->where('id', $itemId)->delete();
        $this->recalcularTotales();
    }

    public function vaciar(): void
    {
        $this->items()->delete();
        $this->update([
            'restaurante_id' => null,
            'cupon_id' => null,
            'subtotal' => 0,
            'descuento' => 0,
        ]);
    }

    public function aplicarCupon(Cupon $cupon): array
    {
        if (!$this->usuario) {
            return ['error' => 'Debes iniciar sesión para usar cupones'];
        }

        $errores = $cupon->puedeUsarsePor($this->usuario, $this->restaurante, $this->subtotal);

        if (count($errores) > 0) {
            return ['error' => $errores[0]];
        }

        $descuento = $cupon->calcularDescuento($this->subtotal, $this->restaurante?->tarifa_delivery ?? 0);

        $this->update([
            'cupon_id' => $cupon->id,
            'descuento' => $descuento,
        ]);

        return ['descuento' => $descuento];
    }

    public function removerCupon(): void
    {
        $this->update([
            'cupon_id' => null,
            'descuento' => 0,
        ]);
    }

    public function recalcularTotales(): void
    {
        $subtotal = $this->items()->sum('subtotal');
        $descuento = 0;

        if ($this->cupon) {
            $descuento = $this->cupon->calcularDescuento($subtotal, $this->restaurante?->tarifa_delivery ?? 0);
        }

        $this->update([
            'subtotal' => $subtotal,
            'descuento' => $descuento,
        ]);

        // Limpiar restaurante si el carrito está vacío
        if ($this->items()->count() === 0) {
            $this->update([
                'restaurante_id' => null,
                'cupon_id' => null,
            ]);
        }
    }

    protected function calcularPrecioOpciones(array $opciones): float
    {
        $total = 0;

        foreach ($opciones as $opcionId => $cantidad) {
            $opcion = OpcionPlato::find($opcionId);
            if ($opcion) {
                $total += $opcion->precio_adicional * $cantidad;
            }
        }

        return $total;
    }

    public function extenderExpiracion(int $minutos = 60): void
    {
        $this->update([
            'expira_en' => now()->addMinutes($minutos),
        ]);
    }

    public static function obtenerParaUsuario(?int $usuarioId, ?string $idSesion): self
    {
        if ($usuarioId) {
            return static::firstOrCreate(
                ['usuario_id' => $usuarioId],
                ['subtotal' => 0, 'descuento' => 0]
            );
        }

        if ($idSesion) {
            return static::firstOrCreate(
                ['id_sesion' => $idSesion],
                ['subtotal' => 0, 'descuento' => 0, 'expira_en' => now()->addHours(24)]
            );
        }

        throw new \Exception('Se requiere usuario o sesión para obtener el carrito');
    }

    public function transferirAUsuario(int $usuarioId): void
    {
        // Eliminar carrito existente del usuario
        static::where('usuario_id', $usuarioId)->delete();

        // Transferir este carrito
        $this->update([
            'usuario_id' => $usuarioId,
            'id_sesion' => null,
            'expira_en' => null,
        ]);
    }
}
