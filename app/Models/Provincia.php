<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    use HasFactory;

    protected $table = 'provincias';

    protected $fillable = [
        'nombre',
        'codigo',
        'region',
        'activa',
    ];

    protected $casts = [
        'activa' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'provincia_id');
    }

    public function sectores()
    {
        return $this->hasManyThrough(Sector::class, Municipio::class);
    }

    public function restaurantes()
    {
        return $this->hasMany(Restaurante::class, 'provincia_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopePorRegion($query, string $region)
    {
        return $query->where('region', $region);
    }
}
