<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'user_id',
        'restaurant_id',
        'status_id',
        'delivery_driver_id',
        'type',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_instructions',
        'subtotal',
        'tax',
        'delivery_fee',
        'service_fee',
        'tip',
        'discount',
        'total',
        'payment_method',
        'payment_status',
        'payment_reference',
        'paid_at',
        'coupon_id',
        'coupon_code',
        'estimated_preparation_time',
        'estimated_delivery_time',
        'scheduled_for',
        'confirmed_at',
        'preparing_at',
        'ready_at',
        'picked_up_at',
        'delivered_at',
        'cancelled_at',
        'customer_notes',
        'restaurant_notes',
        'cancellation_reason',
        'cancelled_by',
        'rating',
        'review',
        'reviewed_at',
    ];

    protected $casts = [
        'delivery_address' => 'array',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'tip' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'confirmed_at' => 'datetime',
        'preparing_at' => 'datetime',
        'ready_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'SRD';
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(4));
        return "{$prefix}-{$date}-{$random}";
    }

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    public function deliveryDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivery_driver_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderByDesc('created_at');
    }

    public function tracking(): HasMany
    {
        return $this->hasMany(OrderTracking::class)->orderByDesc('created_at');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ========== Accessors ==========

    protected function formattedTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->total, 2)
        );
    }

    protected function deliveryAddressFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->delivery_address) return null;
                $addr = $this->delivery_address;
                return implode(', ', array_filter([
                    $addr['address_line_1'] ?? null,
                    $addr['address_line_2'] ?? null,
                    $addr['sector'] ?? null,
                    $addr['municipio'] ?? null,
                ]));
            }
        );
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                'delivery' => 'Delivery',
                'pickup' => 'Recoger',
                'dine_in' => 'En el local',
                default => $this->type,
            }
        );
    }

    protected function paymentMethodLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->payment_method) {
                'cash' => 'Efectivo',
                'card' => 'Tarjeta',
                'wallet' => 'Wallet',
                'transfer' => 'Transferencia',
                default => $this->payment_method,
            }
        );
    }

    protected function isPaid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_status === 'paid'
        );
    }

    protected function canBeCancelled(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->status?->slug, ['pending', 'confirmed']) && !$this->cancelled_at
        );
    }

    protected function estimatedArrival(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->delivered_at) return null;
                $prepTime = $this->estimated_preparation_time ?? 30;
                $deliveryTime = $this->estimated_delivery_time ?? 20;
                return $this->created_at->addMinutes($prepTime + $deliveryTime);
            }
        );
    }

    // ========== Scopes ==========

    public function scopePending($query)
    {
        return $query->whereHas('status', fn($q) => $q->where('slug', 'pending'));
    }

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at')
            ->whereNull('delivered_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('delivered_at');
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ========== Helpers ==========

    public function updateStatus(int $statusId, ?int $changedBy = null, ?string $notes = null): void
    {
        $this->update(['status_id' => $statusId]);

        $this->statusHistory()->create([
            'status_id' => $statusId,
            'changed_by' => $changedBy,
            'notes' => $notes,
        ]);

        // Actualizar timestamps según el estado
        $status = OrderStatus::find($statusId);
        match ($status?->slug) {
            'confirmed' => $this->update(['confirmed_at' => now()]),
            'preparing' => $this->update(['preparing_at' => now()]),
            'ready' => $this->update(['ready_at' => now()]),
            'picked_up', 'on_the_way' => $this->update(['picked_up_at' => now()]),
            'delivered' => $this->update(['delivered_at' => now()]),
            'cancelled' => $this->update(['cancelled_at' => now()]),
            default => null,
        };
    }

    public function cancel(string $reason, string $cancelledBy = 'customer'): void
    {
        $cancelledStatus = OrderStatus::where('slug', 'cancelled')->first();

        $this->update([
            'status_id' => $cancelledStatus?->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy,
        ]);

        if ($cancelledStatus) {
            $this->statusHistory()->create([
                'status_id' => $cancelledStatus->id,
                'notes' => "Cancelado: {$reason}",
            ]);
        }
    }

    public function assignDriver(int $driverId): void
    {
        $this->update(['delivery_driver_id' => $driverId]);
    }

    public function calculateTotals(): array
    {
        $subtotal = $this->items->sum(fn($item) => $item->subtotal);
        $tax = $subtotal * 0.18; // ITBIS 18%
        $total = $subtotal + $tax + $this->delivery_fee + $this->service_fee + $this->tip - $this->discount;

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }

    public function getItemsCount(): int
    {
        return $this->items->sum('quantity');
    }
}
