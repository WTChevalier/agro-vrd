<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'dish_id',
        'combo_id',
        'name',
        'description',
        'image',
        'unit_price',
        'options_price',
        'quantity',
        'subtotal',
        'selected_options',
        'special_instructions',
        'is_combo',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'options_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
        'selected_options' => 'array',
        'is_combo' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->calculateSubtotal();
        });

        static::updating(function ($item) {
            $item->calculateSubtotal();
        });
    }

    // ========== Relaciones ==========

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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

    protected function totalPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->unit_price + $this->options_price) * $this->quantity
        );
    }

    protected function formattedSubtotal(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->subtotal, 2)
        );
    }

    protected function formattedUnitPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->unit_price + $this->options_price, 2)
        );
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? asset('storage/' . $this->image) : asset('images/dish-default.png')
        );
    }

    protected function selectedOptionsText(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->selected_options || empty($this->selected_options)) {
                    return null;
                }

                $optionTexts = [];
                foreach ($this->selected_options as $group) {
                    $groupName = $group['group_name'] ?? '';
                    $options = collect($group['options'] ?? [])->pluck('name')->implode(', ');
                    if ($options) {
                        $optionTexts[] = "{$groupName}: {$options}";
                    }
                }

                return implode(' | ', $optionTexts);
            }
        );
    }

    protected function hasOptions(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->selected_options)
        );
    }

    protected function hasSpecialInstructions(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->special_instructions)
        );
    }

    // ========== Scopes ==========

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeDishes($query)
    {
        return $query->where('is_combo', false);
    }

    public function scopeCombos($query)
    {
        return $query->where('is_combo', true);
    }

    // ========== Helpers ==========

    public function calculateSubtotal(): void
    {
        $this->subtotal = ($this->unit_price + ($this->options_price ?? 0)) * $this->quantity;
    }

    public function incrementQuantity(int $amount = 1): void
    {
        $this->quantity += $amount;
        $this->calculateSubtotal();
        $this->save();
    }

    public function decrementQuantity(int $amount = 1): void
    {
        $this->quantity = max(1, $this->quantity - $amount);
        $this->calculateSubtotal();
        $this->save();
    }

    public function updateQuantity(int $quantity): void
    {
        $this->quantity = max(1, $quantity);
        $this->calculateSubtotal();
        $this->save();
    }

    public function getFormattedOptions(): array
    {
        if (!$this->selected_options) {
            return [];
        }

        $formatted = [];
        foreach ($this->selected_options as $group) {
            foreach ($group['options'] ?? [] as $option) {
                $formatted[] = [
                    'group' => $group['group_name'] ?? '',
                    'name' => $option['name'] ?? '',
                    'price' => $option['price_adjustment'] ?? 0,
                ];
            }
        }

        return $formatted;
    }

    public static function createFromDish(Dish $dish, int $quantity, array $selectedOptions = [], ?string $instructions = null): self
    {
        $optionsData = [];
        $optionsPrice = 0;

        if (!empty($selectedOptions)) {
            foreach ($dish->optionGroups as $group) {
                $groupOptions = $group->options()
                    ->whereIn('id', $selectedOptions)
                    ->get();

                if ($groupOptions->isNotEmpty()) {
                    $optionsData[] = [
                        'group_id' => $group->id,
                        'group_name' => $group->name,
                        'options' => $groupOptions->map(fn($opt) => [
                            'id' => $opt->id,
                            'name' => $opt->name,
                            'price_adjustment' => $opt->price_adjustment,
                        ])->toArray(),
                    ];
                    $optionsPrice += $groupOptions->sum('price_adjustment');
                }
            }
        }

        return new self([
            'dish_id' => $dish->id,
            'name' => $dish->name,
            'description' => $dish->description,
            'image' => $dish->image,
            'unit_price' => $dish->price,
            'options_price' => $optionsPrice,
            'quantity' => $quantity,
            'selected_options' => $optionsData,
            'special_instructions' => $instructions,
            'is_combo' => false,
        ]);
    }
}
