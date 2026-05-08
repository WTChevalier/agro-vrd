<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoRepartidor extends Model
{
    use HasFactory;

    protected $table = 'pagos_repartidor';

    protected $fillable = [
        'repartidor_id',
        'monto',
        'metodo_pago',
        'referencia',
        'notas',
        'pagado_en',
        'pagado_por',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'pagado_en' => 'datetime',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function repartidor()
    {
        return $this->belongsTo(Repartidor::class, 'repartidor_id');
    }

    public function procesadoPor()
    {
        return $this->belongsTo(Usuario::class, 'pagado_por');
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getMontoFormateadoAttribute(): string
    {
        return 'RD$ ' . number_format($this->monto, 2);
    }

    public function getEstaPagadoAttribute(): bool
    {
        return $this->pagado_en !== null;
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopePagados($query)
    {
        return $query->whereNotNull('pagado_en');
    }

    public function scopePendientes($query)
    {
        return $query->whereNull('pagado_en');
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function marcarPagado(int $pagadoPor, ?string $referencia = null): void
    {
        $this->update([
            'pagado_en' => now(),
            'pagado_por' => $pagadoPor,
            'referencia' => $referencia,
        ]);

        // Descontar del balance del repartidor
        $this->repartidor->decrement('balance_pendiente', $this->monto);
    }
}
