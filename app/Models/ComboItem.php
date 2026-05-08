<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComboItem extends Model
{
    protected $fillable = [
        'combo_id',
        'dish_id',
        'quantity',
        'is_optional',
        'is_substitutable',
        'substitute_options',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'is_optional' => 'boolean',
        'is_substitutable' => 'boolean',
        'substitute_options' => 'array',
        'sort_order' => 'integer',
    ];

    // ========== Relaciones ==========

    public function combo(): BelongsTo
    {
        return $this->belongsTo(Combo::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    // ========== Accessors ==========

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dish?->name ?? 'Item'
        );
    }

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                $name = $this->dish?->name ?? 'Item';
                if ($this->quantity > 1) {
                    $name = $this->quantity . 'x ' . $name;
                }
                return $name;
            }
        );
    }

    protected function unitPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->dish?->price ?? 0
        );
    }

    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->dish?->price ?? 0) * $this->quantity
        );
    }

    protected function formattedSubtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->subtotal, 2)
        );
    }

    protected function substituteOptionsList(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->substitute_options) {
                    return collect();
                }

                return Dish::whereIn('id', $this->substitute_options)->get();
            }
        );
    }

    // ========== Scopes ==========

    public function scopeRequired($query)
    {
        return $query->where('is_optional', false);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_optional', true);
    }

    public function scopeSubstitutable($query)
    {
        return $query->where('is_substitutable', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeForCombo($query, int $comboId)
    {
        return $query->where('combo_id', $comboId);
    }

    // ========== Helpers ==========

    public function canBeSubstitutedWith(int $dishId): bool
    {
        if (!$this->is_substitutable) {
            return false;
        }

        if (!$this->substitute_options) {
            return true; // Puede sustituirse con cualquiera de la misma categoria
        }

        return in_array($dishId, $this->substitute_options);
    }
}
