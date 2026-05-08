<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    use HasFactory;

    protected $table = 'sectores';

    protected $fillable = [
        'municipio_id',
        'nombre',
        'codigo_postal',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function provincia()
    {
        return $this->hasOneThrough(
            Provincia::class,
            Municipio::class,
            'id',
            'id',
            'municipio_id',
            'provincia_id'
        );
    }

    public function restaurantes()
    {
        return $this->hasMany(Restaurante::class, 'sector_id');
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
        return "{$this->nombre}, {$this->municipio->nombre}, {$this->municipio->provincia->nombre}";
    }
}
