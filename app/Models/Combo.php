<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Combo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'combos';

    protected $fillable = [
        'restaurante_id',
        'nombre',
        'slug',
        'descripcion',
        'imagen',
        'precio',
        'precio_regular',
        'disponible',
        'destacado',
        'fecha_inicio',
        'fecha_fin',
        'orden',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_regular' => 'decimal:2',
        'disponible' => 'boolean',
        'destacado' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'orden' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($combo) {
            if (empty($combo->slug)) {
                $combo->slug = Str::slug($combo->nombre);
            }
        });

        static::saving(function ($combo) {
            // Calcular ahorro automáticamente
            if ($combo->precio_regular && $combo->precio) {
                $combo->ahorro = $combo->precio_regular - $combo->precio;
            }
        });
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getUrlImagenAttribute(): string
    {
        return $this->imagen
            ? asset('storage/' . $this->imagen)
            : asset('images/combo-default.png');
    }

    public function getAhorroAttribute(): float
    {
        return max(0, ($this->precio_regular ?? 0) - $this->precio);
    }

    public function getPorcentajeAhorroAttribute(): int
    {
        if (!$this->precio_regular || $this->precio_regular <= 0) {
            return 0;
        }

        return (int) round(($this->ahorro / $this->precio_regular) * 100);
    }

    public function getEstaVigenteAttribute(): bool
    {
        if (!$this->disponible) {
            return false;
        }

        $ahora = now();

        if ($this->fecha_inicio && $ahora < $this->fecha_inicio) {
            return false;
        }

        if ($this->fecha_fin && $ahora > $this->fecha_fin) {
            return false;
        }

        return true;
    }

    public function getPrecioFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->precio, 2);
    }

    public function getPrecioRegularFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->precio_regular, 2);
    }

    public function getAhorroFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->ahorro, 2);
    }

    public function getCantidadItemsAttribute(): int
    {
        return $this->items->sum('cantidad');
    }

    public function getResumenItemsAttribute(): string
    {
        return $this->items->map(function ($item) {
            return ($item->cantidad > 1 ? $item->cantidad . 'x ' : '') . $item->plato?->nombre;
        })->filter()->implode(' + ');
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class, 'restaurante_id');
    }

    public function items()
    {
        return $this->hasMany(ItemCombo::class, 'combo_id');
    }

    public function platos()
    {
        return $this->belongsToMany(Plato::class, 'items_combo', 'combo_id', 'plato_id')
            ->withPivot('cantidad', 'obligatorio');
    }

    public function itemsCarrito()
    {
        return $this->hasMany(ItemCarrito::class, 'combo_id');
    }

    public function itemsPedido()
    {
        return $this->hasMany(ItemPedido::class, 'combo_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeDisponibles($query)
    {
        return $query->where('disponible', true);
    }

    public function scopeVigentes($query)
    {
        return $query->where('disponible', true)
            ->where(function ($q) {
                $q->whereNull('fecha_inicio')
                    ->orWhere('fecha_inicio', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', now());
            });
    }

    public function scopeDestacados($query)
    {
        return $query->where('destacado', true);
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    public function scopeDelRestaurante($query, int $restauranteId)
    {
        return $query->where('restaurante_id', $restauranteId);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function calcularPrecioRegular(): float
    {
        return $this->items->sum(function ($item) {
            return ($item->plato?->precio_final ?? 0) * $item->cantidad;
        });
    }

    public function actualizarPrecioRegular(): void
    {
        $this->update([
            'precio_regular' => $this->calcularPrecioRegular(),
        ]);
    }
}
