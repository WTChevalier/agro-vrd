<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    use HasFactory;

    protected $table = 'municipios';

    protected $fillable = [
        'provincia_id',
        'nombre',
        'codigo',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function sectores()
    {
        return $this->hasMany(Sector::class, 'municipio_id');
    }

    public function restaurantes()
    {
        return $this->hasMany(Restaurante::class, 'municipio_id');
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // =============================================
    // ACCESORES
    // =============================================

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombre}, {$this->provincia->nombre}";
    }
}
