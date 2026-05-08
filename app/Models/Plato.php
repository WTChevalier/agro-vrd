<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Concerns\HasTranslations;
class Plato extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $table = 'platos';


    protected array $translatable = ['nombre', 'descripcion'];
    protected $fillable = [
        'restaurante_id',
        'categoria_id',
        'nombre',
        'slug',
        'descripcion',
        'imagen',
        'precio',
        'precio_oferta',
        'calorias',
        'tiempo_preparacion',
        'ingredientes',
        'alergenos',
        'etiquetas',
        'disponible',
        'destacado',
        'nuevo',
        'orden',
        'calificacion',
        'total_resenas',
        'total_pedidos',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'precio_oferta' => 'decimal:2',
        'calorias' => 'integer',
        'tiempo_preparacion' => 'integer',
        'ingredientes' => 'array',
        'alergenos' => 'array',
        'etiquetas' => 'array',
        'disponible' => 'boolean',
        'destacado' => 'boolean',
        'nuevo' => 'boolean',
        'orden' => 'integer',
        'calificacion' => 'decimal:2',
        'total_resenas' => 'integer',
        'total_pedidos' => 'integer',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getUrlImagenAttribute(): string
    {
        return $this->imagen
            ? asset('storage/' . $this->imagen)
            : asset('images/plato-default.png');
    }

    public function getPrecioFinalAttribute(): float
    {
        return $this->precio_oferta ?? $this->precio;
    }

    public function getTieneOfertaAttribute(): bool
    {
        return $this->precio_oferta !== null && $this->precio_oferta < $this->precio;
    }

    public function getPorcentajeDescuentoAttribute(): ?int
    {
        if (!$this->tiene_oferta) {
            return null;
        }

        return (int) round((($this->precio - $this->precio_oferta) / $this->precio) * 100);
    }

    public function getTextoAlergenosAttribute(): string
    {
        return $this->alergenos ? implode(', ', $this->alergenos) : '';
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class, 'restaurante_id');
    }

    public function favoritos()
    {
        return $this->morphMany(Favorito::class, 'favoritable');
    }

    public function itemsPedido()
    {
        return $this->hasMany(ItemPedido::class, 'plato_id');
    }

    public function itemsCarrito()
    {
        return $this->hasMany(ItemCarrito::class, 'plato_id');
    }

    public function combos()
    {
        return $this->belongsToMany(Combo::class, 'items_combo', 'plato_id', 'combo_id')
            ->withPivot('cantidad', 'obligatorio');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeDisponibles($query)
    {
        return $query->where('disponible', true);
    }

    public function scopeDestacados($query)
    {
        return $query->where('destacado', true);
    }

    public function scopeNuevos($query)
    {
        return $query->where('nuevo', true);
    }

    public function scopeConOferta($query)
    {
        return $query->whereNotNull('precio_oferta')
            ->whereColumn('precio_oferta', '<', 'precio');
    }

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    public function scopePopulares($query)
    {
        return $query->orderByDesc('total_pedidos');
    }

    public function scopeMejorCalificados($query)
    {
        return $query->orderByDesc('calificacion');
    }

    public function scopePorCategoria($query, int $categoriaId)
    {
        return $query->where('categoria_id', $categoriaId);
    }

    public function scopeBuscar($query, string $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'like', "%{$termino}%")
                ->orWhere('descripcion', 'like', "%{$termino}%");
        });
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function calcularPrecioConOpciones(array $opcionesSeleccionadas): float
    {
        $precioBase = $this->precio_final;
        $precioOpciones = 0;

        foreach ($opcionesSeleccionadas as $opcionId => $cantidad) {
            $opcion = OpcionPlato::find($opcionId);
            if ($opcion) {
                $precioOpciones += $opcion->precio_adicional * $cantidad;
            }
        }

        return $precioBase + $precioOpciones;
    }

    public function actualizarCalificacion(): void
    {
        $promedio = $this->resenas()->avg('calificacion') ?? 0;
        $total = $this->resenas()->count();

        $this->update([
            'calificacion' => round($promedio, 2),
            'total_resenas' => $total,
        ]);
    }

    public function incrementarPedidos(): void
    {
        $this->increment('total_pedidos');
    }

    public function validarOpciones(array $opcionesSeleccionadas): array
    {
        $errores = [];

        foreach ($this->gruposOpciones as $grupo) {
            $seleccionadasEnGrupo = 0;

            foreach ($grupo->opciones as $opcion) {
                if (isset($opcionesSeleccionadas[$opcion->id])) {
                    $seleccionadasEnGrupo += $opcionesSeleccionadas[$opcion->id];
                }
            }

            if ($grupo->obligatorio && $seleccionadasEnGrupo < $grupo->minimo) {
                $errores[] = "Debes seleccionar al menos {$grupo->minimo} opción(es) en '{$grupo->nombre}'";
            }

            if ($grupo->maximo && $seleccionadasEnGrupo > $grupo->maximo) {
                $errores[] = "No puedes seleccionar más de {$grupo->maximo} opción(es) en '{$grupo->nombre}'";
            }
        }

        return $errores;
    }
}
