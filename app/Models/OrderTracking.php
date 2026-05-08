<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTracking extends Model
{
    protected $table = 'order_tracking';

    protected $fillable = [
        'order_id',
        'delivery_driver_id',
        'latitude',
        'longitude',
        'accuracy',
        'heading',
        'speed',
        'altitude',
        'battery_level',
        'event_type',
        'notes',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy' => 'decimal:2',
        'heading' => 'decimal:2',
        'speed' => 'decimal:2',
        'altitude' => 'decimal:2',
        'battery_level' => 'integer',
        'recorded_at' => 'datetime',
    ];

    // ========== Relaciones ==========

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveryDriver(): BelongsTo
    {
        return $this->belongsTo(DeliveryDriver::class);
    }

    // ========== Accessors ==========

    protected function coordinates(): Attribute
    {
        return Attribute::make(
            get: fn () => [
                'lat' => $this->latitude,
                'lng' => $this->longitude,
            ]
        );
    }

    protected function coordinatesString(): Attribute
    {
        return Attribute::make(
            get: fn () => "{$this->latitude}, {$this->longitude}"
        );
    }

    protected function speedKmh(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->speed ? round($this->speed * 3.6, 1) : null
        );
    }

    protected function eventTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->event_type) {
                'location_update' => 'Actualizacion GPS',
                'pickup_arrived' => 'Llego al restaurante',
                'pickup_completed' => 'Recogio pedido',
                'delivery_arrived' => 'Llego a destino',
                'delivery_completed' => 'Entrega completada',
                'route_deviation' => 'Desvio de ruta',
                'pause' => 'Pausa',
                'resume' => 'Reanudacion',
                default => $this->event_type,
            }
        );
    }

    protected function batteryStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->battery_level === null) {
                    return 'Desconocido';
                }
                if ($this->battery_level <= 20) {
                    return 'Bateria baja';
                }
                if ($this->battery_level <= 50) {
                    return 'Bateria media';
                }
                return 'Bateria buena';
            }
        );
    }

    protected function formattedTime(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->recorded_at?->format('H:i:s') ?? $this->created_at->format('H:i:s')
        );
    }

    // ========== Scopes ==========

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('delivery_driver_id', $driverId);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('recorded_at')->orderByDesc('created_at');
    }

    public function scopeLocationUpdates($query)
    {
        return $query->where('event_type', 'location_update');
    }

    public function scopeEvents($query)
    {
        return $query->where('event_type', '!=', 'location_update');
    }

    public function scopeInTimeRange($query, $start, $end)
    {
        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', today());
    }

    // ========== Helpers ==========

    public static function recordLocation(
        int $orderId,
        int $driverId,
        float $latitude,
        float $longitude,
        ?float $accuracy = null,
        ?float $heading = null,
        ?float $speed = null,
        string $eventType = 'location_update'
    ): self {
        return static::create([
            'order_id' => $orderId,
            'delivery_driver_id' => $driverId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'heading' => $heading,
            'speed' => $speed,
            'event_type' => $eventType,
            'recorded_at' => now(),
        ]);
    }

    public static function recordEvent(
        int $orderId,
        int $driverId,
        string $eventType,
        ?float $latitude = null,
        ?float $longitude = null,
        ?string $notes = null
    ): self {
        return static::create([
            'order_id' => $orderId,
            'delivery_driver_id' => $driverId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'event_type' => $eventType,
            'notes' => $notes,
            'recorded_at' => now(),
        ]);
    }

    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 6371000; // metros

        $latDiff = deg2rad($lat - $this->latitude);
        $lngDiff = deg2rad($lng - $this->longitude);

        $a = sin($latDiff / 2) * sin($latDiff / 2) +
            cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
            sin($lngDiff / 2) * sin($lngDiff / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public static function getRouteForOrder(int $orderId): \Illuminate\Support\Collection
    {
        return static::forOrder($orderId)
            ->locationUpdates()
            ->orderBy('recorded_at')
            ->get()
            ->map(fn($point) => [
                'lat' => (float) $point->latitude,
                'lng' => (float) $point->longitude,
                'time' => $point->recorded_at,
            ]);
    }

    public static function getLatestLocationForOrder(int $orderId): ?self
    {
        return static::forOrder($orderId)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->recent()
            ->first();
    }
}
