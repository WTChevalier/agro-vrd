<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'restaurant_id',
        'coupon_id',
        'coupon_code',
        'subtotal',
        'discount',
        'notes',
        'expires_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // ========== Accessors ==========

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, $this->subtotal - $this->discount)
        );
    }

    protected function formattedSubtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->subtotal, 2)
        );
    }

    protected function itemsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items->sum('quantity')
        );
    }

    protected function isEmpty(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->items->count() === 0
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ========== Helpers ==========

    public function recalculate(): void
    {
        $subtotal = $this->items->sum(fn ($item) => $item->subtotal);
        $this->update(['subtotal' => $subtotal]);
    }

    public function clear(): void
    {
        $this->items()->delete();
        $this->update([
            'subtotal' => 0,
            'discount' => 0,
            'coupon_id' => null,
            'coupon_code' => null,
        ]);
    }

    public function applyCoupon(Coupon $coupon): bool
    {
        if (!$coupon->isValid() || !$coupon->isApplicableToRestaurant($this->restaurant_id)) {
            return false;
        }

        $discount = $coupon->calculateDiscount($this->subtotal);

        $this->update([
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'discount' => $discount,
        ]);

        return true;
    }

    public function removeCoupon(): void
    {
        $this->update([
            'coupon_id' => null,
            'coupon_code' => null,
            'discount' => 0,
        ]);
    }
}
