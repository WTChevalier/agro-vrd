<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantDeliveryZone extends Model
{
    protected $fillable = [
        'restaurant_id',
        'provincia_id',
        'municipio_id',
        'sector_id',
        'name',
        'delivery_fee',
        'minimum_order',
        'estimated_time_min',
        'estimated_time_max',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'delivery_fee' => 'decimal:2',
        'minimum_order' => 'decimal:2',
        'estimated_time_min' => 'integer',
        'estimated_time_max' => 'integer',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    // ========== Relaciones ==========

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(GeoProvincia::class, 'provincia_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(GeoMunicipio::class, 'municipio_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(GeoSector::class, 'sector_id');
    }

    // ========== Accessors ==========

    protected function zoneName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->name) {
                    return $this->name;
                }

                $parts = array_filter([
                    $this->sector?->name,
                    $this->municipio?->name,
                    $this->provincia?->name,
                ]);

                return implode(', ', $parts) ?: 'Zona sin nombre';
            }
        );
    }

    protected function formattedDeliveryFee(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->delivery_fee > 0
                ? 'RD$ ' . number_format($this->delivery_fee, 2)
                : 'Gratis'
        );
    }

    protected function formattedMinimumOrder(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->minimum_order, 2)
        );
    }

    protected function estimatedTimeRange(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->estimated_time_min && !$this->estimated_time_max) {
                    return null;
                }

                if ($this->estimated_time_min === $this->estimated_time_max) {
                    return $this->estimated_time_min . ' min';
                }

                return $this->estimated_time_min . '-' . $this->estimated_time_max . ' min';
            }
        );
    }

    protected function coverageLevel(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->sector_id) {
                    return 'sector';
                }
                if ($this->municipio_id) {
                    return 'municipio';
                }
                if ($this->provincia_id) {
                    return 'provincia';
                }
                return 'unknown';
            }
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeInProvincia($query, int $provinciaId)
    {
        return $query->where('provincia_id', $provinciaId);
    }

    public function scopeInMunicipio($query, int $municipioId)
    {
        return $query->where('municipio_id', $municipioId);
    }

    public function scopeInSector($query, int $sectorId)
    {
        return $query->where('sector_id', $sectorId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority')->orderBy('delivery_fee');
    }

    // ========== Helpers ==========

    public function coversLocation(?int $provinciaId, ?int $municipioId, ?int $sectorId): bool
    {
        // Si la zona cubre sector especifico
        if ($this->sector_id) {
            return $this->sector_id === $sectorId;
        }

        // Si la zona cubre municipio (todos sus sectores)
        if ($this->municipio_id) {
            return $this->municipio_id === $municipioId;
        }

        // Si la zona cubre provincia (todos sus municipios)
        if ($this->provincia_id) {
            return $this->provincia_id === $provinciaId;
        }

        return false;
    }

    public static function findForLocation(int $restaurantId, ?int $provinciaId, ?int $municipioId, ?int $sectorId): ?self
    {
        return static::active()
            ->forRestaurant($restaurantId)
            ->ordered()
            ->get()
            ->first(function ($zone) use ($provinciaId, $municipioId, $sectorId) {
                return $zone->coversLocation($provinciaId, $municipioId, $sectorId);
            });
    }

    public function meetsMinimumOrder(float $orderAmount): bool
    {
        return $orderAmount >= $this->minimum_order;
    }
}
