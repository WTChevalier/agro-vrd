<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_amount',
        'order_amount',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'order_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    // ========== Relaciones ==========

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // ========== Accessors ==========

    protected function formattedDiscountAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->discount_amount, 2)
        );
    }

    protected function formattedOrderAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => 'RD$ ' . number_format($this->order_amount, 2)
        );
    }

    protected function discountPercentage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->order_amount > 0
                ? round(($this->discount_amount / $this->order_amount) * 100, 2)
                : 0
        );
    }

    // ========== Scopes ==========

    public function scopeForCoupon($query, int $couponId)
    {
        return $query->where('coupon_id', $couponId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ========== Static Helpers ==========

    public static function recordUsage(
        int $couponId,
        int $userId,
        int $orderId,
        float $discountAmount,
        float $orderAmount,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        $usage = static::create([
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount,
            'order_amount' => $orderAmount,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);

        // Incrementar contador de uso en el cupon
        Coupon::where('id', $couponId)->increment('times_used');

        return $usage;
    }

    public static function hasUserUsedCoupon(int $userId, int $couponId): bool
    {
        return static::where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->exists();
    }

    public static function getUserUsageCount(int $userId, int $couponId): int
    {
        return static::where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->count();
    }

    public static function getTotalSavingsForUser(int $userId): float
    {
        return static::where('user_id', $userId)->sum('discount_amount');
    }

    public static function getTotalSavingsForCoupon(int $couponId): float
    {
        return static::where('coupon_id', $couponId)->sum('discount_amount');
    }

    public static function getStatisticsForCoupon(int $couponId): array
    {
        $usages = static::where('coupon_id', $couponId);

        return [
            'total_uses' => $usages->count(),
            'unique_users' => $usages->distinct('user_id')->count('user_id'),
            'total_discount' => $usages->sum('discount_amount'),
            'total_orders_amount' => $usages->sum('order_amount'),
            'average_discount' => $usages->avg('discount_amount'),
            'average_order' => $usages->avg('order_amount'),
        ];
    }
}
