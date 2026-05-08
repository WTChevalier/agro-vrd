<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantSpecialHour extends Model
{
    protected $fillable = [
        'restaurant_id',
        'date',
        'title',
        'description',
        'is_closed',
        'open_time',
        'close_time',
        'type',
    ];

    protected $casts = [
        'date' => 'date',
        'is_closed' => 'boolean',
    ];

    // ========== Relaciones ==========

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    // ========== Accessors ==========

    protected function formattedDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date?->format('d/m/Y')
        );
    }

    protected function dayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date?->translatedFormat('l')
        );
    }

    protected function timeRange(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_closed) {
                    return 'Cerrado';
                }

                if (!$this->open_time || !$this->close_time) {
                    return 'Horario regular';
                }

                return $this->open_time . ' - ' . $this->close_time;
            }
        );
    }

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                'holiday' => 'Feriado',
                'special_event' => 'Evento Especial',
                'maintenance' => 'Mantenimiento',
                'vacation' => 'Vacaciones',
                'weather' => 'Clima',
                'extended' => 'Horario Extendido',
                'reduced' => 'Horario Reducido',
                default => $this->type ?? 'Horario Especial',
            }
        );
    }

    protected function isToday(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date?->isToday()
        );
    }

    protected function isFuture(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date?->isFuture()
        );
    }

    protected function isPast(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date?->isPast()
        );
    }

    // ========== Scopes ==========

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', today())->orderBy('date');
    }

    public function scopePast($query)
    {
        return $query->where('date', '<', today())->orderByDesc('date');
    }

    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    // ========== Helpers ==========

    public function isOpenAt(string $time): bool
    {
        if ($this->is_closed) {
            return false;
        }

        if (!$this->open_time || !$this->close_time) {
            return true; // Si no hay horario especificado, asumimos horario regular
        }

        return $time >= $this->open_time && $time <= $this->close_time;
    }

    public static function getForRestaurantAndDate(int $restaurantId, $date): ?self
    {
        return static::forRestaurant($restaurantId)
            ->forDate($date)
            ->first();
    }

    public static function isRestaurantClosedOn(int $restaurantId, $date): bool
    {
        $specialHour = static::getForRestaurantAndDate($restaurantId, $date);
        return $specialHour && $specialHour->is_closed;
    }
}
