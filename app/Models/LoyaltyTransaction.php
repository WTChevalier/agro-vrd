<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'points',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'points' => 'integer',
        'balance_after' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
    ];

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ========== Accessors ==========

    protected function typeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->type) {
                'earned' => 'Ganados',
                'redeemed' => 'Canjeados',
                'bonus' => 'Bonus',
                'expired' => 'Expirados',
                'adjustment' => 'Ajuste',
                'referral' => 'Referido',
                'welcome' => 'Bienvenida',
                default => $this->type,
            }
        );
    }

    protected function isCredit(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->type, ['earned', 'bonus', 'referral', 'welcome', 'adjustment'])
                && $this->points > 0
        );
    }

    protected function isDebit(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->type, ['redeemed', 'expired'])
                || $this->points < 0
        );
    }

    protected function signedPoints(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_debit ? -abs($this->points) : abs($this->points)
        );
    }

    protected function formattedPoints(): Attribute
    {
        return Attribute::make(
            get: function () {
                $sign = $this->is_credit ? '+' : '-';
                return $sign . number_format(abs($this->points));
            }
        );
    }

    protected function isExpired(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->expires_at && $this->expires_at->isPast()
        );
    }

    // ========== Scopes ==========

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEarned($query)
    {
        return $query->where('type', 'earned');
    }

    public function scopeRedeemed($query)
    {
        return $query->where('type', 'redeemed');
    }

    public function scopeCredits($query)
    {
        return $query->whereIn('type', ['earned', 'bonus', 'referral', 'welcome'])
            ->orWhere(function ($q) {
                $q->where('type', 'adjustment')->where('points', '>', 0);
            });
    }

    public function scopeDebits($query)
    {
        return $query->whereIn('type', ['redeemed', 'expired'])
            ->orWhere(function ($q) {
                $q->where('type', 'adjustment')->where('points', '<', 0);
            });
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now());
    }

    public function scopeRecent($query, int $days = 90)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ========== Helpers ==========

    public static function earnPoints(User $user, int $points, string $description, ?int $orderId = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'type' => 'earned',
            'points' => $points,
            'balance_after' => $user->loyalty_points + $points,
            'description' => $description,
            'expires_at' => now()->addYear(), // Los puntos expiran en 1 ano
        ]);
    }

    public static function redeemPoints(User $user, int $points, string $description, ?int $orderId = null): self
    {
        return static::create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'type' => 'redeemed',
            'points' => $points,
            'balance_after' => $user->loyalty_points - $points,
            'description' => $description,
        ]);
    }

    public static function addBonus(User $user, int $points, string $description): self
    {
        return static::create([
            'user_id' => $user->id,
            'type' => 'bonus',
            'points' => $points,
            'balance_after' => $user->loyalty_points + $points,
            'description' => $description,
            'expires_at' => now()->addMonths(6), // Bonus expira en 6 meses
        ]);
    }
}
