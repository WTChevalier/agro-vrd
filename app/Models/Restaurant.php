<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Restaurant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'visitrd_restaurant_id',
        'owner_id',
        'provincia_id',
        'municipio_id',
        'sector_id',
        'name',
        'slug',
        'description',
        'short_description',
        'phone',
        'whatsapp',
        'email',
        'website',
        'address',
        'latitude',
        'longitude',
        'logo',
        'cover_image',
        'gallery',
        'opening_hours',
        'cuisine_types',
        'minimum_order',
        'preparation_time',
        'delivery_fee',
        'commission_rate',
        'is_active',
        'is_featured',
        'is_open',
        'accepts_delivery',
        'accepts_pickup',
        'accepts_online_payment',
        'rating',
        'total_reviews',
        'total_orders',
    ];

    protected $casts = [
        'gallery' => 'array',
        'opening_hours' => 'array',
        'cuisine_types' => 'array',
        'minimum_order' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_open' => 'boolean',
        'accepts_delivery' => 'boolean',
        'accepts_pickup' => 'boolean',
        'accepts_online_payment' => 'boolean',
        'rating' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($restaurant) {
            if (empty($restaurant->slug)) {
                $restaurant->slug = Str::slug($restaurant->name);
            }
        });
    }

    // ========== Relaciones ==========

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
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

    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class)->orderBy('sort_order');
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    public function combos(): HasMany
    {
        return $this->hasMany(Combo::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(RestaurantDeliveryZone::class);
    }

    public function specialHours(): HasMany
    {
        return $this->hasMany(RestaurantSpecialHour::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(Promotion::class);
    }

    // ========== Accessors ==========

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo ? asset('storage/' . $this->logo) : asset('images/restaurant-default.png')
        );
    }

    protected function coverImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cover_image ? asset('storage/' . $this->cover_image) : asset('images/cover-default.jpg')
        );
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([
                    $this->address,
                    $this->sector?->name,
                    $this->municipio?->name,
                    $this->provincia?->name,
                ]);
                return implode(', ', $parts);
            }
        );
    }

    protected function cuisineTypesText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cuisine_types ? implode(', ', $this->cuisine_types) : 'Variada'
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeAcceptsDelivery($query)
    {
        return $query->where('accepts_delivery', true);
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

    public function isCurrentlyOpen(): bool
    {
        if (!$this->is_open || !$this->is_active) {
            return false;
        }

        // Verificar horario especial para hoy
        $specialHour = $this->specialHours()
            ->where('date', today())
            ->first();

        if ($specialHour) {
            if ($specialHour->is_closed) {
                return false;
            }
            return now()->between(
                today()->setTimeFromTimeString($specialHour->open_time),
                today()->setTimeFromTimeString($specialHour->close_time)
            );
        }

        // Verificar horario regular
        if (!$this->opening_hours) {
            return true; // Si no hay horario definido, asumimos abierto
        }

        $dayOfWeek = strtolower(now()->format('l'));
        $todayHours = $this->opening_hours[$dayOfWeek] ?? null;

        if (!$todayHours || ($todayHours['closed'] ?? false)) {
            return false;
        }

        return now()->between(
            today()->setTimeFromTimeString($todayHours['open']),
            today()->setTimeFromTimeString($todayHours['close'])
        );
    }

    public function updateRating(): void
    {
        $stats = $this->reviews()
            ->where('status', 'approved')
            ->selectRaw('AVG(overall_rating) as avg_rating, COUNT(*) as total')
            ->first();

        $this->update([
            'rating' => round($stats->avg_rating ?? 0, 2),
            'total_reviews' => $stats->total ?? 0,
        ]);
    }

    public function canDeliver(float $lat, float $lng): bool
    {
        // Verificar si la ubicación está en alguna zona de delivery
        return $this->deliveryZones()
            ->where('is_active', true)
            ->whereHas('municipio', function ($q) use ($lat, $lng) {
                // Aquí podrías implementar lógica más compleja con polígonos
            })
            ->exists();
    }

    public function getDeliveryFeeFor(float $lat, float $lng): float
    {
        // Calcular fee basado en distancia
        if (!$this->latitude || !$this->longitude) {
            return $this->delivery_fee;
        }

        $distance = $this->calculateDistance($lat, $lng);
        $baseKm = 3; // Primeros 3km incluidos en fee base

        if ($distance <= $baseKm) {
            return $this->delivery_fee;
        }

        $extraKm = $distance - $baseKm;
        $perKmFee = config('sazonrd.delivery_per_km_fee', 25);

        return $this->delivery_fee + ($extraKm * $perKmFee);
    }

    private function calculateDistance(float $lat, float $lng): float
    {
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
