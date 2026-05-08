<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResenaRepartidor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'resenas_repartidor';

    protected $fillable = [
        'repartidor_id',
        'usuario_id',
        'pedido_id',
        'calificacion',
        'comentario',
        'fue_puntual',
        'fue_amable',
        'entrega_bien_estado',
        'aprobada',
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'fue_puntual' => 'boolean',
        'fue_amable' => 'boolean',
        'entrega_bien_estado' => 'boolean',
        'aprobada' => 'boolean',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getFechaFormateadaAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getEtiquetasPositivasAttribute(): array
    {
        $etiquetas = [];

        if ($this->fue_puntual) {
            $etiquetas[] = 'Puntual';
        }
        if ($this->fue_amable) {
            $etiquetas[] = 'Amable';
        }
        if ($this->entrega_bien_estado) {
            $etiquetas[] = 'Entrega en buen estado';
        }

        return $etiquetas;
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function repartidor()
    {
        return $this->belongsTo(Repartidor::class, 'repartidor_id');
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
    // SCOPES
    // =============================================

    public function scopeAprobadas($query)
    {
        return $query->where('aprobada', true);
    }

    public function scopeRecientes($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopePositivas($query)
    {
        return $query->where('calificacion', '>=', 4);
    }

    // =============================================
    // MÉTODOS
    // =============================================
}
