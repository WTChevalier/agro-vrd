<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResenaPlato extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'resenas_plato';

    protected $fillable = [
        'plato_id',
        'usuario_id',
        'pedido_id',
        'calificacion',
        'comentario',
        'imagen',
        'aprobada',
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'aprobada' => 'boolean',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getUrlImagenAttribute(): ?string
    {
        return $this->imagen ? asset('storage/' . $this->imagen) : null;
    }

    public function getTieneImagenAttribute(): bool
    {
        return !empty($this->imagen);
    }

    public function getFechaFormateadaAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function plato()
    {
        return $this->belongsTo(Plato::class, 'plato_id');
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

    public function scopeConImagenes($query)
    {
        return $query->whereNotNull('imagen');
    }

    public function scopeRecientes($query)
    {
        return $query->orderByDesc('created_at');
    }

    // =============================================
    // MÉTODOS
    // =============================================
}
