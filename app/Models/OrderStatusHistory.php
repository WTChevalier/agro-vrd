<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'status_id',
        'changed_by',
        'changed_by_type',
        'notes',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ========== Relaciones ==========

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // ========== Accessors ==========

    protected function changedByName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->changed_by_type === 'system') {
                    return 'Sistema';
                }
                return $this->changedByUser?->name ?? 'Usuario desconocido';
            }
        );
    }

    protected function changedByTypeLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->changed_by_type) {
                'customer' => 'Cliente',
                'restaurant' => 'Restaurante',
                'driver' => 'Repartidor',
                'admin' => 'Administrador',
                'system' => 'Sistema',
                default => $this->changed_by_type,
            }
        );
    }

    protected function formattedDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->format('d/m/Y H:i:s')
        );
    }

    protected function timeAgo(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->created_at->diffForHumans()
        );
    }

    // ========== Scopes ==========

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByStatus($query, int $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('changed_by_type', $type);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ========== Helpers ==========

    public static function createEntry(
        int $orderId,
        int $statusId,
        ?int $changedBy = null,
        string $changedByType = 'system',
        ?string $notes = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'order_id' => $orderId,
            'status_id' => $statusId,
            'changed_by' => $changedBy,
            'changed_by_type' => $changedByType,
            'notes' => $notes,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getDurationToNext(): ?int
    {
        $next = static::where('order_id', $this->order_id)
            ->where('created_at', '>', $this->created_at)
            ->orderBy('created_at')
            ->first();

        if (!$next) {
            return null;
        }

        return $this->created_at->diffInMinutes($next->created_at);
    }

    public static function getAverageTimeForStatus(int $statusId): ?float
    {
        $histories = static::where('status_id', $statusId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->get();

        $durations = $histories->map(fn($h) => $h->getDurationToNext())->filter();

        if ($durations->isEmpty()) {
            return null;
        }

        return $durations->average();
    }
}
