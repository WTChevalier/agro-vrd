<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrupoOpcionesPlato extends Model
{
    use HasFactory;

    protected $table = 'grupos_opciones_plato';

    protected $fillable = [
        'plato_id',
        'nombre',
        'descripcion',
        'tipo',
        'obligatorio',
        'minimo',
        'maximo',
        'orden',
    ];

    protected $casts = [
        'obligatorio' => 'boolean',
        'minimo' => 'integer',
        'maximo' => 'integer',
        'orden' => 'integer',
    ];

    // =============================================
    // ACCESORES
    // =============================================

    public function getEsSeleccionUnicaAttribute(): bool
    {
        return $this->tipo === 'unica';
    }

    public function getEsSeleccionMultipleAttribute(): bool
    {
        return $this->tipo === 'multiple';
    }

    public function getTextoRequisitoAttribute(): string
    {
        if (!$this->obligatorio) {
            return 'Opcional';
        }

        if ($this->minimo === $this->maximo) {
            return "Selecciona {$this->minimo}";
        }

        return "Selecciona entre {$this->minimo} y {$this->maximo}";
    }

    // =============================================
    // RELACIONES
    // =============================================

    public function plato()
    {
        return $this->belongsTo(Plato::class, 'plato_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeOrdenados($query)
    {
        return $query->orderBy('orden');
    }

    public function scopeObligatorios($query)
    {
        return $query->where('obligatorio', true);
    }
}
