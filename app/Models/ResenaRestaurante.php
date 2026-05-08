<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResenaRestaurante extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'resenas_restaurante';

    protected $fillable = [
        'restaurante_id',
        'usuario_id',
        'pedido_id',
        'calificacion',
        'titulo',
        'comentario',
        'imagenes',
        'calificacion_comida',
        'calificacion_servicio',
        'calificacion_entrega',
        'aprobada',
        'destacada',
        'respuesta_restaurante',
        'respondido_en',
    ];

    protected $casts = [
        'calificacion' => 'integer',
        'calificacion_comida' => 'integer',
        'calificacion_servicio' => 'integer',
        'calificacion_entrega' => 'integer',
        'imagenes' => 'array',
        'aprobada' => 'boolean',
        'destacada' => 'boolean',
        'respondido_en' => 'datetime',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getImagenesUrlsAttribute(): array
    {
        if (!$this->imagenes) {
            return [];
        }

        return array_map(fn($img) => asset('storage/' . $img), $this->imagenes);
    }

    public function getTieneImagenesAttribute(): bool
    {
        return $this->imagenes && count($this->imagenes) > 0;
    }

    public function getTieneRespuestaAttribute(): bool
    {
        return !empty($this->respuesta_restaurante);
    }

    public function getFechaFormateadaAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getCalificacionEstrellaAttribute(): string
    {
        return str_repeat('⭐', $this->calificacion);
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class, 'restaurante_id');
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

    public function scopeDestacadas($query)
    {
        return $query->where('destacada', true);
    }

    public function scopeConImagenes($query)
    {
        return $query->whereNotNull('imagenes');
    }

    public function scopeRecientes($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeMejorCalificadas($query)
    {
        return $query->orderByDesc('calificacion');
    }

    public function scopePorCalificacion($query, int $calificacion)
    {
        return $query->where('calificacion', $calificacion);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function rechazar(): void
    {
        $this->delete();
    }

    public function responder(string $respuesta): void
    {
        $this->update([
            'respuesta_restaurante' => $respuesta,
            'respondido_en' => now(),
        ]);
    }

    public function marcarDestacada(bool $destacada = true): void
    {
        $this->update(['destacada' => $destacada]);
    }

    public function obtenerVotosUtiles(): int
    {
        return $this->votos()->where('util', true)->count();
    }

    public function obtenerVotosNoUtiles(): int
    {
        return $this->votos()->where('util', false)->count();
    }
}
