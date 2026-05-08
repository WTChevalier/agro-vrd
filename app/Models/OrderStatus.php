<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderStatus extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'icon',
        'sort_order',
        'is_active',
        'is_default',
        'is_final',
        'notify_customer',
        'notify_restaurant',
        'notify_driver',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_final' => 'boolean',
        'notify_customer' => 'boolean',
        'notify_restaurant' => 'boolean',
        'notify_driver' => 'boolean',
    ];

    // ========== Relaciones ==========

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'status_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class, 'status_id');
    }

    // ========== Accessors ==========

    protected function badgeColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->slug) {
                'pending' => 'warning',
                'confirmed' => 'info',
                'preparing' => 'primary',
                'ready' => 'success',
                'picked_up', 'on_the_way' => 'info',
                'delivered' => 'success',
                'cancelled' => 'danger',
                'refunded' => 'secondary',
                default => $this->color ?? 'gray',
            }
        );
    }

    protected function displayIcon(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->slug) {
                'pending' => 'heroicon-o-clock',
                'confirmed' => 'heroicon-o-check-circle',
                'preparing' => 'heroicon-o-fire',
                'ready' => 'heroicon-o-shopping-bag',
                'picked_up' => 'heroicon-o-truck',
                'on_the_way' => 'heroicon-o-map-pin',
                'delivered' => 'heroicon-o-check-badge',
                'cancelled' => 'heroicon-o-x-circle',
                'refunded' => 'heroicon-o-arrow-uturn-left',
                default => $this->icon ?? 'heroicon-o-ellipsis-horizontal',
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
        return $query->orderBy('sort_order');
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }

    public function scopeNotFinal($query)
    {
        return $query->where('is_final', false);
    }

    // ========== Helpers ==========

    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first()
            ?? static::where('slug', 'pending')->first();
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public function isPending(): bool
    {
        return $this->slug === 'pending';
    }

    public function isConfirmed(): bool
    {
        return $this->slug === 'confirmed';
    }

    public function isPreparing(): bool
    {
        return $this->slug === 'preparing';
    }

    public function isReady(): bool
    {
        return $this->slug === 'ready';
    }

    public function isOnTheWay(): bool
    {
        return in_array($this->slug, ['picked_up', 'on_the_way']);
    }

    public function isDelivered(): bool
    {
        return $this->slug === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->slug === 'cancelled';
    }

    public function canTransitionTo(string $targetSlug): bool
    {
        $transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['picked_up', 'delivered', 'cancelled'],
            'picked_up' => ['on_the_way', 'delivered'],
            'on_the_way' => ['delivered'],
            'delivered' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
        ];

        return in_array($targetSlug, $transitions[$this->slug] ?? []);
    }

    public static function getAllSlugs(): array
    {
        return [
            'pending',
            'confirmed',
            'preparing',
            'ready',
            'picked_up',
            'on_the_way',
            'delivered',
            'cancelled',
            'refunded',
        ];
    }
}
