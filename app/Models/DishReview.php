<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DishReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'dish_id',
        'user_id',
        'order_id',
        'order_item_id',
        'rating',
        'title',
        'comment',
        'photos',
        'is_anonymous',
        'status',
        'helpful_count',
        'reported_count',
    ];

    protected $casts = [
        'rating' => 'decimal:1',
        'photos' => 'array',
        'is_anonymous' => 'boolean',
        'helpful_count' => 'integer',
        'reported_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            if ($review->status === 'approved') {
                $review->dish?->updateRating();
            }
        });

        static::updated(function ($review) {
            $review->dish?->updateRating();
        });

        static::deleted(function ($review) {
            $review->dish?->updateRating();
        });
    }

    // ========== Relaciones ==========

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
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

    protected function hasPhotos(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->photos)
        );
    }

    protected function photoUrls(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photos
                ? array_map(fn ($photo) => asset('storage/' . $photo), $this->photos)
                : []
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

    public function scopeForDish($query, int $dishId)
    {
        return $query->where('dish_id', $dishId);
    }

    public function scopeWithPhotos($query)
    {
        return $query->whereNotNull('photos')
            ->whereJsonLength('photos', '>', 0);
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
