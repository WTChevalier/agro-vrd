<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReporteResena extends Model
{
    use HasFactory;

    protected $table = 'reportes_resena';

    protected $fillable = [
        'resena_restaurante_id',
        'usuario_id',
        'motivo',
        'descripcion',
        'estado',
        'revisado_por',
        'revisado_en',
    ];

    protected $casts = [
        'revisado_en' => 'datetime',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getEstaPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function getEstaRevisadoAttribute(): bool
    {
        return in_array($this->estado, ['aceptado', 'rechazado']);
    }

    public function getColorEstadoAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'yellow',
            'aceptado' => 'green',
            'rechazado' => 'red',
            default => 'gray',
        };
    }

    public function getMotivoFormateadoAttribute(): string
    {
        return match ($this->motivo) {
            'spam' => 'Spam',
            'contenido_inapropiado' => 'Contenido inapropiado',
            'informacion_falsa' => 'Información falsa',
            'otro' => 'Otro',
            default => $this->motivo,
        };
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function revisor()
    {
        return $this->belongsTo(Usuario::class, 'revisado_por');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeRevisados($query)
    {
        return $query->whereIn('estado', ['aceptado', 'rechazado']);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function aceptar(int $revisadoPor): void
    {
        $this->update([
            'estado' => 'aceptado',
            'revisado_por' => $revisadoPor,
            'revisado_en' => now(),
        ]);

        // Ocultar la reseña reportada
        $this->resena?->update(['aprobada' => false]);
    }

    public function rechazar(int $revisadoPor): void
    {
        $this->update([
            'estado' => 'rechazado',
            'revisado_por' => $revisadoPor,
            'revisado_en' => now(),
        ]);
    }
}
