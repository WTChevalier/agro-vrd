<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DishOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'option_group_id',
        'name',
        'description',
        'price_adjustment',
        'is_default',
        'is_active',
        'is_available',
        'calories',
        'sort_order',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_available' => 'boolean',
        'calories' => 'integer',
        'sort_order' => 'integer',
    ];

    // ========== Relaciones ==========

    public function optionGroup(): BelongsTo
    {
        return $this->belongsTo(DishOptionGroup::class, 'option_group_id');
    }

    // ========== Accessors ==========

    protected function priceAdjustmentFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->price_adjustment == 0) {
                    return 'Incluido';
                }
                $sign = $this->price_adjustment > 0 ? '+' : '';
                return $sign . 'RD$ ' . number_format($this->price_adjustment, 2);
            }
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $name = $this->name;
                if ($this->price_adjustment != 0) {
                    $sign = $this->price_adjustment > 0 ? '+' : '';
                    $name .= " ({$sign}RD$ " . number_format(abs($this->price_adjustment), 2) . ")";
                }
                return $name;
            }
        );
    }

    protected function isOrderable(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active && $this->is_available
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

    public function scopeOrderable($query)
    {
        return $query->where('is_active', true)->where('is_available', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeWithPriceAdjustment($query)
    {
        return $query->where('price_adjustment', '!=', 0);
    }

    public function scopeFree($query)
    {
        return $query->where('price_adjustment', 0);
    }

    // ========== Helpers ==========

    public function hasPriceAdjustment(): bool
    {
        return $this->price_adjustment != 0;
    }

    public function isUpcharge(): bool
    {
        return $this->price_adjustment > 0;
    }

    public function isDiscount(): bool
    {
        return $this->price_adjustment < 0;
    }
}
