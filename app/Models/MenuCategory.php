<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MenuCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'sort_order',
        'is_active',
        'is_featured',
        'available_from',
        'available_until',
        'available_days',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'available_from' => 'datetime:H:i',
        'available_until' => 'datetime:H:i',
        'available_days' => 'array',
        'sort_order' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // ========== Relaciones ==========

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class, 'category_id')->orderBy('sort_order');
    }

    // ========== Accessors ==========

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? asset('storage/' . $this->image) : null
        );
    }

    protected function dishesCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dishes()->count()
        );
    }

    protected function activeDishesCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dishes()->active()->available()->count()
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeAvailableNow($query)
    {
        $now = now();
        $currentTime = $now->format('H:i:s');
        $currentDay = strtolower($now->format('l'));

        return $query->where('is_active', true)
            ->where(function ($q) use ($currentTime, $currentDay) {
                $q->whereNull('available_from')
                    ->orWhere(function ($q2) use ($currentTime, $currentDay) {
                        $q2->whereTime('available_from', '<=', $currentTime)
                            ->whereTime('available_until', '>=', $currentTime)
                            ->where(function ($q3) use ($currentDay) {
                                $q3->whereNull('available_days')
                                    ->orWhereJsonContains('available_days', $currentDay);
                            });
                    });
            });
    }

    // ========== Helpers ==========

    public function isAvailableNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->available_from || !$this->available_until) {
            return true;
        }

        $now = now();
        $currentTime = $now->format('H:i:s');
        $currentDay = strtolower($now->format('l'));

        $timeInRange = $currentTime >= $this->available_from->format('H:i:s') &&
                       $currentTime <= $this->available_until->format('H:i:s');

        if (!$timeInRange) {
            return false;
        }

        if ($this->available_days && !in_array($currentDay, $this->available_days)) {
            return false;
        }

        return true;
    }

    public function getAvailableDishes()
    {
        return $this->dishes()->active()->available()->ordered()->get();
    }
}
