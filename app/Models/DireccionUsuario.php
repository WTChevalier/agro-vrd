<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DireccionUsuario extends Model
{
    protected $table = 'direcciones_usuarios';

    protected $fillable = [
        'usuario_id', 'etiqueta', 'sector', 'direccion_completa',
        'referencia', 'latitud', 'longitud', 'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}