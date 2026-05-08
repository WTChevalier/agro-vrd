<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'delivery_driver_id',
        'user_id',
        'order_id',
        'overall_rating',
        'professionalism_rating',
        'speed_rating',
        'communication_rating',
        'comment',
        'is_anonymous',
        'status',
        'helpful_count',
        'reported_count',
    ];

    protected $casts = [
        'overall_rating' => 'decimal:1',
        'professionalism_rating' => 'decimal:1',
        'speed_rating' => 'decimal:1',
        'communication_rating' => 'decimal:1',
        'is_anonymous' => 'boolean',
        'helpful_count' => 'integer',
        'reported_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            if ($review->status === 'approved') {
                $review->deliveryDriver?->updateRating();
            }
        });

        static::updated(function ($review) {
            $review->deliveryDriver?->updateRating();
        });

        static::deleted(function ($review) {
            $review->deliveryDriver?->updateRating();
        });
    }

    // ========== Relaciones ==========

    public function deliveryDriver(): BelongsTo
    {
        return $this->belongsTo(DeliveryDriver::class);
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

    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_anonymous ? 'Usuario Anonimo' : $this->user?->name
        );
    }

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->status) {
                'pending' => 'Pendiente',
                'approved' => 'Aprobado',
                'rejected' => 'Rechazado',
                'flagged' => 'Reportado',
                default => $this->status,
            }
        );
    }

    protected function averageDetailRating(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ratings = array_filter([
                    $this->professionalism_rating,
                    $this->speed_rating,
                    $this->communication_rating,
                ]);

                return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : null;
            }
        );
    }

    // ========== Scopes ==========

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeForDriver($query, int $driverId)
    {
        return $query->where('delivery_driver_id', $driverId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ========== Helpers ==========

    public function approve(): void
    {
        $this->update(['status' => 'approved']);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rejected']);
    }

    public function markAsHelpful(): void
    {
        $this->increment('helpful_count');
    }

    public function report(): void
    {
        $this->increment('reported_count');

        if ($this->reported_count >= 5) {
            $this->update(['status' => 'flagged']);
        }
    }
}
