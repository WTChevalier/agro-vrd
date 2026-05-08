<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZonaDelivery extends Model
{
    use HasFactory;

    protected $table = 'zonas_delivery';

    protected $fillable = [
        'nombre',
        'descripcion',
        'poligono',
        'tarifa_base',
        'tarifa_por_km',
        'tiempo_estimado_min',
        'activa',
    ];

    protected $casts = [
        'poligono' => 'array',
        'tarifa_base' => 'decimal:2',
        'tarifa_por_km' => 'decimal:2',
        'tiempo_estimado_min' => 'integer',
        'activa' => 'boolean',
    ];

    // =============================================
    // RELACIONES
    // =============================================

    public function sectores()
    {
        return $this->belongsToMany(Sector::class, 'zona_delivery_sector', 'zona_delivery_id', 'sector_id');
    }

    public function repartidores()
    {
        return $this->belongsToMany(Repartidor::class, 'repartidor_zona_delivery', 'zona_delivery_id', 'repartidor_id');
    }

    public function repartidoresDisponibles()
    {
        return $this->repartidores()->where('estado', 'aprobado')->where('disponible', true);
    }

    // =============================================
    // SCOPES
    // =============================================

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    // =============================================
    // MÉTODOS
    // =============================================

    public function calcularTarifa(float $distanciaKm): float
    {
        return $this->tarifa_base + ($this->tarifa_por_km * $distanciaKm);
    }

    public function contienePunto(float $latitud, float $longitud): bool
    {
        if (!$this->poligono || count($this->poligono) < 3) {
            return false;
        }

        $dentro = false;
        $n = count($this->poligono);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $this->poligono[$i]['lng'];
            $yi = $this->poligono[$i]['lat'];
            $xj = $this->poligono[$j]['lng'];
            $yj = $this->poligono[$j]['lat'];

            if ((($yi > $latitud) != ($yj > $latitud)) &&
                ($longitud < ($xj - $xi) * ($latitud - $yi) / ($yj - $yi) + $xi)) {
                $dentro = !$dentro;
            }
        }

        return $dentro;
    }
}
