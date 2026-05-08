<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promocion extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'restaurante_id', 'nombre', 'descripcion', 'tipo',
        'descuento', 'condiciones', 'fecha_inicio', 'fecha_fin', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }
}