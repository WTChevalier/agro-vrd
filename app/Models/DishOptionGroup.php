<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DishOptionGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dish_id',
        'name',
        'description',
        'type',
        'min_selections',
        'max_selections',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'min_selections' => 'integer',
        'max_selections' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // ========== Relaciones ==========

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(DishOption::class, 'option_group_id')->orderBy('sort_order');
    }

    // ========== Accessors ==========

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                'single' => 'Seleccion unica',
                'multiple' => 'Seleccion multiple',
                'size' => 'Tamano',
                'extra' => 'Extras',
                'addon' => 'Acompanantes',
                default => $this->type,
            }
        );
    }

    protected function selectionRangeText(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->min_selections === $this->max_selections) {
                    return "Selecciona {$this->min_selections}";
                }
                if ($this->min_selections === 0 && $this->max_selections === 1) {
                    return "Opcional";
                }
                if ($this->min_selections === 1 && $this->max_selections === 1) {
                    return "Requerido";
                }
                return "Selecciona entre {$this->min_selections} y {$this->max_selections}";
            }
        );
    }

    protected function activeOptionsCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->options()->active()->count()
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ========== Helpers ==========

    public function isSingleSelection(): bool
    {
        return $this->type === 'single' || $this->max_selections === 1;
    }

    public function isMultipleSelection(): bool
    {
        return $this->type === 'multiple' || $this->max_selections > 1;
    }

    public function validateSelection(array $selectedOptionIds): bool
    {
        $count = count($selectedOptionIds);

        if ($this->is_required && $count < $this->min_selections) {
            return false;
        }

        if ($this->max_selections && $count > $this->max_selections) {
            return false;
        }

        // Verificar que las opciones seleccionadas pertenecen a este grupo
        $validOptions = $this->options()->whereIn('id', $selectedOptionIds)->count();

        return $validOptions === $count;
    }

    public function getActiveOptions()
    {
        return $this->options()->active()->ordered()->get();
    }

    public function calculatePriceForOptions(array $selectedOptionIds): float
    {
        return $this->options()
            ->whereIn('id', $selectedOptionIds)
            ->sum('price_adjustment');
    }
}
