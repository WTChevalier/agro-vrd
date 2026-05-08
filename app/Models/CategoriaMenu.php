<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaMenu extends Model
{
    protected $table = 'categorias_menu';

    protected $fillable = [
        'restaurante_id', 'nombre', 'slug', 'descripcion', 'orden', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }
}