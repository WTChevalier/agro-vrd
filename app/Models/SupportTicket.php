<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'order_id',
        'restaurant_id',
        'assigned_to',
        'category',
        'priority',
        'subject',
        'description',
        'status',
        'resolution',
        'satisfaction_rating',
        'satisfaction_comment',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'satisfaction_rating' => 'integer',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $ticket->ticket_number = static::generateTicketNumber();
            }
        });
    }

    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(4));
        return "{$prefix}-{$date}-{$random}";
    }

    // ========== Relaciones ==========

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    // ========== Accessors ==========

    protected function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->status) {
                'open' => 'Abierto',
                'pending' => 'Pendiente',
                'in_progress' => 'En Progreso',
                'waiting_customer' => 'Esperando Cliente',
                'resolved' => 'Resuelto',
                'closed' => 'Cerrado',
                default => $this->status,
            }
        );
    }

    protected function priorityLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->priority) {
                'low' => 'Baja',
                'medium' => 'Media',
                'high' => 'Alta',
                'urgent' => 'Urgente',
                default => $this->priority,
            }
        );
    }

    protected function priorityColor(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->priority) {
                'low' => 'gray',
                'medium' => 'blue',
                'high' => 'orange',
                'urgent' => 'red',
                default => 'gray',
            }
        );
    }

    protected function categoryLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => match($this->category) {
                'order_issue' => 'Problema con Pedido',
                'payment' => 'Pagos',
                'delivery' => 'Delivery',
                'refund' => 'Reembolso',
                'account' => 'Cuenta',
                'restaurant' => 'Restaurante',
                'app_bug' => 'Error en App',
                'suggestion' => 'Sugerencia',
                'other' => 'Otro',
                default => $this->category,
            }
        );
    }

    protected function isOpen(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->status, ['open', 'pending', 'in_progress', 'waiting_customer'])
        );
    }

    protected function isClosed(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->status, ['resolved', 'closed'])
        );
    }

    protected function responseTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->first_response_at) {
                    return null;
                }
                return $this->created_at->diffInMinutes($this->first_response_at);
            }
        );
    }

    protected function resolutionTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->resolved_at) {
                    return null;
                }
                return $this->created_at->diffInHours($this->resolved_at);
            }
        );
    }

    // ========== Scopes ==========

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['open', 'pending', 'in_progress', 'waiting_customer']);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', ['resolved', 'closed']);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeAwaitingResponse($query)
    {
        return $query->open()->whereNull('first_response_at');
    }

    // ========== Helpers ==========

    public function assignTo(int $userId): void
    {
        $this->update([
            'assigned_to' => $userId,
            'status' => 'in_progress',
        ]);
    }

    public function addMessage(string $message, int $senderId, bool $isInternal = false, ?array $attachments = null): SupportMessage
    {
        $supportMessage = $this->messages()->create([
            'user_id' => $senderId,
            'message' => $message,
            'is_internal' => $isInternal,
            'attachments' => $attachments,
        ]);

        // Marcar primera respuesta si es del agente
        if ($senderId !== $this->user_id && !$this->first_response_at) {
            $this->update(['first_response_at' => now()]);
        }

        return $supportMessage;
    }

    public function resolve(string $resolution): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution' => $resolution,
            'resolved_at' => now(),
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => 'open',
            'resolved_at' => null,
            'closed_at' => null,
        ]);
    }

    public function rateSatisfaction(int $rating, ?string $comment = null): void
    {
        $this->update([
            'satisfaction_rating' => $rating,
            'satisfaction_comment' => $comment,
        ]);
    }
}
