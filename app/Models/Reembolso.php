<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reembolso extends Model
{
    use HasFactory;

    protected $table = 'reembolsos';

    protected $fillable = [
        'pedido_id',
        'pago_id',
        'monto',
        'motivo',
        'estado',
        'id_transaccion_externa',
        'notas_internas',
        'solicitado_por',
        'procesado_por',
        'procesado_en',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'procesado_en' => 'datetime',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getMontoFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->monto, 2);
    }

    public function getEstaPendienteAttribute(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function getEstaAprobadoAttribute(): bool
    {
        return $this->estado === 'aprobado';
    }

    public function getEstaRechazadoAttribute(): bool
    {
        return $this->estado === 'rechazado';
    }

    public function getEstaProcesadoAttribute(): bool
    {
        return $this->estado === 'procesado';
    }

    public function getColorEstadoAttribute(): string
    {
        return match ($this->estado) {
            'pendiente' => 'yellow',
            'aprobado' => 'blue',
            'procesado' => 'green',
            'rechazado' => 'red',
            default => 'gray',
        };
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function pago()
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function solicitante()
    {
        return $this->belongsTo(Usuario::class, 'solicitado_por');
    }

    public function procesador()
    {
        return $this->belongsTo(Usuario::class, 'procesado_por');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', 'aprobado');
    }

    public function scopeProcesados($query)
    {
        return $query->where('estado', 'procesado');
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function aprobar(int $procesadoPor): void
    {
        $this->update([
            'estado' => 'aprobado',
            'procesado_por' => $procesadoPor,
        ]);
    }

    public function rechazar(int $procesadoPor, ?string $notas = null): void
    {
        $this->update([
            'estado' => 'rechazado',
            'procesado_por' => $procesadoPor,
            'notas_internas' => $notas,
            'procesado_en' => now(),
        ]);
    }

    public function procesarReembolso(string $transaccionId): void
    {
        $this->update([
            'estado' => 'procesado',
            'id_transaccion_externa' => $transaccionId,
            'procesado_en' => now(),
        ]);

        $this->pago?->marcarReembolsado();
    }
}
