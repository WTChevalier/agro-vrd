<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class GeoProvincia extends Model
{
    protected $table = 'geo_provincias';

    protected $fillable = [
        'code',
        'name',
        'region',
        'latitude',
        'longitude',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ========== Relaciones ==========

    public function municipios(): HasMany
    {
        return $this->hasMany(GeoMunicipio::class, 'provincia_id')->orderBy('name');
    }

    public function sectores(): HasManyThrough
    {
        return $this->hasManyThrough(
            GeoSector::class,
            GeoMunicipio::class,
            'provincia_id',
            'municipio_id'
        );
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class, 'provincia_id');
    }

    // ========== Accessors ==========

    protected function municipiosCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->municipios()->count()
        );
    }

    protected function activeMunicipiosCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->municipios()->active()->count()
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

    protected function regionLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->region) {
                'norte' => 'Region Norte',
                'sur' => 'Region Sur',
                'este' => 'Region Este',
                'ozama' => 'Region Ozama',
                'cibao_norte' => 'Cibao Norte',
                'cibao_sur' => 'Cibao Sur',
                'cibao_nordeste' => 'Cibao Nordeste',
                'cibao_noroeste' => 'Cibao Noroeste',
                'valdesia' => 'Region Valdesia',
                'enriquillo' => 'Region Enriquillo',
                'el_valle' => 'Region El Valle',
                'yuma' => 'Region Yuma',
                'higuamo' => 'Region Higuamo',
                default => $this->region,
            }
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

    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    public function scopeWithRestaurants($query)
    {
        return $query->whereHas('restaurants', fn($q) => $q->active());
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    // ========== Helpers ==========

    public static function findByCode(string $code): ?self
    {
        return static::byCode($code)->first();
    }

    public static function findByName(string $name): ?self
    {
        return static::where('name', 'LIKE', "%{$name}%")->first();
    }

    public function getActiveMunicipios()
    {
        return $this->municipios()->active()->ordered()->get();
    }

    public static function getAllActive()
    {
        return static::active()->ordered()->get();
    }

    public static function getForSelect()
    {
        return static::active()
            ->ordered()
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function getRegions(): array
    {
        return [
            'ozama' => 'Region Ozama',
            'cibao_norte' => 'Cibao Norte',
            'cibao_sur' => 'Cibao Sur',
            'cibao_nordeste' => 'Cibao Nordeste',
            'cibao_noroeste' => 'Cibao Noroeste',
            'valdesia' => 'Region Valdesia',
            'enriquillo' => 'Region Enriquillo',
            'el_valle' => 'Region El Valle',
            'yuma' => 'Region Yuma',
            'higuamo' => 'Region Higuamo',
        ];
    }

    public function hasActiveRestaurants(): bool
    {
        return $this->restaurants()->active()->exists();
    }
}
