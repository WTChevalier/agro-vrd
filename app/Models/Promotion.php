<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'name',
        'slug',
        'description',
        'type',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount',
        'applicable_items',
        'applicable_categories',
        'image',
        'banner_image',
        'starts_at',
        'ends_at',
        'days_of_week',
        'start_time',
        'end_time',
        'usage_limit',
        'usage_count',
        'is_active',
        'is_featured',
        'priority',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'applicable_items' => 'array',
        'applicable_categories' => 'array',
        'days_of_week' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'priority' => 'integer',
    ];

    // ========== Relaciones ==========

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    // ========== Accessors ==========

    protected function discountLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->discount_type) {
                'percentage' => $this->discount_value . '%',
                'fixed' => 'RD$ ' . number_format($this->discount_value, 2),
                'free_delivery' => 'Delivery Gratis',
                'bogo' => '2x1',
                default => $this->discount_value,
            }
        );
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                'discount' => 'Descuento',
                'free_delivery' => 'Delivery Gratis',
                'bogo' => '2x1',
                'bundle' => 'Combo',
                'flash_sale' => 'Venta Flash',
                'happy_hour' => 'Happy Hour',
                default => $this->type,
            }
        );
    }

    protected function isValid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active
                && (!$this->starts_at || $this->starts_at->isPast())
                && (!$this->ends_at || $this->ends_at->isFuture())
                && (!$this->usage_limit || $this->usage_count < $this->usage_limit)
        );
    }

    protected function isCurrentlyActive(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->is_valid) {
                    return false;
                }

                // Verificar dia de la semana
                if ($this->days_of_week && !empty($this->days_of_week)) {
                    $today = strtolower(now()->format('l'));
                    if (!in_array($today, $this->days_of_week)) {
                        return false;
                    }
                }

                // Verificar hora
                if ($this->start_time && $this->end_time) {
                    $now = now();
                    $start = today()->setTimeFromTimeString($this->start_time);
                    $end = today()->setTimeFromTimeString($this->end_time);
                    if (!$now->between($start, $end)) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    protected function remainingUses(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->usage_limit ? max(0, $this->usage_limit - $this->usage_count) : null
        );
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? asset('storage/' . $this->image) : null
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeAvailable($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('usage_count', '<', 'usage_limit');
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ========== Helpers ==========

    public function calculateDiscount(float $orderAmount): float
    {
        if (!$this->is_currently_active) {
            return 0;
        }

        if ($this->min_order_amount && $orderAmount < $this->min_order_amount) {
            return 0;
        }

        $discount = match($this->discount_type) {
            'percentage' => $orderAmount * ($this->discount_value / 100),
            'fixed' => $this->discount_value,
            default => 0,
        };

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        return round($discount, 2);
    }

    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    public function isApplicableTo(Dish $dish): bool
    {
        if (!$this->applicable_items && !$this->applicable_categories) {
            return true;
        }

        if ($this->applicable_items && in_array($dish->id, $this->applicable_items)) {
            return true;
        }

        if ($this->applicable_categories && in_array($dish->menu_category_id, $this->applicable_categories)) {
            return true;
        }

        return false;
    }
}
