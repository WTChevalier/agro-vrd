<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'restaurante_id', 'categoria_menu_id', 'nombre', 'slug',
        'descripcion', 'precio', 'precio_oferta', 'imagen',
        'tiempo_preparacion', 'calorias', 'orden', 'activo',
        'disponible', 'es_popular', 'total_vendidos',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'disponible' => 'boolean',
        'es_popular' => 'boolean',
    ];

    public function restaurante(): BelongsTo
    {
        return $this->belongsTo(Restaurante::class);
    }

    public function categoriaMenu(): BelongsTo
    {
        return $this->belongsTo(CategoriaMenu::class);
    }
}