<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcionPlato extends Model
{
    use HasFactory;

    protected $table = 'opciones_plato';

    protected $fillable = [
        'grupo_id',
        'nombre',
        'descripcion',
        'precio_adicional',
        'disponible',
        'orden',
    ];

    protected $casts = [
        'precio_adicional' => 'decimal:2',
        'disponible' => 'boolean',
        'orden' => 'integer',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getTieneCostoAdicionalAttribute(): bool
    {
        return $this->precio_adicional > 0;
    }

    public function getTextoPrecioAttribute(): string
    {
        if ($this->precio_adicional <= 0) {
            return '';
        }

        return '+RD$ ' . number_format($this->precio_adicional, 2);
    }

    // =============================================
    // RELACIONES
    // =============================================

    // =============================================
    // SCOPES
    // =============================================

    public function scopeDisponibles($query)
    {
        return $query->where('disponible', true);
    }

    public function scopeOrdenadas($query)
    {
        return $query->orderBy('orden');
    }
}
