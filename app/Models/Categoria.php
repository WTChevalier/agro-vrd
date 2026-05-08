<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'nombre', 'slug', 'descripcion', 'icono', 'imagen', 'orden', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function restaurantes(): BelongsToMany
    {
        return $this->belongsToMany(Restaurante::class, 'restaurante_categoria');
    }
}