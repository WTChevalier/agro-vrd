<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoMunicipio extends Model
{
    protected $table = 'geo_municipios';

    protected $fillable = [
        'provincia_id',
        'code',
        'name',
        'latitude',
        'longitude',
        'is_active',
        'has_delivery_coverage',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'has_delivery_coverage' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ========== Relaciones ==========

    public function provincia(): BelongsTo
    {
        return $this->belongsTo(GeoProvincia::class, 'provincia_id');
    }

    public function sectores(): HasMany
    {
        return $this->hasMany(GeoSector::class, 'municipio_id')->orderBy('name');
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'municipio_id');
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(RestaurantDeliveryZone::class, 'municipio_id');
    }

    // ========== Accessors ==========

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name . ', ' . ($this->provincia?->name ?? '')
        );
    }

    protected function sectoresCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sectores()->count()
        );
    }

    protected function activeSectoresCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sectores()->active()->count()
        );
    }

    protected function restaurantsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->restaurants()->active()->count()
        );
    }

    protected function coordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->latitude && $this->longitude
                ? ['lat' => $this->latitude, 'lng' => $this->longitude]
                : null
        );
    }

    protected function deliveryCoverageLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->has_delivery_coverage ? 'Con cobertura' : 'Sin cobertura'
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeWithDeliveryCoverage($query)
    {
        return $query->where('has_delivery_coverage', true);
    }

    public function scopeForProvincia($query, int $provinciaId)
    {
        return $query->where('provincia_id', $provinciaId);
    }

    public function scopeWithRestaurants($query)
    {
        return $query->whereHas('restaurants', fn($q) => $q->active());
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 20)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";

        return $query
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<', $radiusKm)
            ->orderBy('distance');
    }

    // ========== Helpers ==========

    public static function findByCode(string $code): ?self
    {
        return static::byCode($code)->first();
    }

    public static function findByName(string $name, ?int $provinciaId = null): ?self
    {
        $query = static::where('name', 'LIKE', "%{$name}%");

        if ($provinciaId) {
            $query->forProvincia($provinciaId);
        }

        return $query->first();
    }

    public function getActiveSectores()
    {
        return $this->sectores()->active()->ordered()->get();
    }

    public static function getForSelect(?int $provinciaId = null)
    {
        $query = static::active()->ordered();

        if ($provinciaId) {
            $query->forProvincia($provinciaId);
        }

        return $query->pluck('name', 'id')->toArray();
    }

    public static function getWithDeliveryCoverage(?int $provinciaId = null)
    {
        $query = static::active()->withDeliveryCoverage()->ordered();

        if ($provinciaId) {
            $query->forProvincia($provinciaId);
        }

        return $query->get();
    }

    public function hasActiveRestaurants(): bool
    {
        return $this->restaurants()->active()->exists();
    }

    public function hasDeliveryService(): bool
    {
        return $this->has_delivery_coverage &&
               $this->deliveryZones()->active()->exists();
    }

    public function distanceTo(float $lat, float $lng): ?float
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat - $this->latitude);
        $lngDiff = deg2rad($lng - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
            sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
