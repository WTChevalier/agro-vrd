<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'favorable_type',
        'favorable_id',
        'notes',
    ];

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function favorable(): MorphTo
    {
        return $this->morphTo();
    }

    // ========== Accessors ==========

    protected function itemName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->favorable?->name ?? 'Item'
        );
    }

    protected function itemType(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->favorable_type) {
                Restaurant::class, 'App\Models\Restaurant' => 'restaurant',
                Dish::class, 'App\Models\Dish' => 'dish',
                default => 'unknown',
            }
        );
    }

    protected function itemTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->favorable_type) {
                Restaurant::class, 'App\Models\Restaurant' => 'Restaurante',
                Dish::class, 'App\Models\Dish' => 'Plato',
                default => 'Desconocido',
            }
        );
    }

    protected function itemImage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->favorable instanceof Restaurant) {
                    return $this->favorable->logo_url;
                }
                if ($this->favorable instanceof Dish) {
                    return $this->favorable->image_url;
                }
                return null;
            }
        );
    }

    // ========== Scopes ==========

    public function scopeRestaurants($query)
    {
        return $query->where('favorable_type', Restaurant::class);
    }

    public function scopeDishes($query)
    {
        return $query->where('favorable_type', Dish::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForItem($query, string $type, int $id)
    {
        return $query->where('favorable_type', $type)
            ->where('favorable_id', $id);
    }

    // ========== Helpers ==========

    public static function toggle(int $userId, string $favorableType, int $favorableId): bool
    {
        $existing = static::where('user_id', $userId)
            ->where('favorable_type', $favorableType)
            ->where('favorable_id', $favorableId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false; // Removed from favorites
        }

        static::create([
            'user_id' => $userId,
            'favorable_type' => $favorableType,
            'favorable_id' => $favorableId,
        ]);

        return true; // Added to favorites
    }

    public static function isFavorited(int $userId, string $favorableType, int $favorableId): bool
    {
        return static::where('user_id', $userId)
            ->where('favorable_type', $favorableType)
            ->where('favorable_id', $favorableId)
            ->exists();
    }
}
