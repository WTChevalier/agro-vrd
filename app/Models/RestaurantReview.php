<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'restaurant_id',
        'user_id',
        'order_id',
        'overall_rating',
        'food_rating',
        'service_rating',
        'delivery_rating',
        'value_rating',
        'title',
        'comment',
        'photos',
        'is_anonymous',
        'status',
        'restaurant_reply',
        'replied_at',
        'helpful_count',
        'reported_count',
        'featured_at',
    ];

    protected $casts = [
        'overall_rating' => 'decimal:1',
        'food_rating' => 'decimal:1',
        'service_rating' => 'decimal:1',
        'delivery_rating' => 'decimal:1',
        'value_rating' => 'decimal:1',
        'photos' => 'array',
        'is_anonymous' => 'boolean',
        'replied_at' => 'datetime',
        'featured_at' => 'datetime',
        'helpful_count' => 'integer',
        'reported_count' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($review) {
            if ($review->status === 'approved') {
                $review->restaurant?->updateRating();
            }
        });

        static::updated(function ($review) {
            $review->restaurant?->updateRating();
        });

        static::deleted(function ($review) {
            $review->restaurant?->updateRating();
        });
    }

    // ========== Relaciones ==========

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
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

    protected function hasPhotos(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->photos)
        );
    }

    protected function hasReply(): Attribute
    {
        return Attribute::make(
            get: fn () => !empty($this->restaurant_reply)
        );
    }

    protected function isFeatured(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->featured_at !== null
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

    public function scopeFeatured($query)
    {
        return $query->whereNotNull('featured_at');
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_id', $restaurantId);
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

    public function reply(string $reply): void
    {
        $this->update([
            'restaurant_reply' => $reply,
            'replied_at' => now(),
        ]);
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

    public function feature(): void
    {
        $this->update(['featured_at' => now()]);
    }

    public function unfeature(): void
    {
        $this->update(['featured_at' => null]);
    }
}
