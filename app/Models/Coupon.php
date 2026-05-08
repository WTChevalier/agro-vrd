<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'code',
        'name',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'usage_limit',
        'usage_limit_per_user',
        'used_count',
        'is_active',
        'is_public',
        'is_first_order_only',
        'applicable_to',
        'applicable_items',
        'excluded_items',
        'valid_days',
        'valid_hours_start',
        'valid_hours_end',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'is_first_order_only' => 'boolean',
        'applicable_items' => 'array',
        'excluded_items' => 'array',
        'valid_days' => 'array',
        'valid_hours_start' => 'datetime:H:i',
        'valid_hours_end' => 'datetime:H:i',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($coupon) {
            $coupon->code = strtoupper($coupon->code);
        });
    }

    // ========== Relaciones ==========

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    // ========== Accessors ==========

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                'percentage' => 'Porcentaje',
                'fixed' => 'Monto fijo',
                'free_delivery' => 'Delivery gratis',
                'buy_one_get_one' => 'Compra 1 lleva 2',
                default => $this->type,
            }
        );
    }

    protected function valueFormatted(): Attribute
    {
        return Attribute::make(
            get: function () {
                return match($this->type) {
                    'percentage' => $this->value . '%',
                    'fixed' => 'RD$ ' . number_format($this->value, 2),
                    'free_delivery' => 'Delivery Gratis',
                    'buy_one_get_one' => '2x1',
                    default => $this->value,
                };
            }
        );
    }

    protected function applicableToLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->applicable_to) {
                'all' => 'Todo el pedido',
                'dishes' => 'Platos especificos',
                'categories' => 'Categorias especificas',
                'delivery' => 'Solo delivery',
                default => $this->applicable_to,
            }
        );
    }

    protected function isValid(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_active &&
                          (!$this->starts_at || $this->starts_at->isPast()) &&
                          (!$this->expires_at || $this->expires_at->isFuture()) &&
                          (!$this->usage_limit || $this->used_count < $this->usage_limit)
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at && $this->expires_at->isPast()
        );
    }

    protected function remainingUses(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->usage_limit ? max(0, $this->usage_limit - $this->used_count) : null
        );
    }

    protected function validDaysText(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->valid_days || count($this->valid_days) === 7) {
                    return 'Todos los dias';
                }

                $dayNames = [
                    'monday' => 'Lun',
                    'tuesday' => 'Mar',
                    'wednesday' => 'Mie',
                    'thursday' => 'Jue',
                    'friday' => 'Vie',
                    'saturday' => 'Sab',
                    'sunday' => 'Dom',
                ];

                return collect($this->valid_days)
                    ->map(fn($day) => $dayNames[$day] ?? $day)
                    ->implode(', ');
            }
        );
    }

    protected function validHoursText(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->valid_hours_start && !$this->valid_hours_end) {
                    return 'Todo el dia';
                }

                $start = $this->valid_hours_start?->format('H:i') ?? '00:00';
                $end = $this->valid_hours_end?->format('H:i') ?? '23:59';

                return "{$start} - {$end}";
            }
        );
    }

    // ========== Scopes ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('restaurant_id');
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', strtoupper($code));
    }

    // ========== Helpers ==========

    public static function findByCode(string $code): ?self
    {
        return static::byCode($code)->first();
    }

    public function canBeUsedBy(User $user): bool
    {
        if (!$this->is_valid) {
            return false;
        }

        // Verificar limite por usuario
        if ($this->usage_limit_per_user) {
            $userUsages = $this->usages()->where('user_id', $user->id)->count();
            if ($userUsages >= $this->usage_limit_per_user) {
                return false;
            }
        }

        // Verificar si es solo para primer pedido
        if ($this->is_first_order_only && $user->orders()->completed()->exists()) {
            return false;
        }

        return true;
    }

    public function canBeUsedNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Verificar dia de la semana
        if ($this->valid_days) {
            $currentDay = strtolower(now()->format('l'));
            if (!in_array($currentDay, $this->valid_days)) {
                return false;
            }
        }

        // Verificar hora del dia
        if ($this->valid_hours_start && $this->valid_hours_end) {
            $currentTime = now()->format('H:i:s');
            if ($currentTime < $this->valid_hours_start->format('H:i:s') ||
                $currentTime > $this->valid_hours_end->format('H:i:s')) {
                return false;
            }
        }

        return true;
    }

    public function canBeAppliedToOrder(float $subtotal, ?int $restaurantId = null): bool
    {
        // Verificar monto minimo
        if ($this->min_order_amount && $subtotal < $this->min_order_amount) {
            return false;
        }

        // Verificar restaurante
        if ($this->restaurant_id && $restaurantId && $this->restaurant_id !== $restaurantId) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $subtotal, float $deliveryFee = 0): float
    {
        $discount = match($this->type) {
            'percentage' => $subtotal * ($this->value / 100),
            'fixed' => $this->value,
            'free_delivery' => $deliveryFee,
            default => 0,
        };

        // Aplicar maximo descuento si existe
        if ($this->max_discount_amount && $discount > $this->max_discount_amount) {
            $discount = $this->max_discount_amount;
        }

        return round($discount, 2);
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    public function recordUsage(int $userId, int $orderId): void
    {
        $this->usages()->create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_applied' => 0, // Se actualizara con el monto real
        ]);
        $this->incrementUsage();
    }

    public function getValidationErrors(User $user, float $subtotal, ?int $restaurantId = null): array
    {
        $errors = [];

        if (!$this->is_active) {
            $errors[] = 'Este cupon no esta activo';
        }

        if ($this->is_expired) {
            $errors[] = 'Este cupon ha expirado';
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            $errors[] = 'Este cupon aun no esta disponible';
        }

        if ($this->usage_limit && $this->used_count >= $this->usage_limit) {
            $errors[] = 'Este cupon ha alcanzado su limite de usos';
        }

        if (!$this->canBeUsedBy($user)) {
            $errors[] = 'Ya has usado este cupon el maximo de veces permitidas';
        }

        if (!$this->canBeUsedNow()) {
            $errors[] = 'Este cupon no es valido en este momento';
        }

        if (!$this->canBeAppliedToOrder($subtotal, $restaurantId)) {
            if ($this->min_order_amount && $subtotal < $this->min_order_amount) {
                $errors[] = "El pedido minimo para usar este cupon es RD$ " . number_format($this->min_order_amount, 2);
            }
            if ($this->restaurant_id && $restaurantId !== $this->restaurant_id) {
                $errors[] = 'Este cupon no es valido para este restaurante';
            }
        }

        return $errors;
    }
}
