<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suscripcion extends Model
{
    use HasFactory;

    protected $table = 'suscripciones';

    // Constantes de Estado
    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_PRUEBA = 'prueba';
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_VENCIDA = 'vencida';
    public const ESTADO_SUSPENDIDA = 'suspendida';
    public const ESTADO_CANCELADA = 'cancelada';

    // Constantes de Ciclo
    public const CICLO_MENSUAL = 'mensual';
    public const CICLO_ANUAL = 'anual';
    public const CICLO_TRIMESTRAL = 'trimestral';

    protected $fillable = [
        'restaurante_id',
        'plan_id',
        'estado',
        'ciclo',
        'fecha_inicio',
        'fecha_fin',
        'fecha_proximo_pago',
        'precio',
        'moneda',
        'metodo_pago',
        'notas',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_proximo_pago' => 'date',
        'precio' => 'decimal:2',
    ];

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public static function getEstados(): array
    {
        return [
            self::ESTADO_ACTIVA => 'Activa',
            self::ESTADO_PRUEBA => 'En Prueba',
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_VENCIDA => 'Vencida',
            self::ESTADO_SUSPENDIDA => 'Suspendida',
            self::ESTADO_CANCELADA => 'Cancelada',
        ];
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', self::ESTADO_ACTIVA);
    }
}