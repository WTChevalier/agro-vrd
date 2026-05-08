<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'dish_id',
        'combo_id',
        'quantity',
        'unit_price',
        'options',
        'options_price',
        'special_instructions',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'options' => 'array',
        'options_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($item) {
            $item->cart?->recalculate();
        });

        static::deleted(function ($item) {
            $item->cart?->recalculate();
        });
    }

    // ========== Relaciones ==========

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    // ========== Accessors ==========

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->unit_price + ($this->options_price ?? 0)) * $this->quantity
        );
    }

    protected function formattedSubtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->subtotal, 2)
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dish?->name ?? $this->combo?->name ?? 'Item'
        );
    }

    protected function itemType(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dish_id ? 'dish' : 'combo'
        );
    }

    protected function formattedOptions(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->options || empty($this->options)) {
                    return null;
                }

                $formatted = [];
                foreach ($this->options as $option) {
                    $formatted[] = $option['name'] ?? $option;
                }
                return implode(', ', $formatted);
            }
        );
    }

    // ========== Scopes ==========

    public function scopeDishes($query)
    {
        return $query->whereNotNull('dish_id');
    }

    public function scopeCombos($query)
    {
        return $query->whereNotNull('combo_id');
    }

    // ========== Helpers ==========

    public function incrementQuantity(int $amount = 1): void
    {
        $this->increment('quantity', $amount);
    }

    public function decrementQuantity(int $amount = 1): void
    {
        if ($this->quantity <= $amount) {
            $this->delete();
        } else {
            $this->decrement('quantity', $amount);
        }
    }

    public function updateOptions(array $options, float $optionsPrice): void
    {
        $this->update([
            'options' => $options,
            'options_price' => $optionsPrice,
        ]);
    }
}
