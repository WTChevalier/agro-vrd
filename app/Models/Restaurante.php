<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Concerns\HasTranslations;


class Restaurante extends Model
{
    protected $table = 'restaurantes';

    protected array $translatable = ['nombre', 'descripcion', 'direccion'];

    protected $fillable = [
        'usuario_id', 'nombre', 'slug', 'descripcion', 'direccion',
        'latitud', 'longitud', 'telefono', 'email', 'imagen_logo',
        'imagen_portada', 'horario_apertura', 'horario_cierre',
        'tiempo_entrega_estimado', 'pedido_minimo', 'costo_delivery',
        'calificacion_promedio', 'total_resenas', 'comision_porcentaje',
        'activo', 'abierto', 'destacado', 'tiene_promocion', 'es_nuevo', 'fcm_token',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'abierto' => 'boolean',
        'destacado' => 'boolean',
        'tiene_promocion' => 'boolean',
        'es_nuevo' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'restaurante_categoria');
    }

    public function categoriasMenu(): HasMany
    {
        return $this->hasMany(CategoriaMenu::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }
}