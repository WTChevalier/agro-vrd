<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensajeSoporte extends Model
{
    use HasFactory;

    protected $table = 'mensajes_soporte';

    protected $fillable = [
        'ticket_id',
        'usuario_id',
        'mensaje',
        'adjuntos',
        'es_interno',
        'es_automatico',
        'leido',
        'leido_en',
    ];

    protected $casts = [
        'adjuntos' => 'array',
        'es_interno' => 'boolean',
        'es_automatico' => 'boolean',
        'leido' => 'boolean',
        'leido_en' => 'datetime',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getTieneAdjuntosAttribute(): bool
    {
        return $this->adjuntos && count($this->adjuntos) > 0;
    }

    public function getAdjuntosUrlsAttribute(): array
    {
        if (!$this->adjuntos) {
            return [];
        }

        return array_map(fn($adj) => asset('storage/' . $adj), $this->adjuntos);
    }

    public function getFechaFormateadaAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    public function getTiempoTranscurridoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    public function getEsDelClienteAttribute(): bool
    {
        return $this->usuario_id === $this->ticket?->usuario_id;
    }

    public function getEsDelAgenteAttribute(): bool
    {
        return !$this->es_del_cliente && !$this->es_automatico;
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopePublicos($query)
    {
        return $query->where('es_interno', false);
    }

    public function scopeInternos($query)
    {
        return $query->where('es_interno', true);
    }

    public function scopeNoLeidos($query)
    {
        return $query->where('leido', false);
    }

    public function scopeAutomaticos($query)
    {
        return $query->where('es_automatico', true);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function marcarLeido(): void
    {
        if (!$this->leido) {
            $this->update([
                'leido' => true,
                'leido_en' => now(),
            ]);
        }
    }

    public static function crearAutomatico(TicketSoporte $ticket, string $mensaje): self
    {
        return static::create([
            'ticket_id' => $ticket->id,
            'usuario_id' => $ticket->usuario_id,
            'mensaje' => $mensaje,
            'es_automatico' => true,
            'leido' => false,
        ]);
    }
}
