<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'points_required',
        'points_multiplier',
        'benefits',
        'discount_percentage',
        'free_delivery_count',
        'priority_support',
        'exclusive_offers',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'points_required' => 'integer',
        'points_multiplier' => 'decimal:2',
        'benefits' => 'array',
        'discount_percentage' => 'decimal:2',
        'free_delivery_count' => 'integer',
        'priority_support' => 'boolean',
        'exclusive_offers' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ========== Accessors ==========

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->icon ? "{$this->icon} {$this->name}" : $this->name
        );
    }

    protected function benefitsList(): Attribute
    {
        return Attribute::make(
            get: function () {
                $list = $this->benefits ?? [];

                if ($this->discount_percentage > 0) {
                    $list[] = "Descuento de {$this->discount_percentage}% en pedidos";
                }

                if ($this->free_delivery_count > 0) {
                    $list[] = "{$this->free_delivery_count} deliveries gratis al mes";
                }

                if ($this->points_multiplier > 1) {
                    $list[] = "Gana {$this->points_multiplier}x puntos por compra";
                }

                if ($this->priority_support) {
                    $list[] = "Soporte prioritario";
                }

                if ($this->exclusive_offers) {
                    $list[] = "Acceso a ofertas exclusivas";
                }

                return $list;
            }
        );
    }

    protected function iconUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->icon ? asset('storage/' . $this->icon) : null
        );
    }

    protected function formattedPointsRequired(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format($this->points_required)
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('points_required');
    }

    public function scopeAchievableWith($query, int $points)
    {
        return $query->where('points_required', '<=', $points);
    }

    // ========== Helpers ==========

    public static function getTierForPoints(int $points): ?self
    {
        return static::active()
            ->where('points_required', '<=', $points)
            ->orderByDesc('points_required')
            ->first();
    }

    public static function getNextTier(int $currentPoints): ?self
    {
        return static::active()
            ->where('points_required', '>', $currentPoints)
            ->orderBy('points_required')
            ->first();
    }

    public function getProgressToNext(int $currentPoints): ?array
    {
        $nextTier = static::getNextTier($currentPoints);

        if (!$nextTier) {
            return null;
        }

        $pointsNeeded = $nextTier->points_required - $currentPoints;
        $progress = (($currentPoints - $this->points_required) / ($nextTier->points_required - $this->points_required)) * 100;

        return [
            'next_tier' => $nextTier,
            'points_needed' => $pointsNeeded,
            'progress_percentage' => min(100, max(0, round($progress, 1))),
        ];
    }
}
