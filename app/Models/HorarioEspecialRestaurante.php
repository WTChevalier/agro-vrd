<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioEspecialRestaurante extends Model
{
    use HasFactory;

    protected $table = 'horarios_especiales_restaurante';

    protected $fillable = [
        'restaurante_id',
        'fecha',
        'hora_apertura',
        'hora_cierre',
        'cerrado',
        'motivo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_apertura' => 'datetime:H:i',
        'hora_cierre' => 'datetime:H:i',
        'cerrado' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class, 'restaurante_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeFuturos($query)
    {
        return $query->where('fecha', '>=', now()->toDateString());
    }

    public function scopeParaFecha($query, string $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }
}
