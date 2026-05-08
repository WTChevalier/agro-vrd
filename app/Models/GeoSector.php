<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GeoSector extends Model
{
    protected $table = 'geo_sectores';

    protected $fillable = [
        'municipio_id',
        'code',
        'name',
        'postal_code',
        'latitude',
        'longitude',
        'polygon',
        'is_active',
        'has_delivery_coverage',
        'delivery_fee_adjustment',
        'estimated_delivery_time',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'polygon' => 'array',
        'is_active' => 'boolean',
        'has_delivery_coverage' => 'boolean',
        'delivery_fee_adjustment' => 'decimal:2',
        'estimated_delivery_time' => 'integer',
        'sort_order' => 'integer',
    ];

    // ========== Relaciones ==========

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(GeoMunicipio::class, 'municipio_id');
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'sector_id');
    }

    // ========== Accessors ==========

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = [
                    $this->name,
                    $this->municipio?->name,
                    $this->municipio?->provincia?->name,
                ];
                return implode(', ', array_filter($parts));
            }
        );
    }

    protected function shortName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->name . ', ' . ($this->municipio?->name ?? '')
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

    protected function deliveryFeeAdjustmentFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->delivery_fee_adjustment || $this->delivery_fee_adjustment == 0) {
                    return 'Sin ajuste';
                }
                $sign = $this->delivery_fee_adjustment > 0 ? '+' : '';
                return $sign . 'RD$ ' . number_format($this->delivery_fee_adjustment, 2);
            }
        );
    }

    protected function estimatedDeliveryTimeText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->estimated_delivery_time
                ? "{$this->estimated_delivery_time} min"
                : 'No especificado'
        );
    }

    protected function restaurantsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->restaurants()->active()->count()
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

    public function scopeForMunicipio($query, int $municipioId)
    {
        return $query->where('municipio_id', $municipioId);
    }

    public function scopeWithRestaurants($query)
    {
        return $query->whereHas('restaurants', fn($q) => $q->active());
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    public function scopeByPostalCode($query, string $postalCode)
    {
        return $query->where('postal_code', $postalCode);
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
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

    public static function findByPostalCode(string $postalCode): ?self
    {
        return static::byPostalCode($postalCode)->first();
    }

    public static function findByName(string $name, ?int $municipioId = null): ?self
    {
        $query = static::where('name', 'LIKE', "%{$name}%");

        if ($municipioId) {
            $query->forMunicipio($municipioId);
        }

        return $query->first();
    }

    public static function getForSelect(?int $municipioId = null)
    {
        $query = static::active()->ordered();

        if ($municipioId) {
            $query->forMunicipio($municipioId);
        }

        return $query->pluck('name', 'id')->toArray();
    }

    public static function getWithDeliveryCoverage(?int $municipioId = null)
    {
        $query = static::active()->withDeliveryCoverage()->ordered();

        if ($municipioId) {
            $query->forMunicipio($municipioId);
        }

        return $query->get();
    }

    public function hasActiveRestaurants(): bool
    {
        return $this->restaurants()->active()->exists();
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

    public function containsPoint(float $lat, float $lng): bool
    {
        if (!$this->polygon || empty($this->polygon)) {
            return false;
        }

        $polygon = $this->polygon;
        $n = count($polygon);
        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i]['lng'];
            $yi = $polygon[$i]['lat'];
            $xj = $polygon[$j]['lng'];
            $yj = $polygon[$j]['lat'];

            if ((($yi > $lat) !== ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    public function calculateDeliveryFee(float $baseFee): float
    {
        return $baseFee + ($this->delivery_fee_adjustment ?? 0);
    }

    public static function findByCoordinates(float $lat, float $lng): ?self
    {
        // Primero buscar por poligono
        $sectors = static::active()->whereNotNull('polygon')->get();

        foreach ($sectors as $sector) {
            if ($sector->containsPoint($lat, $lng)) {
                return $sector;
            }
        }

        // Si no hay match por poligono, buscar el mas cercano
        return static::active()
            ->nearby($lat, $lng, 5)
            ->first();
    }

    public function getProvincia(): ?GeoProvincia
    {
        return $this->municipio?->provincia;
    }
}
