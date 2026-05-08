<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryDriver extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'vehicle_type',
        'vehicle_plate',
        'vehicle_brand',
        'vehicle_model',
        'vehicle_color',
        'vehicle_year',
        'license_number',
        'license_expiry',
        'insurance_number',
        'insurance_expiry',
        'photo',
        'documents',
        'current_latitude',
        'current_longitude',
        'last_location_at',
        'status',
        'is_active',
        'is_verified',
        'is_available',
        'rating',
        'total_deliveries',
        'total_earnings',
        'accepted_zones',
        'max_concurrent_orders',
        'notes',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'insurance_expiry' => 'date',
        'documents' => 'array',
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'last_location_at' => 'datetime',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'is_available' => 'boolean',
        'rating' => 'decimal:2',
        'total_deliveries' => 'integer',
        'total_earnings' => 'decimal:2',
        'accepted_zones' => 'array',
        'max_concurrent_orders' => 'integer',
    ];

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'delivery_driver_id', 'user_id');
    }

    public function trackingHistory(): HasMany
    {
        return $this->hasMany(OrderTracking::class);
    }

    // ========== Accessors ==========

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user?->name ?? 'Sin nombre'
        );
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user?->phone
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->user?->email
        );
    }

    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo ? asset('storage/' . $this->photo) : asset('images/driver-default.png')
        );
    }

    protected function vehicleTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->vehicle_type) {
                'motorcycle' => 'Motocicleta',
                'car' => 'Carro',
                'bicycle' => 'Bicicleta',
                'scooter' => 'Scooter',
                'walking' => 'A pie',
                default => $this->vehicle_type,
            }
        );
    }

    protected function vehicleInfo(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([
                    $this->vehicle_brand,
                    $this->vehicle_model,
                    $this->vehicle_color,
                    $this->vehicle_plate ? "({$this->vehicle_plate})" : null,
                ]);
                return implode(' ', $parts) ?: 'No especificado';
            }
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->status) {
                'offline' => 'Desconectado',
                'online' => 'En linea',
                'busy' => 'Ocupado',
                'on_delivery' => 'En entrega',
                'on_break' => 'En descanso',
                default => $this->status,
            }
        );
    }

    protected function statusColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->status) {
                'offline' => 'gray',
                'online' => 'success',
                'busy' => 'warning',
                'on_delivery' => 'info',
                'on_break' => 'secondary',
                default => 'gray',
            }
        );
    }

    protected function currentCoordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->current_latitude && $this->current_longitude
                ? ['lat' => $this->current_latitude, 'lng' => $this->current_longitude]
                : null
        );
    }

    protected function isLicenseExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->license_expiry && $this->license_expiry->isPast()
        );
    }

    protected function isInsuranceExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->insurance_expiry && $this->insurance_expiry->isPast()
        );
    }

    protected function canAcceptOrders(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active &&
                          $this->is_verified &&
                          $this->is_available &&
                          $this->status === 'online' &&
                          !$this->is_license_expired &&
                          !$this->is_insurance_expired
        );
    }

    protected function activeOrdersCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->orders()->active()->count()
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
            ->where('status', 'online');
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 'online');
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 5)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(current_latitude)) * cos(radians(current_longitude) - radians(?)) + sin(radians(?)) * sin(radians(current_latitude))))";

        return $query
            ->selectRaw("*, {$haversine} AS distance", [$lat, $lng, $lat])
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->having('distance', '<', $radiusKm)
            ->orderBy('distance');
    }

    public function scopeCanAcceptNewOrders($query)
    {
        return $query->active()
            ->verified()
            ->available()
            ->whereRaw('(SELECT COUNT(*) FROM orders WHERE orders.delivery_driver_id = delivery_drivers.user_id AND orders.delivered_at IS NULL AND orders.cancelled_at IS NULL) < delivery_drivers.max_concurrent_orders');
    }

    public function scopeByVehicleType($query, string $type)
    {
        return $query->where('vehicle_type', $type);
    }

    public function scopeTopRated($query)
    {
        return $query->orderByDesc('rating');
    }

    // ========== Helpers ==========

    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_at' => now(),
        ]);
    }

    public function goOnline(): void
    {
        $this->update([
            'status' => 'online',
            'is_available' => true,
        ]);
    }

    public function goOffline(): void
    {
        $this->update([
            'status' => 'offline',
            'is_available' => false,
        ]);
    }

    public function startDelivery(): void
    {
        $this->update(['status' => 'on_delivery']);
    }

    public function finishDelivery(): void
    {
        $this->update(['status' => 'online']);
    }

    public function takeBreak(): void
    {
        $this->update([
            'status' => 'on_break',
            'is_available' => false,
        ]);
    }

    public function updateRating(): void
    {
        $stats = $this->reviews()
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        $this->update([
            'rating' => round($stats->avg_rating ?? 0, 2),
        ]);
    }

    public function incrementDeliveries(): void
    {
        $this->increment('total_deliveries');
    }

    public function addEarnings(float $amount): void
    {
        $this->increment('total_earnings', $amount);
    }

    public function distanceTo(float $lat, float $lng): ?float
    {
        if (!$this->current_latitude || !$this->current_longitude) {
            return null;
        }

        $earthRadius = 6371; // km

        $latDiff = deg2rad($lat - $this->current_latitude);
        $lngDiff = deg2rad($lng - $this->current_longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($this->current_latitude)) * cos(deg2rad($lat)) *
            sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function canDeliverTo(float $lat, float $lng, float $maxDistanceKm = 10): bool
    {
        $distance = $this->distanceTo($lat, $lng);
        return $distance !== null && $distance <= $maxDistanceKm;
    }
}
