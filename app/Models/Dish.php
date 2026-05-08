<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Dish extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'slug',
        'description',
        'image',
        'gallery',
        'price',
        'compare_price',
        'cost',
        'calories',
        'nutritional_info',
        'allergens',
        'preparation_time',
        'sort_order',
        'is_active',
        'is_featured',
        'is_available',
        'is_spicy',
        'is_vegetarian',
        'is_vegan',
        'is_gluten_free',
        'total_orders',
        'rating',
    ];

    protected $casts = [
        'gallery' => 'array',
        'nutritional_info' => 'array',
        'allergens' => 'array',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_available' => 'boolean',
        'is_spicy' => 'boolean',
        'is_vegetarian' => 'boolean',
        'is_vegan' => 'boolean',
        'is_gluten_free' => 'boolean',
        'rating' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dish) {
            if (empty($dish->slug)) {
                $dish->slug = Str::slug($dish->name);
            }
        });
    }

    // ========== Relaciones ==========

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(DishOptionGroup::class)->orderBy('sort_order');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ========== Accessors ==========

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? asset('storage/' . $this->image) : asset('images/dish-default.png')
        );
    }

    protected function discountPercentage(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->compare_price || $this->compare_price <= $this->price) {
                    return 0;
                }
                return round((($this->compare_price - $this->price) / $this->compare_price) * 100);
            }
        );
    }

    protected function hasDiscount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->compare_price && $this->compare_price > $this->price
        );
    }

    protected function badges(): Attribute
    {
        return Attribute::make(
            get: function () {
                $badges = [];
                if ($this->is_spicy) $badges[] = ['label' => 'Picante', 'color' => 'red', 'icon' => 'fire'];
                if ($this->is_vegetarian) $badges[] = ['label' => 'Vegetariano', 'color' => 'green', 'icon' => 'leaf'];
                if ($this->is_vegan) $badges[] = ['label' => 'Vegano', 'color' => 'emerald', 'icon' => 'seedling'];
                if ($this->is_gluten_free) $badges[] = ['label' => 'Sin Gluten', 'color' => 'yellow', 'icon' => 'wheat'];
                return $badges;
            }
        );
    }

    protected function allergensText(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->allergens ? implode(', ', $this->allergens) : null
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('total_orders');
    }

    public function scopeTopRated($query)
    {
        return $query->orderByDesc('rating');
    }

    // ========== Helpers ==========

    public function isOrderable(): bool
    {
        return $this->is_active &&
               $this->is_available &&
               $this->restaurant->is_active &&
               $this->restaurant->is_open;
    }

    public function calculatePriceWithOptions(array $selectedOptions): float
    {
        $basePrice = $this->price;
        $optionsPrice = 0;

        foreach ($this->optionGroups as $group) {
            foreach ($group->options as $option) {
                if (in_array($option->id, $selectedOptions)) {
                    $optionsPrice += $option->price_adjustment;
                }
            }
        }

        return $basePrice + $optionsPrice;
    }

    public function getRequiredOptionGroups(): \Illuminate\Support\Collection
    {
        return $this->optionGroups()->where('is_required', true)->get();
    }

    public function updateRating(): void
    {
        $stats = $this->reviews()
            ->where('status', 'approved')
            ->selectRaw('AVG(rating) as avg_rating')
            ->first();

        $this->update([
            'rating' => round($stats->avg_rating ?? 0, 2),
        ]);
    }

    public function incrementOrders(): void
    {
        $this->increment('total_orders');
    }
}
