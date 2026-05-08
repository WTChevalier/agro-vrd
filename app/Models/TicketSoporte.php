<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSoporte extends Model
{
    use HasFactory;

    protected $table = 'tickets_soporte';

    protected $fillable = [
        'numero_ticket',
        'usuario_id',
        'pedido_id',
        'restaurante_id',
        'asignado_a',
        'asunto',
        'descripcion',
        'categoria',
        'prioridad',
        'estado',
        'resolucion',
        'satisfaccion',
        'primera_respuesta_en',
        'resuelto_en',
        'cerrado_en',
    ];

    protected $casts = [
        'satisfaccion' => 'integer',
        'primera_respuesta_en' => 'datetime',
        'resuelto_en' => 'datetime',
        'cerrado_en' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->numero_ticket)) {
                $ticket->numero_ticket = static::generarNumeroTicket();
            }
        });
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getEstaAbiertoAttribute(): bool
    {
        return in_array($this->estado, ['abierto', 'en_progreso', 'esperando_respuesta']);
    }

    public function getEstaResueltoAttribute(): bool
    {
        return $this->estado === 'resuelto';
    }

    public function getEstaCerradoAttribute(): bool
    {
        return $this->estado === 'cerrado';
    }

    public function getColorEstadoAttribute(): string
    {
        return match ($this->estado) {
            'abierto' => 'blue',
            'en_progreso' => 'yellow',
            'esperando_respuesta' => 'orange',
            'resuelto' => 'green',
            'cerrado' => 'gray',
            default => 'gray',
        };
    }

    public function getColorPrioridadAttribute(): string
    {
        return match ($this->prioridad) {
            'urgente' => 'red',
            'alta' => 'orange',
            'media' => 'yellow',
            'baja' => 'green',
            default => 'gray',
        };
    }

    public function getCategoriaFormateadaAttribute(): string
    {
        return match ($this->categoria) {
            'problema_pedido' => 'Problema con pedido',
            'pago' => 'Pago',
            'delivery' => 'Delivery',
            'restaurante' => 'Restaurante',
            'tecnico' => 'Técnico',
            'sugerencia' => 'Sugerencia',
            'otro' => 'Otro',
            default => $this->categoria,
        };
    }

    public function getPrioridadFormateadaAttribute(): string
    {
        return match ($this->prioridad) {
            'urgente' => 'Urgente',
            'alta' => 'Alta',
            'media' => 'Media',
            'baja' => 'Baja',
            default => $this->prioridad,
        };
    }

    public function getTiempoRespuestaAttribute(): ?string
    {
        if (!$this->primera_respuesta_en) {
            return null;
        }

        return $this->created_at->diffForHumans($this->primera_respuesta_en, true);
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class, 'restaurante_id');
    }

    public function agente()
    {
        return $this->belongsTo(Usuario::class, 'asignado_a');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeAbiertos($query)
    {
        return $query->whereIn('estado', ['abierto', 'en_progreso', 'esperando_respuesta']);
    }

    public function scopeResueltos($query)
    {
        return $query->where('estado', 'resuelto');
    }

    public function scopeCerrados($query)
    {
        return $query->where('estado', 'cerrado');
    }

    public function scopeSinAsignar($query)
    {
        return $query->whereNull('asignado_a');
    }

    public function scopeUrgentes($query)
    {
        return $query->where('prioridad', 'urgente');
    }

    public function scopePorCategoria($query, string $categoria)
    {
        return $query->where('categoria', $categoria);
    }

    public function scopeDelUsuario($query, int $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function scopeAsignadoA($query, int $agenteId)
    {
        return $query->where('asignado_a', $agenteId);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public static function generarNumeroTicket(): string
    {
        $prefijo = 'TKT';
        $fecha = now()->format('ymd');
        $aleatorio = strtoupper(substr(md5(uniqid()), 0, 4));

        return "{$prefijo}-{$fecha}-{$aleatorio}";
    }

    public function asignar(int $agenteId): void
    {
        $this->update([
            'asignado_a' => $agenteId,
            'estado' => 'en_progreso',
        ]);
    }

    public function responder(int $usuarioId, string $mensaje, array $adjuntos = [], bool $esInterno = false): MensajeSoporte
    {
        $mensajeCreado = $this->mensajes()->create([
            'usuario_id' => $usuarioId,
            'mensaje' => $mensaje,
            'adjuntos' => $adjuntos,
            'es_interno' => $esInterno,
        ]);

        // Registrar primera respuesta
        if (!$this->primera_respuesta_en && $usuarioId !== $this->usuario_id) {
            $this->update(['primera_respuesta_en' => now()]);
        }

        // Cambiar estado según quién responde
        if ($usuarioId === $this->usuario_id) {
            $this->update(['estado' => 'abierto']);
        } else {
            $this->update(['estado' => 'esperando_respuesta']);
        }

        return $mensajeCreado;
    }

    public function resolver(string $resolucion): void
    {
        $this->update([
            'estado' => 'resuelto',
            'resolucion' => $resolucion,
            'resuelto_en' => now(),
        ]);
    }

    public function cerrar(): void
    {
        $this->update([
            'estado' => 'cerrado',
            'cerrado_en' => now(),
        ]);
    }

    public function reabrir(): void
    {
        $this->update([
            'estado' => 'abierto',
            'resuelto_en' => null,
            'cerrado_en' => null,
        ]);
    }

    public function calificar(int $satisfaccion): void
    {
        $this->update(['satisfaccion' => min(5, max(1, $satisfaccion))]);
    }
}
